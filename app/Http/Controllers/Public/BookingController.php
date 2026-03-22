<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Http\Requests\Public\BookingRequest;
use App\Models\Appointment;
use App\Models\Availability;
use App\Models\Client;
use App\Models\Service;
use App\Models\Setting;
use App\Services\BrevoService;
use App\Enums\AppointmentStatus;
use App\Enums\ServiceCategory;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class BookingController extends Controller
{
    public function __construct(protected BrevoService $brevo) {}

    // ── Liste des services ────────────────────────────────────────────
    public function services(Request $request): Response
    {
        $query = Service::active()
            ->withCount(['appointments as bookings_count' => fn($q) => $q->where('status', 'completed')])
            ->when($request->category, fn($q) => $q->byCategory($request->category))
            ->orderBy('sort_order');

        $services = $query->get()->map(fn($s) => [
            'id'                 => $s->id,
            'name'               => $s->name,
            'slug'               => $s->slug,
            'short_description'  => $s->short_description,
            'description'        => $s->description,
            'category'           => $s->category->value,
            'category_label'     => $s->category->label(),
            'category_icon'      => $s->category->icon(),
            'duration'           => $s->duration,
            'duration_formatted' => $s->duration_formatted,
            'price'              => $s->price,
            'deposit_required'   => $s->deposit_required,
            'deposit_amount'     => $s->deposit_amount,
            'image_url'          => $s->image_url,
            'includes'           => $s->includes,
            'requirements'       => $s->requirements,
            'is_featured'        => $s->is_featured,
            'bookings_count'     => $s->bookings_count,
        ]);

        $categories = collect(ServiceCategory::cases())->map(fn($c) => [
            'value' => $c->value,
            'label' => $c->label(),
            'icon'  => $c->icon(),
            'count' => Service::active()->byCategory($c->value)->count(),
        ])->filter(fn($c) => $c['count'] > 0)->values();

        return Inertia::render('Public/Services', [
            'services'   => $services,
            'categories' => $categories,
            'filters'    => $request->only(['category']),
            'settings'   => [
                'booking_enabled'            => Setting::get('booking_enabled', true),
                'booking_cancellation_hours' => Setting::get('booking_cancellation_hours', 48),
            ],
        ]);
    }

    // ── Page réservation d'un service ─────────────────────────────────
    public function show(Service $service): Response
    {
        abort_if(! $service->is_active, 404);

        $availableDates = $this->getAvailableDates(30);

        return Inertia::render('Public/Booking', [
            'service' => [
                'id'                 => $service->id,
                'name'               => $service->name,
                'slug'               => $service->slug,
                'short_description'  => $service->short_description,
                'description'        => $service->description,
                'category'           => $service->category->value,
                'category_label'     => $service->category->label(),
                'duration'           => $service->duration,
                'duration_formatted' => $service->duration_formatted,
                'price'              => $service->price,
                'deposit_required'   => $service->deposit_required,
                'deposit_amount'     => $service->deposit_amount,
                'image_url'          => $service->image_url,
                'includes'           => $service->includes ?? [],
                'requirements'       => $service->requirements ?? [],
                'buffer_time'        => $service->buffer_time,
            ],
            'availableDates' => $availableDates,
            'settings' => [
                'min_notice_hours' => Setting::get('booking_min_notice_hours', 24),
                'advance_days'     => Setting::get('booking_advance_days', 60),
            ],
        ]);
    }

    // ── Créneaux disponibles (AJAX) ───────────────────────────────────
    public function slots(Request $request, Service $service): JsonResponse
    {
        $request->validate(['date' => 'required|date|after_or_equal:today']);

        $date   = Carbon::parse($request->date);
        $notice = (int) Setting::get('booking_min_notice_hours', 24);

        if ($date->diffInHours(now()) < $notice && $date->isToday()) {
            return response()->json(['slots' => [], 'reason' => 'notice']);
        }

        $blocked = Availability::where('specific_date', $date->toDateString())
                               ->where('is_blocked', true)
                               ->exists();

        if ($blocked) {
            return response()->json(['slots' => [], 'reason' => 'blocked']);
        }

        $availabilities = Availability::forDate($date)
                                      ->where('is_blocked', false)
                                      ->where('is_active', true)
                                      ->get();

        if ($availabilities->isEmpty()) {
            return response()->json(['slots' => [], 'reason' => 'closed']);
        }

        $existing = Appointment::whereDate('date', $date->toDateString())   // ← colonne 'date'
                               ->whereNotIn('status', ['cancelled'])
                               ->get(['start_time', 'end_time']);

        $slots    = [];
        $step     = 30;
        $duration = $service->duration;

        foreach ($availabilities as $avail) {
            $current = Carbon::parse($date->toDateString() . ' ' . $avail->start_time);
            $end     = Carbon::parse($date->toDateString() . ' ' . $avail->end_time);

            while ($current->copy()->addMinutes($duration)->lte($end)) {
                $slotStart = $current->copy();
                $slotEnd   = $current->copy()->addMinutes($duration);

                if ($slotStart->isPast()) { $current->addMinutes($step); continue; }

                $free = $existing->every(fn($appt) =>
                    $slotEnd->lte(Carbon::parse($appt->start_time)) ||
                    $slotStart->gte(Carbon::parse($appt->end_time))
                );

                if ($free) {
                    $slots[] = [
                        'start' => $slotStart->format('H:i'),
                        'end'   => $slotEnd->format('H:i'),
                        'label' => $slotStart->format('H:i'),
                    ];
                }

                $current->addMinutes($step);
            }
        }

        return response()->json(['slots' => $slots, 'reason' => null]);
    }

    // ── Store : créer le RDV ──────────────────────────────────────────
    public function store(BookingRequest $request, Service $service): RedirectResponse
    {
        $data = $request->validated();

        abort_if(! Setting::get('booking_enabled', true), 403, 'Les réservations sont désactivées.');

        // ── Récupérer ou créer le client ──────────────────────────────
        $client = null;
        if (auth()->check()) {
            $client = auth()->user()->client;
        }

        if (! $client) {
            $client = Client::firstOrCreate(
                ['email' => $data['email']],
                [
                    'first_name' => $data['first_name'],
                    'last_name'  => $data['last_name'],
                    'phone'      => $data['phone'],
                    'user_id'    => auth()->id(),
                ]
            );
        }

        // ── Vérifier disponibilité du créneau ─────────────────────────
        $start    = Carbon::parse($data['start_time']);
        $end      = $start->copy()->addMinutes($service->duration);

        $conflict = Appointment::where('date', $data['date'])   // ← colonne 'date'
                               ->whereNotIn('status', ['cancelled'])
                               ->where(fn($q) => $q
                                   ->whereBetween('start_time', [$start->format('H:i'), $end->format('H:i')])
                                   ->orWhereBetween('end_time', [$start->format('H:i'), $end->format('H:i')])
                               )
                               ->exists();

        if ($conflict) {
            return back()->with('error', 'Ce créneau vient d\'être réservé. Veuillez en choisir un autre.');
        }

        // ── Créer le rendez-vous ──────────────────────────────────────
        $appointment = Appointment::create([
            'client_id'      => $client->id,
            'service_id'     => $service->id,
            'date'           => $data['date'],             // ← colonne 'date'
            'start_time'     => $start->format('H:i'),
            'end_time'       => $end->format('H:i'),
            'status'         => AppointmentStatus::Pending,
            'price'          => $service->price,
            'deposit_amount' => $service->deposit_amount,
            'deposit_paid'   => false,
            'client_notes'   => $data['notes'] ?? null,
            'hair_details'   => $data['hair_details'] ?? null,
        ]);

        // ── Email de confirmation ─────────────────────────────────────
        try {
            $html = view('emails.appointment-confirmation', [
                'appointment' => $appointment,
                'client'      => $client,
                'service'     => $service,
            ])->render();

            $this->brevo->send(
                toEmail:     $client->email ?? $data['email'],
                toName:      $client->full_name ?? ($data['first_name'] . ' ' . $data['last_name']),
                subject:     "Votre demande de rendez-vous — " . ($appointment->reference ?? '#' . $appointment->id),
                htmlContent: $html,
            );
        } catch (\Throwable) {}

        // ── Redirection selon acompte ─────────────────────────────────
        // Si un acompte est requis → proposer le paiement
        if ($service->deposit_required && $service->deposit_amount > 0) {
            return redirect()->route('payment.show', [
                'type' => 'appointment',
                'id'   => $appointment->id,
            ])->with('info', 'Votre rendez-vous est réservé. Finalisez en réglant l\'acompte.');
        }

        // Sinon → confirmation directe
        return redirect()->route('booking.confirmation', $appointment->id)
                         ->with('success', 'Votre rendez-vous a bien été enregistré !');
    }

    // ── Page de confirmation ──────────────────────────────────────────
    public function confirmation(Appointment $appointment): Response
    {
        // S'assurer que le client ne peut voir que ses propres RDV
        if (auth()->check()) {
            abort_if(
                $appointment->client?->user_id !== auth()->id() && ! auth()->user()->is_admin,
                403
            );
        }

        $appointment->load(['service', 'client.user']);

        return Inertia::render('Public/BookingConfirmation', [
            'appointment' => [
                'id'          => $appointment->id,
                'reference'   => $appointment->reference ?? '#' . $appointment->id,
                'date'        => Carbon::parse($appointment->date)->locale('fr')->isoFormat('dddd D MMMM YYYY'),
                'time_start'  => substr($appointment->start_time, 0, 5),
                'time_end'    => substr($appointment->end_time,   0, 5),
                'price'       => $appointment->price,
                'deposit_required' => $appointment->service?->deposit_required,
                'deposit_amount'   => $appointment->deposit_amount,
                'deposit_paid'     => $appointment->deposit_paid,
                'status'      => $appointment->status instanceof \BackedEnum
                    ? $appointment->status->value
                    : (string) $appointment->status,
            ],
            'service' => [
                'name'     => $appointment->service?->name,
                'duration' => $appointment->service?->duration,
            ],
        ]);
    }

    // ── Dates disponibles ─────────────────────────────────────────────
    private function getAvailableDates(int $days): array
    {
        $dates     = [];
        $advance   = (int) Setting::get('booking_advance_days', 60);
        $minNotice = (int) Setting::get('booking_min_notice_hours', 24);

        $start  = now()->addHours($minNotice)->startOfDay();
        $end    = now()->addDays(min($days, $advance));
        $period = \Carbon\CarbonPeriod::create($start, $end);

        foreach ($period as $date) {
            $blocked = Availability::where('specific_date', $date->toDateString())
                                   ->where('is_blocked', true)
                                   ->exists();
            if ($blocked) continue;

            $hasAvail = Availability::forDate($date)
                                    ->where('is_blocked', false)
                                    ->where('is_active', true)
                                    ->exists();

            if ($hasAvail) {
                $dates[] = [
                    'date'       => $date->toDateString(),
                    'label'      => $date->locale('fr')->isoFormat('ddd D MMM'),
                    'day'        => $date->locale('fr')->isoFormat('ddd'),
                    'number'     => $date->format('d'),
                    'month'      => $date->locale('fr')->isoFormat('MMM'),
                    'is_today'   => $date->isToday(),
                    'is_weekend' => $date->isWeekend(),
                ];
            }
        }

        return $dates;
    }
}
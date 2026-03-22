<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\AppointmentRequest;
use App\Models\Appointment;
use App\Models\Client;
use App\Models\Service;
use App\Models\AppNotification;
use App\Services\BrevoService;
use App\Enums\AppointmentStatus;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AppointmentController extends Controller
{
    public function __construct(protected BrevoService $brevo) {}

    public function index(Request $request): Response
    {
        $query = Appointment::with(['client', 'service'])
            ->when($request->status, fn($q) => $q->where('status', $request->status))
            ->when($request->service_id, fn($q) => $q->where('service_id', $request->service_id))
            ->when($request->date_from, fn($q) => $q->whereDate('date', '>=', $request->date_from))
            ->when($request->date_to, fn($q) => $q->whereDate('date', '<=', $request->date_to))
            ->when($request->search, fn($q) => $q->whereHas('client', fn($cq) =>
                $cq->search($request->search)
            ))
            ->orderByDesc('date')->orderByDesc('start_time');

        $appointments = $query->paginate(15)->withQueryString();

        return Inertia::render('Admin/Appointments/Index', [
            'appointments' => $appointments->through(fn($a) => $this->formatAppointment($a)),
            'filters'      => $request->only(['status', 'service_id', 'date_from', 'date_to', 'search']),
            'services'     => Service::active()->get(['id', 'name']),
            'statuses'     => collect(AppointmentStatus::cases())->map(fn($s) => [
                'value' => $s->value, 'label' => $s->label(),
            ]),
            'stats' => [
                'pending'   => Appointment::where('status', 'pending')->count(),
                'confirmed' => Appointment::where('status', 'confirmed')->count(),
                'today'     => Appointment::today()->count(),
                'this_week' => Appointment::thisWeek()->count(),
            ],
        ]);
    }

    public function calendar(Request $request): Response
    {
        $month = $request->month ? Carbon::parse($request->month) : Carbon::now();

        $appointments = Appointment::with(['client', 'service'])
            ->whereYear('date', $month->year)
            ->whereMonth('date', $month->month)
            ->get()
            ->map(fn($a) => [
                'id'         => $a->id,
                'title'      => $a->client->full_name . ' — ' . $a->service->name,
                'date'       => $a->date->toDateString(),
                'start_time' => $a->start_time_formatted,
                'end_time'   => $a->end_time_formatted,
                'status'     => $a->status->value,
                'color'      => $this->statusToCalendarColor($a->status),
                'reference'  => $a->reference,
                'client'     => ['name' => $a->client->full_name, 'phone' => $a->client->phone],
                'service'    => ['name' => $a->service->name, 'price' => $a->price],
            ]);

        return Inertia::render('Admin/Appointments/Calendar', [
            'appointments' => $appointments,
            'month'        => $month->toDateString(),
            'monthLabel'   => $month->locale('fr')->isoFormat('MMMM YYYY'),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Admin/Appointments/Create', [
            'clients'  => Client::orderBy('first_name')->get(['id', 'first_name', 'last_name', 'email', 'phone']),
            'services' => Service::active()->get(['id', 'name', 'duration', 'price', 'deposit_amount', 'deposit_required', 'category']),
        ]);
    }

    public function store(AppointmentRequest $request): RedirectResponse
    {
        $data    = $request->validated();
        $service = Service::findOrFail($data['service_id']);

        // Calculer end_time
        $start   = Carbon::parse($data['start_time']);
        $end     = $start->copy()->addMinutes($service->duration);

        $appointment = Appointment::create([
            ...$data,
            'end_time'       => $end->format('H:i'),
            'price'          => $data['price'] ?? $service->price,
            'deposit_amount' => $service->deposit_amount,
            'status'         => AppointmentStatus::Confirmed,
        ]);

        // Notification interne
        $this->createAdminNotification(
            'appointment_new',
            'Nouveau rendez-vous',
            "RDV créé pour {$appointment->client->full_name} — {$appointment->service->name}",
            route('admin.rendez-vous.show', $appointment)
        );

        // Email de confirmation au client
        $this->sendConfirmationEmail($appointment);

        return redirect()->route('admin.rendez-vous.show', $appointment)
                         ->with('success', "Rendez-vous {$appointment->reference} créé.");
    }

    public function show(Appointment $appointment): Response
    {
        $appointment->load(['client', 'service', 'invoice']);

        return Inertia::render('Admin/Appointments/Show', [
            'appointment' => [
                ...$this->formatAppointment($appointment),
                'client_notes'      => $appointment->client_notes,
                'admin_notes'       => $appointment->admin_notes,
                'cancellation_reason' => $appointment->cancellation_reason,
                'hair_details'      => $appointment->hair_details,
                'deposit_paid'      => $appointment->deposit_paid,
                'deposit_amount'    => $appointment->deposit_amount,
                'deposit_paid_at'   => $appointment->deposit_paid_at?->locale('fr')->isoFormat('D MMM YYYY à HH:mm'),
                'deposit_method'    => $appointment->deposit_payment_method,
                'amount_due'        => $appointment->amount_due,
                'invoice'           => $appointment->invoice ? [
                    'id'             => $appointment->invoice->id,
                    'invoice_number' => $appointment->invoice->invoice_number,
                    'status'         => $appointment->invoice->status->value,
                ] : null,
                'confirmed_at'   => $appointment->confirmed_at?->locale('fr')->isoFormat('D MMM YYYY'),
                'cancelled_at'   => $appointment->cancelled_at?->locale('fr')->isoFormat('D MMM YYYY'),
                'completed_at'   => $appointment->completed_at?->locale('fr')->isoFormat('D MMM YYYY'),
                'reminder_sent'  => $appointment->reminder_sent,
                'reminder_sent_at'=> $appointment->reminder_sent_at?->locale('fr')->isoFormat('D MMM YYYY à HH:mm'),
                'client_full'    => [
                    'id'        => $appointment->client->id,
                    'full_name' => $appointment->client->full_name,
                    'email'     => $appointment->client->email,
                    'phone'     => $appointment->client->phone,
                    'hair_type' => $appointment->client->hair_type,
                    'avatar_url'=> $appointment->client->avatar_url,
                    'is_vip'    => $appointment->client->is_vip,
                ],
                'service_full'  => [
                    'id'       => $appointment->service->id,
                    'name'     => $appointment->service->name,
                    'category' => $appointment->service->category->label(),
                    'duration' => $appointment->service->duration_formatted,
                ],
            ],
        ]);
    }

    public function edit(Appointment $appointment): Response
    {
        return Inertia::render('Admin/Appointments/Edit', [
            'appointment' => $appointment->load(['client', 'service']),
            'clients'     => Client::orderBy('first_name')->get(['id', 'first_name', 'last_name', 'email']),
            'services'    => Service::active()->get(['id', 'name', 'duration', 'price', 'deposit_amount']),
        ]);
    }

    public function update(AppointmentRequest $request, Appointment $appointment): RedirectResponse
    {
        $data    = $request->validated();
        $service = Service::findOrFail($data['service_id']);
        $start   = Carbon::parse($data['start_time']);
        $end     = $start->copy()->addMinutes($service->duration);

        $appointment->update([
            ...$data,
            'end_time' => $end->format('H:i'),
        ]);

        return redirect()->route('admin.rendez-vous.show', $appointment)
                         ->with('success', 'Rendez-vous mis à jour.');
    }

    public function destroy(Appointment $appointment): RedirectResponse
    {
        $ref = $appointment->reference;
        $appointment->delete();

        return redirect()->route('admin.rendez-vous.index')
                         ->with('success', "Rendez-vous {$ref} supprimé.");
    }

    public function confirm(Appointment $appointment): RedirectResponse
    {
        $appointment->update([
            'status'       => AppointmentStatus::Confirmed,
            'confirmed_at' => now(),
        ]);

        $this->sendConfirmationEmail($appointment);

        return back()->with('success', 'Rendez-vous confirmé et email envoyé.');
    }

    public function cancel(Request $request, Appointment $appointment): RedirectResponse
    {
        $request->validate(['reason' => 'nullable|string|max:500']);

        $appointment->update([
            'status'              => AppointmentStatus::Cancelled,
            'cancelled_at'        => now(),
            'cancellation_reason' => $request->reason,
        ]);

        // Email annulation
        $this->sendCancellationEmail($appointment, $request->reason);

        return back()->with('success', 'Rendez-vous annulé.');
    }

    public function complete(Appointment $appointment): RedirectResponse
    {
        $appointment->update([
            'status'       => AppointmentStatus::Completed,
            'completed_at' => now(),
        ]);

        return back()->with('success', 'Rendez-vous marqué comme terminé.');
    }

    public function sendReminder(Appointment $appointment): RedirectResponse
    {
        if ($appointment->reminder_sent) {
            return back()->with('warning', 'Un rappel a déjà été envoyé.');
        }

        $client  = $appointment->client;
        $service = $appointment->service;

        $html = view('emails.appointment-reminder', compact('appointment', 'client', 'service'))->render();

        $sent = $this->brevo->send(
            toEmail:    $client->email,
            toName:     $client->full_name,
            subject:    "Rappel : Votre rendez-vous demain — Patricia Braids",
            htmlContent: $html,
        );

        if ($sent) {
            $appointment->update([
                'reminder_sent'    => true,
                'reminder_sent_at' => now(),
            ]);
            return back()->with('success', 'Rappel envoyé à ' . $client->email);
        }

        return back()->with('error', 'Échec de l\'envoi du rappel.');
    }

    /* ── Helpers privés ──────────────────────────────────────────── */

    private function formatAppointment(Appointment $a): array
    {
        return [
            'id'          => $a->id,
            'reference'   => $a->reference,
            'date'        => $a->date->locale('fr')->isoFormat('D MMM YYYY'),
            'date_raw'    => $a->date->toDateString(),
            'start_time'  => $a->start_time_formatted,
            'end_time'    => $a->end_time_formatted,
            'status'      => $a->status->value,
            'status_label'=> $a->status->label(),
            'status_color'=> $a->status->color(),
            'price'       => $a->price,
            'is_upcoming' => $a->is_upcoming,
            'client'      => [
                'id'        => $a->client?->id,
                'full_name' => $a->client?->full_name,
                'email'     => $a->client?->email,
                'phone'     => $a->client?->phone,
                'avatar_url'=> $a->client?->avatar_url,
                'is_vip'    => $a->client?->is_vip,
            ],
            'service' => [
                'id'       => $a->service?->id,
                'name'     => $a->service?->name,
                'duration' => $a->service?->duration_formatted,
                'category' => $a->service?->category?->label(),
            ],
        ];
    }

    private function statusToCalendarColor(AppointmentStatus $status): string
    {
        return match($status) {
            AppointmentStatus::Pending   => '#f4bd2e',
            AppointmentStatus::Confirmed => '#3b82f6',
            AppointmentStatus::Completed => '#10b981',
            AppointmentStatus::Cancelled => '#ef4444',
            AppointmentStatus::NoShow    => '#6b7280',
        };
    }

    private function sendConfirmationEmail(Appointment $appointment): void
    {
        try {
            $html = view('emails.appointment-confirmation', [
                'appointment' => $appointment,
                'client'      => $appointment->client,
                'service'     => $appointment->service,
            ])->render();

            $this->brevo->send(
                toEmail:     $appointment->client->email,
                toName:      $appointment->client->full_name,
                subject:     "Confirmation de votre rendez-vous — {$appointment->reference}",
                htmlContent: $html,
            );
        } catch (\Throwable $e) {
            \Log::error('Email confirmation RDV failed: ' . $e->getMessage());
        }
    }

    private function sendCancellationEmail(Appointment $appointment, ?string $reason): void
    {
        try {
            $html = view('emails.appointment-cancelled', [
                'appointment' => $appointment,
                'client'      => $appointment->client,
                'reason'      => $reason,
            ])->render();

            $this->brevo->send(
                toEmail:     $appointment->client->email,
                toName:      $appointment->client->full_name,
                subject:     "Annulation de votre rendez-vous — {$appointment->reference}",
                htmlContent: $html,
            );
        } catch (\Throwable $e) {
            \Log::error('Email annulation RDV failed: ' . $e->getMessage());
        }
    }

    private function createAdminNotification(string $type, string $title, string $message, string $link): void
    {
        $admin = \App\Models\User::admin()->first();
        if ($admin) {
            AppNotification::create([
                'user_id'  => $admin->id,
                'type'     => $type,
                'title'    => $title,
                'message'  => $message,
                'link'     => $link,
                'severity' => 'info',
                'icon'     => 'PhCalendarCheck',
            ]);
        }
    }
}
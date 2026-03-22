<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AppointmentController extends Controller
{
    // ── Index ─────────────────────────────────────────────────────────
    public function index(Request $request): Response
    {
        $query = Appointment::with(['client.user', 'service'])
            ->when($request->search, function ($q) use ($request) {
                $q->where(function ($sub) use ($request) {
                    $sub->whereHas('client.user', fn($u) =>
                            $u->where('name', 'like', "%{$request->search}%")
                              ->orWhere('email', 'like', "%{$request->search}%")
                        )
                        ->orWhereHas('client', fn($c) =>
                            $c->where('first_name', 'like', "%{$request->search}%")
                              ->orWhere('last_name',  'like', "%{$request->search}%")
                              ->orWhere('phone',      'like', "%{$request->search}%")
                        )
                        ->orWhereHas('service', fn($s) =>
                            $s->where('name', 'like', "%{$request->search}%")
                        );
                });
            })
            ->when($request->status, fn($q) => $q->where('status', $request->status))
            ->when($request->date,   fn($q) => $q->whereDate('date', $request->date))  // ← colonne 'date'
            ->orderByDesc('date')
            ->orderByDesc('start_time');

        $appointments = $query->paginate(15)->withQueryString();

        return Inertia::render('Admin/Appointments/Index', [
            'appointments' => $appointments->through(fn($a) => $this->mapAppointment($a)),
            'filters'      => $request->only(['search', 'status', 'date']),
            'stats'        => [
                'total'     => Appointment::count(),
                'pending'   => Appointment::where('status', 'pending')->count(),
                'confirmed' => Appointment::where('status', 'confirmed')->count(),
                'completed' => Appointment::where('status', 'completed')->count(),
                'cancelled' => Appointment::where('status', 'cancelled')->count(),
            ],
        ]);
    }

    // ── Calendrier ────────────────────────────────────────────────────
    public function calendar(Request $request): Response
    {
        $year  = (int) $request->get('year',  date('Y'));
        $month = (int) $request->get('month', date('n'));

        $appointments = Appointment::with(['client.user', 'service'])
            ->whereYear('date',  $year)   // ← colonne 'date'
            ->whereMonth('date', $month)
            ->whereNotIn('status', ['cancelled'])
            ->orderBy('date')
            ->orderBy('start_time')
            ->get()
            ->map(fn($a) => $this->mapAppointment($a));

        return Inertia::render('Admin/Appointments/Calendar', [
            'appointments' => $appointments,
            'year'         => $year,
            'month'        => $month,
        ]);
    }

    // ── Show ──────────────────────────────────────────────────────────
    public function show(Appointment $rendezVou): Response
    {
        $rendezVou->load(['client.user', 'service']);

        return Inertia::render('Admin/Appointments/Show', [
            'appointment' => $this->mapAppointment($rendezVou, full: true),
        ]);
    }

    // ── Confirm ───────────────────────────────────────────────────────
    public function confirm(Appointment $rendezVou): RedirectResponse
    {
        $rendezVou->update(['status' => 'confirmed']);
        return back()->with('success', 'Rendez-vous confirmé.');
    }

    // ── Cancel ────────────────────────────────────────────────────────
    public function cancel(Appointment $rendezVou): RedirectResponse
    {
        $rendezVou->update(['status' => 'cancelled']);
        return back()->with('success', 'Rendez-vous annulé.');
    }

    // ── Complete ──────────────────────────────────────────────────────
    public function complete(Appointment $rendezVou): RedirectResponse
    {
        $rendezVou->update(['status' => 'completed']);
        return back()->with('success', 'Rendez-vous marqué comme terminé.');
    }

    // ── Send reminder ─────────────────────────────────────────────────
    public function sendReminder(Appointment $rendezVou): RedirectResponse
    {
        // TODO: app(BrevoService::class)->sendAppointmentReminder($rendezVou);
        return back()->with('success', 'Rappel envoyé.');
    }

    // ── Destroy ───────────────────────────────────────────────────────
    public function destroy(Appointment $rendezVou): RedirectResponse
    {
        $rendezVou->delete();
        return redirect()->route('admin.rendez-vous.index')->with('success', 'Rendez-vous supprimé.');
    }

    // ── Helper ────────────────────────────────────────────────────────
    private function mapAppointment(Appointment $a, bool $full = false): array
    {
        // Nom du client : priorité user.name > client first+last > guest
        $clientName = $a->client?->user?->name
            ?? (($a->client?->first_name ?? '') . ' ' . ($a->client?->last_name ?? ''))
            ?: ($a->guest_name ?? 'Client inconnu');

        $base = [
            'id'           => $a->id,
            'reference'    => $a->reference ?? '#' . $a->id,
            'client_name'  => trim($clientName),
            'client_email' => $a->client?->user?->email ?? $a->client?->email ?? null,
            'client_phone' => $a->client?->phone ?? null,
            'service_name' => $a->service?->name ?? '—',
            'date'         => $a->date
                ? \Carbon\Carbon::parse($a->date)->locale('fr')->isoFormat('ddd D MMM YYYY')
                : '—',
            'time_start'   => $a->start_time ? substr($a->start_time, 0, 5) : '—',
            'time_end'     => $a->end_time   ? substr($a->end_time,   0, 5) : '—',
            'duration'     => $a->service?->duration ?? 60,
            'price'        => $a->price
                ? number_format($a->price, 2, ',', ' ') . ' €'
                : ($a->service ? number_format($a->service->price, 2, ',', ' ') . ' €' : '—'),
            'deposit_paid' => $a->deposit_paid ?? false,
            'status'       => $a->status instanceof \BackedEnum
                ? $a->status->value
                : (string) $a->status,
            'notes'        => $a->client_notes ?? $a->notes ?? null,
        ];

        if ($full) {
            $base['service']         = $a->service ? ['id' => $a->service->id, 'name' => $a->service->name, 'duration' => $a->service->duration] : null;
            $base['client_id']       = $a->client?->id;
            $base['deposit_amount']  = $a->deposit_amount;
            $base['hair_details']    = $a->hair_details ?? null;
            $base['created_at']      = $a->created_at?->locale('fr')->isoFormat('D MMMM YYYY à HH:mm');
        }

        return $base;
    }
}
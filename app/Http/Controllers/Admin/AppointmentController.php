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
    // ── Index (liste) ─────────────────────────────────────────────────
    public function index(Request $request): Response
    {
        $query = Appointment::with(['client.user', 'service'])
            ->when($request->search, function ($q) use ($request) {
                $q->whereHas('client.user', fn($u) => $u->where('name', 'like', "%{$request->search}%"))
                  ->orWhereHas('service', fn($s) => $s->where('name', 'like', "%{$request->search}%"));
            })
            ->when($request->status, fn($q) => $q->where('status', $request->status))
            ->when($request->date,   fn($q) => $q->whereDate('appointment_date', $request->date))
            ->latest('appointment_date');

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
            ->whereYear('appointment_date',  $year)
            ->whereMonth('appointment_date', $month)
            ->whereNotIn('status', ['cancelled'])
            ->orderBy('appointment_date')
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
        // TODO: envoyer un email de rappel via BrevoService
        // app(BrevoService::class)->sendAppointmentReminder($rendezVou);

        return back()->with('success', 'Rappel envoyé au client.');
    }

    // ── Destroy ───────────────────────────────────────────────────────
    public function destroy(Appointment $rendezVou): RedirectResponse
    {
        $rendezVou->delete();

        return redirect()->route('admin.rendez-vous.index')
                         ->with('success', 'Rendez-vous supprimé.');
    }

    // ── Helper ────────────────────────────────────────────────────────
    private function mapAppointment(Appointment $a, bool $full = false): array
    {
        $base = [
            'id'           => $a->id,
            'client_name'  => $a->client?->user?->name ?? $a->guest_name ?? 'Client inconnu',
            'client_email' => $a->client?->user?->email ?? $a->guest_email,
            'client_phone' => $a->client?->phone ?? $a->guest_phone,
            'service_name' => $a->service?->name ?? '—',
            'date'         => $a->appointment_date
                ? \Carbon\Carbon::parse($a->appointment_date)->locale('fr')->isoFormat('ddd D MMM YYYY')
                : '—',
            'time_start'   => $a->start_time ? substr($a->start_time, 0, 5) : '—',
            'time_end'     => $a->end_time   ? substr($a->end_time,   0, 5) : '—',
            'duration'     => $a->service?->duration ?? 60,
            'price'        => $a->service
                ? number_format($a->service->price, 2, ',', ' ') . ' €'
                : '—',
            'status'       => $a->status,
            'notes'        => $a->notes,
        ];

        if ($full) {
            $base['service']       = $a->service ? ['id' => $a->service->id, 'name' => $a->service->name] : null;
            $base['client']        = $a->client  ? ['id' => $a->client->id]  : null;
            $base['created_at']    = $a->created_at?->locale('fr')->isoFormat('D MMMM YYYY');
            $base['confirmed_at']  = $a->confirmed_at?->locale('fr')->isoFormat('D MMMM YYYY');
        }

        return $base;
    }
}
<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\AvailabilityRequest;
use App\Models\Availability;
use App\Models\Appointment;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AvailabilityController extends Controller
{
    public function index(): Response
    {
        $recurring = Availability::whereNotNull('day_of_week')
            ->whereNull('specific_date')
            ->orderBy('day_of_week')
            ->orderBy('start_time')
            ->get()
            ->map(fn($a) => $this->format($a));

        $specific = Availability::whereNotNull('specific_date')
            ->whereDate('specific_date', '>=', today())
            ->orderBy('specific_date')
            ->orderBy('start_time')
            ->get()
            ->map(fn($a) => $this->format($a));

        // Préparer la vue mensuelle (30 jours)
        $calendar = $this->buildCalendarView();

        return Inertia::render('Admin/Availability/Index', [
            'recurring' => $recurring,
            'specific'  => $specific,
            'calendar'  => $calendar,
            'days'      => ['Dimanche','Lundi','Mardi','Mercredi','Jeudi','Vendredi','Samedi'],
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Admin/Availability/Create', [
            'days' => ['Dimanche','Lundi','Mardi','Mercredi','Jeudi','Vendredi','Samedi'],
        ]);
    }

    public function store(AvailabilityRequest $request): RedirectResponse
    {
        Availability::create($request->validated());

        return redirect()->route('admin.disponibilites.index')
                         ->with('success', 'Créneau de disponibilité ajouté.');
    }

    public function edit(Availability $disponibilite): Response
    {
        return Inertia::render('Admin/Availability/Edit', [
            'availability' => $this->format($disponibilite),
            'days'         => ['Dimanche','Lundi','Mardi','Mercredi','Jeudi','Vendredi','Samedi'],
        ]);
    }

    public function update(AvailabilityRequest $request, Availability $disponibilite): RedirectResponse
    {
        $disponibilite->update($request->validated());

        return redirect()->route('admin.disponibilites.index')
                         ->with('success', 'Créneau mis à jour.');
    }

    public function destroy(Availability $disponibilite): RedirectResponse
    {
        $disponibilite->delete();

        return redirect()->route('admin.disponibilites.index')
                         ->with('success', 'Créneau supprimé.');
    }

    /**
     * Bloquer une date ou période (congés, fermeture exceptionnelle).
     */
    public function block(Request $request): RedirectResponse
    {
        $request->validate([
            'date_from'  => 'required|date|after_or_equal:today',
            'date_to'    => 'required|date|after_or_equal:date_from',
            'reason'     => 'nullable|string|max:255',
            'start_time' => 'nullable|date_format:H:i',
            'end_time'   => 'nullable|date_format:H:i|after:start_time',
        ]);

        $period = CarbonPeriod::create($request->date_from, $request->date_to);

        foreach ($period as $date) {
            Availability::create([
                'specific_date' => $date->toDateString(),
                'start_time'    => $request->start_time ?? '08:00',
                'end_time'      => $request->end_time ?? '20:00',
                'is_blocked'    => true,
                'block_reason'  => $request->reason,
                'is_active'     => true,
            ]);
        }

        $count = iterator_count(CarbonPeriod::create($request->date_from, $request->date_to));

        return redirect()->route('admin.disponibilites.index')
                         ->with('success', "{$count} jour(s) bloqué(s) avec succès.");
    }

    /**
     * API : créneaux disponibles pour une date donnée.
     * Utilisé par le formulaire de réservation (admin & public).
     */
    public function slotsForDate(Request $request, string $date): JsonResponse
    {
        $carbon     = Carbon::parse($date);
        $serviceId  = $request->service_id;
        $duration   = $request->duration ?? 60;
        $buffer     = $request->buffer ?? 15;

        // 1. Vérifier si la date est bloquée
        $blocked = Availability::where('specific_date', $carbon->toDateString())
                               ->where('is_blocked', true)
                               ->exists();

        if ($blocked) {
            return response()->json(['slots' => [], 'blocked' => true]);
        }

        // 2. Récupérer les disponibilités du jour
        $availabilities = Availability::forDate($carbon)
                                      ->where('is_blocked', false)
                                      ->where('is_active', true)
                                      ->get();

        if ($availabilities->isEmpty()) {
            return response()->json(['slots' => [], 'blocked' => false]);
        }

        // 3. Récupérer les RDV existants ce jour-là
        $existingAppointments = Appointment::whereDate('date', $carbon->toDateString())
                                           ->whereNotIn('status', ['cancelled'])
                                           ->get(['start_time', 'end_time']);

        // 4. Générer les créneaux libres
        $slots = [];
        $step  = 30; // Incrément de 30min

        foreach ($availabilities as $avail) {
            $current = Carbon::parse($carbon->toDateString() . ' ' . $avail->start_time);
            $end     = Carbon::parse($carbon->toDateString() . ' ' . $avail->end_time);
            $slotEnd = $current->copy()->addMinutes($duration + $buffer);

            while ($slotEnd->lte($end)) {
                $slotStart   = $current->copy();
                $slotEndReal = $current->copy()->addMinutes($duration);

                // Vérifier si ce créneau est libre
                $isFree = $existingAppointments->every(function ($appt) use ($slotStart, $slotEndReal) {
                    $apptStart = Carbon::parse($appt->start_time);
                    $apptEnd   = Carbon::parse($appt->end_time);
                    return $slotEndReal->lte($apptStart) || $slotStart->gte($apptEnd);
                });

                // Pas dans le passé
                if ($isFree && ($carbon->isToday() ? $slotStart->isFuture() : true)) {
                    $slots[] = [
                        'start'  => $slotStart->format('H:i'),
                        'end'    => $slotEndReal->format('H:i'),
                        'label'  => $slotStart->format('H:i') . ' — ' . $slotEndReal->format('H:i'),
                    ];
                }

                $current->addMinutes($step);
                $slotEnd = $current->copy()->addMinutes($duration + $buffer);
            }
        }

        return response()->json(['slots' => $slots, 'blocked' => false]);
    }

    /* ── Helpers ─────────────────────────────────────────────────── */

    private function format(Availability $a): array
    {
        return [
            'id'             => $a->id,
            'day_of_week'    => $a->day_of_week,
            'day_name'       => $a->day_name,
            'specific_date'  => $a->specific_date?->toDateString(),
            'specific_date_formatted' => $a->specific_date?->locale('fr')->isoFormat('D MMM YYYY'),
            'start_time'     => $a->start_time_formatted,
            'end_time'       => $a->end_time_formatted,
            'is_blocked'     => $a->is_blocked,
            'block_reason'   => $a->block_reason,
            'max_appointments'=> $a->max_appointments,
            'is_active'      => $a->is_active,
        ];
    }

    private function buildCalendarView(): array
    {
        $calendar = [];
        $period   = CarbonPeriod::create(today(), today()->addDays(29));

        foreach ($period as $date) {
            $availabilities = Availability::forDate($date)->get();
            $blocked        = $availabilities->where('is_blocked', true)->first();
            $open           = $availabilities->where('is_blocked', false)->where('is_active', true)->first();

            $rdvCount = Appointment::whereDate('date', $date->toDateString())
                                   ->whereNotIn('status', ['cancelled'])
                                   ->count();

            $calendar[] = [
                'date'          => $date->toDateString(),
                'label'         => $date->locale('fr')->isoFormat('D MMM'),
                'day_short'     => $date->locale('fr')->isoFormat('ddd'),
                'is_today'      => $date->isToday(),
                'is_weekend'    => $date->isWeekend(),
                'is_blocked'    => (bool) $blocked,
                'block_reason'  => $blocked?->block_reason,
                'is_open'       => (bool) $open,
                'appointments'  => $rdvCount,
                'hours'         => $open ? $open->start_time_formatted . ' – ' . $open->end_time_formatted : null,
            ];
        }

        return $calendar;
    }
}
<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Availability;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AvailabilityController extends Controller
{
    // ── Index ─────────────────────────────────────────────────────────
    public function index(): Response
    {
        // On récupère TOUTES les disponibilités sans filtrer sur 'type'
        // car la colonne n'existe peut-être pas encore.
        // On distingue les créneaux récurrents (ont day_of_week)
        // des blocages (ont une date spécifique) via is_blocked ou date.
        $all = Availability::orderBy('day_of_week')->orderBy('start_time')->get();

        // Séparer selon les colonnes disponibles
        $availabilities = $all->filter(fn($a) =>
            isset($a->day_of_week) && $a->day_of_week !== null && (!isset($a->is_blocked) || !$a->is_blocked)
        )->map(fn($a) => [
            'id'            => $a->id,
            'day_of_week'   => $a->day_of_week,
            'start_time'    => $a->start_time ? substr($a->start_time, 0, 5) : '00:00',
            'end_time'      => $a->end_time   ? substr($a->end_time,   0, 5) : '00:00',
            'slot_duration' => $a->slot_duration ?? 60,
            'is_active'     => $a->is_active ?? true,
        ])->values();

        $blocked = $all->filter(fn($a) =>
            isset($a->is_blocked) && $a->is_blocked
        )->map(fn($a) => [
            'id'         => $a->id,
            'date'       => $a->date
                ? \Carbon\Carbon::parse($a->date)->locale('fr')->isoFormat('dddd D MMMM YYYY')
                : '—',
            'start_time' => $a->start_time ? substr($a->start_time, 0, 5) : null,
            'end_time'   => $a->end_time   ? substr($a->end_time,   0, 5) : null,
            'full_day'   => $a->full_day ?? true,
            'reason'     => $a->reason ?? null,
        ])->values();

        return Inertia::render('Admin/Availability/Index', [
            'availabilities' => $availabilities,
            'blocked'        => $blocked,
        ]);
    }

    // ── Store ─────────────────────────────────────────────────────────
    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'day_of_week'   => 'required|integer|between:0,6',
            'start_time'    => 'required|date_format:H:i',
            'end_time'      => 'required|date_format:H:i|after:start_time',
            'slot_duration' => 'required|integer|in:30,45,60,90,120,180,240',
            'is_active'     => 'boolean',
        ]);

        Availability::create($data);

        return back()->with('success', 'Créneau ajouté.');
    }

    // ── Update ────────────────────────────────────────────────────────
    public function update(Request $request, Availability $disponibilite): RedirectResponse
    {
        $data = $request->validate([
            'day_of_week'   => 'sometimes|integer|between:0,6',
            'start_time'    => 'sometimes|date_format:H:i',
            'end_time'      => 'sometimes|date_format:H:i',
            'slot_duration' => 'sometimes|integer|in:30,45,60,90,120,180,240',
            'is_active'     => 'sometimes|boolean',
        ]);

        $disponibilite->update($data);

        return back()->with('success', 'Créneau mis à jour.');
    }

    // ── Destroy ───────────────────────────────────────────────────────
    public function destroy(Availability $disponibilite): RedirectResponse
    {
        $disponibilite->delete();

        return back()->with('success', 'Créneau supprimé.');
    }

    // ── Block (nécessite la colonne is_blocked ou une migration) ──────
    public function block(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'date'       => 'required|date|after_or_equal:today',
            'full_day'   => 'boolean',
            'start_time' => 'nullable|date_format:H:i',
            'end_time'   => 'nullable|date_format:H:i',
            'reason'     => 'nullable|string|max:255',
        ]);

        // Vérifier si la colonne is_blocked existe
        $columns = \Illuminate\Support\Facades\Schema::getColumnListing('availabilities');

        if (in_array('is_blocked', $columns)) {
            $data['is_blocked']   = true;
            $data['day_of_week']  = null;
            Availability::create($data);
        } else {
            // La table n'a pas de colonne is_blocked
            // → il faut ajouter cette migration (voir ci-dessous)
            return back()->with('error', 'Migration requise : ajoutez la colonne is_blocked. Voir instructions.');
        }

        return back()->with('success', 'Date bloquée.');
    }

    // ── Créneaux pour une date (utilisé par le tunnel de réservation) ──
    public function slotsForDate(Request $request, string $date): JsonResponse
    {
        $parsed = \Carbon\Carbon::parse($date);
        $dow    = $parsed->dayOfWeek;

        // Vérifier si bloqué
        $columns = \Illuminate\Support\Facades\Schema::getColumnListing('availabilities');
        if (in_array('is_blocked', $columns)) {
            $isBlocked = Availability::where('is_blocked', true)
                ->whereDate('date', $date)
                ->where(fn($q) => $q->where('full_day', true)->orWhereNull('full_day'))
                ->exists();
            if ($isBlocked) return response()->json(['slots' => []]);
        }

        // Créneaux récurrents du jour
        $recurring = Availability::where('day_of_week', $dow)
            ->where(fn($q) => $q->where('is_active', true)->orWhereNull('is_active'))
            ->get();

        if ($recurring->isEmpty()) {
            return response()->json(['slots' => []]);
        }

        // RDV déjà pris
        $takenSlots = \App\Models\Appointment::whereDate('appointment_date', $date)
            ->whereNotIn('status', ['cancelled'])
            ->pluck('start_time')
            ->map(fn($t) => substr($t, 0, 5))
            ->toArray();

        $slots = [];
        foreach ($recurring as $r) {
            $current = \Carbon\Carbon::createFromFormat('H:i', substr($r->start_time, 0, 5));
            $end     = \Carbon\Carbon::createFromFormat('H:i', substr($r->end_time,   0, 5));
            $step    = (int) ($r->slot_duration ?? 60);

            while ($current->copy()->addMinutes($step)->lte($end)) {
                $timeStr = $current->format('H:i');
                $slots[] = [
                    'start'     => $timeStr,
                    'end'       => $current->copy()->addMinutes($step)->format('H:i'),
                    'available' => ! in_array($timeStr, $takenSlots),
                ];
                $current->addMinutes($step);
            }
        }

        return response()->json(['slots' => $slots]);
    }
}
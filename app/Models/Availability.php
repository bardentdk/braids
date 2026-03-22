<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class Availability extends Model
{
    use HasFactory;

    protected $fillable = [
        'day_of_week', 'specific_date', 'start_time', 'end_time',
        'is_blocked', 'block_reason', 'max_appointments', 'is_active',
    ];

    protected function casts(): array
    {
        return [
            'specific_date'    => 'date',
            'is_blocked'       => 'boolean',
            'is_active'        => 'boolean',
            'max_appointments' => 'integer',
            'day_of_week'      => 'integer',
        ];
    }

    public function getDayNameAttribute(): string
    {
        $days = ['Dimanche','Lundi','Mardi','Mercredi','Jeudi','Vendredi','Samedi'];
        return $days[$this->day_of_week] ?? 'Inconnu';
    }

    public function getStartTimeFormattedAttribute(): string
    {
        return substr($this->start_time, 0, 5);
    }

    public function getEndTimeFormattedAttribute(): string
    {
        return substr($this->end_time, 0, 5);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true)->where('is_blocked', false);
    }

    public function scopeRecurring($query)
    {
        return $query->whereNotNull('day_of_week')->whereNull('specific_date');
    }

    public function scopeSpecific($query)
    {
        return $query->whereNotNull('specific_date');
    }

    public function scopeForDate($query, Carbon $date)
    {
        return $query->where(function ($q) use ($date) {
            // Créneau récurrent pour ce jour
            $q->where('day_of_week', $date->dayOfWeek)->whereNull('specific_date');
        })->orWhere(function ($q) use ($date) {
            // Créneau spécifique pour cette date
            $q->where('specific_date', $date->toDateString());
        });
    }
}
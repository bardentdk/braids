<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class Setting extends Model
{
    protected $fillable = ['key', 'value', 'type', 'group', 'label', 'description', 'is_public'];

    protected function casts(): array
    {
        return ['is_public' => 'boolean'];
    }

    /**
     * Récupérer une valeur de setting avec cache.
     */
    public static function get(string $key, mixed $default = null): mixed
    {
        return Cache::remember("setting.{$key}", 3600, function () use ($key, $default) {
            $setting = static::where('key', $key)->first();
            if (! $setting) return $default;

            return match($setting->type) {
                'boolean' => (bool) $setting->value,
                'integer' => (int) $setting->value,
                'float'   => (float) $setting->value,
                'json'    => json_decode($setting->value, true),
                default   => $setting->value,
            };
        });
    }

    /**
     * Mettre à jour ou créer un setting.
     */
    public static function set(string $key, mixed $value): void
    {
        $val = is_array($value) ? json_encode($value) : $value;
        static::updateOrCreate(['key' => $key], ['value' => $val]);
        Cache::forget("setting.{$key}");
    }

    /**
     * Récupérer tous les settings d'un groupe.
     */
    public static function getGroup(string $group): array
    {
        return Cache::remember("settings.group.{$group}", 3600, function () use ($group) {
            return static::where('group', $group)->pluck('value', 'key')->toArray();
        });
    }

    public function scopePublic($query)
    {
        return $query->where('is_public', true);
    }
}
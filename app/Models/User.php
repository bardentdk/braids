<?php

namespace App\Models;

use App\Enums\UserRole;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Storage;

class User extends Authenticatable implements MustVerifyEmail
{
    use HasFactory, Notifiable, SoftDeletes;

    protected $fillable = [
        'name', 'email', 'password', 'role',
        'phone', 'avatar', 'is_active', 'locale', 'preferences',
    ];

    protected $hidden = [
        'password', 'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password'          => 'hashed',
            'is_active'         => 'boolean',
            'role'              => UserRole::class,
            'preferences'       => 'array',
        ];
    }

    /* ── Accessors ─────────────────────────────── */

    public function getAvatarUrlAttribute(): string
    {
        if ($this->avatar) {
            return Storage::url($this->avatar);
        }

        // Avatar par défaut (initiales)
        return "https://ui-avatars.com/api/?name={$this->name}&background=c4956a&color=fff&bold=true&size=128";
    }

    public function getFullNameAttribute(): string
    {
        return $this->name;
    }

    public function getIsAdminAttribute(): bool
    {
        return $this->role === UserRole::Admin;
    }

    /* ── Relations ─────────────────────────────── */

    public function client(): HasOne
    {
        return $this->hasOne(Client::class);
    }

    public function notifications(): HasMany
    {
        return $this->hasMany(AppNotification::class);
    }

    public function unreadNotifications(): HasMany
    {
        return $this->hasMany(AppNotification::class)->whereNull('read_at');
    }

    /* ── Scopes ────────────────────────────────── */

    public function scopeAdmin($query)
    {
        return $query->where('role', UserRole::Admin);
    }

    public function scopeClients($query)
    {
        return $query->where('role', UserRole::Client);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
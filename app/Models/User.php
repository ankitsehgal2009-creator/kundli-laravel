<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $fillable = ['name', 'email', 'password', 'phone', 'role', 'status', 'sq1', 'sa1', 'sq2', 'sa2', 'approved_at'];

    protected $hidden = ['password', 'remember_token', 'sa1', 'sa2'];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'approved_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    public function keys()
    {
        return $this->hasMany(LicenseKey::class);
    }

    // the key that currently lets this user in, if any
    public function activeKey(): ?LicenseKey
    {
        return $this->keys()->where('status', 'active')
            ->where(fn ($q) => $q->whereNull('starts_at')->orWhere('starts_at', '<=', now()))
            ->where(fn ($q) => $q->whereNull('expires_at')->orWhere('expires_at', '>', now()))
            ->orderByRaw('expires_at is null desc')->orderByDesc('expires_at')->first();
    }

    // a key that is given but whose start date is still ahead
    public function upcomingKey(): ?LicenseKey
    {
        return $this->keys()->where('status', 'active')->where('starts_at', '>', now())->orderBy('starts_at')->first();
    }

    public function canUseApp(): bool
    {
        return $this->status === 'active' && ($this->isAdmin() || $this->activeKey() !== null);
    }
}

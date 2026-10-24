<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class LicenseKey extends Model
{
    protected $fillable = ['code', 'plan', 'days', 'period', 'period_unit', 'starts_at', 'user_id', 'status', 'note', 'activated_at', 'expires_at', 'brand_name', 'brand_logo'];

    public const UNITS = ['days' => 'Days', 'months' => 'Months', 'years' => 'Years'];

    protected function casts(): array
    {
        return ['activated_at' => 'datetime', 'expires_at' => 'datetime', 'starts_at' => 'datetime'];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public static function newCode(): string
    {
        do {
            $code = 'KS-' . implode('-', str_split(strtoupper(Str::random(12)), 4));
            $code = strtr($code, ['0' => 'X', 'O' => 'Y', '1' => 'Z', 'I' => 'W', 'L' => 'V']);
        } while (static::where('code', $code)->exists());

        return $code;
    }

    public static function addPeriod(Carbon $from, ?int $n, ?string $unit): ?Carbon
    {
        if (! $n) {
            return null;
        }

        return match ($unit) {
            'months' => $from->copy()->addMonthsNoOverflow($n),
            'years' => $from->copy()->addYearsNoOverflow($n),
            default => $from->copy()->addDays($n),
        };
    }

    public function periodLabel(): string
    {
        $n = $this->period ?: $this->days;
        if (! $n) {
            return 'No expiry';
        }
        $u = $this->period ? $this->period_unit : 'days';

        return $n . ' ' . ($n == 1 ? rtrim($u, 's') : $u);
    }

    // give this key to a user; a key without a fixed start date starts counting now
    public function assignTo(User $user): void
    {
        $this->user_id = $user->id;
        $this->activated_at = now();
        if (! $this->starts_at) {
            $this->starts_at = now()->startOfDay();
        }
        if (! $this->expires_at) {
            $this->expires_at = $this->period ? static::addPeriod($this->starts_at, $this->period, $this->period_unit) : static::addPeriod($this->starts_at, $this->days, 'days');
        }
        $this->save();
    }

    public function state(): string
    {
        if ($this->status !== 'active') {
            return 'revoked';
        }
        if ($this->expires_at && $this->expires_at->isPast()) {
            return 'expired';
        }
        if ($this->starts_at && $this->starts_at->isFuture()) {
            return 'starts later';
        }

        return $this->user_id ? 'in use' : 'unused';
    }

    // remove a logo file when no key and no global setting still points at it
    public static function dropLogo(?string $path): void
    {
        if ($path && ! static::where('brand_logo', $path)->exists() && Setting::get('logo') !== $path) {
            File::delete(public_path($path));
        }
    }
}

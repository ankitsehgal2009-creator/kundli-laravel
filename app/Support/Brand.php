<?php

namespace App\Support;

use App\Models\Setting;
use App\Models\User;

class Brand
{
    // the name and logo a user sees: their licence key's branding first, then the admin's default
    public static function for(?User $user): array
    {
        $key = $user && ! $user->isAdmin() ? ($user->activeKey() ?? $user->upcomingKey()) : null;
        $logo = $key?->brand_logo ?: Setting::get('logo');

        return ['name' => $key?->brand_name ?: Setting::get('brand_name', 'Kundli Software'), 'logo' => $logo];
    }
}

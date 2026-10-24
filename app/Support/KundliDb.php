<?php

namespace App\Support;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schema;

class KundliDb
{
    // Shared hosting often has no terminal, so tables are created or updated from the browser.
    public static function ensure(): void
    {
        if (! Schema::hasTable('users') || ! Schema::hasTable('kundli_clients') || ! Schema::hasTable('license_keys')
            || ! Schema::hasColumn('users', 'status') || ! Schema::hasColumn('license_keys', 'brand_logo') || ! Schema::hasTable('report_logs')) {
            Artisan::call('migrate', ['--force' => true]);
        }
    }

    public static function normAnswer(string $a): string
    {
        return preg_replace('/\s+/', ' ', mb_strtolower(trim($a)));
    }
}

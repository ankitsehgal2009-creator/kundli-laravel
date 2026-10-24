<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;

class Setting extends Model
{
    protected $table = 'kundli_settings';
    protected $primaryKey = 'key';
    public $incrementing = false;
    protected $keyType = 'string';
    protected $fillable = ['key', 'value'];

    public static function get(string $key, $default = null)
    {
        static $ok = null;
        $ok ??= Schema::hasTable('kundli_settings');
        if (! $ok) {
            return $default;
        }

        return static::find($key)?->value ?? $default;
    }

    public static function put(string $key, $value): void
    {
        static::updateOrCreate(['key' => $key], ['value' => $value]);
    }
}

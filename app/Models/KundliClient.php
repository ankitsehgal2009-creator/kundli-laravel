<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class KundliClient extends Model
{
    protected $fillable = ['user_id', 'client_key', 'name', 'data', 'updated_ms'];
}

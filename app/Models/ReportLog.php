<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ReportLog extends Model
{
    const UPDATED_AT = null;

    const ACTIONS = ['pdf' => 'PDF download', 'png' => 'Image download', 'print' => 'Print'];

    protected $fillable = ['user_id', 'user_email', 'action', 'report', 'client_name', 'ip', 'remote_ip', 'user_agent'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PendingRegistration extends Model
{
    protected $fillable = [
        'mobile',
        'role',
        'otp_code',
        'otp_expires_at',
        'payload',
    ];

    protected $hidden = [
        'otp_code',
    ];

    protected function casts(): array
    {
        return [
            'otp_expires_at' => 'datetime',
            'payload' => 'array',
        ];
    }
}


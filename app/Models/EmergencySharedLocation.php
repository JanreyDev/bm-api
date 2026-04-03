<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmergencySharedLocation extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'province',
        'city_municipality',
        'barangay',
        'address',
        'latitude',
        'longitude',
        'high_accuracy',
        'include_landmark',
        'shared_at',
    ];

    protected $casts = [
        'latitude' => 'float',
        'longitude' => 'float',
        'high_accuracy' => 'boolean',
        'include_landmark' => 'boolean',
        'shared_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}


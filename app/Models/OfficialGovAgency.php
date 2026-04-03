<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OfficialGovAgency extends Model
{
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'created_by_user_id',
        'province',
        'city_municipality',
        'barangay',
        'code',
        'display_name',
        'website',
        'sort_order',
        'is_active',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }
}


<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ResidentProfile extends Model
{
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'user_id',
        'education_attainment',
        'employment_type',
        'employment_sector',
        'household_size',
        'monthly_household_income',
        'house_ownership_status',
        'utilities',
        'height_cm',
        'weight_kg',
        'blood_type',
        'medical_notes',
        'is_verified',
        'verified_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'utilities' => 'array',
            'monthly_household_income' => 'decimal:2',
            'height_cm' => 'decimal:2',
            'weight_kg' => 'decimal:2',
            'is_verified' => 'boolean',
            'verified_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}

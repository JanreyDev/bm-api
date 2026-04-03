<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ResidentRbiRecord extends Model
{
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'user_id',
        'rbi_id',
        'first_name',
        'middle_name',
        'last_name',
        'suffix',
        'province',
        'city_municipality',
        'barangay',
        'street_name',
        'zone_purok',
        'year_of_residency',
        'birth_date',
        'gender',
        'disability_tag',
        'blood_donor_opt_in',
        'blood_type',
        'education_aid_status',
        'latest_grade_average',
        'family_count',
        'vehicle_count',
        'vaccination_count',
        'latest_bmi',
        'verification_step',
        'verified_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'birth_date' => 'date',
            'blood_donor_opt_in' => 'boolean',
            'latest_bmi' => 'decimal:2',
            'verified_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}


<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OfficialBarangaySetup extends Model
{
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'updated_by_user_id',
        'province',
        'city_municipality',
        'barangay',
        'division_type',
        'division_count',
        'founding_year',
        'website',
        'facebook_url',
        'latitude',
        'longitude',
        'logo_file_name',
        'logo_image_base64',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'division_count' => 'integer',
            'founding_year' => 'integer',
            'latitude' => 'decimal:6',
            'longitude' => 'decimal:6',
        ];
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by_user_id');
    }
}

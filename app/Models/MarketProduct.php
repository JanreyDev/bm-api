<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MarketProduct extends Model
{
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'user_id',
        'merchant_registration_id',
        'barangay',
        'title',
        'seller_name',
        'price',
        'original_price',
        'description',
        'category',
        'stock',
        'eta',
        'verified',
        'seller_zone',
        'seller_purok',
        'sold',
        'reviews',
        'rating',
        'image_asset',
        'thumbnail_base64',
        'thumbnail_file_name',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'price' => 'float',
            'original_price' => 'float',
            'rating' => 'float',
            'verified' => 'boolean',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function registration(): BelongsTo
    {
        return $this->belongsTo(MerchantRegistration::class, 'merchant_registration_id');
    }

    public function scopeInBarangay(Builder $query, string $barangay): Builder
    {
        return $query->where('barangay', trim($barangay));
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MarketChatMessage extends Model
{
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'user_id',
        'sender_name',
        'sender_role',
        'barangay',
        'buyer_mobile',
        'buyer_name',
        'seller_name',
        'product_title',
        'message',
        'is_official',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_official' => 'boolean',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function scopeInBarangay(Builder $query, string $barangay): Builder
    {
        return $query->where('barangay', trim($barangay));
    }

    public function scopeInProductThread(
        Builder $query,
        string $sellerName,
        string $productTitle
    ): Builder {
        return $query
            ->where('seller_name', trim($sellerName))
            ->where('product_title', trim($productTitle));
    }
}

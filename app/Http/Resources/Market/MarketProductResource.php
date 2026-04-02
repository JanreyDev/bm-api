<?php

namespace App\Http\Resources\Market;

use App\Models\MarketProduct;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin MarketProduct */
class MarketProductResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'seller' => $this->seller_name,
            'price' => (float) $this->price,
            'original_price' => $this->original_price === null ? null : (float) $this->original_price,
            'description' => $this->description,
            'category' => $this->category,
            'stock' => $this->stock,
            'eta' => $this->eta,
            'verified' => (bool) $this->verified,
            'seller_zone' => $this->seller_zone,
            'seller_purok' => $this->seller_purok,
            'sold' => $this->sold,
            'reviews' => $this->reviews,
            'rating' => (float) $this->rating,
            'image_asset' => $this->image_asset,
            'posted_at' => optional($this->created_at)?->toIso8601String(),
        ];
    }
}

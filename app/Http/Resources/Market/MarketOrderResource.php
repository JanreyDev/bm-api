<?php

namespace App\Http\Resources\Market;

use App\Models\MarketOrder;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin MarketOrder */
class MarketOrderResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'barangay' => $this->barangay,
            'order_code' => $this->order_code,
            'status' => $this->status,
            'payer_name' => $this->payer_name,
            'payer_mobile' => $this->payer_mobile,
            'payer_address' => $this->payer_address,
            'payment_provider' => $this->payment_provider,
            'payment_link' => $this->payment_link,
            'subtotal' => (float) $this->subtotal,
            'delivery_fee' => (float) $this->delivery_fee,
            'total' => (float) $this->total,
            'items' => is_array($this->items_json) ? $this->items_json : [],
            'created_at' => optional($this->created_at)?->toIso8601String(),
            'updated_at' => optional($this->updated_at)?->toIso8601String(),
        ];
    }
}

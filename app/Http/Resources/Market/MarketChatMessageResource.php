<?php

namespace App\Http\Resources\Market;

use App\Models\MarketChatMessage;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin MarketChatMessage */
class MarketChatMessageResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'sender_name' => $this->sender_name,
            'sender_role' => $this->sender_role,
            'barangay' => $this->barangay,
            'buyer_mobile' => $this->buyer_mobile,
            'buyer_name' => $this->buyer_name,
            'seller_name' => $this->seller_name,
            'product_title' => $this->product_title,
            'message' => $this->message,
            'is_official' => (bool) $this->is_official,
            'created_at' => optional($this->created_at)?->toIso8601String(),
        ];
    }
}

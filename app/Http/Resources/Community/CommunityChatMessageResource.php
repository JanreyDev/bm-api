<?php

namespace App\Http\Resources\Community;

use App\Models\CommunityChatMessage;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin CommunityChatMessage */
class CommunityChatMessageResource extends JsonResource
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
            'barangay' => $this->barangay,
            'channel' => $this->channel,
            'message' => $this->message,
            'is_official' => (bool) $this->is_official,
            'created_at' => optional($this->created_at)?->toIso8601String(),
        ];
    }
}

<?php

namespace App\Http\Resources;

use App\Models\CommunityPost;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin CommunityPost */
class CommunityPostResource extends JsonResource
{
    public function __construct($resource, private readonly ?User $viewer = null)
    {
        parent::__construct($resource);
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'author' => $this->author_name,
            'message' => $this->message,
            'image_base64' => $this->image_base64,
            'barangay' => $this->barangay,
            'is_official' => (bool) $this->is_official,
            'is_verified_resident' => !(bool) $this->is_official,
            'can_manage' => $this->viewer !== null
                ? (int) $this->user_id === (int) $this->viewer->id
                : false,
            'posted_at' => optional($this->created_at)?->toIso8601String(),
        ];
    }
}

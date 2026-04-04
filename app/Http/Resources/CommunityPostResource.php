<?php

namespace App\Http\Resources;

use App\Models\CommunityPost;
use App\Models\CommunityPostComment;
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
        $commentsCount = (int) ($this->comments_count ?? $this->comments()->count());
        $likesCount = (int) ($this->likes_count ?? $this->likes()->count());
        $likedByMe = (int) ($this->liked_by_me_count ?? 0) > 0;
        /** @var CommunityPostComment|null $latestComment */
        $latestComment = $this->relationLoaded('comments')
            ? $this->comments->sortByDesc('created_at')->first()
            : $this->comments()->latest()->first();

        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'author' => $this->author_name,
            'message' => $this->message,
            'post_type' => trim((string) ($this->post_type ?? 'social')),
            'image_base64' => $this->image_base64,
            'barangay' => $this->barangay,
            'is_official' => (bool) $this->is_official,
            'is_verified_resident' => !(bool) $this->is_official,
            'can_manage' => $this->viewer !== null
                ? (int) $this->user_id === (int) $this->viewer->id
                : false,
            'likes_count' => $likesCount,
            'liked_by_me' => $likedByMe,
            'comments_count' => $commentsCount,
            'latest_comment' => $latestComment === null ? null : [
                'id' => $latestComment->id,
                'user_id' => $latestComment->user_id,
                'author' => $latestComment->author_name,
                'message' => $latestComment->message,
                'posted_at' => optional($latestComment->created_at)?->toIso8601String(),
                'is_mine' => $this->viewer !== null
                    ? (int) $latestComment->user_id === (int) $this->viewer->id
                    : false,
            ],
            'comments' => $this->relationLoaded('comments')
                ? $this->comments
                    ->sortByDesc('created_at')
                    ->values()
                    ->map(function (CommunityPostComment $entry): array {
                        return [
                            'id' => $entry->id,
                            'user_id' => $entry->user_id,
                            'author' => $entry->author_name,
                            'message' => $entry->message,
                            'posted_at' => optional($entry->created_at)?->toIso8601String(),
                            'is_mine' => $this->viewer !== null
                                ? (int) $entry->user_id === (int) $this->viewer->id
                                : false,
                        ];
                    })
                    ->all()
                : null,
            'posted_at' => optional($this->created_at)?->toIso8601String(),
        ];
    }
}

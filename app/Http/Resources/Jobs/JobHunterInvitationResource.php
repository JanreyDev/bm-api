<?php

namespace App\Http\Resources\Jobs;

use App\Models\JobHunterInvitation;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin JobHunterInvitation */
class JobHunterInvitationResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => (string) $this->id,
            'inviter_user_id' => $this->inviter_user_id,
            'talent_user_id' => $this->talent_user_id,
            'talent_profile_id' => $this->talent_profile_id,
            'talent_name' => $this->talent_name,
            'talent_mobile' => $this->talent_mobile,
            'talent_desired_job' => $this->talent_desired_job,
            'inviter_name' => $this->inviter_name,
            'inviter_mobile' => $this->inviter_mobile,
            'message' => $this->message,
            'status' => $this->status,
            'created_at' => optional($this->created_at)?->toIso8601String(),
        ];
    }
}

<?php

namespace App\Http\Resources\Jobs;

use App\Models\JobHiringPost;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin JobHiringPost */
class JobHiringPostResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'company' => $this->company,
            'location' => $this->location,
            'salary' => $this->salary,
            'schedule' => $this->schedule,
            'requirements' => $this->requirements,
            'posted_by' => $this->posted_by,
            'urgent' => (bool) $this->urgent,
            'barangay' => $this->barangay,
            'posted_at' => optional($this->created_at)?->toIso8601String(),
        ];
    }
}

<?php

namespace App\Http\Resources\Jobs;

use App\Models\SavedJob;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin SavedJob */
class SavedJobResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'job_id' => $this->job_hiring_post_id,
            'job_title' => $this->job_title,
            'company' => $this->company,
            'saved_at' => optional($this->created_at)?->toIso8601String(),
        ];
    }
}

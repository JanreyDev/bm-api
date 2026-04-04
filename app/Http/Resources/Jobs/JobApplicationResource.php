<?php

namespace App\Http\Resources\Jobs;

use App\Models\JobApplication;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin JobApplication */
class JobApplicationResource extends JsonResource
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
            'posted_by' => $this->posted_by,
            'applicant_name' => $this->applicant_name,
            'mobile_number' => $this->mobile_number,
            'cover_letter' => $this->cover_letter,
            'attachment_name' => $this->attachment_name,
            'attachment_base64' => $this->attachment_base64,
            'status' => $this->status,
            'submitted_at' => optional($this->created_at)?->toIso8601String(),
        ];
    }
}

<?php

namespace App\Http\Resources\Jobs;

use App\Models\JobHunterProfile;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin JobHunterProfile */
class JobHunterProfileResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'full_name' => $this->full_name,
            'mobile' => optional($this->user)->mobile,
            'desired_job' => $this->desired_job,
            'skills' => $this->skills,
            'preferred_setup' => $this->preferred_setup,
            'expected_salary' => $this->expected_salary,
            'barangay_zone' => $this->barangay_zone,
            'available_now' => (bool) $this->available_now,
            'barangay' => $this->barangay,
            'posted_at' => optional($this->created_at)?->toIso8601String(),
        ];
    }
}

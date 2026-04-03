<?php

namespace App\Http\Resources\Profile;

use App\Models\ResidentProfile;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin ResidentProfile */
class ResidentProfileResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var User|null $user */
        $user = $this->user;
        $utilities = is_array($this->utilities) ? $this->utilities : [];

        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'name' => trim((string) ($user?->name ?? '')),
            'mobile' => trim((string) ($user?->mobile ?? '')),
            'province' => trim((string) ($user?->province ?? '')),
            'city_municipality' => trim((string) ($user?->city_municipality ?? '')),
            'barangay' => trim((string) ($user?->barangay ?? '')),
            'middle_name' => trim((string) ($user?->middle_name ?? '')),
            'suffix' => trim((string) ($user?->suffix ?? '')),
            'religion' => trim((string) ($user?->religion ?? '')),
            'education_attainment' => trim((string) ($this->education_attainment ?? '')),
            'employment_type' => trim((string) ($this->employment_type ?? '')),
            'employment_sector' => trim((string) ($this->employment_sector ?? '')),
            'household_size' => $this->household_size,
            'monthly_household_income' => $this->monthly_household_income !== null
                ? (float) $this->monthly_household_income
                : null,
            'house_ownership_status' => trim((string) ($this->house_ownership_status ?? '')),
            'utilities' => $utilities,
            'height_cm' => $this->height_cm !== null ? (float) $this->height_cm : null,
            'weight_kg' => $this->weight_kg !== null ? (float) $this->weight_kg : null,
            'blood_type' => trim((string) ($this->blood_type ?? '')),
            'medical_notes' => trim((string) ($this->medical_notes ?? '')),
            'is_verified' => (bool) $this->is_verified,
            'verified_at' => optional($this->verified_at)?->toIso8601String(),
            'profile_completion' => $this->profileCompletion(),
            'updated_at' => optional($this->updated_at)?->toIso8601String(),
        ];
    }

    private function profileCompletion(): float
    {
        $checks = [
            trim((string) ($this->education_attainment ?? '')) !== '',
            trim((string) ($this->employment_type ?? '')) !== '',
            $this->household_size !== null,
            trim((string) ($this->house_ownership_status ?? '')) !== '',
            is_array($this->utilities) && count($this->utilities) > 0,
            trim((string) ($this->blood_type ?? '')) !== '',
            $this->height_cm !== null,
            $this->weight_kg !== null,
        ];

        $done = 0;
        foreach ($checks as $ok) {
            if ($ok) {
                $done++;
            }
        }

        if (count($checks) === 0) {
            return 0.0;
        }

        return round($done / count($checks), 2);
    }
}

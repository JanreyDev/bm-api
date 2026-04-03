<?php

namespace App\Http\Resources\Profile;

use App\Models\ResidentRbiRecord;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin ResidentRbiRecord */
class ResidentRbiRecordResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var User|null $user */
        $user = $this->user;

        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'rbi_id' => trim((string) ($this->rbi_id ?? '')),
            'first_name' => trim((string) ($this->first_name ?? '')),
            'middle_name' => trim((string) ($this->middle_name ?? '')),
            'last_name' => trim((string) ($this->last_name ?? '')),
            'suffix' => trim((string) ($this->suffix ?? '')),
            'full_name' => trim((string) implode(' ', array_filter([
                trim((string) ($this->first_name ?? '')),
                trim((string) ($this->middle_name ?? '')),
                trim((string) ($this->last_name ?? '')),
                trim((string) ($this->suffix ?? '')),
            ]))),
            'mobile' => trim((string) ($user?->mobile ?? '')),
            'province' => trim((string) ($this->province ?? $user?->province ?? '')),
            'city_municipality' => trim((string) ($this->city_municipality ?? $user?->city_municipality ?? '')),
            'barangay' => trim((string) ($this->barangay ?? $user?->barangay ?? '')),
            'street_name' => trim((string) ($this->street_name ?? '')),
            'zone_purok' => trim((string) ($this->zone_purok ?? '')),
            'year_of_residency' => $this->year_of_residency,
            'birth_date' => optional($this->birth_date)?->toDateString(),
            'age' => $this->birth_date !== null ? now()->diffInYears($this->birth_date) : null,
            'gender' => trim((string) ($this->gender ?? '')),
            'disability_tag' => trim((string) ($this->disability_tag ?? '')),
            'blood_donor_opt_in' => (bool) $this->blood_donor_opt_in,
            'blood_type' => trim((string) ($this->blood_type ?? '')),
            'education_aid_status' => trim((string) ($this->education_aid_status ?? '')),
            'latest_grade_average' => trim((string) ($this->latest_grade_average ?? '')),
            'family_count' => (int) $this->family_count,
            'vehicle_count' => (int) $this->vehicle_count,
            'vaccination_count' => (int) $this->vaccination_count,
            'latest_bmi' => $this->latest_bmi !== null ? (float) $this->latest_bmi : null,
            'verification_step' => (int) ($this->verification_step ?? 1),
            'is_verified' => (int) ($this->verification_step ?? 1) >= 2,
            'verified_at' => optional($this->verified_at)?->toIso8601String(),
            'updated_at' => optional($this->updated_at)?->toIso8601String(),
        ];
    }
}


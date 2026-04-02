<?php

namespace App\Http\Resources\Market;

use App\Models\MerchantRegistration;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin MerchantRegistration */
class MerchantRegistrationResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'barangay' => $this->barangay,
            'business_name' => $this->business_name,
            'owner_name' => $this->owner_name,
            'business_type' => $this->business_type,
            'contact_number' => $this->contact_number,
            'address' => $this->address,
            'meetup_spot' => $this->meetup_spot,
            'business_permit_number' => $this->business_permit_number,
            'business_permit_file_name' => $this->business_permit_file_name,
            'merchant_verified' => (bool) $this->merchant_verified,
            'verification_status' => $this->verification_status,
            'submitted_at' => optional($this->updated_at)?->toIso8601String(),
        ];
    }
}

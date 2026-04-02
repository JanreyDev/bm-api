<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class StoreMerchantRegistrationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'business_name' => ['required', 'string', 'min:2', 'max:180'],
            'owner_name' => ['required', 'string', 'min:2', 'max:180'],
            'business_type' => ['required', 'string', 'min:2', 'max:120'],
            'contact_number' => ['required', 'string', 'min:7', 'max:40'],
            'address' => ['required', 'string', 'min:8', 'max:500'],
            'meetup_spot' => ['required', 'string', 'min:2', 'max:180'],
            'business_permit_number' => ['required', 'string', 'min:6', 'max:80'],
            'business_permit_file_name' => ['required', 'string', 'min:3', 'max:255'],
            'business_permit_image_base64' => ['nullable', 'string', 'max:5000000'],
        ];
    }
}

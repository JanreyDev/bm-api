<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class StoreJobHunterProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth('api')->check();
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'full_name' => ['required', 'string', 'min:2', 'max:180'],
            'desired_job' => ['required', 'string', 'min:2', 'max:180'],
            'skills' => ['required', 'string', 'min:2', 'max:1500'],
            'preferred_setup' => ['required', 'string', 'in:On-site,Remote,Field-based'],
            'expected_salary' => ['required', 'string', 'min:2', 'max:120'],
            'barangay_zone' => ['required', 'string', 'min:2', 'max:120'],
            'available_now' => ['nullable', 'boolean'],
        ];
    }
}

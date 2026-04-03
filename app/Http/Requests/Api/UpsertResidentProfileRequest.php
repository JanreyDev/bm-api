<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class UpsertResidentProfileRequest extends FormRequest
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
            'education_attainment' => ['required', 'string', 'min:2', 'max:160'],
            'employment_type' => ['required', 'string', 'min:2', 'max:120'],
            'employment_sector' => ['nullable', 'string', 'max:120'],
            'household_size' => ['required', 'integer', 'min:1', 'max:99'],
            'monthly_household_income' => ['nullable', 'numeric', 'min:0', 'max:999999999.99'],
            'house_ownership_status' => ['required', 'string', 'min:2', 'max:120'],
            'utilities' => ['nullable', 'array', 'max:30'],
            'height_cm' => ['nullable', 'numeric', 'min:0', 'max:300'],
            'weight_kg' => ['nullable', 'numeric', 'min:0', 'max:700'],
            'blood_type' => ['nullable', 'string', 'max:20'],
            'medical_notes' => ['nullable', 'string', 'max:2000'],
            'is_verified' => ['nullable', 'boolean'],
        ];
    }
}

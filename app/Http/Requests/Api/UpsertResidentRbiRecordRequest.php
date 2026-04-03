<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class UpsertResidentRbiRecordRequest extends FormRequest
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
            'rbi_id' => ['required', 'string', 'max:80'],
            'first_name' => ['required', 'string', 'max:120'],
            'middle_name' => ['nullable', 'string', 'max:120'],
            'last_name' => ['required', 'string', 'max:120'],
            'suffix' => ['nullable', 'string', 'max:50'],
            'province' => ['nullable', 'string', 'max:120'],
            'city_municipality' => ['nullable', 'string', 'max:120'],
            'barangay' => ['nullable', 'string', 'max:120'],
            'street_name' => ['nullable', 'string', 'max:200'],
            'zone_purok' => ['nullable', 'string', 'max:120'],
            'year_of_residency' => ['nullable', 'integer', 'min:1900', 'max:2100'],
            'birth_date' => ['nullable', 'date'],
            'gender' => ['nullable', 'string', 'max:60'],
            'disability_tag' => ['nullable', 'string', 'max:120'],
            'blood_donor_opt_in' => ['nullable', 'boolean'],
            'blood_type' => ['nullable', 'string', 'max:20'],
            'education_aid_status' => ['nullable', 'string', 'max:120'],
            'latest_grade_average' => ['nullable', 'string', 'max:40'],
            'family_count' => ['nullable', 'integer', 'min:0', 'max:9999'],
            'vehicle_count' => ['nullable', 'integer', 'min:0', 'max:9999'],
            'vaccination_count' => ['nullable', 'integer', 'min:0', 'max:9999'],
            'latest_bmi' => ['nullable', 'numeric', 'min:0', 'max:120'],
            'verification_step' => ['nullable', 'integer', 'min:1', 'max:5'],
        ];
    }
}


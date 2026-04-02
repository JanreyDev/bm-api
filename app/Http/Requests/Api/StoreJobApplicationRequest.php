<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class StoreJobApplicationRequest extends FormRequest
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
            'job_id' => ['nullable', 'integer', 'min:1'],
            'job_title' => ['required', 'string', 'min:2', 'max:180'],
            'company' => ['required', 'string', 'min:2', 'max:180'],
            'posted_by' => ['nullable', 'string', 'max:180'],
            'applicant_name' => ['required', 'string', 'min:2', 'max:180'],
            'mobile_number' => ['required', 'string', 'min:7', 'max:40'],
            'cover_letter' => ['required', 'string', 'min:2', 'max:3000'],
            'attachment_name' => ['nullable', 'string', 'max:255'],
        ];
    }
}

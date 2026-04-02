<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class ToggleSavedJobRequest extends FormRequest
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
        ];
    }
}

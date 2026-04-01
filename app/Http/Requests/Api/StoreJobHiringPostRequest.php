<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class StoreJobHiringPostRequest extends FormRequest
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
            'title' => ['required', 'string', 'min:2', 'max:180'],
            'company' => ['required', 'string', 'min:2', 'max:180'],
            'location' => ['required', 'string', 'min:2', 'max:180'],
            'salary' => ['required', 'string', 'min:2', 'max:120'],
            'schedule' => ['required', 'string', 'in:Full-time,Part-time,Contract'],
            'requirements' => ['required', 'string', 'min:4', 'max:3000'],
            'posted_by' => ['nullable', 'string', 'max:180'],
            'urgent' => ['nullable', 'boolean'],
        ];
    }
}

<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class StoreJobHunterInvitationRequest extends FormRequest
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
            'talent_name' => ['required', 'string', 'min:2', 'max:180'],
            'talent_mobile' => ['nullable', 'string', 'max:40'],
            'talent_desired_job' => ['nullable', 'string', 'max:180'],
            'inviter_name' => ['nullable', 'string', 'max:180'],
            'inviter_mobile' => ['nullable', 'string', 'max:40'],
            'message' => ['required', 'string', 'min:2', 'max:3000'],
        ];
    }
}


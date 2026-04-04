<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class StoreCommunityPostRequest extends FormRequest
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
            'message' => ['required', 'string', 'min:5', 'max:2000'],
            'post_type' => ['nullable', 'string', 'in:social,announcement,event,volunteer'],
            'image_base64' => ['nullable', 'string', 'max:5000000'],
        ];
    }
}

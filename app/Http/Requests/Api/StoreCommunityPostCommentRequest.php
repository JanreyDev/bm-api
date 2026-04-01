<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class StoreCommunityPostCommentRequest extends FormRequest
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
            'message' => ['required', 'string', 'min:2', 'max:1000'],
        ];
    }
}

<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class StoreCommunityChatMessageRequest extends FormRequest
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
            'barangay' => ['required', 'string', 'min:2', 'max:120'],
            'channel' => ['required', 'string', 'min:2', 'max:80'],
            'message' => ['required', 'string', 'min:1', 'max:2000'],
        ];
    }
}

<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class StoreServiceRequestRequest extends FormRequest
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
            'service_category' => ['required', 'string', 'min:2', 'max:120'],
            'service_title' => ['required', 'string', 'min:2', 'max:180'],
            'purpose' => ['required', 'string', 'min:4', 'max:400'],
            'details' => ['nullable', 'string', 'max:3000'],
        ];
    }
}


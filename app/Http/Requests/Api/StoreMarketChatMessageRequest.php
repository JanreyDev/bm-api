<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class StoreMarketChatMessageRequest extends FormRequest
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
            'seller_name' => ['required', 'string', 'min:2', 'max:180'],
            'product_title' => ['required', 'string', 'min:2', 'max:200'],
            'buyer_mobile' => ['required', 'string', 'min:3', 'max:40'],
            'buyer_name' => ['nullable', 'string', 'max:180'],
            'message' => ['required', 'string', 'min:1', 'max:2000'],
        ];
    }
}

<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class StoreMarketProductRequest extends FormRequest
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
            'title' => ['required', 'string', 'min:2', 'max:180'],
            'category' => ['required', 'string', 'min:2', 'max:120'],
            'description' => ['required', 'string', 'min:8', 'max:2000'],
            'price' => ['required', 'numeric', 'gt:0'],
            'original_price' => ['nullable', 'numeric', 'gt:0'],
            'stock' => ['required', 'integer', 'min:1'],
            'eta' => ['required', 'string', 'min:2', 'max:180'],
            'seller_zone' => ['nullable', 'string', 'max:80'],
            'seller_purok' => ['nullable', 'string', 'max:80'],
        ];
    }
}

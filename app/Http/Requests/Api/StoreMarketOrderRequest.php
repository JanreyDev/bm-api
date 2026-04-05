<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class StoreMarketOrderRequest extends FormRequest
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
            'order_code' => ['required', 'string', 'min:3', 'max:64'],
            'status' => ['nullable', 'string', 'max:40'],
            'payer_name' => ['required', 'string', 'min:2', 'max:180'],
            'payer_mobile' => ['required', 'string', 'min:3', 'max:40'],
            'payer_address' => ['nullable', 'string', 'max:1000'],
            'payment_provider' => ['required', 'string', 'min:2', 'max:60'],
            'payment_link' => ['nullable', 'string', 'max:2000'],
            'subtotal' => ['required', 'numeric', 'min:0'],
            'delivery_fee' => ['required', 'numeric', 'min:0'],
            'total' => ['required', 'numeric', 'min:0'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.item_id' => ['nullable', 'string', 'max:120'],
            'items.*.product_key' => ['nullable', 'string', 'max:200'],
            'items.*.title' => ['required', 'string', 'max:200'],
            'items.*.seller' => ['required', 'string', 'max:180'],
            'items.*.price' => ['required', 'numeric', 'min:0'],
            'items.*.qty' => ['required', 'integer', 'min:1', 'max:99'],
            'items.*.fulfillment' => ['nullable', 'string', 'max:120'],
            'items.*.delivery_zone' => ['nullable', 'string', 'max:120'],
            'items.*.delivery_purok' => ['nullable', 'string', 'max:120'],
            'items.*.delivery_fee' => ['nullable', 'numeric', 'min:0'],
        ];
    }
}

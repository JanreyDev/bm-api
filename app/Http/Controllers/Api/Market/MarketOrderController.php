<?php

namespace App\Http\Controllers\Api\Market;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\StoreMarketOrderRequest;
use App\Http\Resources\Market\MarketOrderResource;
use App\Models\MarketOrder;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class MarketOrderController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $user = $this->authenticatedUserOrNull();
        if ($user === null) {
            return response()->json([
                'message' => 'Unauthenticated.',
            ], 401);
        }

        $barangay = trim((string) $user->barangay);
        if ($barangay === '') {
            return response()->json([
                'message' => 'Set your barangay in your profile before opening marketplace orders.',
            ], 422);
        }

        $orders = MarketOrder::query()
            ->where('user_id', $user->id)
            ->inBarangay($barangay)
            ->latest()
            ->limit(300)
            ->get();

        return response()->json([
            'message' => 'Marketplace orders loaded.',
            'orders' => $orders->map(
                fn (MarketOrder $entry): array => (new MarketOrderResource($entry))->toArray($request)
            )->values(),
        ]);
    }

    public function store(StoreMarketOrderRequest $request): JsonResponse
    {
        $user = $this->authenticatedUserOrNull();
        if ($user === null) {
            return response()->json([
                'message' => 'Unauthenticated.',
            ], 401);
        }

        $barangay = trim((string) $user->barangay);
        if ($barangay === '') {
            return response()->json([
                'message' => 'Set your barangay in your profile before placing marketplace orders.',
            ], 422);
        }

        $validated = $request->validated();
        $orderCode = trim((string) $validated['order_code']);

        $order = MarketOrder::query()->updateOrCreate(
            [
                'user_id' => $user->id,
                'order_code' => $orderCode,
            ],
            [
                'barangay' => $barangay,
                'status' => trim((string) ($validated['status'] ?? 'Pending')),
                'payer_name' => trim((string) $validated['payer_name']),
                'payer_mobile' => trim((string) $validated['payer_mobile']),
                'payer_address' => isset($validated['payer_address'])
                    ? trim((string) $validated['payer_address'])
                    : null,
                'payment_provider' => trim((string) $validated['payment_provider']),
                'payment_link' => isset($validated['payment_link'])
                    ? trim((string) $validated['payment_link'])
                    : null,
                'subtotal' => (float) $validated['subtotal'],
                'delivery_fee' => (float) $validated['delivery_fee'],
                'total' => (float) $validated['total'],
                'items_json' => $validated['items'] ?? [],
            ]
        );

        return response()->json([
            'message' => 'Marketplace order saved.',
            'order' => (new MarketOrderResource($order))->toArray($request),
        ], 201);
    }

    private function authenticatedUserOrNull(): ?User
    {
        /** @var User|null $user */
        return Auth::guard('api')->user();
    }
}

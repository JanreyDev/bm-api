<?php

namespace App\Http\Controllers\Api\Market;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\StoreMarketOrderRequest;
use App\Http\Resources\Market\MarketOrderResource;
use App\Models\MarketOrder;
use App\Models\MerchantRegistration;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
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

    public function sellerIndex(Request $request): JsonResponse
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
                'message' => 'Set your barangay in your profile before opening seller orders.',
            ], 422);
        }

        $sellerName = trim((string) $request->query('seller_name', ''));
        if ($sellerName === '') {
            $sellerName = trim((string) MerchantRegistration::query()
                ->where('user_id', $user->id)
                ->latest('id')
                ->value('business_name'));
        }
        if ($sellerName === '') {
            return response()->json([
                'message' => 'Seller name is required to load seller orders.',
            ], 422);
        }

        $orders = MarketOrder::query()
            ->inBarangay($barangay)
            ->latest()
            ->limit(500)
            ->get();

        $sellerOrders = $orders
            ->map(fn (MarketOrder $order): ?array => $this->mapSellerOrderPayload($order, $sellerName, $request))
            ->filter()
            ->values();

        return response()->json([
            'message' => 'Seller marketplace orders loaded.',
            'orders' => $sellerOrders,
        ]);
    }

    public function sellerUpdateStatus(Request $request, string $orderCode): JsonResponse
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
                'message' => 'Set your barangay in your profile before updating seller orders.',
            ], 422);
        }

        $sellerName = trim((string) $request->input('seller_name', ''));
        if ($sellerName === '') {
            $sellerName = trim((string) MerchantRegistration::query()
                ->where('user_id', $user->id)
                ->latest('id')
                ->value('business_name'));
        }
        if ($sellerName === '') {
            return response()->json([
                'message' => 'Seller name is required to update order status.',
            ], 422);
        }

        $status = trim((string) $request->input('status', ''));
        $allowed = ['Pending', 'Paid', 'Fulfilled'];
        if (!in_array($status, $allowed, true)) {
            return response()->json([
                'message' => 'Invalid status. Allowed: Pending, Paid, Fulfilled.',
            ], 422);
        }

        $candidates = MarketOrder::query()
            ->inBarangay($barangay)
            ->where('order_code', trim($orderCode))
            ->latest()
            ->get();

        $target = $candidates->first(function (MarketOrder $order) use ($sellerName): bool {
            $items = is_array($order->items_json) ? $order->items_json : [];
            foreach ($items as $entry) {
                if (!is_array($entry)) {
                    continue;
                }
                $itemSeller = trim((string) ($entry['seller'] ?? ''));
                if (mb_strtolower($itemSeller) === mb_strtolower(trim($sellerName))) {
                    return true;
                }
            }
            return false;
        });

        if ($target === null) {
            return response()->json([
                'message' => 'Order not found for this seller.',
            ], Response::HTTP_NOT_FOUND);
        }

        $target->status = $status;
        $target->save();

        return response()->json([
            'message' => 'Seller order status updated.',
            'order' => $this->mapSellerOrderPayload($target, $sellerName, $request),
        ]);
    }

    private function mapSellerOrderPayload(
        MarketOrder $order,
        string $sellerName,
        Request $request
    ): ?array {
        $allItems = is_array($order->items_json) ? $order->items_json : [];
        $sellerItems = collect($allItems)
            ->filter(function ($entry) use ($sellerName): bool {
                if (!is_array($entry)) {
                    return false;
                }
                $itemSeller = trim((string) ($entry['seller'] ?? ''));
                return mb_strtolower($itemSeller) === mb_strtolower(trim($sellerName));
            })
            ->values()
            ->all();

        if (count($sellerItems) === 0) {
            return null;
        }

        $sellerSubtotal = collect($sellerItems)->sum(
            fn (array $item): float => ((float) ($item['price'] ?? 0)) * ((int) ($item['qty'] ?? 0))
        );
        $sellerDeliveryFee = collect($sellerItems)->sum(
            fn (array $item): float => (float) ($item['delivery_fee'] ?? 0)
        );
        $sellerTotal = $sellerSubtotal + $sellerDeliveryFee;

        $mapped = (new MarketOrderResource($order))->toArray($request);
        $mapped['seller_name'] = $sellerName;
        $mapped['items'] = $sellerItems;
        $mapped['seller_subtotal'] = (float) $sellerSubtotal;
        $mapped['seller_delivery_fee'] = (float) $sellerDeliveryFee;
        $mapped['seller_total'] = (float) $sellerTotal;
        return $mapped;
    }

    private function authenticatedUserOrNull(): ?User
    {
        /** @var User|null $user */
        return Auth::guard('api')->user();
    }
}

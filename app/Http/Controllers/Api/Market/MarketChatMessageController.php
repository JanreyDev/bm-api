<?php

namespace App\Http\Controllers\Api\Market;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\StoreMarketChatMessageRequest;
use App\Http\Resources\Market\MarketChatMessageResource;
use App\Models\MarketChatMessage;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class MarketChatMessageController extends Controller
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
                'message' => 'Set your barangay in your profile before opening marketplace chat.',
            ], 422);
        }

        $sellerName = trim((string) $request->query('seller_name', ''));
        $productTitle = trim((string) $request->query('product_title', ''));
        $buyerMobile = trim((string) $request->query('buyer_mobile', ''));
        if ($sellerName === '' || $productTitle === '' || $buyerMobile === '') {
            return response()->json([
                'message' => 'seller_name, product_title, and buyer_mobile are required.',
            ], 422);
        }

        $messages = MarketChatMessage::query()
            ->inBarangay($barangay)
            ->inThread($buyerMobile, $sellerName, $productTitle)
            ->latest()
            ->limit(500)
            ->get()
            ->reverse()
            ->values();

        return response()->json([
            'message' => 'Marketplace chat messages loaded.',
            'messages' => $messages->map(
                fn (MarketChatMessage $entry): array => (new MarketChatMessageResource($entry))->toArray($request)
            )->values(),
        ]);
    }

    public function store(StoreMarketChatMessageRequest $request): JsonResponse
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
                'message' => 'Set your barangay in your profile before sending marketplace chat messages.',
            ], 422);
        }

        $validated = $request->validated();
        $senderName = trim((string) $user->name) !== '' ? trim((string) $user->name) : 'Resident';
        $senderRole = trim((string) $user->role) === 'official' ? 'seller' : 'buyer';

        $entry = MarketChatMessage::query()->create([
            'user_id' => $user->id,
            'sender_name' => $senderName,
            'sender_role' => $senderRole,
            'barangay' => $barangay,
            'buyer_mobile' => trim((string) ($validated['buyer_mobile'] ?? '')),
            'buyer_name' => trim((string) ($validated['buyer_name'] ?? '')),
            'seller_name' => trim((string) ($validated['seller_name'] ?? '')),
            'product_title' => trim((string) ($validated['product_title'] ?? '')),
            'message' => trim((string) ($validated['message'] ?? '')),
            'is_official' => trim((string) $user->role) === 'official',
        ]);

        return response()->json([
            'message' => 'Marketplace chat message sent.',
            'chat_message' => (new MarketChatMessageResource($entry))->toArray($request),
        ], 201);
    }

    private function authenticatedUserOrNull(): ?User
    {
        /** @var User|null $user */
        return Auth::guard('api')->user();
    }
}

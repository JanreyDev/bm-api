<?php

namespace App\Http\Controllers\Api\Market;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\StoreMarketProductRequest;
use App\Http\Resources\Market\MarketProductResource;
use App\Models\MarketProduct;
use App\Models\MerchantRegistration;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class MarketProductController extends Controller
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
                'message' => 'Set your barangay in your profile before opening marketplace.',
            ], 422);
        }

        $products = MarketProduct::query()
            ->inBarangay($barangay)
            ->latest()
            ->limit(300)
            ->get();

        return response()->json([
            'message' => 'Marketplace products loaded.',
            'products' => $products->map(
                fn (MarketProduct $product): array => (new MarketProductResource($product))->toArray($request)
            )->values(),
        ]);
    }

    public function store(StoreMarketProductRequest $request): JsonResponse
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
                'message' => 'Set your barangay in your profile before posting products.',
            ], 422);
        }

        $registration = MerchantRegistration::query()
            ->where('user_id', $user->id)
            ->latest()
            ->first();
        if ($registration === null) {
            return response()->json([
                'message' => 'Register your business first before adding products.',
            ], 422);
        }

        $validated = $request->validated();
        $category = trim((string) $validated['category']);
        $imageAsset = $this->resolveImageAsset($category);
        $thumbnailBase64 = $this->normalizeImagePayload(
            $validated['thumbnail_base64'] ?? null,
            $validated['thumbnail_file_name'] ?? null,
        );

        $product = MarketProduct::query()->create([
            'user_id' => $user->id,
            'merchant_registration_id' => $registration->id,
            'barangay' => $barangay,
            'title' => trim((string) $validated['title']),
            'seller_name' => trim((string) $registration->business_name),
            'price' => (float) $validated['price'],
            'original_price' => isset($validated['original_price']) ? (float) $validated['original_price'] : null,
            'description' => trim((string) $validated['description']),
            'category' => $category,
            'stock' => (int) $validated['stock'],
            'eta' => trim((string) $validated['eta']),
            'verified' => (bool) $registration->merchant_verified,
            'seller_zone' => trim((string) ($validated['seller_zone'] ?? 'Zone 1')),
            'seller_purok' => trim((string) ($validated['seller_purok'] ?? 'Purok 1')),
            'sold' => 0,
            'reviews' => 0,
            'rating' => 4.8,
            'image_asset' => $imageAsset,
            'thumbnail_base64' => $thumbnailBase64,
            'thumbnail_file_name' => $validated['thumbnail_file_name'] ?? null,
        ]);

        return response()->json([
            'message' => 'Product saved successfully.',
            'product' => (new MarketProductResource($product))->toArray($request),
        ], 201);
    }

    private function resolveImageAsset(string $category): string
    {
        $normalized = mb_strtolower(trim($category));
        if (str_contains($normalized, 'furn') || str_contains($normalized, 'table')) {
            return 'public/item-table.jpg';
        }
        if (str_contains($normalized, 'emerg') || str_contains($normalized, 'bag')) {
            return 'public/item-gobag.jpg';
        }
        if (str_contains($normalized, 'print')) {
            return 'public/item-printer.jpg';
        }
        return 'public/item-laptop.jpg';
    }

    private function normalizeImagePayload(?string $payload, ?string $fileName = null): ?string
    {
        if ($payload === null) {
            return null;
        }

        $trimmed = trim($payload);
        if ($trimmed === '') {
            return null;
        }

        if (preg_match('/^data:image\/[a-zA-Z0-9.+-]+;base64,/', $trimmed) === 1) {
            return $trimmed;
        }

        if (base64_decode($trimmed, true) === false) {
            return null;
        }

        return 'data:' . $this->imageMimeFromFileName($fileName) . ';base64,' . $trimmed;
    }

    private function imageMimeFromFileName(?string $fileName): string
    {
        if ($fileName === null) {
            return 'image/jpeg';
        }

        $lower = mb_strtolower(trim($fileName));
        if (str_ends_with($lower, '.png')) {
            return 'image/png';
        }
        if (str_ends_with($lower, '.webp')) {
            return 'image/webp';
        }
        if (str_ends_with($lower, '.gif')) {
            return 'image/gif';
        }

        return 'image/jpeg';
    }

    private function authenticatedUserOrNull(): ?User
    {
        /** @var User|null $user */
        return Auth::guard('api')->user();
    }
}

<?php

namespace App\Http\Controllers\Api\Official;

use App\Http\Controllers\Controller;
use App\Models\CommunityPost;
use App\Models\MarketProduct;
use App\Models\ServiceRequest;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;

class OfficialTransactionHistoryController extends Controller
{
    private const DEFAULT_LIMIT = 200;
    private const MAX_LIMIT = 500;

    public function __invoke(Request $request): JsonResponse
    {
        $user = $this->authenticatedUserOrNull();
        if ($user === null) {
            return response()->json([
                'message' => 'Unauthenticated.',
            ], 401);
        }

        if ($user->role !== 'official') {
            return response()->json([
                'message' => 'Only official accounts can access transaction history.',
            ], 403);
        }

        [$province, $city, $barangay] = $this->scopeFromUser($request, $user);
        if ($barangay === '') {
            return response()->json([
                'message' => 'Set your province/city/barangay in profile before opening transaction history.',
            ], 422);
        }

        $limit = (int) $request->query('limit', self::DEFAULT_LIMIT);
        $limit = max(1, min(self::MAX_LIMIT, $limit));

        $serviceRequests = ServiceRequest::query()
            ->inBarangay($barangay)
            ->whereHas('user', function ($query) use ($province, $city, $barangay): void {
                $query->whereRaw('LOWER(TRIM(barangay)) = ?', [mb_strtolower($barangay)]);
                if ($province !== '') {
                    $query->whereRaw('LOWER(TRIM(province)) = ?', [mb_strtolower($province)]);
                }
                if ($city !== '') {
                    $query->whereRaw('LOWER(TRIM(city_municipality)) = ?', [mb_strtolower($city)]);
                }
            })
            ->latest()
            ->take(self::MAX_LIMIT)
            ->get()
            ->map(static function (ServiceRequest $entry): array {
                return [
                    'id' => sprintf('SR-%d', (int) $entry->id),
                    'title' => trim((string) ($entry->service_title ?: 'Service request')),
                    'type' => 'Service Request',
                    'reference' => trim((string) ($entry->request_id ?: sprintf('SR-%d', (int) $entry->id))),
                    'status' => trim((string) ($entry->status ?: 'Pending')),
                    'amount' => 0,
                    'created_at' => optional($entry->created_at)?->toIso8601String(),
                ];
            });

        $marketListings = MarketProduct::query()
            ->inBarangay($barangay)
            ->whereHas('user', function ($query) use ($province, $city, $barangay): void {
                $query->whereRaw('LOWER(TRIM(barangay)) = ?', [mb_strtolower($barangay)]);
                if ($province !== '') {
                    $query->whereRaw('LOWER(TRIM(province)) = ?', [mb_strtolower($province)]);
                }
                if ($city !== '') {
                    $query->whereRaw('LOWER(TRIM(city_municipality)) = ?', [mb_strtolower($city)]);
                }
            })
            ->latest()
            ->take(self::MAX_LIMIT)
            ->get()
            ->map(static function (MarketProduct $entry): array {
                return [
                    'id' => sprintf('MP-%d', (int) $entry->id),
                    'title' => trim((string) ($entry->title ?: 'Marketplace listing')),
                    'type' => 'Marketplace Listing',
                    'reference' => sprintf('MP-%d', (int) $entry->id),
                    'status' => $entry->verified ? 'Verified' : 'Pending',
                    'amount' => (float) ($entry->price ?? 0),
                    'created_at' => optional($entry->created_at)?->toIso8601String(),
                ];
            });

        $communityUpdates = CommunityPost::query()
            ->inBarangay($barangay)
            ->whereHas('user', function ($query) use ($province, $city, $barangay): void {
                $query->whereRaw('LOWER(TRIM(barangay)) = ?', [mb_strtolower($barangay)]);
                if ($province !== '') {
                    $query->whereRaw('LOWER(TRIM(province)) = ?', [mb_strtolower($province)]);
                }
                if ($city !== '') {
                    $query->whereRaw('LOWER(TRIM(city_municipality)) = ?', [mb_strtolower($city)]);
                }
            })
            ->latest()
            ->take(self::MAX_LIMIT)
            ->get()
            ->map(static function (CommunityPost $entry): array {
                $title = $entry->is_official ? 'Official community update' : 'Community post';
                $message = trim((string) $entry->message);
                if ($message === '') {
                    $message = 'No message';
                }

                return [
                    'id' => sprintf('CP-%d', (int) $entry->id),
                    'title' => mb_substr($message, 0, 80),
                    'type' => $title,
                    'reference' => sprintf('CP-%d', (int) $entry->id),
                    'status' => 'Published',
                    'amount' => 0,
                    'created_at' => optional($entry->created_at)?->toIso8601String(),
                ];
            });

        $transactions = (new Collection())
            ->concat($serviceRequests)
            ->concat($marketListings)
            ->concat($communityUpdates)
            ->filter(static fn (array $entry): bool => !empty($entry['created_at']))
            ->sortByDesc(static fn (array $entry): string => (string) $entry['created_at'])
            ->values()
            ->take($limit)
            ->all();

        return response()->json([
            'message' => 'Official transaction history loaded.',
            'barangay' => $barangay,
            'transactions' => $transactions,
        ]);
    }

    private function authenticatedUserOrNull(): ?User
    {
        /** @var User|null $user */
        return Auth::guard('api')->user();
    }

    /**
     * @return array{string,string,string}
     */
    private function scopeFromUser(Request $request, User $user): array
    {
        $province = trim((string) $user->province);
        $city = trim((string) $user->city_municipality);
        $barangay = trim((string) $user->barangay);

        if ($province === '') {
            $province = trim((string) $request->query('province', ''));
        }
        if ($city === '') {
            $city = trim((string) $request->query('city_municipality', ''));
        }
        if ($barangay === '') {
            $barangay = trim((string) $request->query('barangay', ''));
        }

        $updates = [];
        if ($province !== '' && trim((string) $user->province) === '') {
            $updates['province'] = mb_substr($province, 0, 100);
        }
        if ($city !== '' && trim((string) $user->city_municipality) === '') {
            $updates['city_municipality'] = mb_substr($city, 0, 100);
        }
        if ($barangay !== '' && trim((string) $user->barangay) === '') {
            $updates['barangay'] = mb_substr($barangay, 0, 100);
        }

        if ($updates !== []) {
            $user->forceFill($updates)->save();
            $province = trim((string) ($updates['province'] ?? $province));
            $city = trim((string) ($updates['city_municipality'] ?? $city));
            $barangay = trim((string) ($updates['barangay'] ?? $barangay));
        }

        return [$province, $city, $barangay];
    }
}


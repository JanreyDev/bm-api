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

class RecentActivityController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $user = $this->authenticatedUserOrNull();
        if ($user === null) {
            return response()->json([
                'message' => 'Unauthenticated.',
            ], 401);
        }

        $barangay = trim((string) $user->barangay);
        if ($barangay === '') {
            $barangay = trim((string) $request->query('barangay', ''));
        }
        if ($barangay === '') {
            return response()->json([
                'message' => 'Set your barangay in your profile before opening recent activity.',
            ], 422);
        }

        $requestEvents = ServiceRequest::query()
            ->inBarangay($barangay)
            ->latest()
            ->take(6)
            ->get()
            ->map(static function (ServiceRequest $entry): array {
                $status = trim((string) $entry->status);
                $statusLabel = $status === '' ? 'pending' : mb_strtolower($status);
                $service = trim((string) ($entry->service_title ?? 'Service request'));

                return [
                    'type' => 'request',
                    'title' => 'Service request received',
                    'note' => sprintf('%s (%s)', $service, $statusLabel),
                    'timestamp' => optional($entry->created_at)?->toIso8601String(),
                ];
            });

        $communityEvents = CommunityPost::query()
            ->inBarangay($barangay)
            ->latest()
            ->take(6)
            ->get()
            ->map(static function (CommunityPost $post): array {
                $label = $post->is_official ? 'Official post published' : 'Community post published';
                $message = trim((string) $post->message);
                if ($message === '') {
                    $message = 'No message';
                }

                return [
                    'type' => 'community',
                    'title' => $label,
                    'note' => mb_substr($message, 0, 80),
                    'timestamp' => optional($post->created_at)?->toIso8601String(),
                ];
            });

        $productEvents = MarketProduct::query()
            ->inBarangay($barangay)
            ->latest()
            ->take(6)
            ->get()
            ->map(static function (MarketProduct $product): array {
                $title = trim((string) ($product->title ?? 'Product'));
                $status = $product->verified ? 'verified listing' : 'pending listing';

                return [
                    'type' => 'market',
                    'title' => 'Market product listed',
                    'note' => sprintf('%s (%s)', $title, $status),
                    'timestamp' => optional($product->created_at)?->toIso8601String(),
                ];
            });

        $activities = (new Collection())
            ->concat($requestEvents)
            ->concat($communityEvents)
            ->concat($productEvents)
            ->filter(static fn (array $entry): bool => !empty($entry['timestamp']))
            ->sortByDesc(static fn (array $entry): string => (string) $entry['timestamp'])
            ->values()
            ->take(10)
            ->all();

        return response()->json([
            'message' => 'Official recent activity loaded.',
            'barangay' => $barangay,
            'activities' => $activities,
        ]);
    }

    private function authenticatedUserOrNull(): ?User
    {
        /** @var User|null $user */
        return Auth::guard('api')->user();
    }
}


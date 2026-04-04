<?php

namespace App\Http\Controllers\Api\Official;

use App\Http\Controllers\Controller;
use App\Models\OfficialNotification;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class OfficialNotificationController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $user = $this->authenticatedUserOrNull();
        if ($user === null) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }
        if ($user->role !== 'official') {
            return response()->json(['message' => 'Only official accounts can open notifications.'], 403);
        }

        [$province, $city, $barangay] = $this->scopeFromUser($request, $user);
        if ($province === '' || $city === '' || $barangay === '') {
            return response()->json([
                'message' => 'Set your province/city/barangay in profile before opening notifications.',
            ], 422);
        }

        $notifications = OfficialNotification::query()
            ->where(static function ($query) use ($user, $province, $city, $barangay): void {
                $query->where('target_user_id', $user->id)
                    ->orWhere(static function ($q) use ($province, $city, $barangay): void {
                        $q->whereNull('target_user_id')
                            ->where('province', $province)
                            ->where('city_municipality', $city)
                            ->where('barangay', $barangay);
                    });
            })
            ->latest()
            ->limit(300)
            ->get();

        return response()->json([
            'message' => 'Official notifications loaded.',
            'notifications' => $notifications->map(
                fn (OfficialNotification $entry): array => $this->mapNotification($entry)
            )->values()->all(),
            'unread_count' => $notifications->where('is_read', false)->count(),
        ]);
    }

    public function markRead(Request $request, int $notificationId): JsonResponse
    {
        $user = $this->authenticatedUserOrNull();
        if ($user === null) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }
        if ($user->role !== 'official') {
            return response()->json(['message' => 'Only official accounts can manage notifications.'], 403);
        }

        [$province, $city, $barangay] = $this->scopeFromUser($request, $user);

        $notification = OfficialNotification::query()->find($notificationId);
        if ($notification === null) {
            return response()->json(['message' => 'Notification not found.'], 404);
        }

        if (!$this->canManageNotification(
            $notification,
            $user,
            $province,
            $city,
            $barangay,
        )) {
            return response()->json(['message' => 'You can only update notifications in your barangay scope.'], 403);
        }

        if (!$notification->is_read) {
            $notification->forceFill([
                'is_read' => true,
                'read_at' => now(),
            ])->save();
        }

        return response()->json([
            'message' => 'Notification marked as read.',
            'notification' => $this->mapNotification($notification->fresh() ?? $notification),
        ]);
    }

    public function markAllRead(Request $request): JsonResponse
    {
        $user = $this->authenticatedUserOrNull();
        if ($user === null) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }
        if ($user->role !== 'official') {
            return response()->json(['message' => 'Only official accounts can manage notifications.'], 403);
        }

        [$province, $city, $barangay] = $this->scopeFromUser($request, $user);
        if ($province === '' || $city === '' || $barangay === '') {
            return response()->json([
                'message' => 'Set your province/city/barangay in profile before updating notifications.',
            ], 422);
        }

        $updated = OfficialNotification::query()
            ->where('is_read', false)
            ->where(static function ($query) use ($user, $province, $city, $barangay): void {
                $query->where('target_user_id', $user->id)
                    ->orWhere(static function ($q) use ($province, $city, $barangay): void {
                        $q->whereNull('target_user_id')
                            ->where('province', $province)
                            ->where('city_municipality', $city)
                            ->where('barangay', $barangay);
                    });
            })
            ->update([
                'is_read' => true,
                'read_at' => now(),
                'updated_at' => now(),
            ]);

        return response()->json([
            'message' => 'All notifications marked as read.',
            'updated' => (int) $updated,
        ]);
    }

    private function canManageNotification(
        OfficialNotification $notification,
        User $user,
        string $province,
        string $city,
        string $barangay,
    ): bool {
        if ($notification->target_user_id !== null) {
            return (int) $notification->target_user_id === (int) $user->id;
        }

        return $notification->province === $province
            && $notification->city_municipality === $city
            && $notification->barangay === $barangay;
    }

    /**
     * @return array<string, mixed>
     */
    private function mapNotification(OfficialNotification $entry): array
    {
        return [
            'id' => $entry->id,
            'title' => trim((string) $entry->title),
            'body' => trim((string) $entry->body),
            'category' => trim((string) $entry->category),
            'priority' => trim((string) $entry->priority),
            'record_type' => trim((string) ($entry->record_type ?? '')),
            'record_id' => trim((string) ($entry->record_id ?? '')),
            'deep_link' => trim((string) ($entry->deep_link ?? '')),
            'metadata' => is_array($entry->metadata_json) ? $entry->metadata_json : [],
            'is_read' => (bool) $entry->is_read,
            'read_at' => optional($entry->read_at)?->toIso8601String(),
            'created_at' => optional($entry->created_at)?->toIso8601String(),
            'updated_at' => optional($entry->updated_at)?->toIso8601String(),
        ];
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
            $province = trim((string) $request->input('province', $request->query('province', '')));
        }
        if ($city === '') {
            $city = trim((string) $request->input('city_municipality', $request->query('city_municipality', '')));
        }
        if ($barangay === '') {
            $barangay = trim((string) $request->input('barangay', $request->query('barangay', '')));
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

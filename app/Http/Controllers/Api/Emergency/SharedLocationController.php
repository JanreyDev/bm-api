<?php

namespace App\Http\Controllers\Api\Emergency;

use App\Http\Controllers\Controller;
use App\Models\EmergencySharedLocation;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SharedLocationController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $user = $this->authenticatedUserOrNull();
        if ($user === null) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        [$province, $city, $barangay] = $this->scopeFromUser($request, $user);
        if ($province === '' || $city === '' || $barangay === '') {
            return response()->json([
                'message' => 'Set your province/city/barangay before opening shared locations.',
                'locations' => [],
            ], 200);
        }

        $limit = (int) $request->integer('limit', 20);
        $limit = max(1, min($limit, 100));

        $query = EmergencySharedLocation::query()
            ->whereRaw('LOWER(TRIM(province)) = ?', [$this->normalizeScopeValue($province)])
            ->whereRaw('LOWER(TRIM(city_municipality)) = ?', [$this->normalizeScopeValue($city)])
            ->whereRaw('LOWER(TRIM(barangay)) = ?', [$this->normalizeScopeValue($barangay)])
            ->with('user:id,name,mobile,role')
            ->latest('shared_at');

        if ($user->role !== 'official') {
            $query->where('user_id', $user->id);
        }

        $rows = $query->limit($limit)->get();

        return response()->json([
            'message' => $rows->isNotEmpty
                ? 'Shared locations loaded.'
                : 'No shared locations yet.',
            'locations' => $rows->map(function (EmergencySharedLocation $entry): array {
                $payload = $this->serializeEntry($entry);
                $payload['user_name'] = trim((string) optional($entry->user)->name);
                $payload['user_mobile'] = trim((string) optional($entry->user)->mobile);
                $payload['user_role'] = trim((string) optional($entry->user)->role);
                return $payload;
            })->values()->all(),
        ]);
    }

    public function show(Request $request): JsonResponse
    {
        $user = $this->authenticatedUserOrNull();
        if ($user === null) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        [$province, $city, $barangay] = $this->scopeFromUser($request, $user);
        if ($province === '' || $city === '' || $barangay === '') {
            return response()->json([
                'message' => 'Set your province/city/barangay before opening live location.',
                'location' => null,
            ], 200);
        }

        $entry = EmergencySharedLocation::query()
            ->where('user_id', $user->id)
            ->whereRaw('LOWER(TRIM(province)) = ?', [$this->normalizeScopeValue($province)])
            ->whereRaw('LOWER(TRIM(city_municipality)) = ?', [$this->normalizeScopeValue($city)])
            ->whereRaw('LOWER(TRIM(barangay)) = ?', [$this->normalizeScopeValue($barangay)])
            ->latest('shared_at')
            ->first();

        return response()->json([
            'message' => $entry ? 'Latest live location loaded.' : 'No shared live location yet.',
            'location' => $entry ? $this->serializeEntry($entry) : null,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $user = $this->authenticatedUserOrNull();
        if ($user === null) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        $validated = $request->validate([
            'address' => ['required', 'string', 'max:255'],
            'latitude' => ['required', 'numeric', 'between:-90,90'],
            'longitude' => ['required', 'numeric', 'between:-180,180'],
            'high_accuracy' => ['nullable', 'boolean'],
            'include_landmark' => ['nullable', 'boolean'],
        ]);

        [$province, $city, $barangay] = $this->scopeFromUser($request, $user);
        if ($province === '' || $city === '' || $barangay === '') {
            return response()->json([
                'message' => 'Set your province/city/barangay before sharing live location.',
            ], 422);
        }

        $entry = EmergencySharedLocation::query()->create([
            'user_id' => $user->id,
            'province' => $province,
            'city_municipality' => $city,
            'barangay' => $barangay,
            'address' => trim((string) $validated['address']),
            'latitude' => (float) $validated['latitude'],
            'longitude' => (float) $validated['longitude'],
            'high_accuracy' => (bool) ($validated['high_accuracy'] ?? true),
            'include_landmark' => (bool) ($validated['include_landmark'] ?? true),
            'shared_at' => now(),
        ]);

        return response()->json([
            'message' => 'Live location shared successfully.',
            'location' => $this->serializeEntry($entry),
        ], 201);
    }

    private function serializeEntry(EmergencySharedLocation $entry): array
    {
        return [
            'id' => $entry->id,
            'address' => trim((string) $entry->address),
            'latitude' => (float) $entry->latitude,
            'longitude' => (float) $entry->longitude,
            'high_accuracy' => (bool) $entry->high_accuracy,
            'include_landmark' => (bool) $entry->include_landmark,
            'shared_at' => optional($entry->shared_at)?->toIso8601String(),
        ];
    }

    private function authenticatedUserOrNull(): ?User
    {
        /** @var User|null $user */
        return Auth::guard('api')->user();
    }

    private function normalizeScopeValue(string $value): string
    {
        return mb_strtolower(trim(preg_replace('/\s+/', ' ', $value) ?? $value));
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

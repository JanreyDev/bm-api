<?php

namespace App\Http\Controllers\Api\Official;

use App\Http\Controllers\Controller;
use App\Models\OfficialBarangaySetup;
use App\Models\ResidentProfile;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class OfficialBarangaySetupController extends Controller
{
    public function branding(Request $request): JsonResponse
    {
        $user = $this->authenticatedUserOrNull();
        if ($user === null) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        [$province, $city, $barangay] = $this->scopeFromUser($request, $user);
        if ($province === '' || $city === '' || $barangay === '') {
            return response()->json([
                'message' => 'Set your province/city/barangay in profile before loading barangay branding.',
            ], 422);
        }

        $entry = OfficialBarangaySetup::query()
            ->where('province', $province)
            ->where('city_municipality', $city)
            ->where('barangay', $barangay)
            ->first();

        return response()->json([
            'message' => 'Barangay branding loaded.',
            'branding' => [
                'province' => $province,
                'city_municipality' => $city,
                'barangay' => $barangay,
                'logo_file_name' => trim((string) ($entry?->logo_file_name ?? '')),
                'logo_image_base64' => trim((string) ($entry?->logo_image_base64 ?? '')),
                'website' => trim((string) ($entry?->website ?? '')),
                'facebook_url' => trim((string) ($entry?->facebook_url ?? '')),
            ],
            'computed_population' => $this->computedPopulation($barangay),
        ]);
    }

    public function show(Request $request): JsonResponse
    {
        $user = $this->authenticatedUserOrNull();
        if ($user === null) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }
        if ($user->role !== 'official') {
            return response()->json(['message' => 'Only official accounts can open barangay setup.'], 403);
        }

        [$province, $city, $barangay] = $this->scopeFromUser($request, $user);
        if ($province === '' || $city === '' || $barangay === '') {
            return response()->json([
                'message' => 'Set your province/city/barangay in profile before opening barangay setup.',
            ], 422);
        }

        $entry = OfficialBarangaySetup::query()
            ->where('province', $province)
            ->where('city_municipality', $city)
            ->where('barangay', $barangay)
            ->first();

        return response()->json([
            'message' => 'Barangay setup loaded.',
            'setup' => $entry === null ? null : $this->mapSetup($entry),
            'computed_population' => $this->computedPopulation($barangay),
        ]);
    }

    public function upsert(Request $request): JsonResponse
    {
        $user = $this->authenticatedUserOrNull();
        if ($user === null) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }
        if ($user->role !== 'official') {
            return response()->json(['message' => 'Only official accounts can update barangay setup.'], 403);
        }

        [$province, $city, $barangay] = $this->scopeFromUser($request, $user);
        if ($province === '' || $city === '' || $barangay === '') {
            return response()->json([
                'message' => 'Set your province/city/barangay in profile before updating barangay setup.',
            ], 422);
        }

        $validated = $request->validate([
            'division_type' => ['required', 'string', 'in:Zone,Purok,Sitio'],
            'division_count' => ['required', 'integer', 'min:1', 'max:99999'],
            'founding_year' => ['required', 'integer', 'min:1900', 'max:2100'],
            'website' => ['nullable', 'string', 'max:255'],
            'facebook_url' => ['nullable', 'string', 'max:255'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
            'logo_file_name' => ['nullable', 'string', 'max:180'],
            'logo_image_base64' => ['nullable', 'string'],
        ]);

        $website = trim((string) ($validated['website'] ?? ''));
        $facebookUrl = trim((string) ($validated['facebook_url'] ?? ''));
        if ($website !== '' && !$this->hasHttpScheme($website)) {
            return response()->json(['message' => 'Website must start with http:// or https://.'], 422);
        }
        if ($facebookUrl !== '' && !$this->hasHttpScheme($facebookUrl)) {
            return response()->json(['message' => 'Facebook URL must start with http:// or https://.'], 422);
        }

        $logoPayload = $this->normalizeImagePayload($validated['logo_image_base64'] ?? null);

        $entry = OfficialBarangaySetup::query()->updateOrCreate(
            [
                'province' => $province,
                'city_municipality' => $city,
                'barangay' => $barangay,
            ],
            [
                'updated_by_user_id' => $user->id,
                'division_type' => trim((string) $validated['division_type']),
                'division_count' => (int) $validated['division_count'],
                'founding_year' => (int) $validated['founding_year'],
                'website' => $website !== '' ? $website : null,
                'facebook_url' => $facebookUrl !== '' ? $facebookUrl : null,
                'latitude' => array_key_exists('latitude', $validated) ? $validated['latitude'] : null,
                'longitude' => array_key_exists('longitude', $validated) ? $validated['longitude'] : null,
                'logo_file_name' => trim((string) ($validated['logo_file_name'] ?? '')) ?: null,
                'logo_image_base64' => $logoPayload,
            ]
        );

        return response()->json([
            'message' => 'Barangay setup saved.',
            'setup' => $this->mapSetup($entry),
            'computed_population' => $this->computedPopulation($barangay),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function mapSetup(OfficialBarangaySetup $entry): array
    {
        return [
            'id' => $entry->id,
            'province' => trim((string) $entry->province),
            'city_municipality' => trim((string) $entry->city_municipality),
            'barangay' => trim((string) $entry->barangay),
            'division_type' => trim((string) $entry->division_type),
            'division_count' => (int) $entry->division_count,
            'founding_year' => (int) $entry->founding_year,
            'website' => trim((string) ($entry->website ?? '')),
            'facebook_url' => trim((string) ($entry->facebook_url ?? '')),
            'latitude' => $entry->latitude === null ? null : (float) $entry->latitude,
            'longitude' => $entry->longitude === null ? null : (float) $entry->longitude,
            'logo_file_name' => trim((string) ($entry->logo_file_name ?? '')),
            'logo_image_base64' => trim((string) ($entry->logo_image_base64 ?? '')),
            'updated_at' => optional($entry->updated_at)?->toIso8601String(),
        ];
    }

    private function computedPopulation(string $barangay): int
    {
        $residentUsers = User::query()
            ->where('role', 'resident')
            ->where('barangay', $barangay)
            ->count();

        $populationFromHousehold = ResidentProfile::query()
            ->whereHas('user', static function ($query) use ($barangay): void {
                $query->where('role', 'resident')->where('barangay', $barangay);
            })
            ->sum('household_size');

        return max((int) $residentUsers, (int) $populationFromHousehold);
    }

    private function hasHttpScheme(string $value): bool
    {
        $lower = strtolower(trim($value));
        return str_starts_with($lower, 'http://') || str_starts_with($lower, 'https://');
    }

    private function normalizeImagePayload(mixed $value): ?string
    {
        if (!is_string($value)) {
            return null;
        }
        $trimmed = trim($value);
        if ($trimmed === '') {
            return null;
        }
        if (preg_match('/^data:image\/[a-zA-Z0-9.+-]+;base64,/', $trimmed) === 1) {
            return $trimmed;
        }

        return preg_match('/^[A-Za-z0-9+\/=\r\n]+$/', $trimmed) === 1 ? $trimmed : null;
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

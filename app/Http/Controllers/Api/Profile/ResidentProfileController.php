<?php

namespace App\Http\Controllers\Api\Profile;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\UpsertResidentProfileRequest;
use App\Http\Resources\Profile\ResidentProfileResource;
use App\Models\ResidentProfile;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ResidentProfileController extends Controller
{
    public function show(Request $request): JsonResponse
    {
        $user = $this->authenticatedUserOrNull();
        if ($user === null) {
            return response()->json([
                'message' => 'Unauthenticated.',
            ], 401);
        }

        $profile = ResidentProfile::query()->firstOrCreate(
            ['user_id' => $user->id],
            ['is_verified' => false]
        );
        $profile->loadMissing('user');

        return response()->json([
            'message' => 'Resident profile loaded.',
            'profile' => (new ResidentProfileResource($profile))->toArray($request),
        ]);
    }

    public function upsert(UpsertResidentProfileRequest $request): JsonResponse
    {
        $user = $this->authenticatedUserOrNull();
        if ($user === null) {
            return response()->json([
                'message' => 'Unauthenticated.',
            ], 401);
        }

        $validated = $request->validated();
        $isVerified = (bool) ($validated['is_verified'] ?? true);

        $profile = ResidentProfile::query()->updateOrCreate(
            ['user_id' => $user->id],
            [
                'education_attainment' => trim((string) ($validated['education_attainment'] ?? '')),
                'employment_type' => trim((string) ($validated['employment_type'] ?? '')),
                'employment_sector' => trim((string) ($validated['employment_sector'] ?? '')),
                'household_size' => (int) ($validated['household_size'] ?? 0),
                'monthly_household_income' => $validated['monthly_household_income'] ?? null,
                'house_ownership_status' => trim((string) ($validated['house_ownership_status'] ?? '')),
                'utilities' => $this->normalizeUtilities($validated['utilities'] ?? null),
                'height_cm' => $validated['height_cm'] ?? null,
                'weight_kg' => $validated['weight_kg'] ?? null,
                'blood_type' => trim((string) ($validated['blood_type'] ?? '')),
                'medical_notes' => trim((string) ($validated['medical_notes'] ?? '')),
                'is_verified' => $isVerified,
                'verified_at' => $isVerified ? now() : null,
            ]
        );

        $profile->loadMissing('user');

        return response()->json([
            'message' => 'Resident profile updated.',
            'profile' => (new ResidentProfileResource($profile))->toArray($request),
        ]);
    }

    /**
     * @param mixed $raw
     * @return array<string, bool>
     */
    private function normalizeUtilities(mixed $raw): array
    {
        if (!is_array($raw)) {
            return [];
        }

        $out = [];
        foreach ($raw as $key => $value) {
            if (!is_string($key)) {
                continue;
            }
            $name = trim($key);
            if ($name === '') {
                continue;
            }
            $out[$name] = (bool) $value;
        }
        return $out;
    }

    private function authenticatedUserOrNull(): ?User
    {
        /** @var User|null $user */
        return Auth::guard('api')->user();
    }
}

<?php

namespace App\Http\Controllers\Api\Jobs;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\StoreJobHunterProfileRequest;
use App\Http\Resources\Jobs\JobHunterProfileResource;
use App\Models\JobHunterProfile;
use App\Models\User;
use App\Services\Jobs\JobHunterProfileService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class JobHunterProfileController extends Controller
{
    public function __construct(
        private readonly JobHunterProfileService $service
    ) {}

    public function index(Request $request): JsonResponse
    {
        $user = $this->authenticatedUserOrNull();
        if ($user === null) {
            return response()->json([
                'message' => 'Unauthenticated.',
            ], 401);
        }

        $barangay = $this->service->resolveBarangayOrNull($user);
        if ($barangay === null) {
            return response()->json([
                'message' => 'Set your barangay in your profile before opening job hunter profiles.',
            ], 422);
        }

        $profiles = $this->service->listForViewer($user);

        return response()->json([
            'message' => 'Job hunter profiles loaded.',
            'barangay' => $barangay,
            'profiles' => $profiles->map(
                fn (JobHunterProfile $profile): array => (new JobHunterProfileResource($profile))->toArray($request)
            )->values(),
        ]);
    }

    public function store(StoreJobHunterProfileRequest $request): JsonResponse
    {
        $user = $this->authenticatedUserOrNull();
        if ($user === null) {
            return response()->json([
                'message' => 'Unauthenticated.',
            ], 401);
        }

        $barangay = $this->service->resolveBarangayOrNull($user);
        if ($barangay === null) {
            return response()->json([
                'message' => 'Set your barangay in your profile before posting a job hunter profile.',
            ], 422);
        }

        $validated = $request->validated();
        $profile = JobHunterProfile::query()->create([
            'user_id' => $user->id,
            'barangay' => $barangay,
            'full_name' => $this->service->resolveDisplayName($user, $validated['full_name'] ?? null),
            'desired_job' => trim((string) $validated['desired_job']),
            'skills' => trim((string) $validated['skills']),
            'preferred_setup' => trim((string) $validated['preferred_setup']),
            'expected_salary' => trim((string) $validated['expected_salary']),
            'barangay_zone' => trim((string) $validated['barangay_zone']),
            'available_now' => (bool) ($validated['available_now'] ?? true),
        ]);

        return response()->json([
            'message' => 'Job hunter profile published.',
            'profile' => (new JobHunterProfileResource($profile))->toArray($request),
        ], 201);
    }

    private function authenticatedUserOrNull(): ?User
    {
        /** @var User|null $user */
        return Auth::guard('api')->user();
    }
}

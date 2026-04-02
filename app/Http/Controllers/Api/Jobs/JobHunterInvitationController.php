<?php

namespace App\Http\Controllers\Api\Jobs;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\StoreJobHunterInvitationRequest;
use App\Http\Resources\Jobs\JobHunterInvitationResource;
use App\Models\JobHunterInvitation;
use App\Models\JobHunterProfile;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class JobHunterInvitationController extends Controller
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
                'message' => 'Set your barangay in your profile before opening invitations.',
            ], 422);
        }

        $invitations = JobHunterInvitation::query()
            ->inBarangay($barangay)
            ->where(function ($query) use ($user): void {
                $query->where('inviter_user_id', $user->id)
                    ->orWhere('talent_user_id', $user->id);
            })
            ->latest()
            ->limit(300)
            ->get();

        return response()->json([
            'message' => 'Invitations loaded.',
            'invitations' => $invitations->map(
                fn (JobHunterInvitation $invitation): array => (new JobHunterInvitationResource($invitation))->toArray($request)
            )->values(),
        ]);
    }

    public function store(StoreJobHunterInvitationRequest $request): JsonResponse
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
                'message' => 'Set your barangay in your profile before sending invitations.',
            ], 422);
        }

        $validated = $request->validated();
        $talentName = trim((string) $validated['talent_name']);
        $talentMobile = trim((string) ($validated['talent_mobile'] ?? ''));
        $talentDesiredJob = trim((string) ($validated['talent_desired_job'] ?? ''));

        $talentUser = null;
        if ($talentMobile !== '') {
            $talentUser = User::query()
                ->where('mobile', $talentMobile)
                ->first();
        }
        $talentProfile = null;
        if ($talentUser !== null) {
            $talentProfile = JobHunterProfile::query()
                ->where('user_id', $talentUser->id)
                ->latest()
                ->first();
        } else {
            $talentProfile = JobHunterProfile::query()
                ->where('full_name', $talentName)
                ->inBarangay($barangay)
                ->latest()
                ->first();
        }

        $invitation = JobHunterInvitation::query()->create([
            'inviter_user_id' => $user->id,
            'talent_user_id' => $talentUser?->id,
            'talent_profile_id' => $talentProfile?->id,
            'barangay' => $barangay,
            'talent_name' => $talentName,
            'talent_mobile' => $talentMobile !== '' ? $talentMobile : ($talentUser?->mobile ?? null),
            'talent_desired_job' => $talentDesiredJob !== '' ? $talentDesiredJob : ($talentProfile?->desired_job ?? null),
            'inviter_name' => trim((string) ($validated['inviter_name'] ?? $user->name)),
            'inviter_mobile' => trim((string) ($validated['inviter_mobile'] ?? $user->mobile)),
            'message' => trim((string) $validated['message']),
            'status' => 'Pending',
        ]);

        return response()->json([
            'message' => 'Invitation sent successfully.',
            'invitation' => (new JobHunterInvitationResource($invitation))->toArray($request),
        ], 201);
    }

    private function authenticatedUserOrNull(): ?User
    {
        /** @var User|null $user */
        return Auth::guard('api')->user();
    }
}


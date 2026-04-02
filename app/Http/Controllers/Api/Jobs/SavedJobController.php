<?php

namespace App\Http\Controllers\Api\Jobs;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\ToggleSavedJobRequest;
use App\Http\Resources\Jobs\SavedJobResource;
use App\Models\JobHiringPost;
use App\Models\SavedJob;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SavedJobController extends Controller
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
                'message' => 'Set your barangay in your profile before opening saved jobs.',
            ], 422);
        }

        $saved = SavedJob::query()
            ->where('user_id', $user->id)
            ->inBarangay($barangay)
            ->latest()
            ->limit(500)
            ->get();

        return response()->json([
            'message' => 'Saved jobs loaded.',
            'saved_jobs' => $saved->map(
                fn (SavedJob $entry): array => (new SavedJobResource($entry))->toArray($request)
            )->values(),
        ]);
    }

    public function toggle(ToggleSavedJobRequest $request): JsonResponse
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
                'message' => 'Set your barangay in your profile before saving jobs.',
            ], 422);
        }

        $validated = $request->validated();
        $jobId = isset($validated['job_id']) ? (int) $validated['job_id'] : null;
        $jobTitle = trim((string) $validated['job_title']);
        $company = trim((string) $validated['company']);

        $query = SavedJob::query()
            ->where('user_id', $user->id)
            ->inBarangay($barangay);
        if ($jobId !== null && $jobId > 0) {
            $query->where('job_hiring_post_id', $jobId);
        } else {
            $query->where('job_title', $jobTitle)->where('company', $company);
        }
        $existing = $query->first();
        if ($existing !== null) {
            $existing->delete();

            return response()->json([
                'message' => 'Job removed from Saved Jobs.',
                'saved' => false,
            ]);
        }

        $jobPost = null;
        if ($jobId !== null && $jobId > 0) {
            $jobPost = JobHiringPost::query()
                ->whereKey($jobId)
                ->inBarangay($barangay)
                ->first();
        }

        $saved = SavedJob::query()->create([
            'user_id' => $user->id,
            'job_hiring_post_id' => $jobPost?->id,
            'barangay' => $barangay,
            'job_title' => $jobTitle,
            'company' => $company,
        ]);

        return response()->json([
            'message' => 'Job saved for later.',
            'saved' => true,
            'saved_job' => (new SavedJobResource($saved))->toArray($request),
        ], 201);
    }

    private function authenticatedUserOrNull(): ?User
    {
        /** @var User|null $user */
        return Auth::guard('api')->user();
    }
}

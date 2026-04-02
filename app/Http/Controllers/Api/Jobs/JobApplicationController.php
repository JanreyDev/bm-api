<?php

namespace App\Http\Controllers\Api\Jobs;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\StoreJobApplicationRequest;
use App\Http\Resources\Jobs\JobApplicationResource;
use App\Models\JobApplication;
use App\Models\JobHiringPost;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class JobApplicationController extends Controller
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
                'message' => 'Set your barangay in your profile before opening applications.',
            ], 422);
        }

        $ownerName = trim((string) $user->name);
        $applications = JobApplication::query()
            ->inBarangay($barangay)
            ->where(function ($query) use ($user, $ownerName): void {
                $query->where('applicant_user_id', $user->id)
                    ->orWhereHas('jobPost', function ($jobQuery) use ($user): void {
                        $jobQuery->where('user_id', $user->id);
                    });

                if ($ownerName !== '') {
                    $query->orWhere('posted_by', $ownerName);
                }
            })
            ->latest()
            ->limit(500)
            ->get();

        return response()->json([
            'message' => 'Applications loaded.',
            'applications' => $applications->map(
                fn (JobApplication $application): array => (new JobApplicationResource($application))->toArray($request)
            )->values(),
        ]);
    }

    public function store(StoreJobApplicationRequest $request): JsonResponse
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
                'message' => 'Set your barangay in your profile before submitting applications.',
            ], 422);
        }

        $validated = $request->validated();
        $jobId = isset($validated['job_id']) ? (int) $validated['job_id'] : null;
        $jobPost = null;
        if ($jobId !== null && $jobId > 0) {
            $jobPost = JobHiringPost::query()
                ->whereKey($jobId)
                ->inBarangay($barangay)
                ->first();
        }

        $application = JobApplication::query()->create([
            'job_hiring_post_id' => $jobPost?->id,
            'applicant_user_id' => $user->id,
            'barangay' => $barangay,
            'job_title' => trim((string) $validated['job_title']),
            'company' => trim((string) $validated['company']),
            'posted_by' => trim((string) ($validated['posted_by'] ?? '')),
            'applicant_name' => trim((string) $validated['applicant_name']),
            'mobile_number' => trim((string) $validated['mobile_number']),
            'cover_letter' => trim((string) $validated['cover_letter']),
            'attachment_name' => trim((string) ($validated['attachment_name'] ?? '')),
            'status' => 'Submitted',
        ]);

        return response()->json([
            'message' => 'Application submitted.',
            'application' => (new JobApplicationResource($application))->toArray($request),
        ], 201);
    }

    private function authenticatedUserOrNull(): ?User
    {
        /** @var User|null $user */
        return Auth::guard('api')->user();
    }
}

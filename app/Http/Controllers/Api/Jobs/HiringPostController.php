<?php

namespace App\Http\Controllers\Api\Jobs;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\StoreJobHiringPostRequest;
use App\Http\Resources\Jobs\JobHiringPostResource;
use App\Models\JobHiringPost;
use App\Models\User;
use App\Services\Jobs\HiringPostService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class HiringPostController extends Controller
{
    public function __construct(
        private readonly HiringPostService $service
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
                'message' => 'Set your barangay in your profile before opening jobs.',
            ], 422);
        }

        $jobs = $this->service->listForViewer($user);

        return response()->json([
            'message' => 'Hiring posts loaded.',
            'barangay' => $barangay,
            'jobs' => $jobs->map(
                fn (JobHiringPost $post): array => (new JobHiringPostResource($post))->toArray($request)
            )->values(),
        ]);
    }

    public function store(StoreJobHiringPostRequest $request): JsonResponse
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
                'message' => 'Set your barangay in your profile before posting jobs.',
            ], 422);
        }

        $validated = $request->validated();
        $post = JobHiringPost::query()->create([
            'user_id' => $user->id,
            'barangay' => $barangay,
            'title' => trim((string) $validated['title']),
            'company' => trim((string) $validated['company']),
            'location' => trim((string) $validated['location']),
            'salary' => trim((string) $validated['salary']),
            'schedule' => trim((string) $validated['schedule']),
            'requirements' => trim((string) $validated['requirements']),
            'posted_by' => $this->service->resolvePostedBy($user, $validated['posted_by'] ?? null),
            'urgent' => (bool) ($validated['urgent'] ?? false),
        ]);

        return response()->json([
            'message' => 'Hiring post published.',
            'job' => (new JobHiringPostResource($post))->toArray($request),
        ], 201);
    }

    private function authenticatedUserOrNull(): ?User
    {
        /** @var User|null $user */
        return Auth::guard('api')->user();
    }
}

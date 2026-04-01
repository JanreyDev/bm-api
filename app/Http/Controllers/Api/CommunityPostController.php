<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\StoreCommunityPostRequest;
use App\Http\Requests\Api\UpdateCommunityPostRequest;
use App\Http\Resources\CommunityPostResource;
use App\Models\CommunityPost;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CommunityPostController extends Controller
{
    /**
     * Roles that can create community posts.
     *
     * @var list<string>
     */
    private const POSTABLE_ROLES = ['resident', 'official'];

    public function index(Request $request): JsonResponse
    {
        $user = $this->authenticatedUserOrNull();
        if ($user === null) {
            return response()->json([
                'message' => 'Unauthenticated.',
            ], 401);
        }
        $barangay = $this->resolveBarangayOrNull($user);
        if ($barangay === null) {
            return response()->json([
                'message' => 'Set your barangay in your profile before opening the community feed.',
            ], 422);
        }

        $posts = CommunityPost::query()
            ->inBarangay($barangay)
            ->latest()
            ->limit(120)
            ->get();

        return response()->json([
            'message' => 'Community posts loaded.',
            'barangay' => $barangay,
            'posts' => $posts->map(
                fn (CommunityPost $post): array => (new CommunityPostResource($post, $user))->toArray($request)
            )->values(),
        ]);
    }

    public function store(StoreCommunityPostRequest $request): JsonResponse
    {
        $user = $this->authenticatedUserOrNull();
        if ($user === null) {
            return response()->json([
                'message' => 'Unauthenticated.',
            ], 401);
        }
        $validated = $request->validated();

        if (!in_array($user->role, self::POSTABLE_ROLES, true)) {
            return response()->json([
                'message' => 'Only resident and official accounts can create community posts.',
            ], 403);
        }

        $barangay = $this->resolveBarangayOrNull($user);
        if ($barangay === null) {
            return response()->json([
                'message' => 'Set your barangay in your profile before publishing posts.',
            ], 422);
        }

        $isOfficial = $user->role === 'official';
        $authorName = $this->resolveAuthorName($user, $barangay, $isOfficial);

        $post = CommunityPost::query()->create([
            'user_id' => $user->id,
            'barangay' => $barangay,
            'author_name' => $authorName,
            'message' => trim((string) $validated['message']),
            'image_base64' => $this->normalizeImagePayload($validated['image_base64'] ?? null),
            'is_official' => $isOfficial,
        ]);

        return response()->json([
            'message' => 'Post published.',
            'post' => (new CommunityPostResource($post, $user))->toArray($request),
        ], 201);
    }

    public function update(UpdateCommunityPostRequest $request, int $postId): JsonResponse
    {
        $user = $this->authenticatedUserOrNull();
        if ($user === null) {
            return response()->json([
                'message' => 'Unauthenticated.',
            ], 401);
        }
        $validated = $request->validated();

        $post = $this->findPostInViewerBarangay($postId, $user);
        if ($post === null) {
            return response()->json([
                'message' => 'Post not found.',
            ], 404);
        }

        if (!$post->canManageBy($user)) {
            return response()->json([
                'message' => 'You can only edit your own posts.',
            ], 403);
        }

        $post->forceFill([
            'message' => trim((string) $validated['message']),
            'image_base64' => $this->normalizeImagePayload($validated['image_base64'] ?? null),
        ])->save();

        return response()->json([
            'message' => 'Post updated.',
            'post' => (new CommunityPostResource($post->fresh() ?? $post, $user))->toArray($request),
        ]);
    }

    public function destroy(Request $request, int $postId): JsonResponse
    {
        $user = $this->authenticatedUserOrNull();
        if ($user === null) {
            return response()->json([
                'message' => 'Unauthenticated.',
            ], 401);
        }
        $post = $this->findPostInViewerBarangay($postId, $user);
        if ($post === null) {
            return response()->json([
                'message' => 'Post not found.',
            ], 404);
        }

        if (!$post->canManageBy($user)) {
            return response()->json([
                'message' => 'You can only delete your own posts.',
            ], 403);
        }

        $post->delete();

        return response()->json([
            'message' => 'Post deleted.',
        ]);
    }

    private function authenticatedUserOrNull(): ?User
    {
        /** @var User|null $user */
        return Auth::guard('api')->user();
    }

    private function resolveBarangayOrNull(User $user): ?string
    {
        $barangay = trim((string) $user->barangay);

        return $barangay !== '' ? $barangay : null;
    }

    private function resolveAuthorName(User $user, string $barangay, bool $isOfficial): string
    {
        if ($isOfficial) {
            return 'Barangay '.$barangay;
        }

        $author = trim((string) $user->name);

        return $author !== '' ? $author : 'Verified Resident';
    }

    private function findPostInViewerBarangay(int $postId, User $viewer): ?CommunityPost
    {
        $barangay = $this->resolveBarangayOrNull($viewer);
        if ($barangay === null) {
            return null;
        }

        return CommunityPost::query()
            ->where('id', $postId)
            ->inBarangay($barangay)
            ->first();
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

        // Accept either plain base64 or data-url style payload.
        if (preg_match('/^data:image\/[a-zA-Z0-9.+-]+;base64,/', $trimmed) === 1) {
            return $trimmed;
        }

        return preg_match('/^[A-Za-z0-9+\/=\r\n]+$/', $trimmed) === 1 ? $trimmed : null;
    }
}

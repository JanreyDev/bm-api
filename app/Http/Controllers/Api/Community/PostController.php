<?php

namespace App\Http\Controllers\Api\Community;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\StoreCommunityPostRequest;
use App\Http\Requests\Api\UpdateCommunityPostRequest;
use App\Http\Resources\CommunityPostResource;
use App\Models\CommunityPost;
use App\Models\CommunityPostLike;
use App\Models\User;
use App\Services\Community\CommunityPostService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PostController extends Controller
{
    /**
     * Roles that can create community posts.
     *
     * @var list<string>
     */
    private const POSTABLE_ROLES = ['resident', 'official'];

    public function __construct(
        private readonly CommunityPostService $postService
    ) {}

    public function index(Request $request): JsonResponse
    {
        $user = $this->authenticatedUserOrNull();
        if ($user === null) {
            return response()->json([
                'message' => 'Unauthenticated.',
            ], 401);
        }

        $barangay = $this->postService->resolveBarangayOrNull($user);
        if ($barangay === null) {
            $fallbackBarangay = trim((string) $request->query('barangay', ''));
            if ($fallbackBarangay !== '') {
                $barangay = $fallbackBarangay;
            }
        }
        if ($barangay === null) {
            return response()->json([
                'message' => 'Set your barangay in your profile before opening the community feed.',
            ], 422);
        }

        $posts = $this->postService->listForViewer($user);

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

        if (!in_array($user->role, self::POSTABLE_ROLES, true)) {
            return response()->json([
                'message' => 'Only resident and official accounts can create community posts.',
            ], 403);
        }

        $barangay = $this->postService->resolveBarangayOrNull($user);
        if ($barangay === null) {
            $fallbackBarangay = trim((string) $request->input('barangay', ''));
            if ($fallbackBarangay === '') {
                $fallbackBarangay = trim((string) $request->query('barangay', ''));
            }
            if ($fallbackBarangay !== '') {
                $barangay = $fallbackBarangay;
                if (trim((string) $user->barangay) === '') {
                    $user->forceFill(['barangay' => $fallbackBarangay])->save();
                }
            }
        }
        if ($barangay === null) {
            return response()->json([
                'message' => 'Set your barangay in your profile before publishing posts.',
            ], 422);
        }

        $validated = $request->validated();
        $isOfficial = $user->role === 'official';
        $authorName = $this->postService->resolveAuthorName($user, $barangay, $isOfficial);

        $post = CommunityPost::query()->create([
            'user_id' => $user->id,
            'barangay' => $barangay,
            'author_name' => $authorName,
            'message' => trim((string) $validated['message']),
            'image_base64' => $this->postService->normalizeImagePayload($validated['image_base64'] ?? null),
            'is_official' => $isOfficial,
        ]);

        return response()->json([
            'message' => 'Post published.',
            'post' => (new CommunityPostResource($post, $user))->toArray($request),
        ], 201);
    }

    public function show(Request $request, int $postId): JsonResponse
    {
        $user = $this->authenticatedUserOrNull();
        if ($user === null) {
            return response()->json([
                'message' => 'Unauthenticated.',
            ], 401);
        }

        $post = $this->postService->findForViewer($postId, $user, withComments: true);
        if ($post === null) {
            return response()->json([
                'message' => 'Post not found.',
            ], 404);
        }

        return response()->json([
            'message' => 'Community post loaded.',
            'post' => (new CommunityPostResource($post, $user))->toArray($request),
        ]);
    }

    public function update(UpdateCommunityPostRequest $request, int $postId): JsonResponse
    {
        $user = $this->authenticatedUserOrNull();
        if ($user === null) {
            return response()->json([
                'message' => 'Unauthenticated.',
            ], 401);
        }

        $post = $this->postService->findForViewer($postId, $user);
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

        $validated = $request->validated();
        $post->forceFill([
            'message' => trim((string) $validated['message']),
            'image_base64' => $this->postService->normalizeImagePayload($validated['image_base64'] ?? null),
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

        $post = $this->postService->findForViewer($postId, $user);
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

    public function toggleLike(Request $request, int $postId): JsonResponse
    {
        $user = $this->authenticatedUserOrNull();
        if ($user === null) {
            return response()->json([
                'message' => 'Unauthenticated.',
            ], 401);
        }

        $post = $this->postService->findForViewer($postId, $user);
        if ($post === null) {
            return response()->json([
                'message' => 'Post not found.',
            ], 404);
        }

        $existing = CommunityPostLike::query()
            ->where('community_post_id', $post->id)
            ->where('user_id', $user->id)
            ->first();

        if ($existing !== null) {
            $existing->delete();
            $message = 'Like removed.';
        } else {
            CommunityPostLike::query()->create([
                'community_post_id' => $post->id,
                'user_id' => $user->id,
            ]);
            $message = 'Post liked.';
        }

        $fresh = $this->postService->findForViewer($post->id, $user, withComments: false);

        return response()->json([
            'message' => $message,
            'post' => $fresh === null ? null : (new CommunityPostResource($fresh, $user))->toArray($request),
        ]);
    }

    private function authenticatedUserOrNull(): ?User
    {
        /** @var User|null $user */
        return Auth::guard('api')->user();
    }
}

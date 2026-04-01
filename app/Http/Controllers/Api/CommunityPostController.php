<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CommunityPost;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CommunityPostController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        /** @var User|null $user */
        $user = Auth::guard('api')->user();
        if ($user === null) {
            return response()->json([
                'message' => 'Unauthenticated.',
            ], 401);
        }

        $barangay = trim((string) $user->barangay);
        if ($barangay === '') {
            return response()->json([
                'message' => 'Set your barangay in your profile before opening the community feed.',
            ], 422);
        }

        $posts = CommunityPost::query()
            ->where('barangay', $barangay)
            ->latest()
            ->limit(120)
            ->get();

        return response()->json([
            'message' => 'Community posts loaded.',
            'barangay' => $barangay,
            'posts' => $posts->map(fn (CommunityPost $post): array => $this->formatPost($post, $user))->values(),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        /** @var User|null $user */
        $user = Auth::guard('api')->user();
        if ($user === null) {
            return response()->json([
                'message' => 'Unauthenticated.',
            ], 401);
        }

        $validated = $request->validate([
            'message' => ['required', 'string', 'min:5', 'max:2000'],
            'image_base64' => ['nullable', 'string', 'max:5000000'],
        ]);

        if (!in_array($user->role, ['resident', 'official'], true)) {
            return response()->json([
                'message' => 'Only resident and official accounts can create community posts.',
            ], 403);
        }

        $barangay = trim((string) $user->barangay);
        if ($barangay === '') {
            return response()->json([
                'message' => 'Set your barangay in your profile before publishing posts.',
            ], 422);
        }

        $isOfficial = $user->role === 'official';
        $authorName = $isOfficial ? 'Barangay '.$barangay : trim((string) $user->name);
        if ($authorName === '') {
            $authorName = $isOfficial ? 'Barangay '.$barangay : 'Verified Resident';
        }

        $post = CommunityPost::query()->create([
            'user_id' => $user->id,
            'barangay' => $barangay,
            'author_name' => $authorName,
            'message' => trim((string) $validated['message']),
            'image_base64' => $this->sanitizeBase64Image($validated['image_base64'] ?? null),
            'is_official' => $isOfficial,
        ]);

        return response()->json([
            'message' => 'Post published.',
            'post' => $this->formatPost($post, $user),
        ], 201);
    }

    public function update(Request $request, int $postId): JsonResponse
    {
        /** @var User|null $user */
        $user = Auth::guard('api')->user();
        if ($user === null) {
            return response()->json([
                'message' => 'Unauthenticated.',
            ], 401);
        }

        $validated = $request->validate([
            'message' => ['required', 'string', 'min:5', 'max:2000'],
            'image_base64' => ['nullable', 'string', 'max:5000000'],
        ]);

        $post = CommunityPost::query()
            ->where('id', $postId)
            ->where('barangay', trim((string) $user->barangay))
            ->first();

        if ($post === null) {
            return response()->json([
                'message' => 'Post not found.',
            ], 404);
        }

        if ((int) $post->user_id !== (int) $user->id) {
            return response()->json([
                'message' => 'You can only edit your own posts.',
            ], 403);
        }

        $post->forceFill([
            'message' => trim((string) $validated['message']),
            'image_base64' => $this->sanitizeBase64Image($validated['image_base64'] ?? null),
        ])->save();

        return response()->json([
            'message' => 'Post updated.',
            'post' => $this->formatPost($post->fresh() ?? $post, $user),
        ]);
    }

    public function destroy(Request $request, int $postId): JsonResponse
    {
        /** @var User|null $user */
        $user = Auth::guard('api')->user();
        if ($user === null) {
            return response()->json([
                'message' => 'Unauthenticated.',
            ], 401);
        }

        $post = CommunityPost::query()
            ->where('id', $postId)
            ->where('barangay', trim((string) $user->barangay))
            ->first();

        if ($post === null) {
            return response()->json([
                'message' => 'Post not found.',
            ], 404);
        }

        if ((int) $post->user_id !== (int) $user->id) {
            return response()->json([
                'message' => 'You can only delete your own posts.',
            ], 403);
        }

        $post->delete();

        return response()->json([
            'message' => 'Post deleted.',
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function formatPost(CommunityPost $post, User $viewer): array
    {
        return [
            'id' => $post->id,
            'user_id' => $post->user_id,
            'author' => $post->author_name,
            'message' => $post->message,
            'image_base64' => $post->image_base64,
            'barangay' => $post->barangay,
            'is_official' => (bool) $post->is_official,
            'is_verified_resident' => !(bool) $post->is_official,
            'can_manage' => (int) $post->user_id === (int) $viewer->id,
            'posted_at' => optional($post->created_at)?->toIso8601String(),
        ];
    }

    private function sanitizeBase64Image(mixed $value): ?string
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

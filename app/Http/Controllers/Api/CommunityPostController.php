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
            'posts' => $posts->map(fn (CommunityPost $post): array => $this->formatPost($post))->values(),
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
            'is_official' => $isOfficial,
        ]);

        return response()->json([
            'message' => 'Post published.',
            'post' => $this->formatPost($post),
        ], 201);
    }

    /**
     * @return array<string, mixed>
     */
    private function formatPost(CommunityPost $post): array
    {
        return [
            'id' => $post->id,
            'author' => $post->author_name,
            'message' => $post->message,
            'barangay' => $post->barangay,
            'is_official' => (bool) $post->is_official,
            'is_verified_resident' => !(bool) $post->is_official,
            'posted_at' => optional($post->created_at)?->toIso8601String(),
        ];
    }
}

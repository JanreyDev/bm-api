<?php

namespace App\Http\Controllers\Api\Community;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\StoreCommunityPostCommentRequest;
use App\Http\Resources\CommunityPostResource;
use App\Models\CommunityPostComment;
use App\Models\User;
use App\Services\Community\CommunityPostService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class CommentController extends Controller
{
    public function __construct(
        private readonly CommunityPostService $postService
    ) {}

    public function store(StoreCommunityPostCommentRequest $request, int $postId): JsonResponse
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

        $authorName = trim((string) $user->name);
        if ($authorName === '') {
            $authorName = 'Resident';
        }

        CommunityPostComment::query()->create([
            'community_post_id' => $post->id,
            'user_id' => $user->id,
            'author_name' => $authorName,
            'message' => trim((string) $request->validated()['message']),
        ]);

        $fresh = $this->postService->findForViewer($post->id, $user, withComments: true);

        return response()->json([
            'message' => 'Comment added.',
            'post' => $fresh === null
                ? null
                : (new CommunityPostResource($fresh, $user))->toArray($request),
        ], 201);
    }

    private function authenticatedUserOrNull(): ?User
    {
        /** @var User|null $user */
        return Auth::guard('api')->user();
    }
}

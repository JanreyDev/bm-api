<?php

namespace App\Http\Controllers\Api\Community;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\StoreCommunityChatMessageRequest;
use App\Http\Resources\Community\CommunityChatMessageResource;
use App\Models\CommunityChatMessage;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CommunityChatMessageController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $user = $this->authenticatedUserOrNull();
        if ($user === null) {
            return response()->json([
                'message' => 'Unauthenticated.',
            ], 401);
        }

        $barangay = trim((string) $request->query('barangay', $user->barangay));
        if ($barangay === '') {
            return response()->json([
                'message' => 'Set your barangay in your profile before opening community chat.',
            ], 422);
        }

        $channel = trim((string) $request->query('channel', ''));

        $query = CommunityChatMessage::query()
            ->inBarangay($barangay)
            ->latest()
            ->limit(500);

        if ($channel !== '') {
            $query->where('channel', $channel);
        }

        $messages = $query
            ->get()
            ->reverse()
            ->values();

        return response()->json([
            'message' => 'Community chat messages loaded.',
            'messages' => $messages->map(
                fn (CommunityChatMessage $entry): array => (new CommunityChatMessageResource($entry))->toArray($request)
            )->values(),
        ]);
    }

    public function store(StoreCommunityChatMessageRequest $request): JsonResponse
    {
        $user = $this->authenticatedUserOrNull();
        if ($user === null) {
            return response()->json([
                'message' => 'Unauthenticated.',
            ], 401);
        }

        $validated = $request->validated();
        $barangay = trim((string) ($validated['barangay'] ?? $user->barangay));
        if ($barangay === '') {
            return response()->json([
                'message' => 'Set your barangay in your profile before sending a message.',
            ], 422);
        }

        $entry = CommunityChatMessage::query()->create([
            'user_id' => $user->id,
            'sender_name' => trim((string) $user->name) !== ''
                ? trim((string) $user->name)
                : 'Resident',
            'barangay' => $barangay,
            'channel' => trim((string) ($validated['channel'] ?? 'General')),
            'message' => trim((string) ($validated['message'] ?? '')),
            'is_official' => trim((string) $user->role) === 'official',
        ]);

        return response()->json([
            'message' => 'Message sent.',
            'chat_message' => (new CommunityChatMessageResource($entry))->toArray($request),
        ], 201);
    }

    private function authenticatedUserOrNull(): ?User
    {
        /** @var User|null $user */
        return Auth::guard('api')->user();
    }
}

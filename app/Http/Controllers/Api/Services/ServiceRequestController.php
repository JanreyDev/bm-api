<?php

namespace App\Http\Controllers\Api\Services;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\StoreServiceRequestRequest;
use App\Http\Resources\Services\ServiceRequestResource;
use App\Models\ServiceRequest;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ServiceRequestController extends Controller
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
                'message' => 'Set your barangay in your profile before opening requests.',
            ], 422);
        }

        $requestsQuery = ServiceRequest::query()
            ->inBarangay($barangay)
            ->latest()
            ->limit(500);

        if ($user->role !== 'official') {
            $requestsQuery->where('user_id', $user->id);
        }

        $requests = $requestsQuery->get();

        return response()->json([
            'message' => 'Service requests loaded.',
            'requests' => $requests->map(
                fn (ServiceRequest $entry): array => (new ServiceRequestResource($entry))->toArray($request)
            )->values(),
        ]);
    }

    public function store(StoreServiceRequestRequest $request): JsonResponse
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
                'message' => 'Set your barangay in your profile before submitting requests.',
            ], 422);
        }

        $validated = $request->validated();
        $attachments = $this->normalizeAttachments($validated['attachments'] ?? null);
        $entry = ServiceRequest::query()->create([
            'user_id' => $user->id,
            'barangay' => $barangay,
            'service_category' => trim((string) $validated['service_category']),
            'service_title' => trim((string) $validated['service_title']),
            'request_id' => $this->makeRequestId((string) $validated['service_category']),
            'purpose' => trim((string) $validated['purpose']),
            'details' => trim((string) ($validated['details'] ?? '')),
            'attachments_json' => $attachments,
            'status' => 'Pending',
        ]);

        return response()->json([
            'message' => 'Service request submitted.',
            'request' => (new ServiceRequestResource($entry))->toArray($request),
        ], 201);
    }

    private function makeRequestId(string $category): string
    {
        $prefix = strtoupper(substr(preg_replace('/[^A-Za-z]/', '', $category) ?: 'SR', 0, 2));
        if ($prefix === '') {
            $prefix = 'SR';
        }
        $date = now()->format('ymd');
        $random = random_int(100, 999);
        return sprintf('%s-%s-%03d', $prefix, $date, $random);
    }

    /**
     * @param mixed $raw
     * @return array<int, array<string, string>>
     */
    private function normalizeAttachments(mixed $raw): array
    {
        if (!is_array($raw)) {
            return [];
        }
        $out = [];
        foreach ($raw as $item) {
            if (!is_array($item)) {
                continue;
            }
            $fileName = trim((string) ($item['file_name'] ?? ''));
            $payload = trim((string) ($item['image_base64'] ?? ''));
            if ($fileName === '' || $payload === '') {
                continue;
            }
            if (preg_match('/^data:image\/[a-zA-Z0-9.+-]+;base64,/', $payload) !== 1 &&
                base64_decode($payload, true) === false) {
                continue;
            }
            $out[] = [
                'file_name' => $fileName,
                'image_base64' => $payload,
            ];
        }
        return $out;
    }

    private function authenticatedUserOrNull(): ?User
    {
        /** @var User|null $user */
        return Auth::guard('api')->user();
    }
}

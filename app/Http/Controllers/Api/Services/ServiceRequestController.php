<?php

namespace App\Http\Controllers\Api\Services;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\StoreServiceRequestRequest;
use App\Http\Requests\Api\UpdateServiceRequestStatusRequest;
use App\Http\Resources\Services\ServiceRequestResource;
use App\Models\OfficialBarangaySetup;
use App\Models\ServiceRequest;
use App\Models\User;
use App\Services\Official\OfficialNotificationPublisher;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ServiceRequestController extends Controller
{
    public function __construct(
        private readonly OfficialNotificationPublisher $notificationPublisher
    ) {}

    public function index(Request $request): JsonResponse
    {
        $user = $this->authenticatedUserOrNull();
        if ($user === null) {
            return response()->json([
                'message' => 'Unauthenticated.',
            ], 401);
        }

        $barangay = $this->resolveBarangay($user);
        if ($barangay === '') {
            return response()->json([
                'message' => 'Set your barangay in your profile before opening requests.',
            ], 422);
        }

        $requestsQuery = ServiceRequest::query()
            ->inBarangay($barangay)
            ->with('user:id,name,mobile')
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

        $barangay = $this->resolveBarangay($user);
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

        $residentName = trim((string) $user->name);
        if ($residentName === '') {
            $residentName = 'A resident';
        }
        $this->notificationPublisher->publishForOfficialScope(
            sourceUser: $user,
            title: 'New service request submitted',
            body: sprintf(
                '%s submitted %s (%s).',
                $residentName,
                trim((string) $entry->service_title),
                trim((string) $entry->request_id),
            ),
            category: 'Services',
            priority: 'high',
            recordType: 'service_request',
            recordId: (string) $entry->id,
            deepLink: 'barangaymo://official/services/requests',
            metadata: [
                'service_category' => trim((string) $entry->service_category),
                'request_id' => trim((string) $entry->request_id),
                'barangay' => $barangay,
            ],
        );

        return response()->json([
            'message' => 'Service request submitted.',
            'request' => (new ServiceRequestResource($entry))->toArray($request),
        ], 201);
    }

    public function updateStatus(
        UpdateServiceRequestStatusRequest $request,
        int $serviceRequestId
    ): JsonResponse {
        $user = $this->authenticatedUserOrNull();
        if ($user === null) {
            return response()->json([
                'message' => 'Unauthenticated.',
            ], 401);
        }

        if ($user->role !== 'official') {
            return response()->json([
                'message' => 'Only official accounts can update service request status.',
            ], 403);
        }

        $barangay = $this->resolveBarangay($user);
        if ($barangay === '') {
            return response()->json([
                'message' => 'Set your barangay in your profile before updating requests.',
            ], 422);
        }

        $entry = ServiceRequest::query()
            ->inBarangay($barangay)
            ->whereKey($serviceRequestId)
            ->with('user:id,name,mobile')
            ->first();

        if ($entry === null) {
            return response()->json([
                'message' => 'Service request not found.',
            ], 404);
        }

        $validated = $request->validated();
        $entry->forceFill([
            'status' => trim((string) $validated['status']),
        ])->save();

        return response()->json([
            'message' => 'Service request status updated.',
            'request' => (new ServiceRequestResource($entry->fresh('user') ?? $entry))->toArray($request),
        ]);
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

    private function resolveBarangay(User $user): string
    {
        $barangay = trim((string) $user->barangay);
        if ($barangay !== '') {
            return $barangay;
        }
        if (trim((string) $user->role) === 'official') {
            $setup = OfficialBarangaySetup::query()
                ->where('updated_by_user_id', $user->id)
                ->latest('id')
                ->first();
            if ($setup !== null) {
                $fallback = trim((string) $setup->barangay);
                if ($fallback !== '') {
                    $user->forceFill(['barangay' => mb_substr($fallback, 0, 100)])->save();
                    return $fallback;
                }
            }
        }
        return '';
    }
}

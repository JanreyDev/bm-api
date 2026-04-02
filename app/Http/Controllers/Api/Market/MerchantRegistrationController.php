<?php

namespace App\Http\Controllers\Api\Market;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\StoreMerchantRegistrationRequest;
use App\Http\Resources\Market\MerchantRegistrationResource;
use App\Models\MerchantRegistration;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class MerchantRegistrationController extends Controller
{
    public function show(Request $request): JsonResponse
    {
        $user = $this->authenticatedUserOrNull();
        if ($user === null) {
            return response()->json([
                'message' => 'Unauthenticated.',
            ], 401);
        }

        $registration = MerchantRegistration::query()
            ->where('user_id', $user->id)
            ->latest()
            ->first();

        if ($registration === null) {
            return response()->json([
                'message' => 'No merchant registration yet.',
                'registration' => null,
            ]);
        }

        return response()->json([
            'message' => 'Merchant registration loaded.',
            'registration' => (new MerchantRegistrationResource($registration))->toArray($request),
        ]);
    }

    public function store(StoreMerchantRegistrationRequest $request): JsonResponse
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
                'message' => 'Set your barangay in your profile before merchant registration.',
            ], 422);
        }

        $validated = $request->validated();
        $permitNumber = trim((string) $validated['business_permit_number']);
        $verified = str_starts_with(strtoupper($permitNumber), 'BP-') || mb_strlen($permitNumber) >= 10;
        $verificationStatus = $verified ? 'Verified' : 'Pending Review';

        $registration = MerchantRegistration::query()->updateOrCreate(
            ['user_id' => $user->id],
            [
                'barangay' => $barangay,
                'business_name' => trim((string) $validated['business_name']),
                'owner_name' => trim((string) $validated['owner_name']),
                'business_type' => trim((string) $validated['business_type']),
                'contact_number' => trim((string) $validated['contact_number']),
                'address' => trim((string) $validated['address']),
                'meetup_spot' => trim((string) $validated['meetup_spot']),
                'business_permit_number' => $permitNumber,
                'business_permit_file_name' => trim((string) $validated['business_permit_file_name']),
                'business_permit_image_base64' => $this->normalizeImagePayload(
                    $validated['business_permit_image_base64'] ?? null
                ),
                'merchant_verified' => $verified,
                'verification_status' => $verificationStatus,
            ]
        );

        return response()->json([
            'message' => $verified
                ? 'Merchant registration submitted and verified.'
                : 'Merchant registration submitted for review.',
            'registration' => (new MerchantRegistrationResource($registration))->toArray($request),
        ], 201);
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
        if (preg_match('/^data:image\/[a-zA-Z0-9.+-]+;base64,/', $trimmed) === 1) {
            return $trimmed;
        }

        return preg_match('/^[A-Za-z0-9+\/=\r\n]+$/', $trimmed) === 1 ? $trimmed : null;
    }

    private function authenticatedUserOrNull(): ?User
    {
        /** @var User|null $user */
        return Auth::guard('api')->user();
    }
}

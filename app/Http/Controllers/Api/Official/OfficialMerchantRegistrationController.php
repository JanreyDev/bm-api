<?php

namespace App\Http\Controllers\Api\Official;

use App\Http\Controllers\Controller;
use App\Models\MerchantRegistration;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class OfficialMerchantRegistrationController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $user = $this->authenticatedUserOrNull();
        if ($user === null || $user->role !== 'official') {
            return response()->json(['message' => 'Unauthorized.'], 403);
        }

        $barangay = trim((string) $user->barangay);
        if ($barangay === '') {
            return response()->json(['message' => 'Barangay not set.'], 422);
        }

        $registrations = MerchantRegistration::query()
            ->with('user:id,name,mobile')
            ->where('barangay', $barangay)
            ->orderBy('merchant_verified', 'asc')
            ->latest()
            ->get();

        return response()->json([
            'message' => 'Merchant registrations loaded.',
            'registrations' => $registrations,
        ]);
    }

    public function verify(Request $request, int $id): JsonResponse
    {
        $user = $this->authenticatedUserOrNull();
        if ($user === null || $user->role !== 'official') {
            return response()->json(['message' => 'Unauthorized.'], 403);
        }

        $registration = MerchantRegistration::query()->find($id);
        if ($registration === null) {
            return response()->json(['message' => 'Registration not found.'], 404);
        }

        if (trim($registration->barangay) !== trim((string) $user->barangay)) {
            return response()->json(['message' => 'Unauthorized for this barangay.'], 403);
        }

        $verified = (bool) $request->input('verified', true);
        $registration->update([
            'merchant_verified' => $verified,
            'verification_status' => $verified ? 'Verified' : 'Rejected',
        ]);

        return response()->json([
            'message' => $verified ? 'Merchant verified.' : 'Merchant status updated.',
            'registration' => $registration,
        ]);
    }

    private function authenticatedUserOrNull(): ?User
    {
        /** @var User|null $user */
        $user = Auth::guard('api')->user();
        return $user;
    }
}

<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Throwable;

class AuthController extends Controller
{
    public function register(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'string', 'email:rfc,dns', 'max:255', 'unique:users,email'],
            'mobile' => ['required', 'string', 'regex:/^\d{10,15}$/'],
            'role' => ['required', 'in:resident,official'],
            'password' => ['required', 'digits:6', 'confirmed'],
            'middle_name' => ['nullable', 'string', 'max:255'],
            'suffix' => ['nullable', 'string', 'max:50'],
            'religion' => ['nullable', 'string', 'max:100'],
            'province' => ['nullable', 'string', 'max:100'],
            'city_municipality' => ['nullable', 'string', 'max:100'],
            'barangay' => ['nullable', 'string', 'max:100'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => $validator->errors()->first(),
                'errors' => $validator->errors(),
            ], 422);
        }

        $mobile = $this->normalizeMobile((string) $request->string('mobile'));
        $role = (string) $request->string('role');
        $existing = User::query()
            ->where('mobile', $mobile)
            ->where('role', $role)
            ->first();
        if ($existing !== null) {
            return response()->json([
                'message' => 'An account with this mobile already exists for this role.',
            ], 422);
        }

        $otp = $this->generateOtp();
        $user = User::query()->create([
            'name' => trim((string) $request->string('name')),
            'email' => $this->resolveRegistrationEmail($request, $mobile, $role),
            'password' => Hash::make((string) $request->string('password')),
            'mobile' => $mobile,
            'role' => $role,
            'middle_name' => $request->filled('middle_name') ? trim((string) $request->string('middle_name')) : null,
            'suffix' => $request->filled('suffix') ? trim((string) $request->string('suffix')) : null,
            'religion' => $request->filled('religion') ? trim((string) $request->string('religion')) : null,
            'province' => $request->filled('province') ? trim((string) $request->string('province')) : null,
            'city_municipality' => $request->filled('city_municipality') ? trim((string) $request->string('city_municipality')) : null,
            'barangay' => $request->filled('barangay') ? trim((string) $request->string('barangay')) : null,
            'activation_completed' => $role === 'resident',
            'otp_code' => $otp,
            'otp_expires_at' => now()->addMinutes(10),
            'otp_verified_at' => null,
        ]);

        if (!$this->sendOtp($user, $otp)) {
            return response()->json([
                'message' => 'Account created, but OTP SMS could not be sent. Please tap Resend OTP.',
                'otp_required' => true,
                'otp_debug_code' => $this->debugOtpCode($otp),
                'user' => $this->formatUser($user),
            ], 503);
        }

        return response()->json([
            'message' => 'Account created successfully. OTP has been sent.',
            'otp_required' => true,
            'otp_debug_code' => $this->debugOtpCode($otp),
            'user' => $this->formatUser($user),
        ], 201);
    }

    public function login(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'mobile' => ['required', 'string', 'regex:/^\d{10,15}$/'],
            'role' => ['required', 'in:resident,official'],
            'password' => ['required', 'digits:6'],
        ]);
        if ($validator->fails()) {
            return response()->json([
                'message' => $validator->errors()->first(),
                'errors' => $validator->errors(),
            ], 422);
        }

        $mobile = $this->normalizeMobile((string) $request->string('mobile'));
        $role = (string) $request->string('role');
        $user = User::query()
            ->where('mobile', $mobile)
            ->where('role', $role)
            ->first();
        if ($user === null || !Hash::check((string) $request->string('password'), $user->password)) {
            return response()->json([
                'message' => 'Invalid credentials.',
            ], 401);
        }

        if ($user->otp_verified_at === null) {
            $otp = $this->generateOtp();
            $user->forceFill([
                'otp_code' => $otp,
                'otp_expires_at' => now()->addMinutes(10),
            ])->save();

            if (!$this->sendOtp($user, $otp)) {
                return response()->json([
                    'message' => 'OTP SMS could not be sent right now. Please tap Resend OTP.',
                    'otp_required' => true,
                    'otp_debug_code' => $this->debugOtpCode($otp),
                    'user' => $this->formatUser($user),
                ], 503);
            }

            return response()->json([
                'message' => 'Verify your OTP before logging in.',
                'otp_required' => true,
                'otp_debug_code' => $this->debugOtpCode($otp),
                'user' => $this->formatUser($user),
            ], 403);
        }

        $token = $this->issueToken($user);

        return response()->json([
            'message' => 'Login successful.',
            'token' => $token,
            'user' => $this->formatUser($user),
        ]);
    }

    public function verifyOtp(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'mobile' => ['required', 'string', 'regex:/^\d{10,15}$/'],
            'role' => ['required', 'in:resident,official'],
            'otp' => ['required', 'digits:6'],
        ]);
        if ($validator->fails()) {
            return response()->json([
                'message' => $validator->errors()->first(),
                'errors' => $validator->errors(),
            ], 422);
        }

        $mobile = $this->normalizeMobile((string) $request->string('mobile'));
        $role = (string) $request->string('role');
        $otp = trim((string) $request->string('otp'));
        $user = User::query()
            ->where('mobile', $mobile)
            ->where('role', $role)
            ->first();

        if ($user === null || $user->otp_code === null || !hash_equals((string) $user->otp_code, $otp)) {
            return response()->json([
                'message' => 'OTP verification failed.',
                'otp_required' => true,
            ], 422);
        }

        if ($user->otp_expires_at !== null && now()->greaterThan($user->otp_expires_at)) {
            return response()->json([
                'message' => 'OTP has expired. Please request a new code.',
                'otp_required' => true,
            ], 422);
        }

        $token = $this->issueToken($user);
        $user->forceFill([
            'otp_verified_at' => now(),
            'otp_code' => null,
            'otp_expires_at' => null,
        ])->save();

        return response()->json([
            'message' => 'OTP verified successfully.',
            'token' => $token,
            'user' => $this->formatUser($user->fresh()),
        ]);
    }

    public function resendOtp(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'mobile' => ['required', 'string', 'regex:/^\d{10,15}$/'],
            'role' => ['required', 'in:resident,official'],
        ]);
        if ($validator->fails()) {
            return response()->json([
                'message' => $validator->errors()->first(),
                'errors' => $validator->errors(),
            ], 422);
        }

        $mobile = $this->normalizeMobile((string) $request->string('mobile'));
        $role = (string) $request->string('role');
        $user = User::query()
            ->where('mobile', $mobile)
            ->where('role', $role)
            ->first();
        if ($user === null) {
            return response()->json([
                'message' => 'Account not found.',
            ], 404);
        }

        $otp = $this->generateOtp();
        $user->forceFill([
            'otp_code' => $otp,
            'otp_expires_at' => now()->addMinutes(10),
        ])->save();

        if (!$this->sendOtp($user, $otp)) {
            return response()->json([
                'message' => 'OTP SMS could not be sent right now. Please try again.',
                'otp_required' => true,
                'otp_debug_code' => $this->debugOtpCode($otp),
            ], 503);
        }

        return response()->json([
            'message' => 'OTP has been resent.',
            'otp_required' => true,
            'otp_debug_code' => $this->debugOtpCode($otp),
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        /** @var User|null $user */
        $user = Auth::guard('api')->user();
        if ($user === null) {
            return response()->json([
                'message' => 'Unauthenticated.',
            ], 401);
        }

        $user->forceFill(['api_token' => null])->save();

        return response()->json([
            'message' => 'Logged out.',
        ]);
    }

    public function completeActivation(Request $request): JsonResponse
    {
        /** @var User|null $user */
        $user = Auth::guard('api')->user();
        if ($user === null) {
            return response()->json([
                'message' => 'Unauthenticated.',
            ], 401);
        }

        if ($user->role !== 'official') {
            return response()->json([
                'message' => 'Only official accounts can complete activation.',
            ], 403);
        }

        $user->forceFill(['activation_completed' => true])->save();

        return response()->json([
            'message' => 'Activation details saved.',
            'user' => $this->formatUser($user),
        ]);
    }

    private function normalizeMobile(string $mobile): string
    {
        return preg_replace('/\D+/', '', $mobile) ?? '';
    }

    private function buildSyntheticEmail(string $mobile, string $role): string
    {
        return sprintf('%s.%s@barangaymo.local', $mobile, $role);
    }

    private function resolveRegistrationEmail(Request $request, string $mobile, string $role): string
    {
        $email = strtolower(trim((string) $request->input('email', '')));
        if ($email !== '') {
            return $email;
        }

        return $this->buildSyntheticEmail($mobile, $role);
    }

    private function generateOtp(): string
    {
        return (string) random_int(100000, 999999);
    }

    private function issueToken(User $user): string
    {
        $plainToken = Str::random(80);
        $hashedToken = hash('sha256', $plainToken);

        do {
            if (!User::query()->where('api_token', $hashedToken)->exists()) {
                break;
            }
            $plainToken = Str::random(80);
            $hashedToken = hash('sha256', $plainToken);
        } while (true);

        $user->forceFill(['api_token' => $hashedToken])->save();

        return $plainToken;
    }

    private function sendOtp(User $user, string $otp): bool
    {
        if (!$this->shouldSendSms()) {
            return true;
        }

        $url = trim((string) config('services.txtbox.url'));
        $apiKey = trim((string) config('services.txtbox.api_key'));
        $timeout = (int) config('services.txtbox.timeout', 10);

        if ($url === '' || $apiKey === '') {
            Log::warning('TXTBOX OTP config missing.');
            return false;
        }

        $mobile = $this->toTxtboxNumber((string) $user->mobile);
        $message = "Your BarangayMo OTP is {$otp}. Valid for 10 minutes. Do not share this code.";

        try {
            $response = Http::timeout($timeout)
                ->withHeaders([
                    'X-TxtBox-Auth' => $apiKey,
                    'Content-Type' => 'application/json',
                ])
                ->asJson()
                ->post($url, [
                    'message' => $message,
                    'number' => $mobile,
                ]);

            if ($response->successful()) {
                return true;
            }

            Log::warning('TXTBOX OTP send failed.', [
                'status' => $response->status(),
                'body' => $response->body(),
                'mobile' => $mobile,
                'attempt' => 'spacall-compatible',
            ]);
            return false;
        } catch (Throwable $e) {
            Log::error('TXTBOX OTP send exception.', [
                'error' => $e->getMessage(),
                'mobile' => $mobile,
            ]);
            return false;
        }
    }

    private function shouldSendSms(): bool
    {
        if ((bool) config('services.txtbox.force_send', false)) {
            return true;
        }

        return !app()->environment(['local', 'testing']);
    }

    private function debugOtpCode(string $otp): ?string
    {
        if (app()->environment(['local', 'testing'])) {
            return $otp;
        }

        return null;
    }

    private function toTxtboxNumber(string $mobile): string
    {
        $digits = preg_replace('/\D+/', '', $mobile) ?? '';
        if (str_starts_with($digits, '63') && strlen($digits) === 12) {
            return '0'.substr($digits, 2);
        }
        if (str_starts_with($digits, '9') && strlen($digits) === 10) {
            return '0'.$digits;
        }
        return $digits;
    }

    /**
     * @return array<string, mixed>
     */
    private function formatUser(User $user): array
    {
        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'mobile' => $user->mobile,
            'role' => $user->role,
            'activation_completed' => (bool) $user->activation_completed,
            'province' => $user->province,
            'city_municipality' => $user->city_municipality,
            'barangay' => $user->barangay,
        ];
    }
}

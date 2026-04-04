<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PendingRegistration;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Throwable;

class AuthController extends Controller
{
    public function register(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'string', 'email', 'max:255', 'unique:users,email'],
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
        $province = trim((string) $request->string('province'));
        $cityMunicipality = trim((string) $request->string('city_municipality'));
        $barangay = trim((string) $request->string('barangay'));
        $existing = User::query()
            ->where('mobile', $mobile)
            ->where('role', $role)
            ->first();
        if ($existing !== null) {
            if ($existing->otp_verified_at === null) {
                PendingRegistration::query()
                    ->where('mobile', $mobile)
                    ->where('role', $role)
                    ->delete();

                $otp = $this->generateOtp();
                $existing->forceFill([
                    'otp_code' => $otp,
                    'otp_expires_at' => now()->addMinutes(10),
                ])->save();

                if (!$this->sendOtpToMobile((string) $existing->mobile, $otp)) {
                    return response()->json([
                        'message' => 'Account exists but OTP SMS could not be sent. Please tap Resend OTP.',
                        'otp_required' => true,
                        'otp_debug_code' => $this->debugOtpCode($otp),
                        'user' => $this->formatUser($existing),
                    ], 503);
                }

                return response()->json([
                    'message' => 'Account already exists and is pending OTP verification.',
                    'otp_required' => true,
                    'otp_debug_code' => $this->debugOtpCode($otp),
                    'user' => $this->formatUser($existing),
                ], 202);
            }
            return response()->json([
                'message' => 'An account with this mobile already exists for this role.',
            ], 422);
        }

        $otp = $this->generateOtp();
        PendingRegistration::query()->updateOrCreate(
            [
                'mobile' => $mobile,
                'role' => $role,
            ],
            [
                'otp_code' => $otp,
                'otp_expires_at' => now()->addMinutes(10),
                'payload' => [
                    'name' => trim((string) $request->string('name')),
                    'email' => $this->resolveRegistrationEmail($request, $mobile, $role),
                    'password_hash' => Hash::make((string) $request->string('password')),
                    'middle_name' => $request->filled('middle_name') ? trim((string) $request->string('middle_name')) : null,
                    'suffix' => $request->filled('suffix') ? trim((string) $request->string('suffix')) : null,
                    'religion' => $request->filled('religion') ? trim((string) $request->string('religion')) : null,
                    'province' => $province !== '' ? $province : null,
                    'city_municipality' => $cityMunicipality !== '' ? $cityMunicipality : null,
                    'barangay' => $barangay !== '' ? $barangay : null,
                    'activation_completed' => $role === 'resident',
                ],
            ]
        );

        if (!$this->sendOtpToMobile($mobile, $otp)) {
            return response()->json([
                'message' => 'Registration received, but OTP SMS could not be sent. Please tap Resend OTP.',
                'otp_required' => true,
                'otp_debug_code' => $this->debugOtpCode($otp),
            ], 503);
        }

        return response()->json([
            'message' => 'OTP has been sent. Verify code to complete account creation.',
            'otp_required' => true,
            'otp_debug_code' => $this->debugOtpCode($otp),
        ], 202);
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
            $pending = PendingRegistration::query()
                ->where('mobile', $mobile)
                ->where('role', $role)
                ->first();
            if ($pending !== null) {
                $otp = $this->generateOtp();
                $pending->forceFill([
                    'otp_code' => $otp,
                    'otp_expires_at' => now()->addMinutes(10),
                ])->save();

                if (!$this->sendOtpToMobile($mobile, $otp)) {
                    return response()->json([
                        'message' => 'Account is pending verification, but OTP SMS could not be sent.',
                        'otp_required' => true,
                        'otp_debug_code' => $this->debugOtpCode($otp),
                    ], 503);
                }

                return response()->json([
                    'message' => 'Verify your OTP before logging in.',
                    'otp_required' => true,
                    'otp_debug_code' => $this->debugOtpCode($otp),
                ], 403);
            }

            return response()->json([
                'message' => 'Invalid credentials.',
            ], 401);
        }

        if ($user->otp_verified_at === null) {
            PendingRegistration::query()
                ->where('mobile', $mobile)
                ->where('role', $role)
                ->delete();

            $otp = $this->generateOtp();
            $user->forceFill([
                'otp_code' => $otp,
                'otp_expires_at' => now()->addMinutes(10),
            ])->save();

            if (!$this->sendOtpToMobile((string) $user->mobile, $otp)) {
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
        $pending = PendingRegistration::query()
            ->where('mobile', $mobile)
            ->where('role', $role)
            ->first();

        if ($pending !== null) {
            $pendingOtpMatches = hash_equals((string) $pending->otp_code, $otp);
            $pendingOtpExpired = $pending->otp_expires_at !== null && now()->greaterThan($pending->otp_expires_at);
            if ($pendingOtpMatches && !$pendingOtpExpired) {
                $payload = is_array($pending->payload) ? $pending->payload : [];
                $user = DB::transaction(function () use ($mobile, $role, $payload): User {
                    $user = User::query()
                        ->where('mobile', $mobile)
                        ->where('role', $role)
                        ->first();

                    $data = [
                        'name' => (string) ($payload['name'] ?? ''),
                        'email' => (string) ($payload['email'] ?? $this->buildSyntheticEmail($mobile, $role)),
                        'mobile' => $mobile,
                        'role' => $role,
                        'middle_name' => $payload['middle_name'] ?? null,
                        'suffix' => $payload['suffix'] ?? null,
                        'religion' => $payload['religion'] ?? null,
                        'province' => $payload['province'] ?? null,
                        'city_municipality' => $payload['city_municipality'] ?? null,
                        'barangay' => $payload['barangay'] ?? null,
                        'activation_completed' => (bool) ($payload['activation_completed'] ?? ($role === 'resident')),
                        'otp_verified_at' => now(),
                        'otp_code' => null,
                        'otp_expires_at' => null,
                    ];

                    $passwordHash = (string) ($payload['password_hash'] ?? '');
                    if ($passwordHash !== '') {
                        $data['password'] = $passwordHash;
                    }

                    if ($user === null) {
                        return User::query()->create($data);
                    }

                    $user->forceFill($data)->save();
                    return $user->fresh();
                });

                $pending->delete();
                $token = $this->issueToken($user);

                return response()->json([
                    'message' => 'OTP verified successfully.',
                    'token' => $token,
                    'user' => $this->formatUser($user->fresh()),
                ]);
            }
        }

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
        $pending = PendingRegistration::query()
            ->where('mobile', $mobile)
            ->where('role', $role)
            ->first();
        if ($pending !== null) {
            $otp = $this->generateOtp();
            $pending->forceFill([
                'otp_code' => $otp,
                'otp_expires_at' => now()->addMinutes(10),
            ])->save();

            if (!$this->sendOtpToMobile($mobile, $otp)) {
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

        if (!$this->sendOtpToMobile((string) $user->mobile, $otp)) {
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

        $province = trim((string) $request->input('province', ''));
        $cityMunicipality = trim((string) $request->input('city_municipality', ''));
        $barangay = trim((string) $request->input('barangay', ''));

        $updates = [
            'activation_completed' => true,
        ];
        if ($province !== '') {
            $updates['province'] = $province;
        }
        if ($cityMunicipality !== '') {
            $updates['city_municipality'] = $cityMunicipality;
        }
        if ($barangay !== '') {
            $updates['barangay'] = $barangay;
        }

        $user->forceFill($updates)->save();

        return response()->json([
            'message' => 'Activation details saved.',
            'user' => $this->formatUser($user),
        ]);
    }

    public function deleteAccount(Request $request): JsonResponse
    {
        /** @var User|null $user */
        $user = Auth::guard('api')->user();
        if ($user === null) {
            return response()->json([
                'message' => 'Unauthenticated.',
            ], 401);
        }

        $validator = Validator::make($request->all(), [
            'current_pin' => ['required', 'digits:6'],
            'confirm_text' => ['required', 'string', 'in:DELETE'],
        ]);
        if ($validator->fails()) {
            return response()->json([
                'message' => $validator->errors()->first(),
                'errors' => $validator->errors(),
            ], 422);
        }

        $currentPin = trim((string) $request->input('current_pin'));
        if (!Hash::check($currentPin, (string) $user->password)) {
            return response()->json([
                'message' => 'Current PIN is incorrect.',
            ], 422);
        }

        $userId = (int) $user->id;
        DB::transaction(function () use ($userId): void {
            if (Schema::hasTable('official_notifications')) {
                DB::table('official_notifications')
                    ->where('source_user_id', $userId)
                    ->orWhere('target_user_id', $userId)
                    ->delete();
            }
            if (Schema::hasTable('official_gov_agencies')) {
                DB::table('official_gov_agencies')->where('created_by_user_id', $userId)->delete();
            }
            if (Schema::hasTable('emergency_contacts')) {
                DB::table('emergency_contacts')->where('created_by_user_id', $userId)->delete();
            }
            if (Schema::hasTable('community_post_likes')) {
                DB::table('community_post_likes')->where('user_id', $userId)->delete();
            }
            if (Schema::hasTable('community_post_comments')) {
                DB::table('community_post_comments')->where('user_id', $userId)->delete();
            }
            if (Schema::hasTable('community_posts')) {
                DB::table('community_posts')->where('user_id', $userId)->delete();
            }
            if (Schema::hasTable('community_chat_messages')) {
                DB::table('community_chat_messages')->where('user_id', $userId)->delete();
            }
            if (Schema::hasTable('saved_jobs')) {
                DB::table('saved_jobs')->where('user_id', $userId)->delete();
            }
            if (Schema::hasTable('job_hunter_invitations')) {
                DB::table('job_hunter_invitations')
                    ->where('inviter_user_id', $userId)
                    ->orWhere('talent_user_id', $userId)
                    ->delete();
            }
            if (Schema::hasTable('job_hunter_profiles')) {
                DB::table('job_hunter_profiles')->where('user_id', $userId)->delete();
            }
            if (Schema::hasTable('job_hiring_posts')) {
                DB::table('job_hiring_posts')->where('user_id', $userId)->delete();
            }
            if (Schema::hasTable('job_applications')) {
                DB::table('job_applications')->where('applicant_user_id', $userId)->delete();
            }
            if (Schema::hasTable('market_products')) {
                DB::table('market_products')->where('user_id', $userId)->delete();
            }
            if (Schema::hasTable('merchant_registrations')) {
                DB::table('merchant_registrations')->where('user_id', $userId)->delete();
            }
            if (Schema::hasTable('resident_profiles')) {
                DB::table('resident_profiles')->where('user_id', $userId)->delete();
            }
            if (Schema::hasTable('resident_rbi_records')) {
                DB::table('resident_rbi_records')->where('user_id', $userId)->delete();
            }
            if (Schema::hasTable('service_requests')) {
                DB::table('service_requests')->where('user_id', $userId)->delete();
            }
            if (Schema::hasTable('emergency_shared_locations')) {
                DB::table('emergency_shared_locations')->where('user_id', $userId)->delete();
            }
            DB::table('users')->where('id', $userId)->delete();
        });

        return response()->json([
            'message' => 'Account deleted permanently.',
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
        return $this->sendOtpToMobile((string) $user->mobile, $otp);
    }

    private function sendOtpToMobile(string $mobile, string $otp): bool
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

        $mobile = $this->toTxtboxNumber($mobile);
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

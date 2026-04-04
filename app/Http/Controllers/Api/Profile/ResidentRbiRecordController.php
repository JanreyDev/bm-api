<?php

namespace App\Http\Controllers\Api\Profile;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\UpsertResidentRbiRecordRequest;
use App\Http\Resources\Profile\ResidentRbiRecordResource;
use App\Models\ResidentRbiRecord;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ResidentRbiRecordController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $user = $this->authenticatedUserOrNull();
        if ($user === null) {
            return response()->json([
                'message' => 'Unauthenticated.',
            ], 401);
        }

        if ($user->role === 'resident') {
            $record = ResidentRbiRecord::query()
                ->where('user_id', $user->id)
                ->first();

            return response()->json([
                'message' => 'Resident RBI record loaded.',
                'records' => $record === null
                    ? []
                    : [(new ResidentRbiRecordResource($record->loadMissing('user')))->toArray($request)],
            ]);
        }

        if ($user->role !== 'official') {
            return response()->json([
                'message' => 'Only resident or official accounts can access RBI records.',
            ], 403);
        }

        [$province, $city, $barangay] = $this->resolveScope($request, $user);
        if ($barangay === '') {
            return response()->json([
                'message' => 'Set your barangay in your profile before opening RBI records.',
            ], 422);
        }
        $search = trim((string) $request->query('q', ''));

        $baseQuery = ResidentRbiRecord::query()
            ->with('user')
            ->whereRaw('LOWER(TRIM(barangay)) = ?', [$this->normalizeScopeValue($barangay)]);

        if ($search !== '') {
            $baseQuery->where(function ($builder) use ($search): void {
                $like = '%' . str_replace('%', '\\%', $search) . '%';
                $builder
                    ->where('rbi_id', 'like', $like)
                    ->orWhere('first_name', 'like', $like)
                    ->orWhere('middle_name', 'like', $like)
                    ->orWhere('last_name', 'like', $like)
                    ->orWhere('zone_purok', 'like', $like);
            });
        }

        $records = (clone $baseQuery)
            ->when($city !== '', fn ($query) => $query->whereRaw(
                'LOWER(TRIM(city_municipality)) = ?',
                [$this->normalizeScopeValue($city)]
            ))
            ->when($province !== '', fn ($query) => $query->whereRaw(
                'LOWER(TRIM(province)) = ?',
                [$this->normalizeScopeValue($province)]
            ))
            ->latest()
            ->take(300)
            ->get();

        if ($records->isEmpty() && $city !== '') {
            $records = (clone $baseQuery)
                ->when($province !== '', fn ($query) => $query->whereRaw(
                    'LOWER(TRIM(province)) = ?',
                    [$this->normalizeScopeValue($province)]
                ))
                ->latest()
                ->take(300)
                ->get();
        }

        if ($records->isEmpty() && $province !== '') {
            $records = (clone $baseQuery)
                ->latest()
                ->take(300)
                ->get();
        }

        return response()->json([
            'message' => 'RBI records loaded.',
            'records' => $records
                ->map(fn (ResidentRbiRecord $record): array => (new ResidentRbiRecordResource($record))->toArray($request))
                ->values()
                ->all(),
        ]);
    }

    public function upsert(UpsertResidentRbiRecordRequest $request): JsonResponse
    {
        $user = $this->authenticatedUserOrNull();
        if ($user === null) {
            return response()->json([
                'message' => 'Unauthenticated.',
            ], 401);
        }

        if ($user->role !== 'resident') {
            return response()->json([
                'message' => 'Only resident accounts can submit RBI records.',
            ], 403);
        }

        $validated = $request->validated();

        $verificationStep = (int) ($validated['verification_step'] ?? 1);
        $verificationStep = max(1, min(5, $verificationStep));

        $record = ResidentRbiRecord::query()->updateOrCreate(
            ['user_id' => $user->id],
            [
                'rbi_id' => trim((string) ($validated['rbi_id'] ?? '')),
                'first_name' => trim((string) ($validated['first_name'] ?? '')),
                'middle_name' => trim((string) ($validated['middle_name'] ?? '')),
                'last_name' => trim((string) ($validated['last_name'] ?? '')),
                'suffix' => trim((string) ($validated['suffix'] ?? '')),
                'province' => trim((string) ($validated['province'] ?? $user->province ?? '')),
                'city_municipality' => trim((string) ($validated['city_municipality'] ?? $user->city_municipality ?? '')),
                'barangay' => trim((string) ($validated['barangay'] ?? $user->barangay ?? '')),
                'street_name' => trim((string) ($validated['street_name'] ?? '')),
                'zone_purok' => trim((string) ($validated['zone_purok'] ?? '')),
                'year_of_residency' => $validated['year_of_residency'] ?? null,
                'birth_date' => $validated['birth_date'] ?? null,
                'gender' => trim((string) ($validated['gender'] ?? '')),
                'disability_tag' => trim((string) ($validated['disability_tag'] ?? '')),
                'blood_donor_opt_in' => (bool) ($validated['blood_donor_opt_in'] ?? false),
                'blood_type' => trim((string) ($validated['blood_type'] ?? '')),
                'education_aid_status' => trim((string) ($validated['education_aid_status'] ?? '')),
                'latest_grade_average' => trim((string) ($validated['latest_grade_average'] ?? '')),
                'family_count' => (int) ($validated['family_count'] ?? 0),
                'vehicle_count' => (int) ($validated['vehicle_count'] ?? 0),
                'vaccination_count' => (int) ($validated['vaccination_count'] ?? 0),
                'latest_bmi' => $validated['latest_bmi'] ?? null,
                'verification_step' => $verificationStep,
                'verified_at' => $verificationStep >= 2 ? now() : null,
            ]
        );

        $record->loadMissing('user');

        return response()->json([
            'message' => 'RBI record saved.',
            'record' => (new ResidentRbiRecordResource($record))->toArray($request),
        ]);
    }

    public function updateVerificationStatus(Request $request, int $recordId): JsonResponse
    {
        $user = $this->authenticatedUserOrNull();
        if ($user === null) {
            return response()->json([
                'message' => 'Unauthenticated.',
            ], 401);
        }

        if ($user->role !== 'official') {
            return response()->json([
                'message' => 'Only official accounts can update RBI verification.',
            ], 403);
        }

        $validated = $request->validate([
            'verified' => ['required', 'boolean'],
        ]);

        $record = ResidentRbiRecord::query()->with('user')->find($recordId);
        if ($record === null) {
            return response()->json([
                'message' => 'RBI record not found.',
            ], 404);
        }

        $officialBarangay = trim((string) $user->barangay);
        if (
            $officialBarangay !== '' &&
            $this->normalizeScopeValue(trim((string) $record->barangay)) !== $this->normalizeScopeValue($officialBarangay)
        ) {
            return response()->json([
                'message' => 'You can only verify records inside your barangay.',
            ], 403);
        }

        $verified = (bool) $validated['verified'];
        $record->forceFill([
            'verification_step' => $verified ? 2 : 1,
            'verified_at' => $verified ? now() : null,
        ])->save();

        return response()->json([
            'message' => $verified ? 'RBI record marked as verified.' : 'RBI record marked as unverified.',
            'record' => (new ResidentRbiRecordResource($record->fresh(['user'])))->toArray($request),
        ]);
    }

    private function authenticatedUserOrNull(): ?User
    {
        /** @var User|null $user */
        return Auth::guard('api')->user();
    }

    /**
     * @return array{string,string,string}
     */
    private function resolveScope(Request $request, User $user): array
    {
        $province = trim((string) $user->province);
        $city = trim((string) $user->city_municipality);
        $barangay = trim((string) $user->barangay);

        if ($province === '') {
            $province = trim((string) $request->query('province', $request->input('province', '')));
        }
        if ($city === '') {
            $city = trim((string) $request->query('city_municipality', $request->input('city_municipality', '')));
        }
        if ($barangay === '') {
            $barangay = trim((string) $request->query('barangay', $request->input('barangay', '')));
        }

        $updates = [];
        if ($province !== '' && trim((string) $user->province) === '') {
            $updates['province'] = mb_substr($province, 0, 100);
        }
        if ($city !== '' && trim((string) $user->city_municipality) === '') {
            $updates['city_municipality'] = mb_substr($city, 0, 100);
        }
        if ($barangay !== '' && trim((string) $user->barangay) === '') {
            $updates['barangay'] = mb_substr($barangay, 0, 191);
        }
        if ($updates !== []) {
            $user->forceFill($updates)->save();
            $province = trim((string) ($updates['province'] ?? $province));
            $city = trim((string) ($updates['city_municipality'] ?? $city));
            $barangay = trim((string) ($updates['barangay'] ?? $barangay));
        }

        return [$province, $city, $barangay];
    }

    private function normalizeScopeValue(string $value): string
    {
        return mb_strtolower(trim((string) preg_replace('/\s+/', ' ', $value)));
    }
}

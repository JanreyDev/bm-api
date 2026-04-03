<?php

namespace App\Http\Controllers\Api\Official;

use App\Http\Controllers\Controller;
use App\Models\OfficialGovAgency;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class OfficialGovAgencyController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $user = $this->authenticatedUserOrNull();
        if ($user === null) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        [$province, $city, $barangay] = $this->scopeFromUser($request, $user);
        if ($province === '' || $city === '' || $barangay === '') {
            return response()->json([
                'message' => 'Set your province/city/barangay before loading government agencies.',
                'agencies' => [],
            ], 422);
        }

        $rows = OfficialGovAgency::query()
            ->where('province', $province)
            ->where('city_municipality', $city)
            ->where('barangay', $barangay)
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('code')
            ->get();

        if ($rows->isEmpty()) {
            $this->seedDefaultAgencies($province, $city, $barangay);
            $rows = OfficialGovAgency::query()
                ->where('province', $province)
                ->where('city_municipality', $city)
                ->where('barangay', $barangay)
                ->where('is_active', true)
                ->orderBy('sort_order')
                ->orderBy('code')
                ->get();
        }

        return response()->json([
            'message' => $rows->isNotEmpty() ? 'Government agencies loaded.' : 'No government agencies available.',
            'agencies' => $rows->map(static fn (OfficialGovAgency $entry): array => [
                'id' => $entry->id,
                'label' => trim((string) $entry->code),
                'display_name' => trim((string) $entry->display_name),
                'website' => trim((string) $entry->website),
                'sort_order' => (int) $entry->sort_order,
                'updated_at' => optional($entry->updated_at)?->toIso8601String(),
            ])->values()->all(),
        ]);
    }

    private function seedDefaultAgencies(string $province, string $city, string $barangay): void
    {
        $defaults = [
            ['code' => 'DFA', 'display_name' => 'Department of Foreign Affairs', 'website' => 'https://dfa.gov.ph', 'sort_order' => 1],
            ['code' => 'DILG', 'display_name' => 'Department of the Interior and Local Government', 'website' => 'https://dilg.gov.ph', 'sort_order' => 2],
            ['code' => 'DOLE', 'display_name' => 'Department of Labor and Employment', 'website' => 'https://www.dole.gov.ph', 'sort_order' => 3],
            ['code' => 'DPWH', 'display_name' => 'Department of Public Works and Highways', 'website' => 'https://www.dpwh.gov.ph', 'sort_order' => 4],
            ['code' => 'DSWD', 'display_name' => 'Department of Social Welfare and Development', 'website' => 'https://www.dswd.gov.ph', 'sort_order' => 5],
            ['code' => 'LTO', 'display_name' => 'Land Transportation Office', 'website' => 'https://lto.gov.ph', 'sort_order' => 6],
            ['code' => 'OP', 'display_name' => 'Office of the President', 'website' => 'https://op-proper.gov.ph', 'sort_order' => 7],
            ['code' => 'OLG', 'display_name' => 'City Government', 'website' => 'https://www.olongapocity.gov.ph', 'sort_order' => 8],
            ['code' => 'PNP', 'display_name' => 'Philippine National Police', 'website' => 'https://pnp.gov.ph', 'sort_order' => 9],
            ['code' => 'SEN', 'display_name' => 'Senate of the Philippines', 'website' => 'https://legacy.senate.gov.ph', 'sort_order' => 10],
            ['code' => 'CSC', 'display_name' => 'Civil Service Commission', 'website' => 'https://csc.gov.ph', 'sort_order' => 11],
            ['code' => 'TESDA', 'display_name' => 'Technical Education and Skills Development Authority', 'website' => 'https://www.tesda.gov.ph', 'sort_order' => 12],
        ];

        foreach ($defaults as $row) {
            OfficialGovAgency::query()->create([
                'created_by_user_id' => null,
                'province' => $province,
                'city_municipality' => $city,
                'barangay' => $barangay,
                'code' => $row['code'],
                'display_name' => $row['display_name'],
                'website' => $row['website'],
                'sort_order' => $row['sort_order'],
                'is_active' => true,
            ]);
        }
    }

    private function authenticatedUserOrNull(): ?User
    {
        /** @var User|null $user */
        return Auth::guard('api')->user();
    }

    /**
     * @return array{string,string,string}
     */
    private function scopeFromUser(Request $request, User $user): array
    {
        $province = trim((string) $user->province);
        $city = trim((string) $user->city_municipality);
        $barangay = trim((string) $user->barangay);

        if ($province === '') {
            $province = trim((string) $request->input('province', $request->query('province', '')));
        }
        if ($city === '') {
            $city = trim((string) $request->input('city_municipality', $request->query('city_municipality', '')));
        }
        if ($barangay === '') {
            $barangay = trim((string) $request->input('barangay', $request->query('barangay', '')));
        }

        $updates = [];
        if ($province !== '' && trim((string) $user->province) === '') {
            $updates['province'] = mb_substr($province, 0, 100);
        }
        if ($city !== '' && trim((string) $user->city_municipality) === '') {
            $updates['city_municipality'] = mb_substr($city, 0, 100);
        }
        if ($barangay !== '' && trim((string) $user->barangay) === '') {
            $updates['barangay'] = mb_substr($barangay, 0, 100);
        }
        if ($updates !== []) {
            $user->forceFill($updates)->save();
            $province = trim((string) ($updates['province'] ?? $province));
            $city = trim((string) ($updates['city_municipality'] ?? $city));
            $barangay = trim((string) ($updates['barangay'] ?? $barangay));
        }

        return [$province, $city, $barangay];
    }
}


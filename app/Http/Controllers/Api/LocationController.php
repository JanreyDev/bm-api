<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\BarangayLocation;
use Illuminate\Http\JsonResponse;

class LocationController extends Controller
{
    public function index(): JsonResponse
    {
        $rows = BarangayLocation::query()
            ->select([
                'province',
                'city_municipality',
                'barangay',
                'is_active',
                'sort_order',
            ])
            ->whereNotNull('province')
            ->whereNotNull('city_municipality')
            ->whereNotNull('barangay')
            ->orderBy('province')
            ->orderBy('city_municipality')
            ->orderBy('sort_order')
            ->orderBy('barangay')
            ->get();

        $directory = [];
        foreach ($rows as $row) {
            $province = trim((string) $row->province);
            $city = trim((string) $row->city_municipality);
            $barangay = trim((string) $row->barangay);
            if ($province === '' || $city === '' || $barangay === '') {
                continue;
            }

            if (!array_key_exists($province, $directory)) {
                $directory[$province] = [];
            }
            if (!array_key_exists($city, $directory[$province])) {
                $directory[$province][$city] = [];
            }
            $directory[$province][$city][] = $barangay;
        }

        foreach ($directory as $province => $cityMap) {
            foreach ($cityMap as $city => $barangays) {
                $uniqueSorted = array_values(array_unique($barangays));
                sort($uniqueSorted);
                $directory[$province][$city] = $uniqueSorted;
            }
        }

        return response()->json([
            'message' => 'Address directory loaded.',
            'count' => $rows->count(),
            'data' => $rows->map(fn (BarangayLocation $row): array => [
                'province' => $row->province,
                'city_municipality' => $row->city_municipality,
                'barangay' => $row->barangay,
                'is_active' => (bool) $row->is_active,
            ])->values(),
            'directory' => $directory,
        ]);
    }
}


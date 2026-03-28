<?php

namespace App\Services\Locations;

use App\Models\BarangayLocation;
use Illuminate\Support\Facades\DB;

class PsgcLocationImporter
{
    public function __construct(
        private readonly PsgcClient $psgcClient,
    ) {
    }

    /**
     * @param callable(array{province:string,code:string,error:string}):void|null $onProvinceError
     * @param callable():void|null $onProvinceProcessed
     * @return array{inserted:int,errors:int,total_provinces:int}
     */
    public function import(
        bool $truncate = false,
        string $provinceFilter = '',
        int $chunkSize = 800,
        ?callable $onProvinceError = null,
        ?callable $onProvinceProcessed = null,
    ): array {
        $normalizedFilter = strtolower(trim($provinceFilter));
        $safeChunkSize = max(100, $chunkSize);

        $provinces = $this->psgcClient->provinces();
        if ($truncate) {
            DB::table('barangay_locations')->truncate();
        }

        $buffer = [];
        $inserted = 0;
        $errors = 0;
        $sortOrder = 0;

        $flush = function () use (&$buffer, &$inserted): void {
            if ($buffer === []) {
                return;
            }
            BarangayLocation::query()->upsert(
                $buffer,
                ['province', 'city_municipality', 'barangay'],
                ['is_active', 'sort_order', 'updated_at'],
            );
            $inserted += count($buffer);
            $buffer = [];
        };

        foreach ($provinces as $provinceNode) {
            $provinceCode = trim((string) ($provinceNode['code'] ?? $provinceNode['psgc_code'] ?? ''));
            $provinceName = trim((string) ($provinceNode['name'] ?? $provinceNode['province_name'] ?? ''));
            if ($provinceCode === '' || $provinceName === '') {
                $onProvinceProcessed?->__invoke();

                continue;
            }

            if ($normalizedFilter !== '' &&
                $normalizedFilter !== strtolower($provinceCode) &&
                $normalizedFilter !== strtolower($provinceName)) {
                $onProvinceProcessed?->__invoke();

                continue;
            }

            try {
                $localities = $this->psgcClient->citiesMunicipalities($provinceCode);
            } catch (\Throwable $e) {
                $errors++;
                $onProvinceError?->__invoke([
                    'province' => $provinceName,
                    'code' => $provinceCode,
                    'error' => $e->getMessage(),
                ]);
                $onProvinceProcessed?->__invoke();

                continue;
            }

            foreach ($localities as $localityNode) {
                $localityCode = trim((string) ($localityNode['code'] ?? $localityNode['psgc_code'] ?? ''));
                $cityMunicipality = trim((string) ($localityNode['name'] ?? $localityNode['city_municipality_name'] ?? ''));
                if ($localityCode === '' || $cityMunicipality === '') {
                    continue;
                }

                try {
                    $barangays = $this->psgcClient->barangays($localityCode);
                } catch (\Throwable) {
                    $errors++;
                    continue;
                }

                foreach ($barangays as $barangayNode) {
                    $barangay = trim((string) ($barangayNode['name'] ?? $barangayNode['barangay_name'] ?? ''));
                    if ($barangay === '') {
                        continue;
                    }

                    $sortOrder++;
                    $now = now();
                    $buffer[] = [
                        'province' => $provinceName,
                        'city_municipality' => $cityMunicipality,
                        'barangay' => $barangay,
                        'is_active' => true,
                        'sort_order' => $sortOrder,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ];
                    if (count($buffer) >= $safeChunkSize) {
                        $flush();
                    }
                }
            }

            $onProvinceProcessed?->__invoke();
        }

        $flush();

        return [
            'inserted' => $inserted,
            'errors' => $errors,
            'total_provinces' => count($provinces),
        ];
    }
}


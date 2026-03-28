<?php

use App\Models\BarangayLocation;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('locations:import-psgc {--truncate : Clear barangay_locations before import} {--province= : Import only one province code or name} {--chunk=800 : Upsert batch size}', function (): int {
    $baseUrl = rtrim((string) config('services.psgc.base_url', 'https://psgc.cloud/api'), '/');
    $timeout = max(10, (int) config('services.psgc.timeout', 30));
    $chunkSize = max(100, (int) $this->option('chunk'));
    $provinceFilter = strtolower(trim((string) $this->option('province')));

    $this->components->info('Loading provinces from PSGC source...');

    try {
        $provinces = Http::timeout($timeout)
            ->acceptJson()
            ->retry(3, 400)
            ->get("{$baseUrl}/provinces")
            ->throw()
            ->json();
    } catch (\Throwable $e) {
        $this->components->error('Could not fetch provinces from PSGC source.');
        $this->line($e->getMessage());

        return 1;
    }

    if (!is_array($provinces) || $provinces === []) {
        $this->components->error('PSGC provinces response is empty or invalid.');

        return 1;
    }

    if ((bool) $this->option('truncate')) {
        $this->components->warn('Truncating barangay_locations...');
        DB::table('barangay_locations')->truncate();
    }

    $this->components->info('Importing province -> city/municipality -> barangay hierarchy...');
    $this->output->progressStart(count($provinces));

    $buffer = [];
    $inserted = 0;
    $errors = 0;
    $sort = 0;

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
            $this->output->progressAdvance();
            continue;
        }

        if ($provinceFilter !== '' &&
            $provinceFilter !== strtolower($provinceCode) &&
            $provinceFilter !== strtolower($provinceName)) {
            $this->output->progressAdvance();
            continue;
        }

        try {
            $localities = Http::timeout($timeout)
                ->acceptJson()
                ->retry(3, 400)
                ->get("{$baseUrl}/provinces/{$provinceCode}/cities-municipalities")
                ->throw()
                ->json();
        } catch (\Throwable $e) {
            $errors++;
            $this->newLine();
            $this->components->warn("Skipped province {$provinceName} ({$provinceCode}): ".$e->getMessage());
            $this->output->progressAdvance();
            continue;
        }

        if (!is_array($localities)) {
            $this->output->progressAdvance();
            continue;
        }

        foreach ($localities as $locality) {
            $localityCode = trim((string) ($locality['code'] ?? $locality['psgc_code'] ?? ''));
            $cityMunicipality = trim((string) ($locality['name'] ?? $locality['city_municipality_name'] ?? ''));
            if ($localityCode === '' || $cityMunicipality === '') {
                continue;
            }

            try {
                $barangays = Http::timeout($timeout)
                    ->acceptJson()
                    ->retry(3, 400)
                    ->get("{$baseUrl}/cities-municipalities/{$localityCode}/barangays")
                    ->throw()
                    ->json();
            } catch (\Throwable $e) {
                $errors++;
                continue;
            }

            if (!is_array($barangays)) {
                continue;
            }

            foreach ($barangays as $barangayNode) {
                $barangay = trim((string) ($barangayNode['name'] ?? $barangayNode['barangay_name'] ?? ''));
                if ($barangay === '') {
                    continue;
                }
                $sort++;
                $now = now();
                $buffer[] = [
                    'province' => $provinceName,
                    'city_municipality' => $cityMunicipality,
                    'barangay' => $barangay,
                    'is_active' => true,
                    'sort_order' => $sort,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];

                if (count($buffer) >= $chunkSize) {
                    $flush();
                }
            }
        }

        $this->output->progressAdvance();
    }

    $flush();
    $this->output->progressFinish();
    $this->newLine();

    $this->components->info("Import complete. Upserted rows: {$inserted}");
    if ($errors > 0) {
        $this->components->warn("Completed with {$errors} request errors (some localities may be missing). Re-run command to fill gaps.");
    }

    return 0;
})->purpose('Import full Philippines province/city/barangay hierarchy from PSGC source');

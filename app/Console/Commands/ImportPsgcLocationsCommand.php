<?php

namespace App\Console\Commands;

use App\Services\Locations\PsgcLocationImporter;
use Illuminate\Console\Command;

class ImportPsgcLocationsCommand extends Command
{
    protected $signature = 'locations:import-psgc
        {--truncate : Clear barangay_locations before import}
        {--province= : Import only one province code or name}
        {--chunk=800 : Upsert batch size}';

    protected $description = 'Import full Philippines province/city/barangay hierarchy from PSGC source';

    public function handle(PsgcLocationImporter $importer): int
    {
        $truncate = (bool) $this->option('truncate');
        $province = (string) $this->option('province');
        $chunk = (int) $this->option('chunk');

        if ($truncate) {
            $this->components->warn('Truncating barangay_locations...');
        }

        $this->components->info('Importing PSGC location hierarchy...');

        try {
            $report = $importer->import(
                truncate: $truncate,
                provinceFilter: $province,
                chunkSize: $chunk,
                onProvinceError: function (array $error): void {
                    $this->components->warn(
                        "Skipped province {$error['province']} ({$error['code']}): {$error['error']}",
                    );
                },
                onProvinceProcessed: function (): void {
                    $this->output->write('.');
                },
            );
        } catch (\Throwable $e) {
            $this->newLine();
            $this->components->error('PSGC import failed.');
            $this->line($e->getMessage());

            return self::FAILURE;
        }

        $this->newLine(2);
        $this->components->info("Import complete. Upserted rows: {$report['inserted']}");
        if ($report['errors'] > 0) {
            $this->components->warn(
                "Completed with {$report['errors']} request errors (some localities may be missing). Re-run command to fill gaps.",
            );
        }

        return self::SUCCESS;
    }
}


<?php

namespace App\Services\Jobs;

use App\Models\JobHunterProfile;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;

class JobHunterProfileService
{
    public function resolveBarangayOrNull(User $user): ?string
    {
        $barangay = trim((string) $user->barangay);

        return $barangay !== '' ? $barangay : null;
    }

    public function resolveDisplayName(User $user, ?string $fullName): string
    {
        $clean = trim((string) $fullName);
        if ($clean !== '') {
            return $clean;
        }
        $fallback = trim((string) $user->name);

        return $fallback !== '' ? $fallback : 'Resident';
    }

    /**
     * @return Collection<int, JobHunterProfile>
     */
    public function listForViewer(User $viewer): Collection
    {
        $barangay = $this->resolveBarangayOrNull($viewer);
        if ($barangay === null) {
            return new Collection();
        }

        return JobHunterProfile::query()
            ->inBarangay($barangay)
            ->latest()
            ->limit(200)
            ->get();
    }
}

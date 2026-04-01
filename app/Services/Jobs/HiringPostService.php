<?php

namespace App\Services\Jobs;

use App\Models\JobHiringPost;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;

class HiringPostService
{
    public function resolveBarangayOrNull(User $user): ?string
    {
        $barangay = trim((string) $user->barangay);

        return $barangay !== '' ? $barangay : null;
    }

    public function resolvePostedBy(User $user, ?string $postedBy): string
    {
        $clean = trim((string) $postedBy);
        if ($clean !== '') {
            return $clean;
        }

        $fallback = trim((string) $user->name);

        return $fallback !== '' ? $fallback : 'Barangay Employer';
    }

    /**
     * @return Collection<int, JobHiringPost>
     */
    public function listForViewer(User $viewer): Collection
    {
        $barangay = $this->resolveBarangayOrNull($viewer);
        if ($barangay === null) {
            return new Collection();
        }

        return JobHiringPost::query()
            ->inBarangay($barangay)
            ->latest()
            ->limit(200)
            ->get();
    }
}

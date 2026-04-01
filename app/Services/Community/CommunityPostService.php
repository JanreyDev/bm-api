<?php

namespace App\Services\Community;

use App\Models\CommunityPost;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;

class CommunityPostService
{
    public function resolveBarangayOrNull(User $user): ?string
    {
        $barangay = trim((string) $user->barangay);

        return $barangay !== '' ? $barangay : null;
    }

    public function resolveAuthorName(User $user, string $barangay, bool $isOfficial): string
    {
        if ($isOfficial) {
            return 'Barangay '.$barangay;
        }

        $author = trim((string) $user->name);

        return $author !== '' ? $author : 'Verified Resident';
    }

    public function normalizeImagePayload(mixed $value): ?string
    {
        if (!is_string($value)) {
            return null;
        }
        $trimmed = trim($value);
        if ($trimmed === '') {
            return null;
        }

        // Accept either plain base64 or data-url style payload.
        if (preg_match('/^data:image\/[a-zA-Z0-9.+-]+;base64,/', $trimmed) === 1) {
            return $trimmed;
        }

        return preg_match('/^[A-Za-z0-9+\/=\r\n]+$/', $trimmed) === 1 ? $trimmed : null;
    }

    /**
     * @return Collection<int, CommunityPost>
     */
    public function listForViewer(User $viewer): Collection
    {
        $barangay = $this->resolveBarangayOrNull($viewer);
        if ($barangay === null) {
            return new Collection();
        }

        return CommunityPost::query()
            ->inBarangay($barangay)
            ->withCount('comments')
            ->latest()
            ->limit(120)
            ->get();
    }

    public function findForViewer(
        int $postId,
        User $viewer,
        bool $withComments = false
    ): ?CommunityPost {
        $barangay = $this->resolveBarangayOrNull($viewer);
        if ($barangay === null) {
            return null;
        }

        $query = CommunityPost::query()
            ->where('id', $postId)
            ->inBarangay($barangay);

        if ($withComments) {
            $query->with([
                'comments' => fn ($builder) => $builder->latest(),
            ]);
        } else {
            $query->withCount('comments');
        }

        return $query->first();
    }
}

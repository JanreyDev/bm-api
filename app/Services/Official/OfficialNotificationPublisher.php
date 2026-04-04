<?php

namespace App\Services\Official;

use App\Models\OfficialNotification;
use App\Models\User;

class OfficialNotificationPublisher
{
    /**
     * @param array<string, mixed> $metadata
     */
    public function publishForOfficialScope(
        User $sourceUser,
        string $title,
        string $body,
        string $category = 'System',
        string $priority = 'normal',
        ?string $recordType = null,
        ?string $recordId = null,
        ?string $deepLink = null,
        array $metadata = [],
    ): ?OfficialNotification {
        $province = trim((string) $sourceUser->province);
        $city = trim((string) $sourceUser->city_municipality);
        $barangay = trim((string) $sourceUser->barangay);
        if ($barangay === '') {
            return null;
        }

        $created = null;

        $officials = User::query()
            ->where('role', 'official')
            ->whereRaw('LOWER(TRIM(barangay)) = LOWER(TRIM(?))', [$barangay])
            ->get(['id', 'province', 'city_municipality', 'barangay']);

        foreach ($officials as $official) {
            $officialProvince = trim((string) $official->province);
            $officialCity = trim((string) $official->city_municipality);
            $officialBarangay = trim((string) $official->barangay);
            if ($officialProvince === '' || $officialCity === '' || $officialBarangay === '') {
                continue;
            }
            $created ??= OfficialNotification::query()->create([
                'target_user_id' => $official->id,
                'source_user_id' => $sourceUser->id,
                'province' => mb_substr($officialProvince, 0, 100),
                'city_municipality' => mb_substr($officialCity, 0, 100),
                'barangay' => mb_substr($officialBarangay, 0, 100),
                'title' => mb_substr(trim($title), 0, 180),
                'body' => mb_substr(trim($body), 0, 500),
                'category' => mb_substr(trim($category) !== '' ? trim($category) : 'System', 0, 80),
                'priority' => $this->normalizePriority($priority),
                'record_type' => $recordType === null ? null : mb_substr(trim($recordType), 0, 80),
                'record_id' => $recordId === null ? null : mb_substr(trim($recordId), 0, 80),
                'deep_link' => $deepLink === null ? null : mb_substr(trim($deepLink), 0, 255),
                'metadata_json' => $metadata,
                'is_read' => false,
                'read_at' => null,
            ]);
        }

        if ($created !== null) {
            return $created;
        }

        if ($province === '' || $city === '') {
            return null;
        }

        return OfficialNotification::query()->create([
            'target_user_id' => null,
            'source_user_id' => $sourceUser->id,
            'province' => mb_substr($province, 0, 100),
            'city_municipality' => mb_substr($city, 0, 100),
            'barangay' => mb_substr($barangay, 0, 100),
            'title' => mb_substr(trim($title), 0, 180),
            'body' => mb_substr(trim($body), 0, 500),
            'category' => mb_substr(trim($category) !== '' ? trim($category) : 'System', 0, 80),
            'priority' => $this->normalizePriority($priority),
            'record_type' => $recordType === null ? null : mb_substr(trim($recordType), 0, 80),
            'record_id' => $recordId === null ? null : mb_substr(trim($recordId), 0, 80),
            'deep_link' => $deepLink === null ? null : mb_substr(trim($deepLink), 0, 255),
            'metadata_json' => $metadata,
            'is_read' => false,
            'read_at' => null,
        ]);
    }

    private function normalizePriority(string $value): string
    {
        $normalized = strtolower(trim($value));
        return in_array($normalized, ['low', 'normal', 'high', 'emergency'], true)
            ? $normalized
            : 'normal';
    }
}

<?php

namespace App\Http\Controllers\Api\Official;

use App\Http\Controllers\Controller;
use App\Models\EmergencyContact;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class EmergencyContactController extends Controller
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
                'message' => 'Set your province/city/barangay in profile before loading emergency contacts.',
            ], 422);
        }

        $contacts = EmergencyContact::query()
            ->where('province', $province)
            ->where('city_municipality', $city)
            ->where('barangay', $barangay)
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderByDesc('quick_dial')
            ->orderBy('label')
            ->get();

        return response()->json([
            'message' => 'Emergency contacts loaded.',
            'contacts' => $contacts->map(static fn (EmergencyContact $entry): array => [
                'id' => $entry->id,
                'label' => trim((string) $entry->label),
                'phone_number' => trim((string) $entry->phone_number),
                'description' => trim((string) ($entry->description ?? '')),
                'quick_dial' => (bool) $entry->quick_dial,
                'sort_order' => (int) $entry->sort_order,
                'updated_at' => optional($entry->updated_at)?->toIso8601String(),
            ])->values()->all(),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $user = $this->authenticatedUserOrNull();
        if ($user === null) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }
        if ($user->role !== 'official') {
            return response()->json(['message' => 'Only official accounts can manage emergency contacts.'], 403);
        }

        $validated = $request->validate([
            'label' => ['required', 'string', 'max:120'],
            'phone_number' => ['required', 'string', 'max:60'],
            'description' => ['nullable', 'string', 'max:220'],
            'quick_dial' => ['nullable', 'boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:99999'],
        ]);

        [$province, $city, $barangay] = $this->scopeFromUser($request, $user);
        if ($province === '' || $city === '' || $barangay === '') {
            return response()->json([
                'message' => 'Set your province/city/barangay in profile before adding emergency contacts.',
            ], 422);
        }

        $entry = EmergencyContact::query()->create([
            'created_by_user_id' => $user->id,
            'province' => $province,
            'city_municipality' => $city,
            'barangay' => $barangay,
            'label' => trim((string) $validated['label']),
            'phone_number' => trim((string) $validated['phone_number']),
            'description' => trim((string) ($validated['description'] ?? '')),
            'quick_dial' => (bool) ($validated['quick_dial'] ?? false),
            'sort_order' => (int) ($validated['sort_order'] ?? 0),
            'is_active' => true,
        ]);

        return response()->json([
            'message' => 'Emergency contact added.',
            'contact' => [
                'id' => $entry->id,
                'label' => trim((string) $entry->label),
                'phone_number' => trim((string) $entry->phone_number),
                'description' => trim((string) ($entry->description ?? '')),
                'quick_dial' => (bool) $entry->quick_dial,
                'sort_order' => (int) $entry->sort_order,
                'updated_at' => optional($entry->updated_at)?->toIso8601String(),
            ],
        ], 201);
    }

    public function update(Request $request, int $contactId): JsonResponse
    {
        $user = $this->authenticatedUserOrNull();
        if ($user === null) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }
        if ($user->role !== 'official') {
            return response()->json(['message' => 'Only official accounts can manage emergency contacts.'], 403);
        }

        $validated = $request->validate([
            'label' => ['required', 'string', 'max:120'],
            'phone_number' => ['required', 'string', 'max:60'],
            'description' => ['nullable', 'string', 'max:220'],
            'quick_dial' => ['nullable', 'boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:99999'],
        ]);

        $entry = EmergencyContact::query()->find($contactId);
        if ($entry === null) {
            return response()->json(['message' => 'Emergency contact not found.'], 404);
        }

        [$province, $city, $barangay] = $this->scopeFromUser($request, $user);
        if ($province !== $entry->province || $city !== $entry->city_municipality || $barangay !== $entry->barangay) {
            return response()->json(['message' => 'You can only edit contacts inside your barangay scope.'], 403);
        }

        $entry->forceFill([
            'label' => trim((string) $validated['label']),
            'phone_number' => trim((string) $validated['phone_number']),
            'description' => trim((string) ($validated['description'] ?? '')),
            'quick_dial' => (bool) ($validated['quick_dial'] ?? false),
            'sort_order' => (int) ($validated['sort_order'] ?? 0),
            'is_active' => true,
        ])->save();

        return response()->json([
            'message' => 'Emergency contact updated.',
            'contact' => [
                'id' => $entry->id,
                'label' => trim((string) $entry->label),
                'phone_number' => trim((string) $entry->phone_number),
                'description' => trim((string) ($entry->description ?? '')),
                'quick_dial' => (bool) $entry->quick_dial,
                'sort_order' => (int) $entry->sort_order,
                'updated_at' => optional($entry->updated_at)?->toIso8601String(),
            ],
        ]);
    }

    public function destroy(int $contactId): JsonResponse
    {
        $user = $this->authenticatedUserOrNull();
        if ($user === null) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }
        if ($user->role !== 'official') {
            return response()->json(['message' => 'Only official accounts can manage emergency contacts.'], 403);
        }

        $entry = EmergencyContact::query()->find($contactId);
        if ($entry === null) {
            return response()->json(['message' => 'Emergency contact not found.'], 404);
        }

        [$province, $city, $barangay] = $this->scopeFromUser($request, $user);
        if ($province !== $entry->province || $city !== $entry->city_municipality || $barangay !== $entry->barangay) {
            return response()->json(['message' => 'You can only delete contacts inside your barangay scope.'], 403);
        }

        $entry->forceFill(['is_active' => false])->save();

        return response()->json(['message' => 'Emergency contact deleted.']);
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

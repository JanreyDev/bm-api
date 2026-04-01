<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;

class CommunityPost extends Model
{
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'user_id',
        'barangay',
        'author_name',
        'message',
        'image_base64',
        'is_official',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_official' => 'boolean',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function scopeInBarangay(Builder $query, string $barangay): Builder
    {
        return $query->where('barangay', trim($barangay));
    }

    public function canManageBy(User $user): bool
    {
        return (int) $this->user_id === (int) $user->id;
    }
}

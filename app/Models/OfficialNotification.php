<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OfficialNotification extends Model
{
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'target_user_id',
        'source_user_id',
        'province',
        'city_municipality',
        'barangay',
        'title',
        'body',
        'category',
        'priority',
        'record_type',
        'record_id',
        'deep_link',
        'metadata_json',
        'is_read',
        'read_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'metadata_json' => 'array',
            'is_read' => 'boolean',
            'read_at' => 'datetime',
        ];
    }

    public function sourceUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'source_user_id');
    }

    public function targetUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'target_user_id');
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class JobHunterInvitation extends Model
{
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'inviter_user_id',
        'talent_user_id',
        'talent_profile_id',
        'barangay',
        'talent_name',
        'talent_mobile',
        'talent_desired_job',
        'inviter_name',
        'inviter_mobile',
        'message',
        'status',
    ];

    public function inviter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'inviter_user_id');
    }

    public function talentUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'talent_user_id');
    }

    public function talentProfile(): BelongsTo
    {
        return $this->belongsTo(JobHunterProfile::class, 'talent_profile_id');
    }

    public function scopeInBarangay(Builder $query, string $barangay): Builder
    {
        return $query->where('barangay', trim($barangay));
    }
}


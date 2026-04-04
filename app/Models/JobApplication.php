<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class JobApplication extends Model
{
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'job_hiring_post_id',
        'applicant_user_id',
        'barangay',
        'job_title',
        'company',
        'posted_by',
        'applicant_name',
        'mobile_number',
        'cover_letter',
        'attachment_name',
        'attachment_base64',
        'status',
    ];

    public function jobPost(): BelongsTo
    {
        return $this->belongsTo(JobHiringPost::class, 'job_hiring_post_id');
    }

    public function applicant(): BelongsTo
    {
        return $this->belongsTo(User::class, 'applicant_user_id');
    }

    public function scopeInBarangay(Builder $query, string $barangay): Builder
    {
        return $query->where('barangay', trim($barangay));
    }
}

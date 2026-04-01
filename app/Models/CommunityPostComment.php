<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CommunityPostComment extends Model
{
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'community_post_id',
        'user_id',
        'author_name',
        'message',
    ];

    public function post(): BelongsTo
    {
        return $this->belongsTo(CommunityPost::class, 'community_post_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}

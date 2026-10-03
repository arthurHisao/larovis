<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['user_id', 'post_id'])]
class PostLike extends Model
{
    /**
     * Usuário que der curtida
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Post que for curtido
     */
    public function post(): BelongsTo
    {
        return $this->belongsTo(Post::class);
    }
}

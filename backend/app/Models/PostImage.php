<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;


#[Fillable(['user_id', 'department_id', 'title', 'content', 'type', 'is_pinned'])]
class PostImage extends Model
{
    public function post(): BelongsTo
    {
        return $this->belongsTo(Post::class);
    }

}

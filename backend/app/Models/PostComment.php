<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable(['user_id', 'post_id', 'parent_id', 'comment'])]
class PostComment extends Model
{

    use SoftDeletes;

    protected $table = 'post_comments';

    /**
     * Gatilhos automáticos do Eloquent
     */
    protected static function booted()
    {
        // Quando o pai sofrer soft delete, dispara soft delete nas respostas
        static::deleting(function ($comment) {
            $comment->replies()->delete();
        });

        // Se restarurar o pai no banco, as respostas voltam juntas
        static::restoring(function ($comment) {
            $comment->replies()->restore();
        });
    }

    /**
     * O autor do comentário
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Post onde o comentário foi feito
     */
    public function post(): BelongsTo
    {
        return $this->belongsTo(Post::class);
    }

    /**
     * Respostas ao comentário pai
     */
    public function replies(): HasMany
    {
        return $this->hasMany(PostComment::class, 'parent_id');
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(PostComment::class, 'parent_id');
    }

    /**
     * 🚀 RELACIONAMENTO RECURSIVO INFINITO
     * Ele carrega os filhos atuais e diz para os filhos carregarem os próprios filhos + autores
     */
    public function repliesRecursive(): HasMany
    {
        return $this->replies()->with(['user', 'repliesRecursive']);
    }

    public function comments(): HasMany
    {
        return $this->hasMany(PostComment::class);
    }
}

<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class PostResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $userId = $request->user()?->id ?? 1;

        return [
            'id' => $this->id,
            'title' => $this->title,
            'content' => $this->content,
            'type' => $this->type,
            'isPinned' => (bool) $this->is_pinned,
            'createdAt' => $this->created_at->diffForHumans(), // Ex: "Há 2 horas"
            'author' => [
                'id' => $this->user->id,
                'name' => $this->user->name,
                'avatar' => $this->user->avatar_url,
                'department' => $this->department?->name ?? 'Geral',
            ],
            'images' => $this->whenLoaded('images', function() {
                return $this->images->map(fn ($image) => [
                    'id' => $image->id,
                    'url' => url(Storage::url($image->path)),
                ]);
            }),
            // Caso já tenha relacionamentos/métodos para likes e comentários:
            // 'likesCount' => $this->likes_count ?? 0,
            // 'commentsCount' => $this->comments_count ?? 0,
            // 'isLiked' => false, // Pode ajustar com auth()->user() no futuro
            'likesCount' => $this->likes_count ?? $this->likes()->count(),
            'commentsCount' => $this->comments_count ?? $this->comments()->count(),
            // Verifica no banco/relacionamento se o usuário logado curtiu este post
            'isLiked' => $userId ? $this->likes()->where('user_id', $userId)->exists() : false,
        ];
    }
}
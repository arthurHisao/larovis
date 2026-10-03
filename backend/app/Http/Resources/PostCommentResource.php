<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PostCommentResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'         => $this->id,
            'postId'     => $this->post_id,
            'mention'    => $this->parent?->user?->name,
            'content'    => $this->comment,
            'createdAt'  => $this->created_at->diffForHumans(), // Transforma em "há 5 min"

            // Transforma a relação 'user' no objeto 'author' que o seu front espera
            'author' => [
                'id'     => $this->user->id,
                'name'   => $this->user->name,
                'avatar' => $this->user->avatar_url,
            ],

            // Mapeia as respostas usando este mesmo Resource (Recursividade)
            'replies'   => PostCommentResource::collection($this->whenLoaded('repliesRecursive')),
            
        ];
    }
}

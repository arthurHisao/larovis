<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ChatUserResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $isGroup = (bool) ($this->is_group ?? false);

        return [
            'key'           => $this->row_key ?? ($isGroup ? "group-{$this->id}" : "user-{$this->id}"),
            'id'            => (int) $this->id,
            'chatId'        => $this->chat_id ? (int) $this->chat_id : null,
            'isGroup'       => $isGroup,
            'name'          => $this->name,
            'email'         => $this->email,
            'avatar'        => $this->avatar,
            'department'    => $isGroup  
                ? "{$this->members_count} membros"
                : ($this->department?->slug ?? $this->department ?? null),
            'memberCount'   => $isGroup ? (int) ($this->members_count ?? 0) : null,
        ];
    }
}

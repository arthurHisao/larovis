<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

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

        $avatar = $this->avatar;
        if ($isGroup && $avatar && !str_starts_with($avatar, 'http://') && !str_starts_with($avatar, 'https://') && !str_starts_with($avatar, '/')) {
            $avatar = Storage::disk('public')->url($avatar);
        }

        return [
            'key'           => $this->row_key ?? ($isGroup ? "group-{$this->id}" : "user-{$this->id}"),
            'id'            => (int) $this->id,
            'chatId'        => $this->chat_id ? (int) $this->chat_id : null,
            'isGroup'       => $isGroup,
            'name'          => $this->name,
            'email'         => $this->email,
            'avatar'        => $avatar,
            'coverImage'    => $isGroup ? $avatar : null,
            'creatorId'     => $isGroup && isset($this->created_by) ? (int) $this->created_by : null,
            'department'    => $isGroup  
                ? "{$this->members_count} membros"
                : ($this->department?->slug ?? $this->department ?? null),
            'memberCount'   => $isGroup ? (int) ($this->members_count ?? 0) : null,
        ];
    }
}

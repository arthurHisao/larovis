<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MessageResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $isRead = false;

        // Calcula isRead via "outro participante" apenas em conversas diretas (1-a-1)
        if (!$this->chat?->is_group) {
            $otherParticipant = $this->chat->users()
                ->whereKeyNot($this->sender_id)
                ->first();

            if ($otherParticipant && $otherParticipant->pivot->last_read_at) {
                $isRead = $this->created_at <= $otherParticipant->pivot->last_read_at;
            }
        }

        return [
            'id'        =>  $this->id,
            'senderId'  =>  $this->sender_id,
            'text'      =>  $this->text,
            'time'      =>  $this->created_at ? $this->created_at->format('H:i') : '',
            'isRead'    =>  $isRead,
            'refer'     =>  $this->parentMessage ? [
                [
                    'referId' => $this->parentMessage->id,
                    'text'    => $this->parentMessage->text,
                    'senderId' => $this->parentMessage->sender_id,
                ]
            ] : []
        ];
    }
}

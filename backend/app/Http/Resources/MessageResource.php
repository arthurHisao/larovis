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
        // $authUserId = $request->user()->id;

        // Pega no outro participante usando a relação do chat de forma direta
        $otherParticipant = $this->chat->users()
            ->whereKeyNot($this->sender_id)
            ->first();
        

        $isRead = false;
        if ($otherParticipant && $otherParticipant->pivot->last_read_at) {
            $isRead = $this->created_at <= $otherParticipant->pivot->last_read_at;
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

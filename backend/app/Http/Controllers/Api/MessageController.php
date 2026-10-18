<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\MessageResource;
use App\Models\Message;
use App\Models\User;
use Illuminate\Http\Request;

class MessageController extends Controller
{
    
    public function index(Request $request) 
    {
        $userId = $request->user()->id;


        $chatUsers = User::where(function ($query) use ($userId) {
            $query->whereHas('receivedMessages', function ($q) use ($userId) {
                 $q->where('sender_id', $userId);
            })
            ->orWhereHas('sentMessages', function ($q) use ($userId) {
                $q->where('receiver_id', $userId);
            });
        })
        ->where('id', '!=', $userId)
        ->get();

        return response()->json($chatUsers);
    }

    public function show(Request $request, User $user) 
    {   
        $authUserId = $request->user()->id;

        $contactId = $user->id;

        $messages = Message::with([
            'sender:id,name,avatar',

            'parentMessage:id,text,sender_id'
        ])
        ->where(function ($query) use ($authUserId, $contactId) {
            $query->where('sender_id', $authUserId)
                ->where('receiver_id', $contactId);
        })
        ->orWhere(function ($query) use ($authUserId, $contactId) {
            $query->where('sender_id', $contactId)
                ->where('receiver_id', $authUserId);
        })
        ->orderBy('created_at', 'asc')
        ->get();

        return MessageResource::collection($messages);
    }
}

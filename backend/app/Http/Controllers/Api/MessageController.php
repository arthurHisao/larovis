<?php

namespace App\Http\Controllers\Api;

use App\Events\MessageSent;
use App\Http\Controllers\Controller;
use App\Http\Resources\MessageResource;
use App\Models\Message;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

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

    public function store(Request $request)
    {
        $validated = $request->validate([
            'receiver_id'  => 'required|exists:users,id',
            'text'         => 'required|string',
            // 'parent_id'    => 'required|exists:messages,id', 
        ]);

        // Cria a mensagem associando o remetente logado
        $message = Message::create([
            'sender_id'    => Auth::id(), 
            'receiver_id'  => $validated['receiver_id'],
            'text'         => $validated['text'],
            'parent_id'    => $validated['parent_id'] ?? null,
        ]);

        Log::info('Mensagem guardada no banco', $message->toArray());

        // Carrega os relacionamentos se necessário (ex: dados do remetente)
        $message->load('sender');

        // 4. Log para confirmar que o broadcast foi acionado
        Log::info('Evento de broadcast disparado para o Reverb!');

        broadcast(new MessageSent($message))->toOthers();
        
        // return response()->json([
        //     'success' => true,
        //     'message' => $message
        // ], 201);
        return (new MessageResource($message))->response()->setStatusCode(201);
    }
}

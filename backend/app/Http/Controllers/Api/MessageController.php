<?php

namespace App\Http\Controllers\Api;

use App\Events\MessageSent;
use App\Http\Controllers\Controller;
use App\Http\Resources\ChatUserResource;
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
        $search = $request->input('search');

        $subQuery = User::query()
            ->select('users.id', 'users.name', 'users.email', 'users.avatar', 'users.department_id', 'users.deleted_at')
            ->where('users.id', '!=', $userId)
            ->when($search, function ($query, $search) {
                $query->where('users.name', 'like', "%{$search}%");
            })
            // Garante uma data válida (nunca NULL) para viabilizar a paginação por cursor
            ->selectSub(function ($query) use ($userId) {
                $query->from('messages')
                    ->selectRaw("COALESCE(MAX(messages.created_at), '1970-01-01 00:00:00')")
                    ->where(function ($q) use ($userId) {
                        $q->whereColumn('messages.receiver_id', 'users.id')
                            ->where('messages.sender_id', $userId)
                            ->orWhere(function ($sub) use ($userId) {
                                $sub->whereColumn('messages.sender_id', 'users.id')
                                    ->where('messages.receiver_id', $userId);
                            });
                    });
            }, 'last_interaction_at');

        $chatUsers = User::query()
            ->fromSub($subQuery, 'users')
            ->with('department:id,slug')
            // 1. Quem tem mensagens recentes fica no topo
            ->orderBy('last_interaction_at', 'desc')
            // 2. Quem não tem conversa (data 1970) fica ordenado por ordem alfabética
            ->orderBy('name', 'asc')
            // 3. Desempate determinístico para o cursor
            ->orderBy('id', 'asc')
            ->cursorPaginate(15);
            
        return ChatUserResource::collection($chatUsers);
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
            'parent_id'    => 'nullable|exists:messages,id', 
        ]);

        // Cria a mensagem associando o remetente logado
        $message = Message::create([
            'sender_id'    => Auth::id(), 
            'receiver_id'  => $validated['receiver_id'],
            'text'         => $validated['text'],
            'parent_id'    => $validated['parent_id'] ?? null,
        ]);

        // Carrega os relacionamentos se necessário (ex: dados do remetente)
        $message->load(['sender', 'parentMessage:id,text,sender_id']);

        // Dispara o Emit
        broadcast(new MessageSent($message))->toOthers();

        return (new MessageResource($message))->response()->setStatusCode(201);
    }
}

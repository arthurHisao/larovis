<?php

namespace App\Http\Controllers\Api;

use App\Events\MessageRead;
use App\Events\MessageSent;
use App\Http\Controllers\Controller;
use App\Http\Resources\ChatUserResource;
use App\Http\Resources\MessageResource;
use App\Models\Chat;
use App\Models\Message;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class MessageController extends Controller
{
    public function index(Request $request)
    {
        $userId = $request->user()->id;
        $search = trim((string) $request->input('search', ''));

        $subQuery = User::query()
            ->select(
                'users.id',
                'users.name',
                'users.email',
                'users.avatar',
                'users.department_id',
                'users.deleted_at'
            )
            ->where('users.id', '!=', $userId)
            ->when($search, function ($query, $search) {
                $query->where('users.name', 'ilike', "%{$search}%");
            })
            ->selectSub(function ($query) use ($userId) {
                $query->from('messages')
                    ->join('chats', 'chats.id', '=', 'messages.chat_id')
                    ->join('chat_user as cu_auth', function ($join) use ($userId) {
                        $join->on('cu_auth.chat_id', '=', 'chats.id')
                            ->where('cu_auth.user_id', '=', $userId);
                    })
                    ->join('chat_user as cu_contact', function ($join) {
                        $join->on('cu_contact.chat_id', '=', 'chats.id')
                            ->whereColumn('cu_contact.user_id', 'users.id');
                    })
                    ->where('chats.is_group', false)
                    ->selectRaw("COALESCE(MAX(messages.created_at), '1970-01-01 00:00:00')");
            }, 'last_interaction_at');

        $chatUsers = User::query()
            ->fromSub($subQuery, 'users')
            ->with('department:id,name,slug')
            ->orderBy('last_interaction_at', 'desc')
            ->orderBy('name', 'asc')
            ->orderBy('id', 'asc')
            ->cursorPaginate(15);

        return ChatUserResource::collection($chatUsers);
    }

    public function show(Request $request, User $user)
    {
        $authUserId = $request->user()->id;
        $chat = $this->findOrCreateDirectChat($authUserId, $user->id);

        $messages = $chat->messages()
            ->with([
                'sender:id,name,avatar',
                'parentMessage:id,text,sender_id',
            ])
            ->orderBy('created_at', 'asc')
            ->orderBy('id', 'asc')
            ->get();

        return MessageResource::collection($messages);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'receiver_id' => 'required|exists:users,id',
            'text'        => 'required|string',
            'parent_id'   => 'nullable|exists:messages,id',
        ]);

        $authUserId = Auth::id();
        $chat = $this->findOrCreateDirectChat($authUserId, (int) $validated['receiver_id']);

        $message = Message::create([
            'chat_id'   => $chat->id,
            'sender_id' => $authUserId,
            'text'      => $validated['text'],
            'parent_id' => $validated['parent_id'] ?? null,
        ]);

        $message->load(['sender', 'parentMessage:id,text,sender_id']);

        broadcast(new MessageSent($message))->toOthers();

        return (new MessageResource($message))->response()->setStatusCode(201);
    }

    public function ensureRoom(Request $request, User $user): JsonResponse
    {
        $authUserId = $request->user()->id;
        $chat = $this->findOrCreateDirectChat($authUserId, $user->id);

        return response()->json([
            'chat_id' => $chat->id,
        ]);
    }

    private function findOrCreateDirectChat(int $userAId, int $userBId): Chat
    {
        $chat = Chat::query()
            ->where('is_group', false)
            ->whereHas('users', fn ($q) => $q->where('users.id', $userAId))
            ->whereHas('users', fn ($q) => $q->where('users.id', $userBId))
            ->first();

        if (!$chat) {
            $chat = DB::transaction(function () use ($userAId, $userBId) {
                $newChat = Chat::create(['is_group' => false]);
                $newChat->users()->syncWithoutDetaching([$userAId, $userBId]);

                return $newChat;
            });
        }

        return $chat;
    }

    public function markAsRead(Request $request, Chat $chat): JsonResponse
    {
        $userId = $request->user()->id;

        $chat->users()->updateExistingPivot($userId, [
            'last_read_at' => now()
        ]);

        broadcast(new MessageRead($chat->id, $userId))->toOthers();

        return response()->json(['success' => true]);
    }
}
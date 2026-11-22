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

        // 1. Conversas Diretas (Usuários)
        $directUsersQuery = User::query()
            ->leftJoin('departments', 'departments.id', '=', 'users.department_id')
            ->selectRaw("
                CONCAT('user-', users.id) as row_key,
                users.id as id,
                NULL::bigint as chat_id,
                false as is_group,
                users.name as name,
                users.email as email,
                users.avatar as avatar,
                departments.slug as department,
                0::bigint as members_count
            ")
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

        // 2. Conversas em Grupo do usuário autenticado
        $groupsQuery = Chat::query()
            ->join('chat_user as cu_auth', function ($join) use ($userId) {
                $join->on('cu_auth.chat_id', '=', 'chats.id')
                    ->where('cu_auth.user_id', '=', $userId);
            })
            ->selectRaw("
                CONCAT('group-', chats.id) as row_key,
                chats.id as id,
                chats.id as chat_id,
                true as is_group,
                COALESCE(chats.name, 'Grupo') as name,
                NULL::varchar as email,
                NULL::varchar as avatar,
                NULL::varchar as department,
                (SELECT COUNT(*) FROM chat_user WHERE chat_user.chat_id = chats.id) as members_count,
                COALESCE(
                    (SELECT MAX(messages.created_at) FROM messages WHERE messages.chat_id = chats.id),
                    chats.created_at,
                    '1970-01-01 00:00:00'
                ) as last_interaction_at
            ")
            ->where('chats.is_group', true)
            ->when($search, function ($query, $search) {
                $query->where('chats.name', 'ilike', "%{$search}%");
            });

        $unioned = $directUsersQuery->unionAll($groupsQuery);

        $conversations = DB::query()
            ->fromSub($unioned, 'conversations')
            ->orderBy('last_interaction_at', 'desc')
            ->orderBy('name', 'asc')
            ->orderBy('row_key', 'asc')
            ->cursorPaginate(15);

        return ChatUserResource::collection($conversations);


        // Funcional
        // $subQuery = User::query()
        //     ->select(
        //         'users.id',
        //         'users.name',
        //         'users.email',
        //         'users.avatar',
        //         'users.department_id',
        //         'users.deleted_at'
        //     )
        //     ->where('users.id', '!=', $userId)
        //     ->when($search, function ($query, $search) {
        //         $query->where('users.name', 'ilike', "%{$search}%");
        //     })
        //     ->selectSub(function ($query) use ($userId) {
        //         $query->from('messages')
        //             ->join('chats', 'chats.id', '=', 'messages.chat_id')
        //             ->join('chat_user as cu_auth', function ($join) use ($userId) {
        //                 $join->on('cu_auth.chat_id', '=', 'chats.id')
        //                     ->where('cu_auth.user_id', '=', $userId);
        //             })
        //             ->join('chat_user as cu_contact', function ($join) {
        //                 $join->on('cu_contact.chat_id', '=', 'chats.id')
        //                     ->whereColumn('cu_contact.user_id', 'users.id');
        //             })
        //             ->where('chats.is_group', false)
        //             ->selectRaw("COALESCE(MAX(messages.created_at), '1970-01-01 00:00:00')");
        //     }, 'last_interaction_at');

        // $chatUsers = User::query()
        //     ->fromSub($subQuery, 'users')
        //     ->with('department:id,name,slug')
        //     ->orderBy('last_interaction_at', 'desc')
        //     ->orderBy('name', 'asc')
        //     ->orderBy('id', 'asc')
        //     ->cursorPaginate(15);

        // return ChatUserResource::collection($chatUsers);
    }

    public function show(Request $request, User $user)
    {
        $authUserId = $request->user()->id;
        $chat = $this->findOrCreateDirectChat($authUserId, $user->id);

        $perPage = min((int) $request->input('per_page', 25), 100);

        $messages = $chat->messages()
            ->with([
                'sender:id,name,avatar',
                'parentMessage:id,text,sender_id',
            ])
            ->orderBy('created_at', 'desc')
            ->orderBy('id', 'desc')
            ->cursorPaginate($perPage);

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


    public function storeGroup(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name'         => 'required|string|max:120',
            'member_ids'   => 'required|array|min:1',
            'member_ids.*' => 'required|exists:users,id|distinct',
        ]);

        $authUserId = $request->user()->id;
        $participantIds = collect($validated['member_ids'])
            ->push($authUserId)
            ->unique()
            ->values()
            ->all();

        $chat = DB::transaction(function () use ($validated, $participantIds) {
            $groupChat = Chat::create([
                'name'     => $validated['name'],
                'is_group' => true,
            ]);

            $groupChat->users()->sync($participantIds);

            return $groupChat;
        });

        return response()->json([
            'id'       => $chat->id,
            'name'     => $chat->name,
            'is_group' => (bool) $chat->is_group,
        ], 201);
    }


    public function showGroup(Request $request, Chat $chat)
    {
        $authUserId = $request->user()->id;

        $isMember = $chat->users()
            ->where('users.id', $authUserId)
            ->exists();

        abort_unless($isMember, 403, 'Você não participa desta conversa.');

        $perPage = min((int) $request->input('per_page', 25), 100);

        $messages = $chat->messages()
            ->with([
                'chat',
                'sender:id,name,avatar',
                'parentMessage:id,text,sender_id',
            ])
            ->orderBy('created_at', 'desc')
            ->orderBy('id', 'desc')
            ->cursorPaginate($perPage);

        return MessageResource::collection($messages);
    }

    public function sendGroupMessage(Request $request, Chat $chat)
    {
        $validated = $request->validate([
            'text'      => 'required|string',
            'parent_id' => 'nullable|exists:messages,id',
        ]);

        $authUserId = Auth::id();

        $isMember = $chat->users()
            ->where('users.id', $authUserId)
            ->exists();

        abort_unless($isMember, 403, 'Você não participa deste grupo.');

        $message = Message::create([
            'chat_id'   => $chat->id,
            'sender_id' => $authUserId,
            'text'      => $validated['text'],
            'parent_id' => $validated['parent_id'] ?? null,
        ]);

        $message->load(['chat', 'sender', 'parentMessage:id,text,sender_id']);

        broadcast(new MessageSent($message))->toOthers();

        return (new MessageResource($message))->response()->setStatusCode(201);
    }
}
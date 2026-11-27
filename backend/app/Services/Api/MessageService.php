<?php

namespace App\Services\Api;

use App\Events\MessageRead;
use App\Events\MessageSent;
use App\Models\Chat;
use App\Models\Message;
use App\Models\User;
use Illuminate\Contracts\Pagination\CursorPaginator;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class MessageService
{
    public function getConversations(int $userId, string $search): CursorPaginator
    {
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
                NULL::bigint as created_by,
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
                chats.cover_image as avatar,
                NULL::varchar as department,
                chats.created_by as created_by,
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

        return DB::query()
            ->fromSub($unioned, 'conversations')
            ->orderBy('last_interaction_at', 'desc')
            ->orderBy('name', 'asc')
            ->orderBy('row_key', 'asc')
            ->cursorPaginate(15);
    }

    public function findOrCreateDirectChat(int $userAId, int $userBId): Chat
    {
        $chat = Chat::query()
            ->where('is_group', false)
            ->whereHas('users', fn ($query) => $query->where('users.id', $userAId))
            ->whereHas('users', fn ($query) => $query->where('users.id', $userBId))
            ->first();

        if (! $chat) {
            $chat = DB::transaction(function () use ($userAId, $userBId) {
                $newChat = Chat::create(['is_group' => false]);
                $newChat->users()->syncWithoutDetaching([$userAId, $userBId]);

                return $newChat;
            });
        }

        return $chat;
    }

    public function getMessages(Chat $chat, int $perPage, bool $includeChat = false): CursorPaginator
    {
        $query = $chat->messages()->with([
            ...($includeChat ? ['chat'] : []),
            'sender:id,name,avatar',
            'parentMessage:id,text,sender_id',
        ]);

        return $query
            ->orderBy('created_at', 'desc')
            ->orderBy('id', 'desc')
            ->cursorPaginate($perPage);
    }

    public function createDirectMessage(Chat $chat, int $senderId, array $validated): Message
    {
        $message = Message::create([
            'chat_id' => $chat->id,
            'sender_id' => $senderId,
            'text' => $validated['text'],
            'parent_id' => $validated['parent_id'] ?? null,
        ]);

        $message->load(['sender', 'parentMessage:id,text,sender_id']);

        broadcast(new MessageSent($message))->toOthers();

        return $message;
    }

    public function markAsRead(Chat $chat, int $userId): void
    {
        $chat->users()->updateExistingPivot($userId, [
            'last_read_at' => now(),
        ]);

        broadcast(new MessageRead($chat->id, $userId))->toOthers();
    }

    public function createGroup(string $name, array $memberIds, int $creatorId): Chat
    {
        $participantIds = collect($memberIds)
            ->push($creatorId)
            ->unique()
            ->values()
            ->all();

        return DB::transaction(function () use ($name, $participantIds, $creatorId) {
            $chat = Chat::create([
                'name' => $name,
                'is_group' => true,
                'created_by' => $creatorId,
            ]);

            $chat->users()->sync($participantIds);

            return $chat;
        });
    }

    public function isMember(Chat $chat, int $userId): bool
    {
        return $chat->users()->where('users.id', $userId)->exists();
    }

    public function sendGroupMessage(Chat $chat, int $senderId, array $validated): Message
    {
        $message = Message::create([
            'chat_id' => $chat->id,
            'sender_id' => $senderId,
            'text' => $validated['text'],
            'parent_id' => $validated['parent_id'] ?? null,
        ]);

        $message->load(['chat', 'sender', 'parentMessage:id,text,sender_id']);

        broadcast(new MessageSent($message))->toOthers();

        return $message;
    }

    public function getGroupMembers(Chat $chat): Collection
    {
        return $chat->users()
            ->select('users.id', 'users.name', 'users.email', 'users.avatar')
            ->orderBy('users.name')
            ->get()
            ->map(fn ($user) => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'avatar' => $user->avatar,
            ]);
    }

    public function updateGroup(
        Chat $chat,
        int $userId,
        string $name,
        array $memberIds,
        ?UploadedFile $coverImage,
        bool $removeCoverImage
    ): void {
        $memberIds = collect($memberIds)
            ->push($userId)
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values()
            ->all();

        DB::transaction(function () use ($chat, $name, $memberIds, $coverImage, $removeCoverImage) {
            $updates = [
                'name' => trim($name),
            ];

            if ($coverImage) {
                if ($chat->cover_image) {
                    Storage::disk('public')->delete($chat->cover_image);
                }

                $updates['cover_image'] = $coverImage->store('groups/covers', 'public');
            } elseif ($removeCoverImage) {
                if ($chat->cover_image) {
                    Storage::disk('public')->delete($chat->cover_image);
                }

                $updates['cover_image'] = null;
            }

            $chat->update($updates);
            $chat->users()->sync($memberIds);
        });
    }

    public function deleteGroup(Chat $chat): void
    {
        DB::transaction(function () use ($chat) {
            if ($chat->cover_image) {
                Storage::disk('public')->delete($chat->cover_image);
            }

            $chat->messages()->delete();
            $chat->users()->detach();
            $chat->delete();
        });
    }
}

<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ChatUserResource;
use App\Http\Resources\MessageResource;
use App\Models\Chat;
use App\Models\User;
use App\Services\Api\MessageService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class MessageController extends Controller
{
    public function __construct(private MessageService $messageService) {}

    public function index(Request $request)
    {
        $conversations = $this->messageService->getConversations(
            $request->user()->id,
            trim((string) $request->input('search', ''))
        );

        return ChatUserResource::collection($conversations);
    }

    public function show(Request $request, User $user)
    {
        $chat = $this->messageService->findOrCreateDirectChat(
            $request->user()->id,
            $user->id
        );

        $perPage = min((int) $request->input('per_page', 25), 100);
        $messages = $this->messageService->getMessages($chat, $perPage);

        return MessageResource::collection($messages);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'receiver_id' => 'required|exists:users,id',
            'text' => 'required|string',
            'parent_id' => 'nullable|exists:messages,id',
        ]);

        $authUserId = Auth::id();
        $chat = $this->messageService->findOrCreateDirectChat(
            $authUserId,
            (int) $validated['receiver_id']
        );
        $message = $this->messageService->createDirectMessage(
            $chat,
            $authUserId,
            $validated
        );

        return (new MessageResource($message))->response()->setStatusCode(201);
    }

    public function ensureRoom(Request $request, User $user): JsonResponse
    {
        $chat = $this->messageService->findOrCreateDirectChat(
            $request->user()->id,
            $user->id
        );

        return response()->json([
            'chat_id' => $chat->id,
        ]);
    }

    public function markAsRead(Request $request, Chat $chat): JsonResponse
    {
        $userId = $request->user()->id;
        $this->messageService->markAsRead($chat, $userId);

        return response()->json(['success' => true]);
    }

    public function storeGroup(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:120',
            'member_ids' => 'required|array|min:1',
            'member_ids.*' => 'required|exists:users,id|distinct',
        ]);

        $chat = $this->messageService->createGroup(
            $validated['name'],
            $validated['member_ids'],
            $request->user()->id
        );

        return response()->json([
            'id' => $chat->id,
            'name' => $chat->name,
            'is_group' => (bool) $chat->is_group,
            'creatorId' => $chat->created_by,
        ], 201);
    }

    public function showGroup(Request $request, Chat $chat)
    {
        abort_unless(
            $this->messageService->isMember($chat, $request->user()->id),
            403,
            'Você não participa desta conversa.'
        );

        $perPage = min((int) $request->input('per_page', 25), 100);
        $messages = $this->messageService->getMessages($chat, $perPage, true);

        return MessageResource::collection($messages);
    }

    public function sendGroupMessage(Request $request, Chat $chat)
    {
        $validated = $request->validate([
            'text' => 'required|string',
            'parent_id' => 'nullable|exists:messages,id',
        ]);

        $authUserId = Auth::id();

        abort_unless(
            $this->messageService->isMember($chat, $authUserId),
            403,
            'Você não participa deste grupo.'
        );

        $message = $this->messageService->sendGroupMessage($chat, $authUserId, $validated);

        return (new MessageResource($message))->response()->setStatusCode(201);
    }

    public function groupMembers(Request $request, Chat $chat)
    {
        abort_unless($chat->is_group, 404, 'Esta conversa não é um grupo');

        abort_unless(
            $this->messageService->isMember($chat, $request->user()->id),
            403,
            'Você não participa deste grupo.'
        );

        return response()->json([
            'data' => $this->messageService->getGroupMembers($chat),
        ]);
    }

    public function updateGroup(Request $request, Chat $chat)
    {
        abort_unless($chat->is_group, 404, 'Esta conversa não é um grupo.');

        $authUser = $request->user();

        abort_unless(
            $this->messageService->isMember($chat, $authUser->id),
            403,
            'Você não participa deste grupo.'
        );

        $validated = $request->validate([
            'name' => ['required', 'string', 'min:1', 'max:120'],
            'member_ids' => ['required', 'array', 'min:1'],
            'member_ids.*' => ['integer', 'distinct', Rule::exists('users', 'id')],
            'cover_image' => ['nullable', 'image', 'mimes:jpeg,png,gif,webp', 'max:5120'],
            'remove_cover_image' => ['nullable', 'boolean'],
        ]);

        $removeCoverImage = filter_var(
            $validated['remove_cover_image'] ?? false,
            FILTER_VALIDATE_BOOLEAN
        );

        $this->messageService->updateGroup(
            $chat,
            $authUser->id,
            $validated['name'],
            $validated['member_ids'],
            $request->hasFile('cover_image') ? $request->file('cover_image') : null,
            $removeCoverImage
        );

        $chat->load('users');

        $coverUrl = $chat->cover_image
            ? Storage::disk('public')->url($chat->cover_image)
            : null;

        return response()->json([
            'data' => [
                'id' => $chat->id,
                'chat_id' => $chat->id,
                'key' => "group-{$chat->id}",
                'isGroup' => true,
                'name' => $chat->name,
                'avatar' => $coverUrl,
                'coverImage' => $coverUrl,
                'creatorId' => $chat->created_by,
                'membersCount' => $chat->users->count(),
                'department' => "{$chat->users->count()} membros",
            ],
        ]);
    }

    public function deleteGroup(Request $request, Chat $chat)
    {
        abort_unless($chat->is_group, 404, 'Esta conversa não é um grupo.');

        $authUser = $request->user();

        if (! is_null($chat->created_by) && (int) $chat->created_by !== (int) $authUser->id) {
            abort(403, 'Apenas o criador do grupo pode deletá-lo.');
        }

        abort_unless(
            $this->messageService->isMember($chat, $authUser->id),
            403,
            'Você não participa deste grupo.'
        );

        $chatId = $chat->id;
        $this->messageService->deleteGroup($chat);

        return response()->json([
            'message' => 'Grupo deletado com sucesso.',
            'id' => $chatId,
        ]);
    }
}

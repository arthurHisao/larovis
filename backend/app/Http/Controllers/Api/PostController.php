<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\PostCommentResource;
use App\Http\Resources\PostResource;
use App\Models\Post;
use App\Models\PostComment;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class PostController extends Controller
{
    /**
     * Retorna a lista de posts para o feed
     */
    public function index()
    {
        $posts = Post::with(['user', 'department', 'images'])
            ->orderBy('is_pinned', 'desc') // Fixados primeiro
            ->latest()                     // Mais recentes em seguida
            ->get();

        return PostResource::collection($posts);
    }

    public function show(Post $post) 
    {
        $post->load(['user', 'department', 'images']);

        return new PostResource($post);
    }

    public function getPostComments(Request $request, int $postId): AnonymousResourceCollection 
    {
        $comments = PostComment::where('post_id', $postId)
            ->whereNull('parent_id')
            ->with([
                'user', // Carrega autor do comentário principal
                'parent.user',
                'repliesRecursive',
            ])
            ->latest()
            ->get();
        return PostCommentResource::collection($comments);
    }

    public function toggleLike(Post $post, Request $request) 
    {
        /**
         * @todo Substituir pelo id do usuário quando implementar a autenticação */
        $userId = $request->user()?->id ?? 1;

        $like = $post->likes()->where('user_id', $userId)->first();

        if ($like) {
            $like->delete();
            return response()->json(['message' => 'Unliked', 'isLiked' => false]);
        }

        $post->likes()->create(['user_id' => $userId]);

        return response()->json(['message' => 'Liked', 'isLiked' => true]);
    }

    public function storeComment(Request $request, Post $post) {
        $validated = $request->validate([
            'content'    => 'required|string|max:1000',
            'parent_id'  => 'nullable|exists:comments,id',
        ]);

        $comment = $post->comments()->create([
            'comment'   => $validated['content'],
            'parent_id' => $validated['parent_id'] ?? null,
            'user_id'   => $request->user()->id, 
        ]);

        return new PostCommentResource($comment->load('user'));

    }

    // Ativa as travas de autorização
    use AuthorizesRequests;

    public function destroyComment(Request $request, PostComment $comment) 
    {
        if ($comment->user_id != $request->user()->id) {
            return response()->json(['message' => 'Você não tem permissão para deletar este comentário.'], 403);
        }

        $comment->delete();

        return response()->json([
            'message' => 'Comentário e respostas removidas com sucesso!'
        ], 200);
    }
}


<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\PostCommentResource;
use App\Http\Resources\PostResource;
use App\Models\Department;
use App\Models\Post;
use App\Models\PostComment;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Str;

class PostController extends Controller
{
    /**
     * Retorna a lista de posts para o feed com regras de visibilidade
     */
    public function index(Request $request)
    {
        $user = $request->user();
        $filter = $request->query('filter');

        $posts = Post::with(['user', 'department', 'images'])
            // Regra de Visibilidade:
            // O usuário visualiza posts gerais (sem departamento)
            // OU posts do seu próprio departamento
            // OU posts criados por ele mesmo
            ->where(function ($query) use ($user) {
                $query->whereNull('department_id');
                if ($user && $user->department_id) {
                    $query->orWhere('department_id', $user->department_id);
                }
                if ($user) {
                    $query->orWhere('user_id', $user->id);
                }
            })
            // Filtros da barra superior (Todos, Comunicados, Departamentos)
            ->when($filter === 'official', function ($q) {
                $q->where('type', 'official');
            })
            ->when($filter === 'department', function ($q) {
                $q->whereNotNull('department_id');
            })
            ->orderBy('is_pinned', 'desc') // Fixados primeiro
            ->latest()                     // Mais recentes em seguida
            ->get();

        return PostResource::collection($posts);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'content'               => 'required|string',
            'title'                 => 'nullable|string|max:255',
            'post_type'             => 'nullable|string',
            'type'                  => 'nullable|string',
            'department_id'         => 'nullable',
            'department_visibility' => 'nullable',
            'images.*'              => 'nullable|image|max:10240',
        ]);

        // Trata visibilidade do departamento (todos = null, id numérico ou slug)
        $departmentInput = $request->input('department_id') ?? $request->input('department_visibility');
        $departmentId = null;

        if ($departmentInput && $departmentInput !== 'todos') {
            if (is_numeric($departmentInput)) {
                $departmentId = (int) $departmentInput;
            } else {
                $departmentId = Department::where('slug', $departmentInput)->value('id');
            }
        }

        // Mapeia os tipos do select para o enum do banco ('official' ou 'casual')
        $typeInput = $request->input('post_type') ?? $request->input('type');
        $type = match ($typeInput) {
            'comunicado', 'official' => 'official',
            default => 'casual',
        };

        // Se o título não for enviado, gera um fallback curto com base no conteúdo
        $title = $request->input('title')
            ?: Str::limit(strip_tags($request->input('content')), 60, '...');

        $post = Post::create([
            'user_id'       => $request->user()->id,
            'department_id' => $departmentId,
            'title'         => $title,
            'content'       => $validated['content'],
            'type'          => $type,
            'is_pinned'     => false,
        ]);

        // Upload e gravação das imagens
        if ($request->hasFile('images')) {
            foreach ($request->file('images') as $index => $imageFile) {
                $path = $imageFile->store('posts', 'public');
                $post->images()->create([
                    'path'  => $path,
                    'order' => $index,
                ]);
            }
        }

        $post->load(['user', 'department', 'images']);

        return (new PostResource($post))->response()->setStatusCode(201);
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
                'parent.id',
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
        $userId = $request->user()->id;

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
            'parent_id'  => 'nullable|exists:post_comments,id',
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


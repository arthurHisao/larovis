<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\PostCommentResource;
use App\Http\Resources\PostResource;
use App\Models\Post;
use App\Models\PostComment;
use App\Services\Api\PostService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class PostController extends Controller
{
    use AuthorizesRequests;

    public function __construct(private PostService $postService) {}

    public function index(Request $request)
    {
        $posts = $this->postService->getVisiblePosts(
            $request->user(),
            $request->query('filter')
        );

        return PostResource::collection($posts);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'content' => 'required|string',
            'title' => 'nullable|string|max:255',
            'post_type' => 'nullable|string',
            'type' => 'nullable|string',
            'department_id' => 'nullable',
            'department_visibility' => 'nullable',
            'images.*' => 'nullable|image|max:10240',
        ]);

        $images = $request->hasFile('images')
            ? $request->file('images')
            : [];

        $post = $this->postService->createPost(
            $request->user(),
            $validated,
            $request->input('department_id') ?? $request->input('department_visibility'),
            $request->input('post_type') ?? $request->input('type'),
            $request->input('title'),
            $images
        );

        return (new PostResource($post))->response()->setStatusCode(201);
    }

    public function show(Post $post)
    {
        return new PostResource($this->postService->getPost($post));
    }

    public function destroyPost(Request $request, Post $post)
    {
        if ($request->user()->id !== $post->user_id) {
            abort(403, 'Ação não autorizada');
        }

        $this->postService->deletePost($post);
    }

    public function updatePost(Request $request, Post $post)
    {
        if ($request->user()->id !== $post->user_id) {
            abort(403, 'Ação não autorizada');
        }

        $validated = $request->validate([
            'content' => 'required|string',
            'title' => 'nullable|string|max:255',
            'post_type' => 'nullable|string',
            'type' => 'nullable|string',
            'department_id' => 'nullable',
            'department_visibility' => 'nullable',
            'images.*' => 'nullable|image|max:10240',
        ]);

        $this->postService->updatePost($post, $validated);

        return response()->json([
            'message' => 'Post atualizado com sucesso',
            'post' => $post,
        ]);
    }

    public function getPostComments(Request $request, int $postId): AnonymousResourceCollection
    {
        return PostCommentResource::collection(
            $this->postService->getTopLevelComments($postId)
        );
    }

    public function toggleLike(Post $post, Request $request)
    {
        $isLiked = $this->postService->toggleLike($post, $request->user()->id);

        return response()->json([
            'message' => $isLiked ? 'Liked' : 'Unliked',
            'isLiked' => $isLiked,
        ]);
    }

    public function storeComment(Request $request, Post $post)
    {
        $validated = $request->validate([
            'content' => 'required|string|max:1000',
            'parent_id' => 'nullable|exists:post_comments,id',
        ]);

        $comment = $this->postService->createComment(
            $post,
            $validated,
            $request->user()->id
        );

        return new PostCommentResource($comment->load('user'));
    }

    public function destroyComment(Request $request, PostComment $comment)
    {
        if ($comment->user_id != $request->user()->id) {
            return response()->json(['message' => 'Você não tem permissão para deletar este comentário.'], 403);
        }

        $this->postService->deleteComment($comment);

        return response()->json([
            'message' => 'Comentário e respostas removidas com sucesso!',
        ], 200);
    }
}

<?php

namespace App\Services\Api;

use App\Models\Department;
use App\Models\Post;
use App\Models\PostComment;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;

class PostService
{
    public function getVisiblePosts(?User $user, mixed $filter): Collection
    {
        return Post::with(['user', 'department', 'images'])
            ->where(function ($query) use ($user) {
                $query->whereNull('department_id');
                if ($user && $user->department_id) {
                    $query->orWhere('department_id', $user->department_id);
                }
                if ($user) {
                    $query->orWhere('user_id', $user->id);
                }
            })
            ->when($filter === 'official', function ($query) {
                $query->where('type', 'official');
            })
            ->when($filter === 'department', function ($query) {
                $query->whereNotNull('department_id');
            })
            ->orderBy('is_pinned', 'desc')
            ->latest()
            ->get();
    }

    /**
     * @param  array<string, mixed>  $validated
     * @param  array<int, UploadedFile>  $images
     */
    public function createPost(
        User $user,
        array $validated,
        mixed $departmentInput,
        mixed $typeInput,
        mixed $requestedTitle,
        array $images
    ): Post {
        $departmentId = null;

        if ($departmentInput && $departmentInput !== 'todos') {
            if (is_numeric($departmentInput)) {
                $departmentId = (int) $departmentInput;
            } else {
                $departmentId = Department::where('slug', $departmentInput)->value('id');
            }
        }

        $type = match ($typeInput) {
            'comunicado', 'official' => 'official',
            default => 'casual',
        };

        $title = $requestedTitle
            ?: Str::limit(strip_tags($validated['content']), 60, '...');

        $post = Post::create([
            'user_id' => $user->id,
            'department_id' => $departmentId,
            'title' => $title,
            'content' => $validated['content'],
            'type' => $type,
            'is_pinned' => false,
        ]);

        foreach ($images as $index => $imageFile) {
            $path = $imageFile->store('posts', 'public');
            $post->images()->create([
                'path' => $path,
                'order' => $index,
            ]);
        }

        return $post->load(['user', 'department', 'images']);
    }

    public function getPost(Post $post): Post
    {
        return $post->load(['user', 'department', 'images']);
    }

    public function deletePost(Post $post): void
    {
        $post->delete();
    }

    /**
     * @param  array<string, mixed>  $validated
     */
    public function updatePost(Post $post, array $validated): void
    {
        $post->update($validated);
    }

    public function getTopLevelComments(int $postId): Collection
    {
        return PostComment::where('post_id', $postId)
            ->whereNull('parent_id')
            ->with([
                'user',
                'parent.id',
                'parent.user',
                'repliesRecursive',
            ])
            ->latest()
            ->get();
    }

    public function toggleLike(Post $post, int $userId): bool
    {
        $like = $post->likes()->where('user_id', $userId)->first();

        if ($like) {
            $like->delete();

            return false;
        }

        $post->likes()->create(['user_id' => $userId]);

        return true;
    }

    /**
     * @param  array<string, mixed>  $validated
     */
    public function createComment(Post $post, array $validated, int $userId): PostComment
    {
        return $post->comments()->create([
            'comment' => $validated['content'],
            'parent_id' => $validated['parent_id'] ?? null,
            'user_id' => $userId,
        ]);
    }

    public function deleteComment(PostComment $comment): void
    {
        $comment->delete();
    }
}

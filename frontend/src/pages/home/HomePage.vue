<script setup lang="ts">
import { onMounted, ref } from "vue";
import { useRouter } from "vue-router";

import PostCard from "@/entities/post/ui/PostCard.vue";
// import { mockPosts } from '@/entities/post/model/mock-posts'
import type { Post } from "@/entities/post/model/types";

import { Button } from "@/shared/ui/button";
import { Textarea } from "@/shared/ui/textarea";

import ChatWidget from "@/widgets/chat-widget/ui/ChatWidget.vue";

import { postService } from "@/entities/post/api/post-service";

const router = useRouter();

const handleCreatePost = () => {
  router.push({ name: "create-post" });
};

const posts = ref<Post[]>();
const isLoading = ref(true);
const activeFilter = ref<"all" | "official" | "department">("all");
const editingPost = ref<Post | null>(null);
const editContent = ref("");
const isUpdatingPost = ref(false);
const actionError = ref<string | null>(null);

// Ação de simulação para o Like
const handleToggleLike = async (postId: number) => {
  const like = await postService.toggleLike(postId);
  const post = posts?.value?.find((p) => p.id === postId);
  if (post) {
    post.isLiked = !post.isLiked;
    post.likesCount += post.isLiked ? 1 : -1;
  }
};

const handleDeletePost = async (postId: number) => {
  actionError.value = null;

  try {
    await postService.deletePost(postId);
    posts.value = posts.value?.filter((post) => post.id !== postId) ?? [];
  } catch (error) {
    console.error("Erro ao excluir publicação:", error);
    actionError.value =
      "Não foi possível excluir a publicação. Tente novamente.";
  }
};

const handleEditPost = (post: Post) => {
  actionError.value = null;
  editingPost.value = post;
  editContent.value = post.content;
};

const handleCancelEdit = () => {
  if (isUpdatingPost.value) return;
  editingPost.value = null;
  editContent.value = "";
  actionError.value = null;
};

const handleUpdatePost = async () => {
  const post = editingPost.value;
  const content = editContent.value.trim();

  if (!post || !content) {
    actionError.value = "O conteúdo da publicação não pode ficar vazio.";
    return;
  }

  actionError.value = null;
  isUpdatingPost.value = true;

  try {
    await postService.updatePost(post.id, { content });
    const currentPost = posts.value?.find((item) => item.id === post.id);
    if (currentPost) currentPost.content = content;
    editingPost.value = null;
    editContent.value = "";
  } catch (error) {
    console.error("Erro ao atualizar publicação:", error);
    actionError.value =
      "Não foi possível atualizar a publicação. Tente novamente.";
  } finally {
    isUpdatingPost.value = false;
  }
};

const handleFilterChange = (filter: "all" | "official" | "department") => {
  activeFilter.value = filter;
  fetchPosts(filter);
};

// Buscar posts do banco do Laravel ao carregar a página
const fetchPosts = async (filter?: string) => {
  try {
    isLoading.value = true;
    posts.value = await postService.getAll(filter ?? activeFilter.value);
  } catch (error) {
    console.error("Erro ao carregar publicações:", error);
  } finally {
    isLoading.value = false;
  }
};

onMounted(() => {
  fetchPosts();
});
</script>

<template>
  <div class="space-y-6">
    <!-- Filtros Rápidos do Feed -->
    <div class="flex items-center justify-between border-b border-border pb-2">
      <h2 class="font-semibold text-lg text-foreground">Publicações</h2>
      <div class="flex gap-2">
        <Button
          :variant="activeFilter === 'all' ? 'secondary' : 'ghost'"
          size="sm"
          :class="[
            'text-xs cursor-pointer',
            activeFilter !== 'all' && 'text-muted-foreground',
          ]"
          @click="handleFilterChange('all')"
        >
          Todos
        </Button>

        <Button
          :variant="activeFilter === 'official' ? 'secondary' : 'ghost'"
          size="sm"
          :class="[
            'text-xs cursor-pointer',
            activeFilter !== 'official' && 'text-muted-foreground',
          ]"
          @click="handleFilterChange('official')"
        >
          Comunicados
        </Button>

        <Button
          :variant="activeFilter === 'department' ? 'secondary' : 'ghost'"
          size="sm"
          :class="[
            'text-xs cursor-pointer',
            activeFilter !== 'department' && 'text-muted-foreground',
          ]"
          @click="handleFilterChange('department')"
        >
          Departamentos
        </Button>

        <Button
          variant="ghost"
          size="sm"
          class="text-xs text-muted-foreground"
          @click="handleCreatePost"
        >
          Nova publicação
        </Button>
      </div>
    </div>

    <!-- Lista de Posts (Esqueleto de Card do Post) -->
    <div class="space-y-4">
      <div
        v-if="actionError && !editingPost"
        role="alert"
        class="rounded-md border border-destructive/20 bg-destructive/10 p-3 text-sm text-destructive"
      >
        {{ actionError }}
      </div>

      <template v-if="isLoading">
        <PostCard
          v-for="n in 3"
          :key="n"
          :loading="true"
        />
      </template>

      <template v-else>
        <PostCard
          v-for="post in posts"
          :key="post.id"
          :post="post"
          @like="handleToggleLike"
          @edit="handleEditPost"
          @delete="handleDeletePost"
        />
      </template>
    </div>

    <div
      v-if="editingPost"
      class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4"
      @click.self="handleCancelEdit"
    >
      <form
        role="dialog"
        aria-modal="true"
        aria-labelledby="edit-post-title"
        class="w-full max-w-lg space-y-4 rounded-lg border border-border bg-card p-6 shadow-lg"
        @submit.prevent="handleUpdatePost"
      >
        <div>
          <h2
            id="edit-post-title"
            class="text-lg font-semibold text-foreground"
          >
            Editar publicação
          </h2>
          <p class="mt-1 text-sm text-muted-foreground">
            Atualize o conteúdo da publicação.
          </p>
        </div>

        <Textarea
          v-model="editContent"
          aria-label="Conteúdo da publicação"
          rows="5"
          :disabled="isUpdatingPost"
          class="w-full"
        />

        <p
          v-if="actionError"
          role="alert"
          class="text-sm text-destructive"
        >
          {{ actionError }}
        </p>

        <div class="flex justify-end gap-2">
          <Button
            type="button"
            variant="ghost"
            :disabled="isUpdatingPost"
            @click="handleCancelEdit"
          >
            Cancelar
          </Button>
          <Button
            type="submit"
            :disabled="isUpdatingPost"
          >
            {{ isUpdatingPost ? "Salvando..." : "Salvar alterações" }}
          </Button>
        </div>
      </form>
    </div>

    <!-- Chat -->
    <ChatWidget />
  </div>
</template>

<script setup lang="ts">
import { onMounted, ref } from "vue";
import { useRouter } from "vue-router";

import PostCard from "@/entities/post/ui/PostCard.vue";
// import { mockPosts } from '@/entities/post/model/mock-posts'
import type { Post } from "@/entities/post/model/types";

import { Button } from "@/shared/ui/button";

import ChatWidget from "@/widgets/chat-widget/ui/ChatWidget.vue";

import { postService } from "@/entities/post/api/post-service";

const router = useRouter();

const handleCreatePost = () => {
  router.push({ name: "create-post" });
};

const posts = ref<Post[]>();
const isLoading = ref(true);
const activeFilter = ref<"all" | "official" | "department">("all");

// Ação de simulação para o Like
const handleToggleLike = async (postId: number) => {
  const like = await postService.toggleLike(postId);
  const post = posts?.value?.find((p) => p.id === postId);
  if (post) {
    post.isLiked = !post.isLiked;
    post.likesCount += post.isLiked ? 1 : -1;
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
        />
      </template>
    </div>

    <!-- Chat -->
    <ChatWidget />
  </div>
</template>

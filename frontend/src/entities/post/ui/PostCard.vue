<script setup lang="ts">
import { ref } from "vue";
import {
  MoreHorizontal,
  Heart,
  MessageSquare,
  Bookmark,
} from "lucide-vue-next";
import type { Post } from "../model/types";

import { Card } from "@/shared/ui/card";
import { Button } from "@/shared/ui/button";
import { Avatar, AvatarImage, AvatarFallback } from "@/shared/ui/avatar";
import CreateCommentForm from "@/features/create-comment/ui/CreateCommentForm.vue";
import Skeleton from "@/shared/ui/skeleton/Skeleton.vue";
import { ArrowUp, ChevronUp } from "@lucide/vue";

defineProps<{
  post?: Post;
  loading?: boolean;
}>();

// Sintaxe moderna do Vue 3.3+ (Tuple-based defineEmits)
const emit = defineEmits<{
  like: [id: number];
}>();

const isCommentBoxExpanded = ref(false);

const toggleCommentBox = () => {
  isCommentBoxExpanded.value = !isCommentBoxExpanded.value;
};
</script>

<template>
  <!-- Skeleton do Post -->
  <Card
    v-if="loading"
    class="p-6 border-border bg-card space-y-4"
  >
    <!-- Header Skeleton (Avatar + Infos) -->
    <div class="flex items-center justify-between">
      <div class="flex items-center gap-3">
        <Skeleton class="h-10 w-10 rounded-full" />
        <div class="space-y-1.5">
          <Skeleton class="h-4 w-32" />
          <Skeleton class="h-3 w-24" />
        </div>
      </div>
      <Skeleton class="h-8 w-8 rounded-md" />
    </div>

    <!-- Conteúdo Skeleton -->
    <div class="space-y-2">
      <Skeleton class="h-4 w-full" />
      <Skeleton class="h-4 w-4/5" />
      <Skeleton class="h-4 w-2/3" />
    </div>

    <!-- Ações Skeleton -->
    <div
      class="flex items-center justify-between pt-3 border-t border-border/50"
    >
      <div class="flex gap-4">
        <Skeleton class="h-8 w-24 rounded-md" />
        <Skeleton class="h-8 w-28 rounded-md" />
      </div>
      <Skeleton class="h-8 w-8 rounded-md" />
    </div>
  </Card>

  <!-- Card Principal -->
  <Card
    v-else
    class="px-6 border-border bg-card space-y-4"
  >
    <!-- Header do Post -->
    <div class="flex items-center justify-between">
      <div class="flex items-center gap-3">
        <Avatar class="h-10 w-10">
          <AvatarImage
            :src="post.author.avatar"
            :alt="post.author.name"
          />
          <AvatarFallback class="bg-primary/10 text-primary font-bold">
            {{ post.author.name.substring(0, 2).toUpperCase() }}
          </AvatarFallback>
        </Avatar>
        <div>
          <h3 class="text-sm font-semibold text-foreground">
            {{ post.author.name }}
          </h3>
          <p class="text-xs text-muted-foreground">
            {{ post.createdAt }} • {{ post.author.department }}
          </p>
        </div>
      </div>
      <Button
        variant="ghost"
        size="icon"
        class="h-8 w-8 text-muted-foreground"
      >
        <MoreHorizontal class="h-4 w-4" />
      </Button>
    </div>

    <!-- Conteúdo do Post -->
    <div class="space-y-2 text-sm text-foreground/90 whitespace-pre-line">
      <p>{{ post.content }}</p>
    </div>

    <!-- Imagens com grid inteligente e overlay "+X" -->
    <div
      v-if="post?.images?.length"
      :class="[
        'grid gap-2 overflow-hidden rounded-lg',
        post?.images.length === 1 ? 'grid-cols-1' : 'grid-cols-2',
      ]"
    >
      <div
        v-for="(image, index) in post.images.slice(0, 2)"
        :key="image.id"
        class="relative h-48 w-full overflow-hidden rounded-lg"
      >
        <!-- Imagem principal -->
        <img
          :src="image.url"
          alt="Imagem do post"
          class="h-full w-full object-cover"
        />

        <!-- Overlay do "+X" caso existam mais de 2 imagens -->
        <div
          v-if="index === 1 && post.images.length > 2"
          class="absolute inset-0 flex items-center justify-center bg-black/60 font-semibold text-white text-lg backdrop-blur-[1px] cursor-pointer hover:bg-black/70 transition-colors"
        >
          +{{ post.images.length - 2 }}
        </div>
      </div>
    </div>

    <!-- Ações e Engajamento -->
    <div
      class="flex items-center justify-between pt-3 border-t border-border/50 text-muted-foreground text-xs"
    >
      <div class="flex gap-4">
        <Button
          variant="ghost"
          size="sm"
          class="gap-1.5 h-8 text-xs hover:text-primary"
          :class="{ 'text-primary font-semibold': post.isLiked }"
          @click="emit('like', post.id)"
        >
          <Heart
            class="h-4 w-4"
            :class="{ 'fill-current': post.isLiked }"
          />
          <span>{{ post.likesCount }} Curtidas</span>
        </Button>

        <Button
          variant="ghost"
          size="sm"
          class="gap-1.5 h-8 text-xs"
          @click="toggleCommentBox"
        >
          <MessageSquare class="h-4 w-4" />
          <span>{{ post.commentsCount }} Comentários</span>
        </Button>
      </div>

      <Button
        variant="ghost"
        size="icon"
        class="h-8 w-8"
      >
        <Bookmark class="h-4 w-4" />
      </Button>
    </div>

    <CreateCommentForm
      v-if="isCommentBoxExpanded"
      class="border-none shadow-none w-full px-0"
      textareaPlaceholder="Insira o seu comentário..."
      :postId="post?.id"
    />

    <Button
      v-if="isCommentBoxExpanded"
      variant="ghost"
      class="w-50 mx-auto text-muted-foreground cursor-pointer"
      :aria-expanded="isCommentBoxExpanded"
      :aria-label="
        isCommentBoxExpanded
          ? 'Recolher comentários desta conversa'
          : 'Expandir comentários desta conversa'
      "
      @click="toggleCommentBox"
    >
      <ArrowUp />
      Recolher comentários
    </Button>
  </Card>
</template>

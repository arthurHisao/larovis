<script setup lang="ts">
import { ref, onMounted, onUnmounted } from "vue";
import {
  MoreHorizontal,
  Heart,
  MessageSquare,
  Bookmark,
  X,
  ChevronLeft,
  ChevronRight,
} from "lucide-vue-next";
import type { Post } from "../model/types";

import { Card } from "@/shared/ui/card";
import { Button } from "@/shared/ui/button";
import { Avatar, AvatarImage, AvatarFallback } from "@/shared/ui/avatar";
import CreateCommentForm from "@/features/create-comment/ui/CreateCommentForm.vue";
import Skeleton from "@/shared/ui/skeleton/Skeleton.vue";
import { ArrowUp } from "@lucide/vue";

const props = defineProps<{
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

// Estados e métodos do visualizador de imagem ampliada (Lightbox)
const activeImageIndex = ref<number | null>(null);

const openViewer = (index: number) => {
  activeImageIndex.value = index;
};

const closeViewer = () => {
  activeImageIndex.value = null;
};

const nextImage = () => {
  if (!props.post?.images?.length || activeImageIndex.value === null) return;
  activeImageIndex.value =
    (activeImageIndex.value + 1) % props.post.images.length;
};

const prevImage = () => {
  if (!props.post?.images?.length || activeImageIndex.value === null) return;
  activeImageIndex.value =
    (activeImageIndex.value - 1 + props.post.images.length) %
    props.post.images.length;
};

const handleKeyDown = (e: KeyboardEvent) => {
  if (activeImageIndex.value === null) return;
  if (e.key === "Escape") closeViewer();
  if (e.key === "ArrowRight") nextImage();
  if (e.key === "ArrowLeft") prevImage();
};

onMounted(() => {
  window.addEventListener("keydown", handleKeyDown);
});

onUnmounted(() => {
  window.removeEventListener("keydown", handleKeyDown);
});
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
        class="group relative h-48 w-full overflow-hidden rounded-lg cursor-pointer bg-muted/40"
        @click="openViewer(index)"
      >
        <!-- Imagem principal -->
        <img
          :src="image.url"
          alt="Imagem do post"
          class="h-full w-full object-cover transition-transform duration-200 group-hover:scale-105"
        />

        <!-- Overlay do "+X" caso existam mais de 2 imagens -->
        <div
          v-if="index === 1 && post.images.length > 2"
          class="absolute inset-0 flex items-center justify-center bg-black/60 font-semibold text-white text-lg backdrop-blur-[1px] hover:bg-black/70 transition-colors"
          @click.stop="openViewer(1)"
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
      v-if="isCommentBoxExpanded && post.commentsCount > 0"
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

  <!-- Modal / Lightbox em Tela Cheia -->
  <Teleport to="body">
    <Transition
      enter-active-class="transition duration-200 ease-out"
      enter-from-class="opacity-0"
      enter-to-class="opacity-100"
      leave-active-class="transition duration-150 ease-in"
      leave-from-class="opacity-100"
      leave-to-class="opacity-0"
    >
      <div
        v-if="activeImageIndex !== null && post?.images?.[activeImageIndex]"
        class="fixed inset-0 z-50 flex items-center justify-center bg-black/90 backdrop-blur-md p-4 select-none"
        @click.self="closeViewer"
      >
        <!-- Barra Superior: Contador e Botão Fechar -->
        <div
          class="absolute top-4 inset-x-4 flex items-center justify-between text-white pointer-events-none"
        >
          <!-- Contador de fotos -->
          <div
            v-if="post.images.length > 1"
            class="px-3 py-1 rounded-full bg-white/10 backdrop-blur-xs text-xs font-medium text-white/90 border border-white/10 pointer-events-auto"
          >
            {{ activeImageIndex + 1 }} de {{ post.images.length }}
          </div>
          <div v-else />

          <!-- Botão Fechar (X) -->
          <button
            type="button"
            aria-label="Fechar visualizador"
            class="pointer-events-auto p-2 rounded-full bg-white/10 hover:bg-white/20 text-white transition-colors cursor-pointer"
            @click="closeViewer"
          >
            <X class="h-5 w-5" />
          </button>
        </div>

        <!-- Seta Anterior -->
        <button
          v-if="post.images.length > 1"
          type="button"
          aria-label="Imagem anterior"
          class="absolute left-4 p-2.5 rounded-full bg-black/40 hover:bg-white/20 text-white border border-white/10 backdrop-blur-xs transition-colors cursor-pointer"
          @click.stop="prevImage"
        >
          <ChevronLeft class="h-6 w-6" />
        </button>

        <!-- Imagem Ampliada -->
        <div
          class="max-w-5xl max-h-[85vh] flex items-center justify-center overflow-hidden"
          @click.stop
        >
          <img
            :src="post.images[activeImageIndex].url"
            :alt="'Imagem ' + (activeImageIndex + 1)"
            class="max-h-[85vh] max-w-[90vw] object-contain rounded-lg shadow-2xl transition-all"
          />
        </div>

        <!-- Seta Próxima -->
        <button
          v-if="post.images.length > 1"
          type="button"
          aria-label="Próxima imagem"
          class="absolute right-4 p-2.5 rounded-full bg-black/40 hover:bg-white/20 text-white border border-white/10 backdrop-blur-xs transition-colors cursor-pointer"
          @click.stop="nextImage"
        >
          <ChevronRight class="h-6 w-6" />
        </button>
      </div>
    </Transition>
  </Teleport>
</template>

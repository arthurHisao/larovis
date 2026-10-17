<!-- CommentItem.vue -->
<script setup lang="ts">
import Avatar from "@/shared/ui/avatar/Avatar.vue";
import AvatarFallback from "@/shared/ui/avatar/AvatarFallback.vue";
import AvatarImage from "@/shared/ui/avatar/AvatarImage.vue";
import type { Comment } from "../model/types";
import CommentThreadList from "./CommentThreadList.vue"; // Importa a lista para continuar a árvore
import { computed, ref } from "vue";
import Badge from "@/shared/ui/badge/Badge.vue";
import { AtSign } from "@lucide/vue";
import { commentService } from "../api/comment-service.ts";
import CommentReplyBox from "./CommentReplyBox.vue";
import CommentActionsMenu from "./CommentActionsMenu.vue";

interface Props {
  comment: Comment;
  isLast?: boolean;
  isRoot: boolean;
  depth?: number;
  hasNextSibling: boolean;
  isSibling?: boolean;
  isAuthor: boolean;
}

const emit = defineEmits<{
  delete: [commentId: number];
}>();

const {
  comment,
  isLast = false,
  isRoot,
  depth = 0,
  hasNextSibling,
  isAuthor,
} = defineProps<Props>();

const MAX_DEPTH = 3;

const commentClasses = computed(() => {
  const classes = ["relative flex gap-3 text-xs group w-full pb-3 pl-1"];
  const hasChildren = comment?.replies?.length > 0;

  // 1. LINHA VERTICAL PARA BAIXO (O Pai cria essa linha se ele tiver filhos)
  if (hasChildren) {
    classes.push(
      "before:absolute before:left-4.5 before:top-8 before:bottom-0 before:w-0.5 before:bg-border before:content-[''] before:z-0",
    );
  }

  // 2. CONECTOR LATERAL (A curva em L que conecta a resposta ao fluxo)
  if (!isRoot) {
    if (depth <= MAX_DEPTH) {
      // Desenha a curva perfeita em L conectando o item à esquerda
      classes.push(
        "after:absolute after:-left-3.5 after:-top-2 after:w-5.5 after:h-6 after:border-l-2 after:border-b-2 after:border-border after:rounded-bl-md z-0",
      );
    } else {
      // Se estourar a profundidade máxima, vira apenas uma linha discreta
      classes.push(
        "after:absolute after:left-4.5 after:-top-2 after:w-px after:h-6 after:border-l-2 after:border-b after:border-border",
      );
    }
  }

  return classes;
});

const deletePost = async (commentId: number) => {
  try {
    await commentService.delete(commentId);
    emit("delete", commentId);
  } catch (error) {
    console.error("Erro ao deletar comentário:", error);
  }
};

const isReplyBoxOpen = ref<number[]>([]);

const toggleReplyBox = (commentId: number) => {
  const currentIds = new Set(isReplyBoxOpen.value);

  if (currentIds.has(commentId)) {
    currentIds.delete(commentId);
  } else {
    currentIds.add(commentId);
  }

  isReplyBoxOpen.value = [...currentIds];
};

const handleReplyCreated = (createdComment: Comment) => {
  if (!comment.replies) comment.replies = [];
  comment.replies.push(createdComment);
  isReplyBoxOpen.value = isReplyBoxOpen.value.filter(
    (commentId) => commentId !== comment.id,
  );
};
</script>

<template>
  <div
    :class="[
      'relative flex flex-col w-full',
      !isRoot &&
        hasNextSibling &&
        'before:absolute before:-left-3.5 before:top-0 before:bottom-0 before:w-0.5 before:bg-border before:content-[\'\'] before:z-0',
    ]"
  >
    <!-- Card do Comentário Atual -->
    <div :class="commentClasses">
      <!-- Avatar -->
      <Avatar class="h-8 w-8 shrink-0 z-10 ring-4 ring-background/10">
        <AvatarImage
          :src="comment.author.avatar"
          :alt="comment.author.name"
        />
        <AvatarFallback class="bg-gray-300 text-primary font-bold">
          {{ comment.author.name.substring(0, 2).toUpperCase() }}
        </AvatarFallback>
      </Avatar>

      <!-- Caixa de Conteúdo -->
      <div
        class="flex-1 bg-muted/40 rounded-lg p-3 space-y-1 border border-border/40"
      >
        <div class="flex items-center justify-between">
          <span class="font-semibold text-foreground">
            {{ comment.author.name }}
          </span>

          <div class="flex items-center gap-3">
            <span class="text-[10px] text-muted-foreground">
              {{ comment.createdAt }}
            </span>

            <CommentActionsMenu
              :is-author="isAuthor"
              @delete="deletePost(comment?.id)"
              @toggle-reply="toggleReplyBox(comment?.id)"
            />
          </div>
        </div>

        <Badge
          v-if="comment?.mention"
          class="bg-blue-400 h-5 items-center text-[10px] font-thin py-0.5"
        >
          <AtSign class="size-2.5!" />
          {{ comment?.mention }}
        </Badge>
        <p class="text-foreground/90 whitespace-pre-line">
          {{ comment.content }}
        </p>
      </div>
    </div>

    <CommentReplyBox
      v-if="isReplyBoxOpen.includes(comment.id)"
      :comment="comment"
      @reply-created="handleReplyCreated"
      @cancel="toggleReplyBox(comment.id)"
    />

    <!-- Comentário Filhos -->
    <CommentThreadList
      v-if="comment.replies && comment.replies.length"
      :comments="comment.replies"
      :depth="depth + 1"
      :class="[depth < MAX_DEPTH ? 'pl-8' : '']"
      :is-author="isAuthor"
    />
  </div>
</template>

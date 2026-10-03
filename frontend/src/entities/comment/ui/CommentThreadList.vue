<!-- CommentThreadList.vue -->
<script setup lang="ts">
import Skeleton from "@/shared/ui/skeleton/Skeleton.vue";
import CommentItem from "./CommentItem.vue";
import type { Comment } from "../model/types";
import { useAuthStore } from "@/entities/login/@x/comment";

interface Props {
  comments: Comment[];
  isLoading?: boolean;
  isRootList?: boolean; // Opcional: passe true apenas na primeira chamada lá no seu Card principal
  depth?: number;
}
const authStore = useAuthStore();

const {
  comments,
  isLoading = false,
  isRootList = false,
  depth = 0,
} = defineProps<Props>();

const handleDelete = (commentId: number) => {
  const index = comments.findIndex((comment) => comment.id === commentId);

  if (index !== -1) {
    comments.splice(index, 1);
  }
};
</script>

<template>
  <div
    v-if="isLoading || comments.length"
    class="pt-2 space-y-4 w-full"
  >
    <!-- Skeleton durante carregamento -->
    <template v-if="isLoading">
      <div
        v-for="n in 2"
        :key="n"
        class="relative flex gap-3 pl-2"
      >
        <Skeleton class="h-8 w-8 rounded-full shrink-0" />
        <div class="flex-1 space-y-2">
          <Skeleton class="h-12 w-full rounded-lg" />
        </div>
      </div>
    </template>

    <!-- Loop que renderiza cada linha de comentário -->
    <template v-else>
      <CommentItem
        v-for="(comment, index) in comments"
        :key="comment.id"
        :comment="comment"
        :is-last="index === comments.length - 1"
        :is-root="isRootList"
        :depth="depth"
        :is-author="comment?.author?.id === authStore?.user?.id"
        @delete="handleDelete"
      />
    </template>
  </div>
</template>

<script setup lang="ts">
import Badge from "@/shared/ui/badge/Badge.vue";
import Button from "@/shared/ui/button/Button.vue";
import { ref } from "vue";
import { AtSignIcon } from "@lucide/vue";
import { commentService } from "../api/comment-service";
import type { Comment } from "../model/types";

interface Props {
  comment: Comment;
}

const { comment } = defineProps<Props>();

const emit = defineEmits<{
  replyCreated: [createdComment: Comment];
  cancel: [];
}>();

const isSubmitting = ref(false);
const newCommentContent = ref("");

const handleReply = async (): Promise<void> => {
  if (!newCommentContent.value.trim() || isSubmitting.value) return;

  try {
    isSubmitting.value = true;

    const createdComment = await commentService.create(comment.postId, {
      content: newCommentContent.value,
      parent_id: comment.id,
    });

    newCommentContent.value = "";
    emit("replyCreated", createdComment);
  } catch (error) {
    console.error("Erro ao responder o comentário:", error);
  } finally {
    isSubmitting.value = false;
  }
};
</script>

<template>
  <div
    :class="[
      'relative pt-1 pb-10 pl-12 flex flex-wrap',
      Boolean(comment.replies?.length)
        ? [
            'before:absolute before:left-4.5 before:top-8 before:bottom-0 before:w-0.5 before:bg-border before:content-[\'\'] before:z-0',
            'after:absolute after:left-4.5 after:-top-2 after:w-px after:h-full after:border-l-2 after:border-b after:border-border',
          ]
        : '',
    ]"
  >
    <!-- Container com layout vertical (flex-col) -->
    <div
      class="flex flex-col items-start gap-2 w-full min-h-20 rounded-md border border-muted bg-muted/30 px-3 py-2.5 text-xs shadow-xs transition-[color,box-shadow] outline-none placeholder:text-muted-foreground focus-within:border-ring focus-within:ring-ring/50 focus-within:ring-3 focus-within:bg-background aria-invalid:ring-destructive/20 dark:aria-invalid:ring-destructive/40 aria-invalid:border-destructive disabled:cursor-not-allowed disabled:opacity-50"
    >
      <!-- Tag / Badge da Menção no topo -->
      <Badge
        class="inline-flex items-center px-1 py-0.5 rounded text-xs font-medium bg-primary/10 text-primary border border-primary/20 shrink-0 select-none"
      >
        <AtSignIcon class="size-3" />
        <span class="text-xs">{{ comment?.author?.name }}</span>
      </Badge>

      <!-- Textarea ocupa 100% da largura abaixo da Badge -->
      <textarea
        v-model="newCommentContent"
        placeholder="Escreva a sua resposta..."
        rows="2"
        class="w-full bg-transparent border-none p-0 text-xs text-foreground placeholder:text-muted-foreground outline-none focus:outline-none focus:ring-0 focus-visible:ring-0 resize-none field-sizing-content min-h-9"
      />
    </div>

    <div
      class="flex items-center justify-between pt-2 border-t border-border/60 w-full"
    >
      <div class="inline-flex gap-3">
        <Button
          size="xs"
          variant="outline"
          class="text-xs px-3 py-3.5"
          @click="emit('cancel')"
        >
          Cancelar
        </Button>

        <Button
          size="xs"
          class="text-xs bg-slate-600 px-3 py-3.5"
          :disabled="isSubmitting"
          @click="handleReply"
        >
          {{ isSubmitting ? "Enviando..." : "Responder" }}
        </Button>
      </div>
    </div>
  </div>
</template>

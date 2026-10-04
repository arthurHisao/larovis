<script setup lang="ts">
import { computed, onMounted, ref } from "vue";
import { Paperclip, Image } from "lucide-vue-next";

import Avatar from "@/shared/ui/avatar/Avatar.vue";
import AvatarFallback from "@/shared/ui/avatar/AvatarFallback.vue";
import AvatarImage from "@/shared/ui/avatar/AvatarImage.vue";
import Textarea from "@/shared/ui/textarea/Textarea.vue";
import Card from "@/shared/ui/card/Card.vue";
import { Button } from "@/shared/ui/button";

import type { Comment } from "@/entities/comment/model/types";
import { commentService } from "@/entities/comment/api/comment-service";
import CommentThreadList from "@/entities/comment/ui/CommentThreadList.vue";

interface Props {
  postId: number;
  textareaPlaceholder?: string;
}

const { postId, textareaPlaceholder = "Insira o seu comentário..." } =
  defineProps<Props>();

const comments = ref<Comment[]>([]);
const isLoading = ref(true);
const isSubmitting = ref(false);
const newCommentContent = ref("");

const loadComments = async () => {
  try {
    isLoading.value = true;
    comments.value = await commentService.getByPostId(postId);
  } catch (error) {
    console.error("Erro ao carregar comentários:", error);
  } finally {
    isLoading.value = false;
  }
};

const handleCreateComment = async () => {
  if (!newCommentContent.value.trim() || isSubmitting.value) return;

  try {
    isSubmitting.value = true;
    const createdComment = await commentService.create(postId, {
      content: newCommentContent.value,
    });
    comments.value.unshift(createdComment);
    newCommentContent.value = "";
  } catch (error) {
    console.error("Erro ao comentar:", error);
  } finally {
    isSubmitting.value = false;
  }
};

onMounted(() => {
  loadComments();
});
</script>

<template>
  <Card class="p-4 border-border bg-card space-y-4">
    <!-- Formulário de Comentário -->
    <div class="flex gap-3 relative">
      <Avatar class="h-10 w-10 shrink-0 z-10">
        <AvatarImage src="https://github.com/shadcn.png" />
        <AvatarFallback>AV</AvatarFallback>
      </Avatar>

      <div class="flex-1 space-y-3">
        <Textarea
          v-model="newCommentContent"
          :placeholder="textareaPlaceholder"
          class="resize-none border-muted bg-muted/30 focus-visible:bg-background text-xs"
          rows="2"
        />

        <div
          class="flex items-center justify-between pt-2 border-t border-border/60"
        >
          <div class="flex gap-1 text-muted-foreground">
            <Button
              variant="ghost"
              size="sm"
              class="gap-2 text-xs"
            >
              <Image class="h-4 w-4 text-primary" />
              Imagem
            </Button>
            <Button
              variant="ghost"
              size="sm"
              class="gap-2 text-xs"
            >
              <Paperclip class="h-4 w-4 text-primary" />
              Anexo
            </Button>
          </div>
          <Button
            size="sm"
            class="px-5 text-xs"
            :disabled="isSubmitting"
            @click="handleCreateComment"
          >
            {{ isSubmitting ? "Enviando..." : "Comentar" }}
          </Button>
        </div>
      </div>
    </div>

    <!-- Renderizador da Thread -->
    <CommentThreadList
      :comments="comments"
      :is-loading="isLoading"
      :is-root-list="true"
    />
  </Card>
</template>

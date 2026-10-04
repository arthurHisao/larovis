<!-- CommentItem.vue -->
<script setup lang="ts">
import Avatar from "@/shared/ui/avatar/Avatar.vue";
import AvatarFallback from "@/shared/ui/avatar/AvatarFallback.vue";
import AvatarImage from "@/shared/ui/avatar/AvatarImage.vue";
import type { Comment } from "../model/types";
import CommentThreadList from "./CommentThreadList.vue"; // Importa a lista para continuar a árvore
import { computed, ref } from "vue";
import Badge from "@/shared/ui/badge/Badge.vue";
import {
  AtSign,
  Image,
  MessageCircle,
  MessageSquareReply,
  MoreHorizontal,
  Paperclip,
  Pencil,
  Trash,
} from "@lucide/vue";
import DropdownMenu from "@/shared/ui/dropdown-menu/DropdownMenu.vue";
import DropdownMenuTrigger from "@/shared/ui/dropdown-menu/DropdownMenuTrigger.vue";
import DropdownMenuContent from "@/shared/ui/dropdown-menu/DropdownMenuContent.vue";
import DropdownMenuItem from "@/shared/ui/dropdown-menu/DropdownMenuItem.vue";
import Button from "@/shared/ui/button/Button.vue";
import DropdownMenuGroup from "@/shared/ui/dropdown-menu/DropdownMenuGroup.vue";
import { MessageCircleWarning } from "lucide-vue-next";
import { commentService } from "../api/comment-service.ts";
import { Textarea } from "@/shared/ui/textarea";

interface Props {
  comment: Comment;
  isLast: boolean;
  isRoot: boolean;
  depth?: number;
  isAuthor: boolean;
}

const emit = defineEmits<{
  delete: [commentId: number];
}>();

const { comment, isLast, isRoot, depth = 0, isAuthor } = defineProps<Props>();

const MAX_DEPTH = 3;

const commentClasses = computed(() => {
  const classes = ["relative flex gap-3 text-xs group w-full pb-3 pl-1"];
  const hasChildren = comment?.replies?.length > 0;

  // Linha vertical Pai
  if (isRoot) {
    if (hasChildren) {
      classes.push(
        "before:absolute before:left-4.5 before:top-8 before:bottom-0 before:w-0.5 before:bg-border before:content-[''] before:z-0",
      );
    }
    return classes;
  }

  if (depth <= MAX_DEPTH) {
    // Linha horizontal L
    classes.push(
      "after:absolute after:-left-3.5 after:-top-2 after:w-5.5 after:h-6 after:border-l-2 after:border-b-2 after:border-border after:rounded-bl-md",
    );
  } else {
    classes.push(
      "after:absolute after:left-4.5 after:-top-2 after:w-px after:h-6 after:border-l-2 after:border-b after:border-border",
    );
  }

  // Linha vertical filhos
  if (hasChildren) {
    classes.push(
      "before:absolute before:left-4.5 before:top-8 before:bottom-0 before:w-0.5 before:bg-border before:content-[''] before:z-0",
    );
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

const isReplyBoxOpen = ref([]);

const toggleReplyBox = (commentId: number) => {
  const currentIds = new Set(isReplyBoxOpen.value);

  if (currentIds.has(commentId)) {
    currentIds.delete(commentId);
  } else {
    currentIds.add(commentId);
  }

  isReplyBoxOpen.value = [...currentIds];
};

const isSubmitting = ref(false);
const newCommentContent = ref("");

const handleReply = async (postId, parentId) => {
  console.log("newCommentContent ", newCommentContent);
  if (!newCommentContent.value.trim() || isSubmitting.value) return;

  try {
    isSubmitting.value = true;
    const createdComment = await commentService.create(postId, {
      content: newCommentContent.value,
      parent_id: parentId,
    });

    newCommentContent.value = "";
  } catch (error) {
    console.error("Erro ao comentar:", error);
  } finally {
    isSubmitting.value = false;
  }
};
</script>

<template>
  <div :class="['relative flex flex-col w-full']">
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

            <DropdownMenu>
              <DropdownMenuTrigger as-child>
                <Button
                  size="sm"
                  aria-label="Expandir ações sob o comentário"
                  variant="outline"
                  class="rounded-full h-5.5! w-5.5! p-0 bg-muted hover:bg-muted/40 focus:outline-none border border-transparent outline-none data-[state=open]:ring-4 data-[state=open]:ring-gray-400/70 data-[state=open]:border-gray-300 focus-visible:ring-4 focus-visible:ring-gray-400/70 focus-visible:border-gray-300"
                >
                  <MoreHorizontal class="size-3" />
                </Button>
              </DropdownMenuTrigger>

              <DropdownMenuContent
                class="w-40"
                align="start"
              >
                <!-- Editar -->
                <DropdownMenuGroup v-if="isAuthor">
                  <DropdownMenuItem>
                    <Pencil />
                    Editar
                  </DropdownMenuItem>
                </DropdownMenuGroup>

                <!-- Deletar -->
                <DropdownMenuGroup v-if="isAuthor">
                  <DropdownMenuItem @click="deletePost(comment?.id)">
                    <Trash />
                    Deletar
                  </DropdownMenuItem>
                </DropdownMenuGroup>

                <!-- Denunciar -->
                <DropdownMenuGroup v-if="!isAuthor">
                  <DropdownMenuItem>
                    <MessageCircleWarning />
                    Denunciar
                  </DropdownMenuItem>
                </DropdownMenuGroup>

                <!-- Responder -->
                <DropdownMenuGroup>
                  <DropdownMenuItem @click="toggleReplyBox(comment?.id)">
                    <MessageSquareReply />
                    Responder
                  </DropdownMenuItem>
                </DropdownMenuGroup>
              </DropdownMenuContent>
            </DropdownMenu>
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

    <div
      v-if="isReplyBoxOpen.includes(comment.id)"
      :class="[
        'relative pt-1 pb-10 pl-12 flex flex-wrap',
        comment.replies?.length > 0
          ? [
              'before:absolute before:left-4.5 before:top-8 before:bottom-0 before:w-0.5 before:bg-border before:content-[\'\'] before:z-0',
              'after:absolute after:left-4.5 after:-top-2 after:w-px after:h-full after:border-l-2 after:border-b after:border-border',
            ]
          : '',
      ]"
    >
      <Textarea
        v-model="newCommentContent"
        :placeholder="'Insira a sua resposta'"
        class="resize-none border-muted bg-muted/30 focus-visible:bg-background text-xs w-full"
      />

      <div
        class="flex items-center justify-between pt-2 border-t border-border/60 w-full"
      >
        <div class="inline-flex gap-1 text-muted-foreground">
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

        <div class="inline-flex gap-3">
          <Button
            size="xs"
            variant="outline"
            class="text-xs px-3 py-3.5"
            @click="toggleReplyBox(comment.id)"
          >
            Cancelar
          </Button>

          <Button
            size="xs"
            class="text-xs bg-slate-600 px-3 py-3.5"
            :disabled="isSubmitting"
            @click="handleReply(comment.postId, comment.parentId)"
          >
            {{ isSubmitting ? "Enviando..." : "Responder" }}
          </Button>
        </div>
      </div>
    </div>

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

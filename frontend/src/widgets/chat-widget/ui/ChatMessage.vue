<script setup lang="ts">
import { nextTick, watch, type ComponentPublicInstance } from "vue";
import { EllipsisVertical, Reply, Send, X } from "lucide-vue-next";

import type { ChatMessage as ChatMessageType } from "@/entities/message/model/types";
import { useAuthStore } from "@/entities/user/model/auth.store";
import { useMessageStore } from "@/entities/message/model/message.store";
import { Avatar, AvatarFallback, AvatarImage } from "@/shared/ui/avatar";
import { Button } from "@/shared/ui/button";
import { Card } from "@/shared/ui/card";
import DropdownMenuTrigger from "@/shared/ui/dropdown-menu/DropdownMenuTrigger.vue";
import DropdownMenuContent from "@/shared/ui/dropdown-menu/DropdownMenuContent.vue";
import DropdownMenuGroup from "@/shared/ui/dropdown-menu/DropdownMenuGroup.vue";
import DropdownMenuItem from "@/shared/ui/dropdown-menu/DropdownMenuItem.vue";
import DropdownMenu from "@/shared/ui/dropdown-menu/DropdownMenu.vue";
import Textarea from "@/shared/ui/textarea/Textarea.vue";

const {
  activeChatUserIds,
  conversationErrors,
  draftMessages,
  replyingToMessages,
} = defineProps<{
  activeChatUserIds: number[];
  conversationErrors: Record<number, string>;
  draftMessages: Record<number, string>;
  replyingToMessages: Record<number, ChatMessageType | undefined>;
}>();

const emit = defineEmits<{
  close: [userId: number];
  send: [userId: number, parentId?: number];
  reply: [userId: number, message: ChatMessageType];
  cancelReply: [userId: number];
  updateDraft: [userId: number, text: string];
}>();

const authStore = useAuthStore();
const messageStore = useMessageStore();
const chatContainers: Record<number, HTMLElement | null> = {};

const getUser = (id: number) =>
  messageStore.unifiedContacts.find((user) => user.id === id);

const isOwnMessage = (message: ChatMessageType) =>
  message.senderId === authStore.user?.id;

const getMessageUser = (message: ChatMessageType) =>
  isOwnMessage(message) ? authStore.user : getUser(message.senderId);

const getSenderName = (senderId: number | undefined) => {
  if (senderId === authStore.user?.id) return "Você";
  return getUser(senderId ?? -1)?.name ?? "Mensagem";
};

const setChatContainer = (
  userId: number,
  element: Element | ComponentPublicInstance | null,
) => {
  chatContainers[userId] = element instanceof HTMLElement ? element : null;
};

const scrollToBottom = (userId: number) => {
  nextTick(() => {
    const element = chatContainers[userId];
    if (element) {
      element.scrollTop = element.scrollHeight;
    }
  });
};

watch(
  () => [messageStore.conversations, activeChatUserIds],
  () => {
    for (const userId of activeChatUserIds) {
      if (messageStore.conversations[userId]) {
        scrollToBottom(userId);
      }
    }
  },
  { deep: true, flush: "post" },
);
</script>

<template>
  <div
    v-for="userId in activeChatUserIds"
    :key="userId"
    class="w-80 shadow-lg transition-all duration-300"
  >
    <Card
      v-if="getUser(userId)"
      class="border-border bg-card overflow-hidden rounded-b-none border-b-0 py-0 gap-0"
    >
      <!-- Header da Janela Individual -->
      <div
        class="flex items-center justify-between p-2.5 bg-card border-b border-border/60"
      >
        <div class="flex items-center gap-2">
          <div class="relative">
            <Avatar class="h-8 w-8">
              <AvatarImage :src="getUser(userId)?.avatar ?? ''" />
              <AvatarFallback>
                {{ getUser(userId)?.name.substring(0, 2) }}
              </AvatarFallback>
            </Avatar>

            <span
              :class="[
                'absolute bottom-0 right-0 h-3 w-3 rounded-full border-2 border-card',
                getUser(userId)?.isOnline ? 'bg-emerald-500' : 'bg-muted',
              ]"
            />
          </div>

          <div class="inline-flex items-center flex-col truncate max-w-30">
            <span class="font-semibold text-xs text-foreground">
              {{ getUser(userId)?.name }}
            </span>
            <span class="w-full font-thin text-[11px]">
              {{ getUser(userId)?.department }}
            </span>
          </div>
        </div>

        <div class="flex items-center gap-1">
          <Button
            variant="ghost"
            size="icon"
            class="h-6 w-6 text-muted-foreground hover:text-foreground"
            @click="emit('close', userId)"
          >
            <X class="h-3.5 w-3.5" />
          </Button>
        </div>
      </div>

      <!-- Corpo da Janela Individual -->
      <div class="h-80 flex flex-col justify-between">
        <!-- Mensagem  -->
        <div
          :ref="(el) => setChatContainer(userId, el)"
          class="px-2 py-5 space-y-4 overflow-y-auto flex-1"
        >
          <p
            v-if="messageStore.loadingConversations[userId]"
            class="py-2 text-center text-xs text-muted-foreground"
          >
            Carregando mensagens...
          </p>
          <p
            v-else-if="conversationErrors[userId]"
            class="py-2 text-center text-xs text-destructive"
          >
            {{ conversationErrors[userId] }}
          </p>

          <div
            v-for="msg in messageStore.conversations[userId] || []"
            :key="msg.id"
            :class="[
              'flex items-baseline relative',
              isOwnMessage(msg)
                ? 'flex-row-reverse text-primary-foreground'
                : 'flex-row text-foreground',
            ]"
          >
            <Avatar
              :class="[
                'h-6 w-6 shrink-0 absolute top-0 -translate-y-1',
                isOwnMessage(msg) ? 'right-0' : 'left-0',
              ]"
            >
              <AvatarImage
                :src="getMessageUser(msg)?.avatar ?? ''"
                :alt="getMessageUser(msg)?.name"
              />
              <AvatarFallback>
                {{
                  getMessageUser(msg)?.name?.substring(0, 2).toUpperCase() ??
                  "?"
                }}
              </AvatarFallback>
            </Avatar>

            <div
              class="flex max-w-[85%] flex-col"
              :class="isOwnMessage(msg) ? 'items-end mr-7' : 'items-start ml-7'"
            >
              <div
                :class="[
                  'px-2.5 py-2.5 text-xs relative min-w-16 max-w-32 text-left',
                  isOwnMessage(msg)
                    ? 'bg-primary text-primary-foreground rounded-lg rounded-tr-none rounded-br-xl'
                    : 'bg-muted text-foreground rounded-lg rounded-tl-none rounded-bl-xl',
                ]"
              >
                <div
                  v-if="msg.refer?.length"
                  class="bg-rose-950/40 max-h-10 mb-1 p-1 rounded-sm text-[10px] line-clamp-2 wrap-break-words"
                >
                  <span class="font-semibold">
                    {{ getSenderName(msg.refer[0]?.senderId) }}:
                  </span>
                  {{ msg.refer[0]?.text }}
                </div>

                <p class="mb-1.5 wrap-break-word">{{ msg.text }}</p>

                <div
                  :class="[
                    'text-right text-[9px]',
                    isOwnMessage(msg) ? 'text-muted' : 'text-foreground',
                  ]"
                >
                  {{ msg.time }}
                </div>

                <DropdownMenu :modal="false">
                  <DropdownMenuTrigger as-child>
                    <Button
                      :size="'10px'"
                      variant="ghost"
                      :class="[
                        'absolute top-0.5 rounded-full ',
                        isOwnMessage(msg) ? '-left-4.5 ' : '-right-4.5',
                      ]"
                    >
                      <EllipsisVertical class="size-4 text-primary" />
                    </Button>
                  </DropdownMenuTrigger>

                  <DropdownMenuContent
                    :align="isOwnMessage(msg) ? 'end' : 'start'"
                    :side="isOwnMessage(msg) ? 'left' : 'right'"
                    :side-offset="0"
                    :align-offset="isOwnMessage(msg) ? -20 : -2"
                    class="min-w-10 max-w-30 p-0"
                  >
                    <DropdownMenuGroup>
                      <DropdownMenuItem @select="emit('reply', userId, msg)">
                        <Reply />
                        Responder
                      </DropdownMenuItem>
                    </DropdownMenuGroup>
                  </DropdownMenuContent>
                </DropdownMenu>
              </div>
            </div>
          </div>
          <!-- /End v-for-->
        </div>

        <!-- Input individual -->
        <form
          @submit.prevent="emit('send', userId, replyingToMessages[userId]?.id)"
          class="p-2 border-t border-border flex flex-col gap-1.5 bg-card"
        >
          <div
            v-if="replyingToMessages[userId]"
            class="flex items-center justify-between gap-2 px-2 py-1.5 bg-muted/60 border-l-2 border-primary rounded-sm text-[11px] text-muted-foreground animate-fade-in"
          >
            <div class="flex flex-col truncate flex-1">
              <span class="font-semibold text-primary text-xs">
                Respondendo a
                {{ getSenderName(replyingToMessages[userId]?.senderId) }}
              </span>
              <span class="truncate">
                {{ replyingToMessages[userId]?.text }}
              </span>
            </div>

            <Button
              type="button"
              variant="ghost"
              size="icon"
              class="h-4 w-4 rounded-full opacity-70 hover:opacity-100 shrink-0"
              @click="emit('cancelReply', userId)"
            >
              <X class="h-3 w-3" />
            </Button>
          </div>

          <div class="flex items-center gap-1.5 w-full">
            <Textarea
              :model-value="draftMessages[userId] ?? ''"
              @update:model-value="emit('updateDraft', userId, String($event))"
              placeholder="Escreva algo..."
              class="min-h-8 max-h-15 text-xs bg-muted/40 flex-1 resize-none overflow-y-auto"
            />
            <Button
              type="submit"
              size="icon"
              class="h-8 w-8 shrink-0"
            >
              <Send class="h-3.5 w-3.5" />
            </Button>
          </div>
        </form>
      </div>
    </Card>
  </div>
</template>

<script setup lang="ts">
import { nextTick, watch, type ComponentPublicInstance } from "vue";
import { Send, X } from "lucide-vue-next";

import type { ChatMessage as ChatMessageType } from "@/entities/message/model/types";
import { useAuthStore } from "@/entities/user/model/auth.store";
import { useMessageStore } from "@/entities/message/model/message.store";
import { Avatar, AvatarFallback, AvatarImage } from "@/shared/ui/avatar";
import { Button } from "@/shared/ui/button";
import { Card } from "@/shared/ui/card";
import { Input } from "@/shared/ui/input";

const props = defineProps<{
  activeChatUserIds: number[];
  conversationErrors: Record<number, string>;
  draftMessages: Record<number, string>;
}>();

const emit = defineEmits<{
  close: [userId: number];
  send: [userId: number];
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
  () => [messageStore.conversations, props.activeChatUserIds],
  () => {
    for (const userId of props.activeChatUserIds) {
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
    class="w-72 shadow-lg transition-all duration-300"
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
          <Avatar class="h-6 w-6">
            <AvatarImage :src="getUser(userId)?.avatar ?? ''" />
            <AvatarFallback>
              {{ getUser(userId)?.name.substring(0, 2) }}
            </AvatarFallback>
          </Avatar>
          <span
            class="font-semibold text-xs text-foreground truncate max-w-[120px]"
            >{{ getUser(userId)?.name }}</span
          >
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
          class="p-3 space-y-2 overflow-y-auto flex-1"
        >
          <p
            v-if="messageStore.loadingConversations[userId]"
            class="py-2 text-center text-xs text-muted-foreground"
          >
            Carregando mensagens...
          </p>
          <p
            v-else-if="props.conversationErrors[userId]"
            class="py-2 text-center text-xs text-destructive"
          >
            {{ props.conversationErrors[userId] }}
          </p>
          <div
            v-for="msg in messageStore.conversations[userId] || []"
            :key="msg.id"
            :class="[
              'flex items-baseline gap-1.5',
              isOwnMessage(msg)
                ? 'flex-row-reverse text-primary-foreground'
                : 'flex-row text-foreground',
            ]"
          >
            <Avatar class="h-6 w-6 shrink-0">
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
              :class="isOwnMessage(msg) ? 'items-end' : 'items-start'"
            >
              <!-- Resposta -->
              <div v-if="msg?.refer && msg?.refer.length > 0">
                <div
                  :class="[
                    'p-2 text-xs',
                    isOwnMessage(msg)
                      ? 'bg-primary text-primary-foreground rounded-lg rounded-tr-none'
                      : 'bg-muted text-foreground rounded-lg rounded-tl-none',
                  ]"
                >
                  <div
                    class="bg-rose-950/40 max-h-8 mb-0.5 text-secondary/90 p-1 rounded-sm text-[10px] line-clamp-2 wrap-break-words"
                  >
                    {{ msg.refer?.at(0)?.text }}
                  </div>

                  <span>{{ msg.text }}</span>
                </div>
              </div>

              <div
                v-else
                :class="[
                  'p-2 text-xs',
                  isOwnMessage(msg)
                    ? 'bg-primary text-primary-foreground rounded-lg rounded-tr-none'
                    : 'bg-muted text-foreground rounded-lg rounded-tl-none',
                ]"
              >
                {{ msg.text }}
              </div>
              <span class="mt-0.5 text-[9px] text-muted-foreground">
                {{ msg.time }}
              </span>
            </div>
          </div>
        </div>

        <!-- Input individual -->
        <form
          @submit.prevent="emit('send', userId)"
          class="p-2 border-t border-border flex items-center gap-1.5 bg-card"
        >
          <Input
            :model-value="props.draftMessages[userId] ?? ''"
            @update:model-value="emit('updateDraft', userId, String($event))"
            placeholder="Escreva algo..."
            class="h-8 text-xs bg-muted/40 flex-1"
          />
          <Button
            type="submit"
            size="icon"
            class="h-8 w-8 shrink-0"
          >
            <Send class="h-3.5 w-3.5" />
          </Button>
        </form>
      </div>
    </Card>
  </div>
</template>

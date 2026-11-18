<script setup lang="ts">
import {
  nextTick,
  onUnmounted,
  ref,
  watch,
  type ComponentPublicInstance,
} from "vue";
import {
  ChevronDown,
  ChevronUp,
  EllipsisVertical,
  LoaderCircle,
  Reply,
  Send,
  X,
} from "lucide-vue-next";

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
import {
  sendTypingSignal,
  subscribeToChatRoom,
} from "@/features/send-message/model/useChatSocket";
import { connectEcho } from "@/app/providers/echo";
import { Check, CheckCheck } from "@lucide/vue";

const {
  activeChatUserIds,
  conversationErrors,
  draftMessages,
  replyingToMessages,
  collapsedChatUserIds = [],
} = defineProps<{
  activeChatUserIds: number[];
  conversationErrors: Record<number, string>;
  draftMessages: Record<number, string>;
  replyingToMessages: Record<number, ChatMessageType | undefined>;
  collapsedChatUserIds?: number[];
}>();

const emit = defineEmits<{
  close: [userId: number];
  send: [userId: number, parentId?: number];
  reply: [userId: number, message: ChatMessageType];
  cancelReply: [userId: number];
  updateDraft: [userId: number, text: string];
  toggleCollapse: [userId: number];
}>();

const authStore = useAuthStore();
const messageStore = useMessageStore();
const chatContainers: Record<number, HTMLElement | null> = {};

const isCollapsed = (userId: number) => {
  return collapsedChatUserIds.includes(userId);
};

const handleToggleCollapse = (userId: number) => {
  const willExpand = isCollapsed(userId);
  emit("toggleCollapse", userId);
  if (willExpand) {
    nextTick(() => {
      scrollToBottom(userId);
    });
  }
};

// Estado de quem está a digitar por conversa { [userId]: { isTyping: boolean, userName: string } }
const typingUsers = ref<
  Record<number, { isTyping: boolean; userName?: string }>
>({});
const roomSubscriptions: Record<number, () => void> = {};
const lastTypingSignals: Record<number, number> = {};
let typingEcho: ReturnType<typeof connectEcho> | undefined;
let typingSubscriptionUserId: number | undefined;

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

const isPrependingMessages = ref<Record<number, boolean>>({});
const lastSeenBottomMessageId: Record<number, number | undefined> = {};

const scrollToBottom = (userId: number) => {
  nextTick(() => {
    const element = chatContainers[userId];
    if (element) {
      element.scrollTop = element.scrollHeight;
    }
  });
};

const handleChatScroll = async (userId: number, event: Event) => {
  const target = event.target as HTMLElement;
  if (!target) return;

  // Quando o scroll estiver próximo do topo (<= 40px)
  if (target.scrollTop <= 40) {
    if (
      messageStore.messagesNextCursor[userId] &&
      !messageStore.loadingMoreMessages[userId] &&
      !messageStore.loadingConversations[userId]
    ) {
      const oldScrollHeight = target.scrollHeight;
      const oldScrollTop = target.scrollTop;

      isPrependingMessages.value[userId] = true;
      try {
        const loaded = await messageStore.loadMoreMessages(userId);
        if (loaded) {
          await nextTick();
          const newScrollHeight = target.scrollHeight;
          target.scrollTop = oldScrollTop + (newScrollHeight - oldScrollHeight);
        }
      } finally {
        await nextTick();
        isPrependingMessages.value[userId] = false;
      }
    }
  }
};

watch(
  () => [messageStore.conversations, activeChatUserIds],
  () => {
    for (const userId of activeChatUserIds) {
      if (isPrependingMessages.value[userId]) continue;

      const currentMsgs = messageStore.conversations[userId] || [];
      const lastMsgId = currentMsgs[currentMsgs.length - 1]?.id;
      if (lastMsgId !== lastSeenBottomMessageId[userId]) {
        lastSeenBottomMessageId[userId] = lastMsgId;
        scrollToBottom(userId);
      }
    }
  },
  { deep: true, flush: "post" },
);

watch(
  () => [...collapsedChatUserIds],
  (newVal, oldVal) => {
    for (const userId of activeChatUserIds) {
      const wasCollapsed = oldVal?.includes(userId) ?? false;
      const isNowCollapsed = newVal?.includes(userId) ?? false;
      if (wasCollapsed && !isNowCollapsed) {
        scrollToBottom(userId);
      }
    }
  },
);

// Assina a sala pelo chat_id resolvido ao abrir a conversa.
watch(
  () => ({
    userId: authStore.user?.id,
    chatUserIds: [...activeChatUserIds],
    chatIds: activeChatUserIds.map((id) => messageStore.chatIdsByUser[id]),
  }),
  ({ userId, chatUserIds }) => {
    const activeIds = new Set(chatUserIds);

    if (typingSubscriptionUserId !== userId) {
      Object.entries(roomSubscriptions).forEach(([id, stop]) => {
        stop();
        delete roomSubscriptions[Number(id)];
        delete typingUsers.value[Number(id)];
        delete lastTypingSignals[Number(id)];
      });
      typingSubscriptionUserId = userId;
    }

    for (const [id, stop] of Object.entries(roomSubscriptions)) {
      const chatUserId = Number(id);
      if (!activeIds.has(chatUserId) || userId === undefined) {
        stop();
        delete roomSubscriptions[chatUserId];
        delete typingUsers.value[chatUserId];
        delete lastTypingSignals[chatUserId];
      }
    }

    if (userId === undefined) {
      typingEcho = undefined;
      return;
    }

    typingEcho = connectEcho();

    for (const chatUserId of chatUserIds) {
      if (roomSubscriptions[chatUserId]) continue;

      const chatId = messageStore.chatIdsByUser[chatUserId];
      if (chatId === undefined) continue;

      roomSubscriptions[chatUserId] = subscribeToChatRoom(
        typingEcho,
        chatId,
        userId,
        chatUserId,
        (typingUser, isTyping) => {
          typingUsers.value[chatUserId] = {
            isTyping,
            userName: typingUser.name,
          };
        },
      );
    }
  },
  { immediate: true },
);

onUnmounted(() => {
  Object.values(roomSubscriptions).forEach((stop) => stop());
});

const handleDraftUpdate = (userId: number, text: string) => {
  emit("updateDraft", userId, text);

  const senderId = authStore.user?.id;
  const senderName = authStore.user?.name ?? "Alguém";
  if (senderId === undefined) return;

  const chatId = messageStore.chatIdsByUser[userId];
  if (chatId === undefined) return;

  const now = Date.now();
  const isTyping = text.trim().length > 0;
  if (isTyping && now - (lastTypingSignals[userId] ?? 0) < 1000) return;

  lastTypingSignals[userId] = now;
  if (typingEcho) {
    sendTypingSignal(typingEcho, chatId, senderId, senderName, isTyping);
  }
};
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
        class="flex items-center justify-between p-2.5 bg-card hover:bg-muted/30 transition-colors cursor-pointer select-none"
        :class="{ 'border-b border-border/60': !isCollapsed(userId) }"
        @click="handleToggleCollapse(userId)"
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
            <span class="font-semibold text-xs text-foreground truncate w-full">
              {{ getUser(userId)?.name }}
            </span>
            <span
              v-if="typingUsers[userId]?.isTyping"
              class="w-full font-thin text-[11px] truncate text-primary animate-pulse"
            >
              digitando...
            </span>
            <span
              v-else
              class="w-full font-thin text-[11px] truncate"
            >
              {{ getUser(userId)?.department }}
            </span>
          </div>
        </div>

        <div class="flex items-center gap-1">
          <Button
            variant="ghost"
            size="icon"
            class="h-6 w-6 text-muted-foreground hover:text-foreground cursor-pointer"
            :title="
              isCollapsed(userId) ? 'Expandir conversa' : 'Minimizar conversa'
            "
            @click.stop="handleToggleCollapse(userId)"
          >
          </Button>

          <Button
            variant="ghost"
            size="icon"
            class="h-6 w-6 text-muted-foreground hover:text-foreground cursor-pointer"
            title="Fechar conversa"
            @click.stop="emit('close', userId)"
          >
            <X class="h-3.5 w-3.5" />
          </Button>
        </div>
      </div>

      <!-- Corpo da Janela Individual -->
      <div
        v-show="!isCollapsed(userId)"
        class="h-80 flex flex-col justify-between"
      >
        <!-- Mensagem  -->
        <div
          :ref="(el) => setChatContainer(userId, el)"
          class="px-2 py-5 space-y-4 overflow-y-auto flex-1"
          @scroll.passive="(e) => handleChatScroll(userId, e)"
        >
          <!-- Indicador de carregamento de mensagens anteriores (Infinite Scroll para o topo) -->
          <div
            v-if="messageStore.loadingMoreMessages[userId]"
            class="py-2 flex items-center justify-center gap-1.5 text-[11px] text-muted-foreground"
          >
            <LoaderCircle class="h-3.5 w-3.5 animate-spin text-primary" />
            <span>Carregando mensagens anteriores...</span>
          </div>

          <!-- Início da conversa -->
          <div
            v-else-if="
              !messageStore.messagesNextCursor[userId] &&
              !messageStore.loadingConversations[userId] &&
              (messageStore.conversations[userId]?.length || 0) > 0
            "
            class="py-1 text-center text-[10px] text-muted-foreground/60 select-none"
          >
            Início da conversa
          </div>

          <p
            v-if="messageStore.loadingConversations[userId]"
            class="py-2 text-center text-xs text-muted-foreground flex items-center justify-center gap-1.5"
          >
            <LoaderCircle class="h-3.5 w-3.5 animate-spin text-primary" />
            <span>Carregando mensagens...</span>
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
                    'text-right text-[9px] inline-flex flex-row-reverse justify-between w-full items-center',
                    isOwnMessage(msg) ? 'text-muted' : 'text-foreground',
                  ]"
                >
                  <Component
                    :is="msg.isRead ? CheckCheck : Check"
                    :class="[
                      'h-3 w-3',
                      msg.isRead
                        ? 'text-blue-500'
                        : isOwnMessage(msg)
                          ? 'text-muted'
                          : 'text-muted-foreground',
                    ]"
                  />
                  <span>{{ msg.time }}</span>
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
          @submit.prevent="
            emit('send', userId, replyingToMessages[userId]?.id);
            handleDraftUpdate(userId, '');
          "
          class="p-2 border-t border-border flex flex-col gap-1.5 bg-card"
        >
          <p
            v-if="typingUsers[userId]?.isTyping"
            class="px-2 text-[11px] italic text-muted-foreground animate-pulse"
          >
            {{ typingUsers[userId]?.userName || getUser(userId)?.name }} está
            digitando...
          </p>

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
              @update:model-value="handleDraftUpdate(userId, String($event))"
              @keydown.enter.exact.prevent="
                emit('send', userId, replyingToMessages[userId]?.id);
                handleDraftUpdate(userId, '');
              "
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

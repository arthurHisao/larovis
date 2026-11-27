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
  MoreVertical,
  Pencil,
  Reply,
  Send,
  Trash2,
  Users,
  X,
} from "lucide-vue-next";

import type {
  ChatMessage as ChatMessageType,
  ChatUser,
} from "@/entities/message/model/types";
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

const props = withDefaults(
  defineProps<{
    activeChatUserIds: number[];
    activeGroupIds?: number[];
    conversationErrors: Record<number, string>;
    draftMessages: Record<number, string>;
    replyingToMessages: Record<number, ChatMessageType | undefined>;
    collapsedChatUserIds?: number[];
    collapsedGroupIds?: number[];
  }>(),
  {
    activeGroupIds: () => [],
    collapsedChatUserIds: () => [],
    collapsedGroupIds: () => [],
  },
);

const emit = defineEmits<{
  close: [userId: number];
  send: [userId: number, parentId?: number];
  closeGroup: [groupId: number];
  sendGroup: [groupId: number, parentId?: number];
  reply: [userId: number, message: ChatMessageType];
  cancelReply: [userId: number];
  updateDraft: [userId: number, text: string];
  toggleCollapse: [userId: number];
  toggleGroupCollapse: [groupId: number];
  editGroup: [group: ChatUser];
  deleteGroup: [group: ChatUser];
}>();

const authStore = useAuthStore();
const messageStore = useMessageStore();

const isGroupAdmin = (group?: ChatUser | null) => {
  if (!group) return false;
  const currentUserId = authStore.user?.id;
  if (!currentUserId) return false;

  if (typeof group.isAdmin === "boolean") return group.isAdmin;
  if (typeof (group as any).is_admin === "boolean")
    return (group as any).is_admin;
  if (typeof group.isCreator === "boolean") return group.isCreator;
  if (typeof (group as any).is_creator === "boolean")
    return (group as any).is_creator;

  const creatorId =
    group.creatorId ??
    (group as any).creator_id ??
    (group as any).created_by;

  if (creatorId !== undefined && creatorId !== null) {
    return Number(creatorId) === Number(currentUserId);
  }

  return true;
};

const chatContainers: Record<string, HTMLElement | null> = {};
const isPrependingMessages = ref<Record<string, boolean>>({});
const lastSeenBottomMessageId: Record<string, number | undefined> = {};

const roomKey = (kind: "user" | "group", id: number) => `${kind}-${id}`;

const getGroup = (groupId: number) =>
  messageStore.unifiedContacts.find(
    (item) => item.isGroup && (item.chatId ?? item.id) === groupId,
  );

const isCollapsed = (userId: number) => {
  return props.collapsedChatUserIds.includes(userId);
};

const isGroupCollapsed = (groupId: number) => {
  return props.collapsedGroupIds.includes(groupId);
};

const handleToggleCollapse = (userId: number) => {
  const willExpand = isCollapsed(userId);
  emit("toggleCollapse", userId);
  if (willExpand) {
    nextTick(() => {
      scrollToBottom(roomKey("user", userId));
    });
  }
};

const handleToggleGroupCollapse = (groupId: number) => {
  const willExpand = isGroupCollapsed(groupId);
  emit("toggleGroupCollapse", groupId);
  if (willExpand) {
    nextTick(() => {
      scrollToBottom(roomKey("group", groupId));
    });
  }
};

// --- Painel de membros do grupo ---
const showMembersPanel = ref<Record<number, boolean>>({});

const toggleMembersPanel = (groupId: number) => {
  const isOpening = !showMembersPanel.value[groupId];
  showMembersPanel.value[groupId] = isOpening;
  if (isOpening && !messageStore.groupMembers?.[groupId]?.length) {
    messageStore.fetchGroupMembers(groupId);
  }
};

const typingUsers = ref<
  Record<string, { isTyping: boolean; userName?: string }>
>({});
const roomSubscriptions: Record<string, () => void> = {};
const lastTypingSignals: Record<string, number> = {};
let typingEcho: ReturnType<typeof connectEcho> | undefined;
let typingSubscriptionUserId: number | undefined;

const getUser = (id: number) =>
  messageStore.unifiedContacts.find((user) => !user.isGroup && user.id === id);

const isOwnMessage = (message: ChatMessageType) =>
  message.senderId === authStore.user?.id;

const getMessageUser = (message: ChatMessageType) =>
  isOwnMessage(message) ? authStore.user : getUser(message.senderId);

const getSenderName = (senderId: number | undefined) => {
  if (senderId === authStore.user?.id) return "Você";
  return getUser(senderId ?? -1)?.name ?? "Mensagem";
};

const setChatContainer = (
  key: string,
  element: Element | ComponentPublicInstance | null,
) => {
  const isNewElement = !chatContainers[key] && element instanceof HTMLElement;
  chatContainers[key] = element instanceof HTMLElement ? element : null;

  if (isNewElement) {
    scrollToBottom(key);
  }
};

const scrollToBottom = (key: string) => {
  nextTick(() => {
    const element = chatContainers[key];
    if (element) {
      element.scrollTop = element.scrollHeight;
    }
  });
};

const handleChatScroll = async (userId: number, event: Event) => {
  const target = event.target as HTMLElement;
  if (!target) return;

  const key = roomKey("user", userId);
  if (target.scrollTop <= 40) {
    if (
      messageStore.messagesNextCursor[userId] &&
      !messageStore.loadingMoreMessages[userId] &&
      !messageStore.loadingConversations[userId]
    ) {
      const oldScrollHeight = target.scrollHeight;
      const oldScrollTop = target.scrollTop;

      isPrependingMessages.value[key] = true;
      try {
        const loaded = await messageStore.loadMoreMessages(userId);
        if (loaded) {
          await nextTick();
          const newScrollHeight = target.scrollHeight;
          target.scrollTop = oldScrollTop + (newScrollHeight - oldScrollHeight);
        }
      } finally {
        await nextTick();
        isPrependingMessages.value[key] = false;
      }
    }
  }
};

const handleGroupScroll = async (groupId: number, event: Event) => {
  const target = event.target as HTMLElement;
  if (!target) return;

  const key = roomKey("group", groupId);
  if (target.scrollTop <= 40) {
    if (
      messageStore.groupMessagesNextCursor?.[groupId] &&
      !messageStore.loadingMoreGroupMessages?.[groupId] &&
      !messageStore.loadingGroupConversations?.[groupId]
    ) {
      const oldScrollHeight = target.scrollHeight;
      const oldScrollTop = target.scrollTop;

      isPrependingMessages.value[key] = true;
      try {
        const loaded = await messageStore.loadMoreGroupMessages?.(groupId);
        if (loaded) {
          await nextTick();
          const newScrollHeight = target.scrollHeight;
          target.scrollTop = oldScrollTop + (newScrollHeight - oldScrollHeight);
        }
      } finally {
        await nextTick();
        isPrependingMessages.value[key] = false;
      }
    }
  }
};

watch(
  () => [
    messageStore.conversations,
    messageStore.groupMessages,
    props.activeChatUserIds,
    props.activeGroupIds,
  ],
  () => {
    for (const userId of props.activeChatUserIds) {
      const key = roomKey("user", userId);
      if (isPrependingMessages.value[key]) continue;

      const currentMsgs = messageStore.conversations[userId] || [];
      const lastMsgId = currentMsgs[currentMsgs.length - 1]?.id;
      if (lastMsgId !== lastSeenBottomMessageId[key]) {
        lastSeenBottomMessageId[key] = lastMsgId;
        scrollToBottom(key);
      }
    }

    for (const groupId of props.activeGroupIds) {
      const key = roomKey("group", groupId);
      if (isPrependingMessages.value[key]) continue;

      const currentMsgs = messageStore.groupMessages?.[groupId] || [];
      const lastMsgId = currentMsgs[currentMsgs.length - 1]?.id;
      if (lastMsgId !== lastSeenBottomMessageId[key]) {
        lastSeenBottomMessageId[key] = lastMsgId;
        scrollToBottom(key);
      }
    }
  },
  { deep: true, flush: "post" },
);

watch(
  () => [[...props.collapsedChatUserIds], [...props.collapsedGroupIds]],
  ([newUserCol, newGroupCol], [oldUserCol, oldGroupCol]) => {
    for (const userId of props.activeChatUserIds) {
      const wasCollapsed = oldUserCol?.includes(userId) ?? false;
      const isNowCollapsed = newUserCol?.includes(userId) ?? false;
      if (wasCollapsed && !isNowCollapsed) {
        scrollToBottom(roomKey("user", userId));
      }
    }

    for (const groupId of props.activeGroupIds) {
      const wasCollapsed = oldGroupCol?.includes(groupId) ?? false;
      const isNowCollapsed = newGroupCol?.includes(groupId) ?? false;
      if (wasCollapsed && !isNowCollapsed) {
        scrollToBottom(roomKey("group", groupId));
      }
    }
  },
);

watch(
  () => [[...props.activeChatUserIds], [...props.activeGroupIds]],
  ([newUserIds, newGroupIds], [oldUserIds, oldGroupIds]) => {
    if (oldUserIds) {
      for (const id of oldUserIds) {
        if (!newUserIds.includes(id)) {
          const key = roomKey("user", id);
          delete chatContainers[key];
          delete lastSeenBottomMessageId[key];
          delete isPrependingMessages.value[key];
        }
      }
    }

    if (oldGroupIds) {
      for (const id of oldGroupIds) {
        if (!newGroupIds.includes(id)) {
          const key = roomKey("group", id);
          delete chatContainers[key];
          delete lastSeenBottomMessageId[key];
          delete isPrependingMessages.value[key];
        }
      }
    }

    for (const id of newUserIds) {
      if (!oldUserIds || !oldUserIds.includes(id)) {
        const key = roomKey("user", id);
        delete lastSeenBottomMessageId[key];
        scrollToBottom(key);
      }
    }

    for (const id of newGroupIds) {
      if (!oldGroupIds || !oldGroupIds.includes(id)) {
        const key = roomKey("group", id);
        delete lastSeenBottomMessageId[key];
        scrollToBottom(key);
      }
    }
  },
);

watch(
  () => ({
    userId: authStore.user?.id,
    chatUserIds: [...props.activeChatUserIds],
    groupIds: [...props.activeGroupIds],
    chatIds: props.activeChatUserIds.map(
      (id) => messageStore.chatIdsByUser[id],
    ),
  }),
  ({ userId, chatUserIds, groupIds }) => {
    const activeKeys = new Set<string>([
      ...chatUserIds.map((id) => roomKey("user", id)),
      ...groupIds.map((id) => roomKey("group", id)),
    ]);

    if (typingSubscriptionUserId !== userId) {
      Object.entries(roomSubscriptions).forEach(([key, stop]) => {
        stop();
        delete roomSubscriptions[key];
        delete typingUsers.value[key];
        delete lastTypingSignals[key];
      });
      typingSubscriptionUserId = userId;
    }

    for (const [key, stop] of Object.entries(roomSubscriptions)) {
      if (!activeKeys.has(key) || userId === undefined) {
        stop();
        delete roomSubscriptions[key];
        delete typingUsers.value[key];
        delete lastTypingSignals[key];
      }
    }

    if (userId === undefined) {
      typingEcho = undefined;
      return;
    }

    typingEcho = connectEcho();

    for (const chatUserId of chatUserIds) {
      const key = roomKey("user", chatUserId);
      if (roomSubscriptions[key]) continue;

      const chatId = messageStore.chatIdsByUser[chatUserId];
      if (chatId === undefined) continue;

      roomSubscriptions[key] = subscribeToChatRoom(
        typingEcho,
        chatId,
        userId,
        chatUserId,
        (typingUser, isTyping) => {
          typingUsers.value[key] = {
            isTyping,
            userName: typingUser.name,
          };
        },
        false,
      );
    }

    for (const groupId of groupIds) {
      const key = roomKey("group", groupId);
      if (roomSubscriptions[key]) continue;

      roomSubscriptions[key] = subscribeToChatRoom(
        typingEcho,
        groupId,
        userId,
        0,
        (typingUser, isTyping) => {
          typingUsers.value[key] = {
            isTyping,
            userName: typingUser.name,
          };
        },
        true,
      );
    }
  },
  { immediate: true },
);

onUnmounted(() => {
  Object.values(roomSubscriptions).forEach((stop) => stop());
});

const handleDraftUpdate = (
  id: number,
  text: string,
  kind: "user" | "group" = "user",
) => {
  emit("updateDraft", id, text);

  const senderId = authStore.user?.id;
  const senderName = authStore.user?.name ?? "Alguém";
  if (senderId === undefined) return;

  const chatId = kind === "group" ? id : messageStore.chatIdsByUser[id];
  if (chatId === undefined) return;

  const key = roomKey(kind, id);
  const now = Date.now();
  const isTyping = text.trim().length > 0;
  if (isTyping && now - (lastTypingSignals[key] ?? 0) < 1000) return;

  lastTypingSignals[key] = now;
  if (typingEcho) {
    sendTypingSignal(typingEcho, chatId, senderId, senderName, isTyping);
  }
};
</script>

<template>
  <!-- Janelas de Grupo -->
  <div
    v-for="groupId in activeGroupIds"
    :key="`group-${groupId}`"
    class="w-80 shadow-lg transition-all duration-300"
  >
    <Card
      v-if="getGroup(groupId)"
      class="border-border bg-card overflow-hidden rounded-b-none border-b-0 py-0 gap-0"
    >
      <div
        class="flex items-center justify-between p-2.5 bg-card hover:bg-muted/30 transition-colors cursor-pointer select-none"
        :class="{ 'border-b border-border/60': !isGroupCollapsed(groupId) }"
        @click="handleToggleGroupCollapse(groupId)"
      >
        <div class="flex items-center gap-2 min-w-0">
          <Avatar
            :key="getGroup(groupId)?.avatar ?? 'no-avatar'"
            class="h-8 w-8 shrink-0"
          >
            <AvatarImage
              v-if="getGroup(groupId)?.avatar"
              :src="getGroup(groupId)?.avatar ?? ''"
              :alt="getGroup(groupId)?.name ?? 'Grupo'"
            />
            <AvatarFallback>
              <Users class="h-4 w-4" />
            </AvatarFallback>
          </Avatar>

          <div class="inline-flex items-start flex-col truncate max-w-36">
            <span class="font-semibold text-xs text-foreground truncate w-full">
              {{ getGroup(groupId)?.name }}
            </span>
            <span
              v-if="typingUsers[roomKey('group', groupId)]?.isTyping"
              class="w-full font-thin text-[11px] truncate text-primary animate-pulse"
            >
              {{ typingUsers[roomKey("group", groupId)]?.userName || "Alguém" }}
              digitando...
            </span>
            <span
              v-else
              class="w-full font-thin text-[11px] text-muted-foreground truncate"
            >
              Grupo
            </span>
          </div>
        </div>

        <div class="flex items-center gap-1">
          <Button
            variant="ghost"
            size="icon"
            class="h-6 w-6 text-muted-foreground hover:text-foreground cursor-pointer"
            :title="showMembersPanel[groupId] ? 'Fechar membros' : 'Ver membros'"
            @click.stop="toggleMembersPanel(groupId)"
          >
            <Users class="h-3.5 w-3.5" />
          </Button>

          <DropdownMenu :modal="false">
            <DropdownMenuTrigger as-child>
              <Button
                variant="ghost"
                size="icon"
                class="h-6 w-6 text-muted-foreground hover:text-foreground cursor-pointer"
                title="Opções do grupo"
                @click.stop
              >
                <MoreVertical class="h-3.5 w-3.5" />
              </Button>
            </DropdownMenuTrigger>

            <DropdownMenuContent align="end">
              <DropdownMenuGroup>
                <DropdownMenuItem
                  v-if="getGroup(groupId)"
                  @click.stop="emit('editGroup', getGroup(groupId)!)"
                >
                  <Pencil class="h-3.5 w-3.5 mr-1.5" />
                  Editar Grupo
                </DropdownMenuItem>

                <DropdownMenuItem
                  v-if="getGroup(groupId) && isGroupAdmin(getGroup(groupId))"
                  class="text-destructive focus:text-destructive"
                  @click.stop="emit('deleteGroup', getGroup(groupId)!)"
                >
                  <Trash2 class="h-3.5 w-3.5 mr-1.5 text-destructive" />
                  Deletar Grupo
                </DropdownMenuItem>
              </DropdownMenuGroup>
            </DropdownMenuContent>
          </DropdownMenu>

          <Button
            variant="ghost"
            size="icon"
            class="h-6 w-6 text-muted-foreground hover:text-foreground cursor-pointer"
            :title="
              isGroupCollapsed(groupId)
                ? 'Expandir conversa'
                : 'Minimizar conversa'
            "
            @click.stop="handleToggleGroupCollapse(groupId)"
          >
            <ChevronUp
              v-if="isGroupCollapsed(groupId)"
              class="h-3.5 w-3.5"
            />
            <ChevronDown
              v-else
              class="h-3.5 w-3.5"
            />
          </Button>

          <Button
            variant="ghost"
            size="icon"
            class="h-6 w-6 text-muted-foreground hover:text-foreground cursor-pointer"
            title="Fechar conversa"
            @click.stop="emit('closeGroup', groupId)"
          >
            <X class="h-3.5 w-3.5" />
          </Button>
        </div>
      </div>

      <Transition
        enter-active-class="transition-all duration-300 ease-out overflow-hidden"
        enter-from-class="max-h-0 opacity-0"
        enter-to-class="max-h-120 opacity-100"
        leave-active-class="transition-all duration-300 ease-in overflow-hidden"
        leave-from-class="max-h-120 opacity-100"
        leave-to-class="max-h-0 opacity-0"
      >
        <div
          v-show="!isGroupCollapsed(groupId)"
          class="h-96 flex flex-col justify-between relative"
        >
          <div
            :ref="(el) => setChatContainer(roomKey('group', groupId), el)"
            class="px-2 py-5 space-y-4 overflow-y-auto flex-1"
            @scroll.passive="(e) => handleGroupScroll(groupId, e)"
          >
            <div
              v-if="messageStore.loadingMoreGroupMessages?.[groupId]"
              class="py-2 flex items-center justify-center gap-1.5 text-[11px] text-muted-foreground"
            >
              <LoaderCircle class="h-3.5 w-3.5 animate-spin text-primary" />
              <span>Carregando mensagens anteriores...</span>
            </div>

            <div
              v-else-if="
                !messageStore.groupMessagesNextCursor?.[groupId] &&
                !messageStore.loadingGroupConversations?.[groupId] &&
                (messageStore.groupMessages?.[groupId]?.length || 0) > 0
              "
              class="py-1 text-center text-[10px] text-muted-foreground/60 select-none"
            >
              Início da conversa
            </div>

            <p
              v-if="messageStore.loadingGroupConversations?.[groupId]"
              class="py-2 text-center text-xs text-muted-foreground flex items-center justify-center gap-1.5"
            >
              <LoaderCircle class="h-3.5 w-3.5 animate-spin text-primary" />
              <span>Carregando mensagens...</span>
            </p>
            <p
              v-else-if="conversationErrors[groupId]"
              class="py-2 text-center text-xs text-destructive"
            >
              {{ conversationErrors[groupId] }}
            </p>

            <div
              v-for="msg in messageStore.groupMessages?.[groupId] || []"
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
                :class="
                  isOwnMessage(msg) ? 'items-end mr-7' : 'items-start ml-7'
                "
              >
                <span
                  v-if="!isOwnMessage(msg)"
                  class="text-[10px] font-medium text-muted-foreground mb-0.5 px-1"
                >
                  {{ getSenderName(msg.senderId) }}
                </span>

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
                        <DropdownMenuItem @select="emit('reply', groupId, msg)">
                          <Reply />
                          Responder
                        </DropdownMenuItem>
                      </DropdownMenuGroup>
                    </DropdownMenuContent>
                  </DropdownMenu>
                </div>
              </div>
            </div>
          </div>

          <!-- Painel deslizante de membros do grupo -->
          <Transition
            enter-active-class="transition-all duration-300 ease-out overflow-hidden"
            enter-from-class="max-h-0 opacity-0"
            enter-to-class="max-h-96 opacity-100"
            leave-active-class="transition-all duration-300 ease-in overflow-hidden"
            leave-from-class="max-h-96 opacity-100"
            leave-to-class="max-h-0 opacity-0"
          >
            <div
              v-show="showMembersPanel[groupId]"
              class="absolute inset-0 z-10 bg-card flex flex-col"
            >
              <div class="flex items-center justify-between px-3 py-2 border-b border-border">
                <span class="text-xs font-semibold text-foreground">Membros do grupo</span>
                <Button
                  variant="ghost"
                  size="icon"
                  class="h-6 w-6 text-muted-foreground hover:text-foreground"
                  @click="showMembersPanel[groupId] = false"
                >
                  <X class="h-3.5 w-3.5" />
                </Button>
              </div>

              <div class="flex-1 overflow-y-auto py-2">
                <div
                  v-if="messageStore.loadingGroupMembers?.[groupId]"
                  class="flex items-center justify-center gap-1.5 py-6 text-xs text-muted-foreground"
                >
                  <LoaderCircle class="h-4 w-4 animate-spin text-primary" />
                  <span>Carregando membros...</span>
                </div>

                <div
                  v-else-if="!messageStore.groupMembers?.[groupId]?.length"
                  class="py-6 text-center text-xs text-muted-foreground"
                >
                  Nenhum membro encontrado.
                </div>

                <div
                  v-for="member in messageStore.groupMembers?.[groupId]"
                  :key="member.id"
                  class="flex items-center gap-2.5 px-3 py-1.5 hover:bg-muted/40 transition-colors"
                >
                  <Avatar class="h-7 w-7 shrink-0">
                    <AvatarImage :src="member.avatar ?? ''" :alt="member.name" />
                    <AvatarFallback class="text-[10px]">
                      {{ member.name?.substring(0, 2).toUpperCase() ?? '?' }}
                    </AvatarFallback>
                  </Avatar>
                  <div class="flex flex-col min-w-0">
                    <span class="text-xs font-medium text-foreground truncate">
                      {{ member.name }}
                      <span
                        v-if="member.id === authStore.user?.id"
                        class="text-[10px] text-muted-foreground font-normal ml-1"
                      >(você)</span>
                    </span>
                    <span class="text-[10px] text-muted-foreground truncate">
                      {{ member.email }}
                    </span>
                  </div>
                </div>
              </div>
            </div>
          </Transition>

          <form
            @submit.prevent="
              emit('sendGroup', groupId, replyingToMessages[groupId]?.id);
              handleDraftUpdate(groupId, '', 'group');
            "
            class="p-2 border-t border-border flex flex-col gap-1.5 bg-card"
          >
            <p
              v-if="typingUsers[roomKey('group', groupId)]?.isTyping"
              class="px-2 text-[11px] italic text-muted-foreground animate-pulse"
            >
              {{ typingUsers[roomKey("group", groupId)]?.userName || "Alguém" }}
              está digitando...
            </p>

            <div
              v-if="replyingToMessages[groupId]"
              class="flex items-center justify-between gap-2 px-2 py-1.5 bg-muted/60 border-l-2 border-primary rounded-sm text-[11px] text-muted-foreground animate-fade-in"
            >
              <div class="flex flex-col truncate flex-1">
                <span class="font-semibold text-primary text-xs">
                  Respondendo a
                  {{ getSenderName(replyingToMessages[groupId]?.senderId) }}
                </span>
                <span class="truncate">
                  {{ replyingToMessages[groupId]?.text }}
                </span>
              </div>

              <Button
                type="button"
                variant="ghost"
                size="icon"
                class="h-4 w-4 rounded-full opacity-70 hover:opacity-100 shrink-0"
                @click="emit('cancelReply', groupId)"
              >
                <X class="h-3 w-3" />
              </Button>
            </div>

            <div class="flex items-center gap-1.5 w-full">
              <Textarea
                :model-value="draftMessages[groupId] ?? ''"
                @update:model-value="
                  handleDraftUpdate(groupId, String($event), 'group')
                "
                @keydown.enter.exact.prevent="
                  emit('sendGroup', groupId, replyingToMessages[groupId]?.id);
                  handleDraftUpdate(groupId, '', 'group');
                "
                placeholder="Escreva algo no grupo..."
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
      </Transition>
    </Card>
  </div>
  <template>
    <!-- Janelas Individuais (1-a-1) -->
    <div
      v-for="userId in activeChatUserIds"
      :key="`user-${userId}`"
      class="w-80 shadow-lg transition-all duration-300"
    >
      <Card
        v-if="getUser(userId)"
        class="border-border bg-card overflow-hidden rounded-b-none border-b-0 py-0 gap-0"
      >
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
              <span
                class="font-semibold text-xs text-foreground truncate w-full"
              >
                {{ getUser(userId)?.name }}
              </span>
              <span
                v-if="typingUsers[roomKey('user', userId)]?.isTyping"
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
              <ChevronUp
                v-if="isCollapsed(userId)"
                class="h-3.5 w-3.5"
              />
              <ChevronDown
                v-else
                class="h-3.5 w-3.5"
              />
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

        <Transition
          enter-active-class="transition-all duration-300 ease-out overflow-hidden"
          enter-from-class="max-h-0 opacity-0"
          enter-to-class="max-h-120 opacity-100"
          leave-active-class="transition-all duration-300 ease-in overflow-hidden"
          leave-from-class="max-h-120 opacity-100"
          leave-to-class="max-h-0 opacity-0"
        >
          <div
            v-show="!isCollapsed(userId)"
            class="h-96 flex flex-col justify-between"
          >
            <div
              :ref="(el) => setChatContainer(roomKey('user', userId), el)"
              class="px-2 py-5 space-y-4 overflow-y-auto flex-1"
              @scroll.passive="(e) => handleChatScroll(userId, e)"
            >
              <div
                v-if="messageStore.loadingMoreMessages[userId]"
                class="py-2 flex items-center justify-center gap-1.5 text-[11px] text-muted-foreground"
              >
                <LoaderCircle class="h-3.5 w-3.5 animate-spin text-primary" />
                <span>Carregando mensagens anteriores...</span>
              </div>

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
                      getMessageUser(msg)
                        ?.name?.substring(0, 2)
                        .toUpperCase() ?? "?"
                    }}
                  </AvatarFallback>
                </Avatar>

                <div
                  class="flex max-w-[85%] flex-col"
                  :class="
                    isOwnMessage(msg) ? 'items-end mr-7' : 'items-start ml-7'
                  "
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
                          <DropdownMenuItem
                            @select="emit('reply', userId, msg)"
                          >
                            <Reply />
                            Responder
                          </DropdownMenuItem>
                        </DropdownMenuGroup>
                      </DropdownMenuContent>
                    </DropdownMenu>
                  </div>
                </div>
              </div>
            </div>

            <form
              @submit.prevent="
                emit('send', userId, replyingToMessages[userId]?.id);
                handleDraftUpdate(userId, '', 'user');
              "
              class="p-2 border-t border-border flex flex-col gap-1.5 bg-card"
            >
              <p
                v-if="typingUsers[roomKey('user', userId)]?.isTyping"
                class="px-2 text-[11px] italic text-muted-foreground animate-pulse"
              >
                {{
                  typingUsers[roomKey("user", userId)]?.userName ||
                  getUser(userId)?.name
                }}
                está digitando...
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
                  @update:model-value="
                    handleDraftUpdate(userId, String($event), 'user')
                  "
                  @keydown.enter.exact.prevent="
                    emit('send', userId, replyingToMessages[userId]?.id);
                    handleDraftUpdate(userId, '', 'user');
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
        </Transition>
      </Card>
    </div>
  </template>
</template>

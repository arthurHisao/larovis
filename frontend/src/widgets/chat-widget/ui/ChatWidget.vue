<script setup lang="ts">
import { onMounted, ref } from "vue";
import { MessageCircle, ChevronUp, ChevronDown } from "lucide-vue-next";

import { Card } from "@/shared/ui/card";
import { Button } from "@/shared/ui/button";
import { useMessageStore } from "@/entities/message/model/message.store";
import type {
  ChatMessage as ChatMessageType,
  ChatUser,
  UpdateGroupPayload,
} from "@/entities/message/model/types";
import ChatOnlineUsers from "./ChatOnlineUsers.vue";
import ChatMessage from "./ChatMessage.vue";
import ChatCreateGroup from "./ChatCreateGroup.vue";
import ChatEditGroup from "./ChatEditGroup.vue";
import ChatDeleteGroup from "./ChatDeleteGroup.vue";
import { playBellSound } from "@/shared/lib/play-bell-sound.ts";

const isListOpen = ref(false);
const isCreateGroupOpen = ref(false);
const isCreatingGroup = ref(false);

const isEditGroupOpen = ref(false);
const groupToEdit = ref<ChatUser | null>(null);
const isEditingGroup = ref(false);

const isDeleteGroupOpen = ref(false);
const groupToDelete = ref<ChatUser | null>(null);
const isDeletingGroup = ref(false);

const activeChatUserIds = ref<number[]>([]);
const collapsedChatUserIds = ref<number[]>([]);

// Janelas de grupo separadas pelo chatId do grupo
const activeGroupIds = ref<number[]>([]);
const collapsedGroupIds = ref<number[]>([]);

const draftMessages = ref<Record<number, string>>({});
const replyingToMessages = ref<Record<number, ChatMessageType | undefined>>({});

const messageStore = useMessageStore();
const onlineUsersError = ref("");
const conversationErrors = ref<Record<number, string>>({});
let conversationListRequestId = 0;

const loadConversations = async (search = "") => {
  const requestId = ++conversationListRequestId;
  onlineUsersError.value = "";

  try {
    await messageStore.fetchConversationsList(search);
  } catch (error) {
    console.error("Erro ao carregar conversas:", error);
    if (requestId === conversationListRequestId) {
      onlineUsersError.value = "Não foi possível carregar as conversas.";
    }
  }
};

const loadMoreConversations = async () => {
  try {
    await messageStore.loadMoreConversations();
  } catch (error) {
    console.error("Erro ao carregar mais conversas:", error);
  }
};

onMounted(loadConversations);

const handleCreateGroup = async (payload: {
  name: string;
  memberIds: number[];
}) => {
  isCreatingGroup.value = true;
  try {
    await messageStore.createGroup(payload);
    isCreateGroupOpen.value = false;
  } catch (error) {
    console.error("Erro ao criar grupo:", error);
  } finally {
    isCreatingGroup.value = false;
  }
};

const openEditGroup = (group: ChatUser) => {
  groupToEdit.value = group;
  isEditGroupOpen.value = true;
};

const handleUpdateGroup = async (payload: UpdateGroupPayload) => {
  isEditingGroup.value = true;
  try {
    await messageStore.updateGroup(payload);
    isEditGroupOpen.value = false;
    // Atualiza o objeto que está sendo editado para refletir no modal caso abra de novo
    if (groupToEdit.value) {
      const updated = messageStore.conversationsList.find(
        (item) => item.isGroup && (item.chatId ?? item.id) === payload.id,
      );
      if (updated) groupToEdit.value = { ...updated };
    }
  } catch (error) {
    console.error("Erro ao atualizar grupo:", error);
  } finally {
    isEditingGroup.value = false;
  }
};

const openDeleteGroup = (group: ChatUser) => {
  groupToDelete.value = group;
  isDeleteGroupOpen.value = true;
};

const handleDeleteGroup = async (groupId: number) => {
  isDeletingGroup.value = true;
  try {
    // Fechar a janela de chat do grupo antes de deletar
    closeGroupChat(groupId);
    await messageStore.deleteGroup(groupId);
    isDeleteGroupOpen.value = false;
  } catch (error) {
    console.error("Erro ao deletar grupo:", error);
  } finally {
    isDeletingGroup.value = false;
  }
};

const toggleChatCollapse = (userId: number) => {
  const index = collapsedChatUserIds.value.indexOf(userId);
  if (index === -1) {
    collapsedChatUserIds.value.push(userId);
  } else {
    collapsedChatUserIds.value.splice(index, 1);
  }
};

const toggleGroupCollapse = (groupId: number) => {
  const index = collapsedGroupIds.value.indexOf(groupId);
  if (index === -1) {
    collapsedGroupIds.value.push(groupId);
  } else {
    collapsedGroupIds.value.splice(index, 1);
  }
};

const openChatWith = async (user: ChatUser) => {
  if (user.isGroup) {
    const groupId = user.chatId ?? user.id;
    conversationErrors.value[groupId] = "";

    if (!activeGroupIds.value.includes(groupId)) {
      if (activeGroupIds.value.length >= 3) {
        const closedGroupId = activeGroupIds.value.shift();
        if (closedGroupId !== undefined) {
          delete replyingToMessages.value[closedGroupId];
          collapsedGroupIds.value = collapsedGroupIds.value.filter(
            (id) => id !== closedGroupId,
          );
        }
      }
      activeGroupIds.value.push(groupId);
    }

    collapsedGroupIds.value = collapsedGroupIds.value.filter(
      (id) => id !== groupId,
    );

    try {
      await messageStore.fetchGroupMessages(groupId);
    } catch (error) {
      console.error(`Erro ao abrir grupo ${user.name}:`, error);
      conversationErrors.value[groupId] = "Não foi possível abrir o grupo.";
    }
    return;
  }

  // Fluxo privado (1-a-1)
  conversationErrors.value[user.id] = "";

  if (!activeChatUserIds.value.includes(user.id)) {
    if (activeChatUserIds.value.length >= 3) {
      const closedUserId = activeChatUserIds.value.shift();
      if (closedUserId !== undefined) {
        delete replyingToMessages.value[closedUserId];
        collapsedChatUserIds.value = collapsedChatUserIds.value.filter(
          (id) => id !== closedUserId,
        );
      }
    }
    activeChatUserIds.value.push(user.id);
  }

  collapsedChatUserIds.value = collapsedChatUserIds.value.filter(
    (id) => id !== user.id,
  );

  try {
    await messageStore.ensureConversationRoom(user.id);
    await messageStore.fetchConversationMessages(user.id);
  } catch (error) {
    console.error(`Erro ao abrir conversa com ${user.name}:`, error);
    conversationErrors.value[user.id] = "Não foi possível abrir a conversa.";
  }
};

const closeChat = (userId: number) => {
  activeChatUserIds.value = activeChatUserIds.value.filter(
    (id) => id !== userId,
  );
  collapsedChatUserIds.value = collapsedChatUserIds.value.filter(
    (id) => id !== userId,
  );
  delete replyingToMessages.value[userId];
  messageStore.clearConversation(userId);
};

const closeGroupChat = (groupId: number) => {
  activeGroupIds.value = activeGroupIds.value.filter((id) => id !== groupId);
  collapsedGroupIds.value = collapsedGroupIds.value.filter(
    (id) => id !== groupId,
  );
  delete replyingToMessages.value[groupId];
  messageStore.clearGroupConversation(groupId);
};

const sendMessage = async (userId: number, parentId?: number) => {
  const text = draftMessages.value[userId]?.trim();
  if (!text) return;

  try {
    await messageStore.sendMessage(userId, text, parentId);
    draftMessages.value[userId] = "";
    delete replyingToMessages.value[userId];
    playBellSound();
  } catch (error) {
    console.error("Erro ao enviar mensagem:", error);
  }
};

const sendGroupMessage = async (groupId: number, parentId?: number) => {
  const text = draftMessages.value[groupId]?.trim();
  if (!text) return;

  try {
    await messageStore.sendGroupMessage(groupId, text, parentId);
    draftMessages.value[groupId] = "";
    delete replyingToMessages.value[groupId];
    playBellSound();
  } catch (error) {
    console.error("Erro ao enviar mensagem no grupo:", error);
  }
};

const startReply = (userId: number, message: ChatMessageType) => {
  replyingToMessages.value[userId] = message;
};

const cancelReply = (userId: number) => {
  delete replyingToMessages.value[userId];
};

const updateDraft = (userId: number, text: string) => {
  draftMessages.value[userId] = text;
};
</script>

<template>
  <div
    class="fixed bottom-0 right-6 z-40 flex flex-row-reverse items-end gap-3"
  >
    <div class="w-72 shadow-lg transition-all duration-300">
      <Card
        class="border-border bg-card overflow-hidden rounded-b-none border-b-0 py-0 gap-0"
      >
        <Button
          @click="isListOpen = !isListOpen"
          class="w-full flex items-center justify-between rounded-b-none p-3 bg-card hover:bg-muted/40 transition-colors cursor-pointer border-b border-border/60"
        >
          <div class="flex items-center gap-2">
            <div class="relative">
              <MessageCircle class="h-5 w-5 text-primary" />
              <span
                class="absolute -top-0.5 -right-0.5 h-2 w-2 rounded-full bg-emerald-500"
              />
            </div>
            <span class="font-semibold text-xs text-foreground">Mensagens</span>
          </div>

          <div class="flex items-center gap-1 text-muted-foreground">
            <ChevronUp
              v-if="!isListOpen"
              class="h-4 w-4"
            />
            <ChevronDown
              v-else
              class="h-4 w-4"
            />
          </div>
        </Button>

        <Transition
          enter-active-class="transition-all duration-300 ease-out overflow-hidden"
          enter-from-class="max-h-0 opacity-0"
          enter-to-class="max-h-120 opacity-100"
          leave-active-class="transition-all duration-300 ease-in overflow-hidden"
          leave-from-class="max-h-120 opacity-100"
          leave-to-class="max-h-0 opacity-0"
        >
          <div v-show="isListOpen">
            <ChatOnlineUsers
              :is-open="isListOpen"
              :error="onlineUsersError"
              @open-chat="openChatWith"
              @search="loadConversations"
              @load-more="loadMoreConversations"
              @create-group="isCreateGroupOpen = true"
              @edit-group="openEditGroup"
              @delete-group="openDeleteGroup"
            />
          </div>
        </Transition>
      </Card>
    </div>

    <ChatMessage
      :active-chat-user-ids="activeChatUserIds"
      :collapsed-chat-user-ids="collapsedChatUserIds"
      :active-group-ids="activeGroupIds"
      :collapsed-group-ids="collapsedGroupIds"
      :conversation-errors="conversationErrors"
      :draft-messages="draftMessages"
      :replying-to-messages="replyingToMessages"
      @close="closeChat"
      @send="sendMessage"
      @close-group="closeGroupChat"
      @send-group="sendGroupMessage"
      @toggle-group-collapse="toggleGroupCollapse"
      @reply="startReply"
      @cancel-reply="cancelReply"
      @update-draft="updateDraft"
      @toggle-collapse="toggleChatCollapse"
      @edit-group="openEditGroup"
      @delete-group="openDeleteGroup"
    />

    <ChatCreateGroup
      :open="isCreateGroupOpen"
      :is-submitting="isCreatingGroup"
      @close="isCreateGroupOpen = false"
      @created="handleCreateGroup"
    />

    <ChatEditGroup
      :open="isEditGroupOpen"
      :group="groupToEdit"
      :is-submitting="isEditingGroup"
      @close="isEditGroupOpen = false"
      @updated="handleUpdateGroup"
    />

    <ChatDeleteGroup
      :open="isDeleteGroupOpen"
      :group="groupToDelete"
      :is-submitting="isDeletingGroup"
      @close="isDeleteGroupOpen = false"
      @confirm="handleDeleteGroup"
    />
  </div>
</template>

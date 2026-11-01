<script setup lang="ts">
import { onMounted, ref } from "vue";
import { MessageCircle, ChevronUp, ChevronDown } from "lucide-vue-next";

import { Card } from "@/shared/ui/card";
import { Button } from "@/shared/ui/button";
import { useMessageStore } from "@/entities/message/model/message.store";
import type {
  ChatMessage as ChatMessageType,
  ChatUser,
} from "@/entities/message/model/types";
import ChatOnlineUsers from "./ChatOnlineUsers.vue";
import ChatMessage from "./ChatMessage.vue";

// Controlo do Widget Principal (Lista de Contatos)
const isListOpen = ref(false);

// Controlo das Janelas/Abas de Chat Abertas (Guarda os IDs dos Utilizadores)
const activeChatUserIds = ref<number[]>([]);

// Mensagens digitadas por utilizador { userId: "texto..." }
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

onMounted(loadConversations);

// Abre a aba do chat se ainda não estiver aberta
const openChatWith = async (user: ChatUser) => {
  if (!activeChatUserIds.value.includes(user.id)) {
    // Limite máximo de 3 chats abertos lado a lado para não quebrar a tela
    if (activeChatUserIds.value.length >= 3) {
      const closedUserId = activeChatUserIds.value.shift();
      if (closedUserId !== undefined) {
        delete replyingToMessages.value[closedUserId];
      }
    }
    activeChatUserIds.value.push(user.id);
  }

  conversationErrors.value[user.id] = "";

  try {
    await messageStore.fetchConversationMessages(user.id);
  } catch (error) {
    console.error(`Erro ao carregar conversa com ${user.name}:`, error);
    conversationErrors.value[user.id] =
      "Não foi possível carregar as mensagens.";
  }
};

const closeChat = (userId: number) => {
  activeChatUserIds.value = activeChatUserIds.value.filter(
    (id) => id !== userId,
  );
  delete replyingToMessages.value[userId];
};

const sendMessage = async (userId: number, parentId?: number) => {
  const text = draftMessages.value[userId]?.trim();
  if (!text) return;

  try {
    // Envia via store (que chama a API POST /messages)
    await messageStore.sendMessage(userId, text, parentId);
    draftMessages.value[userId] = "";
    delete replyingToMessages.value[userId];
  } catch (error) {
    console.error("Erro ao enviar mensagem:", error);
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
  <!-- Container Flutuante na Direita (Alinha as janelas lado a lado com flex-row-reverse) -->
  <div
    class="fixed bottom-0 right-6 z-40 flex flex-row-reverse items-end gap-3"
  >
    <!-- 1. WIDGET PRINCIPAL: LISTA DE CONTATOS -->
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

        <!-- 1. Lista de utilizadores online e conversas ativas -->
        <ChatOnlineUsers
          :is-open="isListOpen"
          :error="onlineUsersError"
          @open-chat="openChatWith"
          @search="loadConversations"
        />
      </Card>
    </div>

    <!-- 2. JANELAS DE CHAT ABERTAS (LADO A LADO) -->
    <ChatMessage
      :active-chat-user-ids="activeChatUserIds"
      :conversation-errors="conversationErrors"
      :draft-messages="draftMessages"
      :replying-to-messages="replyingToMessages"
      @close="closeChat"
      @send="sendMessage"
      @reply="startReply"
      @cancel-reply="cancelReply"
      @update-draft="updateDraft"
    />
  </div>
</template>

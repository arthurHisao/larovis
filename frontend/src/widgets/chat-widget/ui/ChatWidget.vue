<script setup lang="ts">
import { onMounted, ref } from "vue";
import { MessageCircle, ChevronUp, ChevronDown } from "lucide-vue-next";

import { Card } from "@/shared/ui/card";
import { Button } from "@/shared/ui/button";
import { useMessageStore } from "@/entities/message/model/message.store";
import type { ChatUser } from "@/entities/message/model/types";
import ChatOnlineUsers from "./ChatOnlineUsers.vue";
import ChatMessage from "./ChatMessage.vue";

// Controlo do Widget Principal (Lista de Contatos)
const isListOpen = ref(false);

// Controlo das Janelas/Abas de Chat Abertas (Guarda os IDs dos Utilizadores)
const activeChatUserIds = ref<number[]>([]);

// Mensagens digitadas por utilizador { userId: "texto..." }
const draftMessages = ref<Record<number, string>>({});

const messageStore = useMessageStore();
const onlineUsersError = ref("");
const conversationErrors = ref<Record<number, string>>({});

const loadConversations = async () => {
  onlineUsersError.value = "";

  try {
    await messageStore.fetchConversationsList();
  } catch (error) {
    console.error("Erro ao carregar conversas:", error);
    onlineUsersError.value = "Não foi possível carregar as conversas.";
  }
};

onMounted(loadConversations);

// Abre a aba do chat se ainda não estiver aberta
const openChatWith = async (user: ChatUser) => {
  if (!activeChatUserIds.value.includes(user.id)) {
    // Limite máximo de 3 chats abertos lado a lado para não quebrar a tela
    if (activeChatUserIds.value.length >= 3) {
      activeChatUserIds.value.shift();
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
};

const sendMessage = async (userId: number) => {
  const text = draftMessages.value[userId]?.trim();
  if (!text) return;

  try {
    // Envia via store (que chama a API POST /messages)
    await messageStore.sendMessage(userId, text);
    draftMessages.value[userId] = "";
  } catch (error) {
    console.error("Erro ao enviar mensagem:", error);
  }
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
        />
      </Card>
    </div>

    <!-- 2. JANELAS DE CHAT ABERTAS (LADO A LADO) -->
    <ChatMessage
      :active-chat-user-ids="activeChatUserIds"
      :conversation-errors="conversationErrors"
      :draft-messages="draftMessages"
      @close="closeChat"
      @send="sendMessage"
      @update-draft="updateDraft"
    />
  </div>
</template>

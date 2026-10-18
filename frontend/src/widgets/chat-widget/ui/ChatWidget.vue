<script setup lang="ts">
import { onMounted, ref, nextTick } from "vue";
import {
  MessageCircle,
  ChevronUp,
  ChevronDown,
  Send,
  X,
} from "lucide-vue-next";

import { Card } from "@/shared/ui/card";
import { Input } from "@/shared/ui/input";
import { Button } from "@/shared/ui/button";
import { Avatar, AvatarImage, AvatarFallback } from "@/shared/ui/avatar";
import { useAuthStore } from "@/entities/user/model/auth.store";
import { useMessageStore } from "@/entities/message/model/message.store";
import type { ChatMessage, ChatUser } from "@/entities/message/model/types";

// Controle do Widget Principal (Lista de Contatos)
const isListOpen = ref(false);

// Controle das Janelas/Abas de Chat Abertas (Guarda os IDs dos Usuários)
const activeChatUserIds = ref<number[]>([]);

// Mensagens digitadas por usuário { userId: "texto..." }
const draftMessages = ref<Record<number, string>>({});

// Referência dinâmica dos containers de chat para autoscroll
const chatContainers = ref<Record<number, HTMLElement | null>>({});

const authStore = useAuthStore();
const messageStore = useMessageStore();
const onlineUsersError = ref("");
const conversationErrors = ref<Record<number, string>>({});

const fetchOnlineUsers = async () => {
  onlineUsersError.value = "";

  try {
    await messageStore.fetchOnlineUsers();
  } catch (error) {
    console.error("Erro ao carregar conversas:", error);
    onlineUsersError.value = "Não foi possível carregar as conversas.";
  }
};

onMounted(fetchOnlineUsers);

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
    await messageStore.fetchConversation(user.id);
    scrollToBottom(user.id);
  } catch (error) {
    console.error(`Erro ao carregar conversa com ${user.name}:`, error);
    conversationErrors.value[user.id] =
      "Não foi possível carregar as mensagens.";
  }
};

// Fecha uma aba específica
const closeChat = (userId: number) => {
  activeChatUserIds.value = activeChatUserIds.value.filter(
    (id) => id !== userId,
  );
};

// Envia mensagem na aba específica
const sendMessage = (userId: number) => {
  const text = draftMessages.value[userId]?.trim();
  if (!text) return;

  const senderId = authStore.user?.id;
  if (senderId == null) {
    console.error(
      "Não foi possível enviar a mensagem: usuário não autenticado.",
    );
    return;
  }

  const now = new Date().toLocaleTimeString([], {
    hour: "2-digit",
    minute: "2-digit",
  });

  const conversation =
    messageStore.conversations[userId] ??
    (messageStore.conversations[userId] = []);

  conversation.push({
    id: Date.now(),
    senderId,
    text,
    time: now,
    refer: [],
  });

  draftMessages.value[userId] = "";
  scrollToBottom(userId);
};

const scrollToBottom = (userId: number) => {
  nextTick(() => {
    const el = chatContainers.value[userId];
    if (el) {
      el.scrollTop = el.scrollHeight;
    }
  });
};

// Helper para pegar objeto do usuário por ID
const getUser = (id: number) =>
  messageStore.onlineUsers.find((user) => user.id === id);

const isOwnMessage = (message: ChatMessage) =>
  message.senderId === authStore.user?.id;

const getMessageUser = (message: ChatMessage) =>
  isOwnMessage(message) ? authStore.user : getUser(message.senderId);
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
          class="w-full flex items-center justify-between p-3 bg-card hover:bg-muted/40 transition-colors cursor-pointer border-b border-border/60"
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

        <!-- Conteúdo da Lista -->
        <div
          v-show="isListOpen"
          class="p-3 space-y-3 h-80 overflow-y-auto"
        >
          <Input
            type="search"
            placeholder="Buscar conversa..."
            class="h-8 text-xs bg-muted/40"
          />

          <div
            v-if="messageStore.isLoadingOnlineUsers"
            class="py-3 text-center text-xs text-muted-foreground"
          >
            Carregando conversas...
          </div>
          <p
            v-else-if="onlineUsersError"
            class="py-3 text-center text-xs text-destructive"
          >
            {{ onlineUsersError }}
          </p>
          <p
            v-else-if="messageStore.onlineUsers.length === 0"
            class="py-3 text-center text-xs text-muted-foreground"
          >
            Nenhuma conversa encontrada.
          </p>

          <div v-else class="space-y-1">
            <div
              v-for="user in messageStore.onlineUsers"
              :key="user.id"
              @click="openChatWith(user)"
              class="flex items-center justify-between p-2 rounded-lg hover:bg-muted/50 cursor-pointer transition-colors"
            >
              <div class="flex items-center gap-2.5">
                <div class="relative">
                  <Avatar class="h-7 w-7">
                    <AvatarImage
                      :src="user.avatar ?? ''"
                      :alt="user.name"
                    />
                    <AvatarFallback class="text-xs">
                      {{ user.name.substring(0, 2) }}
                    </AvatarFallback>
                  </Avatar>
                  <span
                    class="absolute bottom-0 right-0 h-2 w-2 rounded-full bg-emerald-500 border-2 border-card"
                  />
                </div>
                <div class="flex flex-col text-left">
                  <span
                    class="text-xs font-medium text-foreground leading-none"
                  >
                    {{ user.name }}
                  </span>
                  <span
                    v-if="user.role"
                    class="text-[10px] text-muted-foreground mt-1"
                  >
                    {{ user.role }}
                  </span>
                </div>
              </div>
            </div>
          </div>
        </div>
      </Card>
    </div>

    <!-- 2. JANELAS DE CHAT ABERTAS (LADO A LADO) -->
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
              @click="closeChat(userId)"
            >
              <X class="h-3.5 w-3.5" />
            </Button>
          </div>
        </div>

        <!-- Corpo da Janela Individual -->
        <div class="h-80 flex flex-col justify-between">
          <!-- Mensagem  -->
          <div
            :ref="
              (el) => {
                if (el) chatContainers[userId] = el as HTMLElement;
              }
            "
            class="p-3 space-y-2 overflow-y-auto flex-1"
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
                <div v-if="msg?.refer.length > 0">
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
            @submit.prevent="sendMessage(userId)"
            class="p-2 border-t border-border flex items-center gap-1.5 bg-card"
          >
            <Input
              v-model="draftMessages[userId]"
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
  </div>
</template>

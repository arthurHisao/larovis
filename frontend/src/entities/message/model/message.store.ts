import { defineStore } from "pinia";
import { ref, computed } from "vue";
import { messageApi } from "../api/message.api";
import type { ChatMessage, ChatUser } from "./types";

export const useMessageStore = defineStore("messages", () => {
  // 1. Histórico de conversas (usuários com quem já conversou - via HTTP/Banco)
  const conversationsList = ref<ChatUser[]>([]);

  // 2. Usuários ativos em tempo real (vindos do WebSocket Presence Channel)
  const onlineUsers = ref<ChatUser[]>([]);
  const knownOnlineUsers = ref<ChatUser[]>([]);

  const conversations = ref<Record<number, ChatMessage[]>>({});
  const isLoadingConversations = ref(false);
  const loadingConversations = ref<Record<number, boolean>>({});

  /**
   * Getter unificado: Faz o merge do histórico com o status online em tempo real.
   * Adiciona a propriedade `isOnline: true/false` a cada utilizador.
   */
  const unifiedContacts = computed(() => {
    const onlineUserIds = new Set(onlineUsers.value.map((user) => user.id));
    const conversationIds = new Set(
      conversationsList.value.map((user) => user.id),
    );

    const conversationContacts = conversationsList.value.map((user) => ({
      ...user,
      isOnline: onlineUserIds.has(user.id),
    }));

    const onlineContacts = onlineUsers.value
      .filter((user) => !conversationIds.has(user.id))
      .map((user) => ({
        ...user,
        isOnline: true,
      }));

    return [...conversationContacts, ...onlineContacts];
  });

  // Busca o histórico de conversas do utilizador
  async function fetchConversationsList() {
    isLoadingConversations.value = true;
    try {
      conversationsList.value = await messageApi.getConversations();
    } finally {
      isLoadingConversations.value = false;
    }
  }

  async function fetchConversationMessages(userId: number) {
    if (loadingConversations.value[userId]) return;

    loadingConversations.value[userId] = true;

    try {
      conversations.value[userId] = await messageApi.getConversation(userId);
    } finally {
      loadingConversations.value[userId] = false;
    }
  }

  async function sendMessage(receiverId: number, text: string) {
    const response = await messageApi.sendMessage(receiverId, text);

    const newMessage = {
      refer: [],
      ...response,
    };

    if (!conversations.value[receiverId]) {
      conversations.value[receiverId] = [];
    }
    conversations.value[receiverId].push(newMessage);

    return newMessage;
  }

  function addIncomingMessage(
    message: ChatMessage & { receiver_id?: number; sender_id?: number },
  ) {
    const normalizedMsg: ChatMessage = {
      id: message.id,
      senderId: message.senderId,
      text: message.text,
      time:
        message.time ??
        new Date().toLocaleDateString([], {
          hour: "2-digit",
          minute: "2-digit",
        }),
      refer: message.refer ?? [],
    };

    const chatId = normalizedMsg.senderId;

    if (!conversations.value[chatId]) {
      conversations.value[chatId] = [];
    }

    const exists = conversations.value[chatId].some(
      (m) => m.id === normalizedMsg.id,
    );
    if (!exists) {
      conversations.value[chatId].push(normalizedMsg);
    }
  }

  function setOnlineUsers(users: ChatUser[]) {
    users.forEach(rememberOnlineUser);
    onlineUsers.value = users;
  }

  function rememberOnlineUser(user: ChatUser) {
    if (!knownOnlineUsers.value.some((knownUser) => knownUser.id === user.id)) {
      knownOnlineUsers.value.push(user);
    }
  }

  function addOnlineUser(user: ChatUser) {
    rememberOnlineUser(user);

    if (!onlineUsers.value.some((u) => u.id === user.id)) {
      onlineUsers.value.push(user);
    }
  }

  function removeOnlineUser(userId: number) {
    onlineUsers.value = onlineUsers.value.filter((u) => u.id !== userId);
  }

  function clearOnlineUsers() {
    onlineUsers.value = [];
    knownOnlineUsers.value = [];
  }

  return {
    conversationsList,
    onlineUsers,
    knownOnlineUsers,
    conversations,
    isLoadingConversations,
    loadingConversations,
    unifiedContacts, // <- Usar este computed no componente de listagem
    fetchConversationsList,
    fetchConversationMessages,
    sendMessage,
    addIncomingMessage,
    setOnlineUsers,
    addOnlineUser,
    removeOnlineUser,
    clearOnlineUsers,
  };
});

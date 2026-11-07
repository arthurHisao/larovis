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

  // 3. Paginação por cursor e busca
  const nextCursor = ref<string | null>(null);
  const isLoadingMoreConversations = ref(false);
  const currentSearch = ref("");

  const hasMoreConversations = computed(() => !!nextCursor.value);

  const conversations = ref<Record<number, ChatMessage[]>>({});
  const isLoadingConversations = ref(false);
  const loadingConversations = ref<Record<number, boolean>>({});
  let conversationListRequestId = 0;

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

    // Se houver busca ativa, filtra também os usuários online não presentes no histórico
    const onlineCandidates = currentSearch.value
      ? onlineUsers.value.filter((user) =>
          user.name.toLowerCase().includes(currentSearch.value.toLowerCase()),
        )
      : onlineUsers.value;

    const onlineContacts = onlineCandidates
      .filter((user) => !conversationIds.has(user.id))
      .map((user) => ({
        ...user,
        isOnline: true,
      }));

    return [...conversationContacts, ...onlineContacts];
  });

  // Busca a primeira página de conversas ou reseta após termo de busca
  async function fetchConversationsList(search = "") {
    const requestId = ++conversationListRequestId;
    const cleanSearch = search.trim();
    currentSearch.value = cleanSearch;
    isLoadingConversations.value = true;
    nextCursor.value = null;

    try {
      const response = await messageApi.getConversations({
        search: cleanSearch || undefined,
      });
      if (requestId === conversationListRequestId) {
        conversationsList.value = response.data;
        nextCursor.value = response.meta?.next_cursor ?? null;
      }
    } finally {
      if (requestId === conversationListRequestId) {
        isLoadingConversations.value = false;
      }
    }
  }

  // Carrega a próxima página baseada no cursor retornado pelo Laravel (Infinite Scroll)
  async function loadMoreConversations() {
    if (
      !nextCursor.value ||
      isLoadingConversations.value ||
      isLoadingMoreConversations.value
    ) {
      return;
    }

    const currentReqId = conversationListRequestId;
    isLoadingMoreConversations.value = true;

    try {
      const response = await messageApi.getConversations({
        search: currentSearch.value || undefined,
        cursor: nextCursor.value,
      });

      if (currentReqId === conversationListRequestId) {
        const existingIds = new Set(conversationsList.value.map((u) => u.id));
        const newUsers = response.data.filter((u) => !existingIds.has(u.id));
        conversationsList.value.push(...newUsers);
        nextCursor.value = response.meta?.next_cursor ?? null;
      }
    } catch (error) {
      console.error("Erro ao carregar próxima página de conversas:", error);
    } finally {
      if (currentReqId === conversationListRequestId) {
        isLoadingMoreConversations.value = false;
      }
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

  async function sendMessage(
    receiverId: number,
    text: string,
    parentId?: number,
  ) {
    const response = await messageApi.sendMessage(receiverId, text, parentId);

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
    isLoadingMoreConversations,
    loadingConversations,
    nextCursor,
    hasMoreConversations,
    unifiedContacts, // <- Usar este computed no componente de listagem
    fetchConversationsList,
    loadMoreConversations,
    fetchConversationMessages,
    sendMessage,
    addIncomingMessage,
    setOnlineUsers,
    addOnlineUser,
    removeOnlineUser,
    clearOnlineUsers,
  };
});

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
  const messagesNextCursor = ref<Record<number, string | null>>({});
  const loadingMoreMessages = ref<Record<number, boolean>>({});
  const chatIdsByUser = ref<Record<number, number>>({});
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
    messagesNextCursor.value[userId] = null;

    try {
      const response = await messageApi.getConversation(userId);
      // Como o backend retorna em ordem desc (mais recentes primeiro),
      // invertemos para ficar em ordem cronológica (mais antigas no topo, recentes abaixo)
      const chronologicallyOrdered = [...response.data].reverse();
      conversations.value[userId] = chronologicallyOrdered;
      messagesNextCursor.value[userId] = response.meta?.next_cursor ?? null;
    } finally {
      loadingConversations.value[userId] = false;
    }
  }

  async function loadMoreMessages(userId: number): Promise<boolean> {
    const cursor = messagesNextCursor.value[userId];
    if (
      !cursor ||
      loadingConversations.value[userId] ||
      loadingMoreMessages.value[userId]
    ) {
      return false;
    }

    loadingMoreMessages.value[userId] = true;

    try {
      const response = await messageApi.getConversation(userId, { cursor });
      const olderMessages = [...response.data].reverse();

      if (!conversations.value[userId]) {
        conversations.value[userId] = [];
      }

      const existingIds = new Set(conversations.value[userId].map((m) => m.id));
      const uniqueOlder = olderMessages.filter((m) => !existingIds.has(m.id));

      // Prepend das mensagens mais antigas no início
      conversations.value[userId] = [
        ...uniqueOlder,
        ...conversations.value[userId],
      ];
      messagesNextCursor.value[userId] = response.meta?.next_cursor ?? null;
      return uniqueOlder.length > 0;
    } catch (error) {
      console.error(`Erro ao carregar mais mensagens de ${userId}:`, error);
      return false;
    } finally {
      loadingMoreMessages.value[userId] = false;
    }
  }

  async function ensureConversationRoom(userId: number) {
    const chatId = await messageApi.ensureConversationRoom(userId);
    chatIdsByUser.value[userId] = chatId;
    return chatId;
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

    addIncomingMessage(receiverId, newMessage);

    return newMessage;
  }

  function addIncomingMessage(
    conversationUserId: number,
    message: ChatMessage,
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

    if (!conversations.value[conversationUserId]) {
      conversations.value[conversationUserId] = [];
    }

    const exists = conversations.value[conversationUserId].some(
      (m) => m.id === normalizedMsg.id,
    );
    if (!exists) {
      conversations.value[conversationUserId].push(normalizedMsg);
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

  async function markAsRead(chatId: number) {
    try {
      await messageApi.markAsRead(chatId);
      setMessageAsReadByChatId(chatId);
    } catch (error) {
      console.error("Erro ao marcar mensagens como lidas: ", error);
    }
  }

  function setMessageAsReadByChatId(chatId: number) {
    // 1. Descobre qual é o userId com base no chatId guardado no store
    const userId = Object.keys(chatIdsByUser.value).find(
      (key) => chatIdsByUser.value[Number(key)] === chatId,
    );

    if (userId && conversations.value[Number(userId)]) {
      conversations.value[Number(userId)] = conversations.value[
        Number(userId)
      ].map((msg) => ({
        ...msg,
        isRead: true,
      }));
    }
  }

  function clearConversation(userId: number) {
    delete conversations.value[userId];
    delete messagesNextCursor.value[userId];
    delete loadingConversations.value[userId];
    delete loadingMoreMessages.value[userId];
  }

  return {
    conversationsList,
    onlineUsers,
    knownOnlineUsers,
    conversations,
    messagesNextCursor,
    loadingMoreMessages,
    chatIdsByUser,
    isLoadingConversations,
    isLoadingMoreConversations,
    loadingConversations,
    nextCursor,
    hasMoreConversations,
    unifiedContacts, // <- Usar este computed no componente de listagem
    fetchConversationsList,
    loadMoreConversations,
    fetchConversationMessages,
    loadMoreMessages,
    ensureConversationRoom,
    sendMessage,
    addIncomingMessage,
    setOnlineUsers,
    addOnlineUser,
    removeOnlineUser,
    clearOnlineUsers,
    markAsRead,
    setMessageAsReadByChatId,
    clearConversation,
  };
});

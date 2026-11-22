import { defineStore } from "pinia";
import { ref, computed } from "vue";
import { messageApi } from "../api/message.api";
import type { ChatMessage, ChatUser, CreateGroupPayload } from "./types";

export const useMessageStore = defineStore("messages", () => {
  // 1. Histórico de conversas (usuários e grupos - via HTTP/Banco)
  const conversationsList = ref<ChatUser[]>([]);

  // 2. Usuários ativos em tempo real (vindos do WebSocket Presence Channel)
  const onlineUsers = ref<ChatUser[]>([]);
  const knownOnlineUsers = ref<ChatUser[]>([]);

  // 3. Paginação por cursor e busca da lista principal
  const nextCursor = ref<string | null>(null);
  const isLoadingMoreConversations = ref(false);
  const currentSearch = ref("");

  const hasMoreConversations = computed(() => !!nextCursor.value);

  // Estado das conversas 1-a-1 (indexado por userId)
  const conversations = ref<Record<number, ChatMessage[]>>({});
  const messagesNextCursor = ref<Record<number, string | null>>({});
  const loadingMoreMessages = ref<Record<number, boolean>>({});
  const chatIdsByUser = ref<Record<number, number>>({});
  const isLoadingConversations = ref(false);
  const loadingConversations = ref<Record<number, boolean>>({});

  // Estado das conversas em grupo (indexado por chatId do grupo)
  const groupMessages = ref<Record<number, ChatMessage[]>>({});
  const groupMessagesNextCursor = ref<Record<number, string | null>>({});
  const loadingGroupMessages = ref<Record<number, boolean>>({});
  const loadingMoreGroupMessages = ref<Record<number, boolean>>({});

  let conversationListRequestId = 0;

  const unifiedContacts = computed(() => {
    const onlineUserIds = new Set(onlineUsers.value.map((user) => user.id));
    const conversationIds = new Set(
      conversationsList.value
        .filter((item) => !item.isGroup)
        .map((user) => user.id),
    );

    const conversationContacts = conversationsList.value.map((item) => ({
      ...item,
      isOnline: item.isGroup ? false : onlineUserIds.has(item.id),
    }));

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
        const existingKeys = new Set(
          conversationsList.value.map(
            (u) => u.key ?? (u.isGroup ? `group-${u.id}` : `user-${u.id}`),
          ),
        );
        const newItems = response.data.filter((u) => {
          const key = u.key ?? (u.isGroup ? `group-${u.id}` : `user-${u.id}`);
          return !existingKeys.has(key);
        });
        conversationsList.value.push(...newItems);
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

  // --- Conversas 1-a-1 (mantidas como você já tinha) ---

  async function fetchConversationMessages(userId: number) {
    if (loadingConversations.value[userId]) return;

    loadingConversations.value[userId] = true;
    messagesNextCursor.value[userId] = null;

    try {
      const response = await messageApi.getConversation(userId);
      conversations.value[userId] = [...response.data].reverse();
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
    const newMessage = { refer: [], ...response };
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
        new Date().toLocaleTimeString([], {
          hour: "2-digit",
          minute: "2-digit",
        }),
      refer: message.refer ?? [],
      isRead: message.isRead ?? false,
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

  // --- Grupos ---

  async function createGroup(payload: CreateGroupPayload) {
    const created = await messageApi.createGroup(payload);
    const groupItem: ChatUser = {
      id: created.id,
      key: created.key ?? `group-${created.id}`,
      chatId: created.chatId ?? created.chat_id ?? created.id,
      isGroup: true,
      name: created.name,
      avatar: created.avatar ?? null,
      department: created.department ?? "Grupo",
      isOnline: false,
    };

    conversationsList.value = [
      groupItem,
      ...conversationsList.value.filter(
        (item) => !(item.isGroup && item.id === groupItem.id),
      ),
    ];

    return groupItem;
  }

  async function fetchGroupMessages(chatId: number) {
    if (loadingGroupMessages.value[chatId]) return;

    loadingGroupMessages.value[chatId] = true;
    groupMessagesNextCursor.value[chatId] = null;

    try {
      const response = await messageApi.getGroupMessages(chatId);
      groupMessages.value[chatId] = [...response.data].reverse();
      groupMessagesNextCursor.value[chatId] =
        response.meta?.next_cursor ?? null;
    } finally {
      loadingGroupMessages.value[chatId] = false;
    }
  }

  async function loadMoreGroupMessages(chatId: number): Promise<boolean> {
    const cursor = groupMessagesNextCursor.value[chatId];
    if (
      !cursor ||
      loadingGroupMessages.value[chatId] ||
      loadingMoreGroupMessages.value[chatId]
    ) {
      return false;
    }

    loadingMoreGroupMessages.value[chatId] = true;

    try {
      const response = await messageApi.getGroupMessages(chatId, { cursor });
      const olderMessages = [...response.data].reverse();

      if (!groupMessages.value[chatId]) {
        groupMessages.value[chatId] = [];
      }

      const existingIds = new Set(groupMessages.value[chatId].map((m) => m.id));
      const uniqueOlder = olderMessages.filter((m) => !existingIds.has(m.id));

      groupMessages.value[chatId] = [
        ...uniqueOlder,
        ...groupMessages.value[chatId],
      ];
      groupMessagesNextCursor.value[chatId] =
        response.meta?.next_cursor ?? null;
      return uniqueOlder.length > 0;
    } catch (error) {
      console.error(
        `Erro ao carregar mais mensagens do grupo ${chatId}:`,
        error,
      );
      return false;
    } finally {
      loadingMoreGroupMessages.value[chatId] = false;
    }
  }

  async function sendGroupMessage(
    chatId: number,
    text: string,
    parentId?: number,
  ) {
    const response = await messageApi.sendGroupMessage(chatId, text, parentId);
    const newMessage = { refer: [], ...response };
    addIncomingGroupMessage(chatId, newMessage);
    return newMessage;
  }

  function addIncomingGroupMessage(chatId: number, message: ChatMessage) {
    const normalizedMsg: ChatMessage = {
      id: message.id,
      senderId: message.senderId,
      text: message.text,
      time:
        message.time ??
        new Date().toLocaleTimeString([], {
          hour: "2-digit",
          minute: "2-digit",
        }),
      refer: message.refer ?? [],
      isRead: false,
    };

    if (!groupMessages.value[chatId]) {
      groupMessages.value[chatId] = [];
    }

    const exists = groupMessages.value[chatId].some(
      (m) => m.id === normalizedMsg.id,
    );
    if (!exists) {
      groupMessages.value[chatId].push(normalizedMsg);
    }
  }

  function clearGroupConversation(chatId: number) {
    delete groupMessages.value[chatId];
    delete groupMessagesNextCursor.value[chatId];
    delete loadingGroupMessages.value[chatId];
    delete loadingMoreGroupMessages.value[chatId];
  }

  // --- Presença e Leitura ---

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
    unifiedContacts,
    groupMessages,
    groupMessagesNextCursor,
    loadingGroupMessages,
    loadingMoreGroupMessages,
    fetchConversationsList,
    loadMoreConversations,
    fetchConversationMessages,
    loadMoreMessages,
    ensureConversationRoom,
    sendMessage,
    addIncomingMessage,
    createGroup,
    fetchGroupMessages,
    loadMoreGroupMessages,
    sendGroupMessage,
    addIncomingGroupMessage,
    clearGroupConversation,
    setOnlineUsers,
    addOnlineUser,
    removeOnlineUser,
    clearOnlineUsers,
    markAsRead,
    setMessageAsReadByChatId,
    clearConversation,
  };
});

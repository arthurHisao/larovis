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
    const contactsMap = new Map<number, ChatUser & { isOnline: boolean }>();

    // Primeiro, adiciona todos os contactos do histórico do banco
    conversationsList.value.forEach((user) => {
      contactsMap.set(user.id, {
        ...user,
        isOnline: onlineUsers.value.some((o) => o.id === user.id),
      });
    });

    // Mantém disponíveis contatos descobertos pela presença mesmo após ficarem offline.
    knownOnlineUsers.value.forEach((user) => {
      if (!contactsMap.has(user.id)) {
        contactsMap.set(user.id, {
          ...user,
          isOnline: onlineUsers.value.some(
            (onlineUser) => onlineUser.id === user.id,
          ),
        });
      }
    });

    // Opcional: Ordena para colocar utilizadores online no topo (ou mantém a ordem natural)
    return Array.from(contactsMap.values()).sort((a, b) => {
      if (a.isOnline === b.isOnline) return 0;
      return a.isOnline ? -1 : 1; // Online primeiro
    });
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

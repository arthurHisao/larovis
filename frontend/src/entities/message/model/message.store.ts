import { defineStore } from "pinia";
import { ref } from "vue";
import { messageApi } from "../api/message.api";
import type { ChatMessage, ChatUser } from "./types";

export const useMessageStore = defineStore("messages", () => {
  const onlineUsers = ref<ChatUser[]>([]);
  const conversations = ref<Record<number, ChatMessage[]>>({});
  const isLoadingOnlineUsers = ref(false);
  const loadingConversations = ref<Record<number, boolean>>({});

  async function fetchOnlineUsers() {
    isLoadingOnlineUsers.value = true;

    try {
      onlineUsers.value = await messageApi.getConversations();
    } finally {
      isLoadingOnlineUsers.value = false;
    }
  }

  async function fetchConversation(userId: number) {
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

    // Garante que o array existe e adiciona a mensagem persistida
    if (!conversations.value[receiverId]) {
      conversations.value[receiverId] = [];
    }
    conversations.value[receiverId].push(newMessage);

    // console.log(
    //   "resultado de conversations.value[receirverId] ",
    //   conversations.value[receiverId],
    // );

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

  return {
    onlineUsers,
    conversations,
    isLoadingOnlineUsers,
    loadingConversations,
    fetchOnlineUsers,
    fetchConversation,
    sendMessage,
    addIncomingMessage,
  };
});

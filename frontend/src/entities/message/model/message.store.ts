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

  return {
    onlineUsers,
    conversations,
    isLoadingOnlineUsers,
    loadingConversations,
    fetchOnlineUsers,
    fetchConversation,
  };
});

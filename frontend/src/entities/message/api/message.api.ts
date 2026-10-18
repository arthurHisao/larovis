import { api } from "@/shared/api/axios";
import type { ChatMessage, ChatUser } from "../model/types";

export const messageApi = {
  async getConversations(): Promise<ChatUser[]> {
    const response = await api.get<ChatUser[]>("/messages");
    return response.data;
  },

  async getConversation(userId: number): Promise<ChatMessage[]> {
    const response = await api.get<ChatMessage[]>(`/messages/${userId}`);
    return response.data;
  },
};

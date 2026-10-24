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

  async sendMessage(receiverId: number, text: string): Promise<ChatMessage> {
    const response = await api.post<ChatMessage>("/messages", {
      receiver_id: receiverId,
      text,
    });
    return response.data;
  },
};

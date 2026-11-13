import { api } from "@/shared/api/axios";
import type {
  ChatMessage,
  ChatUser,
  CursorPaginatedResponse,
} from "../model/types";

export interface GetConversationsParams {
  search?: string;
  cursor?: string | null;
}

function extractNextCursor(responseData: any): string | null {
  if (responseData?.meta?.next_cursor) {
    return responseData.meta.next_cursor;
  }
  if (responseData?.links?.next) {
    try {
      const url = new URL(responseData.links.next, "http://localhost");
      return url.searchParams.get("cursor");
    } catch {
      const match = String(responseData.links.next).match(/[?&]cursor=([^&]+)/);
      return match ? decodeURIComponent(match[1]) : null;
    }
  }
  return null;
}

export const messageApi = {
  async ensureConversationRoom(userId: number): Promise<number> {
    const response = await api.post<{ chat_id: number }>(
      `/messages/${userId}/room`,
    );
    return response.data.chat_id;
  },

  async getConversations(
    params?: GetConversationsParams,
  ): Promise<CursorPaginatedResponse<ChatUser>> {
    const response = await api.get("/messages", {
      params: {
        ...(params?.search ? { search: params.search } : {}),
        ...(params?.cursor ? { cursor: params.cursor } : {}),
      },
    });

    const raw = response.data;
    const items: ChatUser[] = Array.isArray(raw)
      ? raw
      : Array.isArray(raw?.data)
        ? raw.data
        : [];

    const nextCursor = extractNextCursor(raw);

    return {
      data: items,
      links: raw?.links,
      meta: {
        ...raw?.meta,
        next_cursor: nextCursor,
      },
    };
  },

  async getConversation(userId: number): Promise<ChatMessage[]> {
    const response = await api.get<ChatMessage[]>(`/messages/${userId}`);
    return (response.data as any)?.data ?? response.data ?? [];
  },

  async sendMessage(
    receiverId: number,
    text: string,
    parentId?: number,
  ): Promise<ChatMessage> {
    const response = await api.post<ChatMessage>("/messages", {
      receiver_id: receiverId,
      text,
      parent_id: parentId,
    });
    return (response.data as any)?.data ?? response.data;
  },
};

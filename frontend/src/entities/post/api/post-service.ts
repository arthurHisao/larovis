import { api } from "@/shared/api/axios";
import type { Post, Like } from "../model/types";

export const postService = {
  // GET /api/posts
  async getAll(filter?: string): Promise<Post[]> {
    const response = await api.get("/posts", {
      params: filter && filter !== "all" ? { filter } : {},
    });
    return (response.data as any)?.data ?? response.data ?? [];
  },

  // POST /api/posts
  async create(content: string): Promise<Post> {
    const response = await api.post("/posts", { content });
    return response.data;
  },

  // POST /api/posts/{id}/like
  async toggleLike(postId: number): Promise<Like> {
    const response = await api.post(`/posts/${postId}/like`);
    return response.data;
  },
};

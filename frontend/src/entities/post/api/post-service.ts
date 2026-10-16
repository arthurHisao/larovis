import { api } from "@/shared/api/axios";
import type { Post, Like } from "../model/types";

export const postService = {
  // GET /api/posts
  async getAll(): Promise<Post[]> {
    const response = await api.get("/posts");
    return response.data;
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

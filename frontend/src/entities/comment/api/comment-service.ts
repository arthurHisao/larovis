import { api } from "@/shared/api/axios";
// import { mockComments } from "../model/mock-comments";
import type { Comment } from "../model/types";

export const commentService = {
  // Get /posts/:id/comments
  async getByPostId(postId: number): Promise<Comment[]> {
    const response = await api.get(`/posts/${postId}/comments`);
    return response.data;
  },

  // Post /posts/:id/comments
  async create(postId: number, content: string): Promise<Comment> {
    const response = await api.post(`/posts/${postId}/comments`, { content });
    return response.data;
  },

  async delete(commentId: number): Promise<Comment> {
    const response = await api.delete(`/comments/${commentId}`);
    return response.data;
  },
};

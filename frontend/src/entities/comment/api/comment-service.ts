import { api } from "@/shared/api/axios";
// import { mockComments } from "../model/mock-comments";
import type { Comment } from "../model/types";

interface Postdata {
  content: string;
  parent_id?: number;
}

export const commentService = {
  // Get /posts/:id/comments
  async getByPostId(postId: number): Promise<Comment[]> {
    const response = await api.get(`/posts/${postId}/comments`);
    return response.data;
  },

  // Post /posts/:id/comments
  async create(postId: number, sendData: Postdata): Promise<Comment> {
    console.log("sendData ", sendData);

    const response = await api.post(`/posts/${postId}/comments`, { sendData });
    return response.data;
  },

  async delete(commentId: number): Promise<Comment> {
    const response = await api.delete(`/comments/${commentId}`);
    return response.data;
  },
};

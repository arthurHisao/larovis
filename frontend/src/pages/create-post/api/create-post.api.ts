import { api } from "@/shared/api/axios";
import type { Department } from "../model/types";

export const createPostApi = {
  async getDepartments(): Promise<Department[]> {
    const response = await api.get<{ data: Department[] }>("/departments");
    return (response.data as any)?.data ?? response.data ?? [];
  },

  async createPost(formData: FormData) {
    const response = await api.post("/posts", formData, {
      headers: {
        "Content-Type": "multipart/form-data",
      },
    });
    return response.data;
  },
};

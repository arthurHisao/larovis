import { defineStore } from "pinia";
import { ref } from "vue";
import { createPostApi } from "../api/create-post.api";
import type { Department, CreatePostPayload } from "./types";

export const useCreatePostStore = defineStore("create-post", () => {
  const departments = ref<Department[]>([]);
  const isLoadingDepartments = ref(false);
  const isSubmitting = ref(false);
  const error = ref<string | null>(null);

  async function fetchDepartments() {
    isLoadingDepartments.value = true;
    error.value = null;
    try {
      departments.value = await createPostApi.getDepartments();
    } catch (err: any) {
      console.error("Erro ao carregar departamentos:", err);
      error.value = "Não foi possível carregar os departamentos.";
    } finally {
      isLoadingDepartments.value = false;
    }
  }

  async function publishPost(payload: CreatePostPayload) {
    isSubmitting.value = true;
    error.value = null;
    try {
      const formData = new FormData();
      formData.append("content", payload.content);
      formData.append("post_type", payload.postType);
      formData.append("department_visibility", payload.departmentVisibility);

      payload.images.forEach((file) => {
        formData.append("images[]", file);
      });

      const response = await createPostApi.createPost(formData);
      return response;
    } catch (err: any) {
      console.error("Erro ao publicar post:", err);
      error.value = err?.response?.data?.message || "Erro ao publicar post.";
      throw err;
    } finally {
      isSubmitting.value = false;
    }
  }

  return {
    departments,
    isLoadingDepartments,
    isSubmitting,
    error,
    fetchDepartments,
    publishPost,
  };
});

import { defineStore } from "pinia";
import { ref, computed } from "vue";
import { authApi } from "../api/auth.api";

export interface User {
  id: number;
  name: string;
  email: string;
  avatar?: string;
}

export const useAuthStore = defineStore("auth", () => {
  const user = ref<User | null>(
    JSON.parse(localStorage.getItem("larovis_user") || "null"),
  );

  const isInitialized = ref(false);

  const isAuthenticated = computed(() => !!user.value);

  function setSession(userData: User) {
    user.value = userData;
    localStorage.setItem("larovis_user", JSON.stringify(userData));
  }

  function clearSession() {
    user.value = null;
    localStorage.removeItem("larovis_user");
  }

  async function logout() {
    try {
      await authApi.logout();
    } catch (error) {
      console.error("Erro ao realizar o logout ", error);
    } finally {
      clearSession();
    }
  }

  async function fetchUser() {
    try {
      const userData = await authApi.me();
      setSession(userData);
    } catch (error) {
      // Se a sessão expirou ou o cookie não existe, limpa o estado
      clearSession();
    } finally {
      isInitialized.value = true;
    }
  }

  return {
    user,
    isInitialized,
    isAuthenticated,
    setSession,
    clearSession,
    fetchUser,
    logout,
  };
});

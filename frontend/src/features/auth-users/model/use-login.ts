import { ref } from "vue";
import { useRouter } from "vue-router";
import { authApi } from "@/entities/login/api/auth.api";
import { useAuthStore } from "@/entities/login/model/auth.store";

export function useLogin() {
  const router = useRouter();
  const authStore = useAuthStore();

  const email = ref("");
  const password = ref("");
  const isLoading = ref(false);
  const errorMessage = ref<string | null>(null);

  async function handleLogin() {
    errorMessage.value = null;
    isLoading.value = true;

    try {
      const response = await authApi.login({
        email: email.value,
        password: password.value,
      });

      // Salva os dados do usuário na Pinia store
      if (response.user) {
        authStore.setSession(response.user);
      }

      // Redireciona para o Feed
      await router.push("/");
    } catch (error: any) {
      console.error("Erro de Autenticação:", error);
      errorMessage.value =
        error.response?.data?.message || "E-mail ou senha incorretos.";
    } finally {
      isLoading.value = false;
    }
  }

  return {
    email,
    password,
    isLoading,
    errorMessage,
    handleLogin,
  };
}

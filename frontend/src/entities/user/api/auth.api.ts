import { api } from "@/shared/api/axios";

export const authApi = {
  // 1. Pede o cookie CSRF para o Laravel inicializar a sessão
  async getCsrfToken() {
    await api.get("/sanctum/csrf-cookie", { baseURL: "http://localhost:8000" });
  },

  // 2. Realiza a autenticação
  async login(payload: LoginPayload) {
    await this.getCsrfToken();
    const response = await api.post("/login", payload);
    return response.data;
  },

  // 3. Logout encerra a sessão e invalida o cookie
  async logout(): Promise<void> {
    await api.post("/logout");
  },

  // 4. Retorna os dados do usuário autenticado atual
  async me() {
    const response = await api.get("/user");
    return response.data;
  },
};

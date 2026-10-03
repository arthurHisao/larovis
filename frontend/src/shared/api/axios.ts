import axios from "axios";

export const api = axios.create({
  // Aponta para a porta do Laravel (substitua conforme seu ambiente)
  baseURL: import.meta.env.VITE_API_URL || "http://localhost:8000/api",

  // OBRIGATÓRIO PARA SANCTUM STATEFUL:
  withCredentials: true, // Envia e recebe os cookies de sessão e XSRF
  withXSRFToken: true, // Lê automaticamente o cookie XSRF-TOKEN e injeta no header

  headers: {
    "Content-Type": "application/json",
    Accept: "application/json",
  },
});

// Interceptor global para tratar sessão expirada / não autorizada
api.interceptors.response.use(
  (response) => response,
  (error) => {
    const isAuthRequest =
      error.config?.url?.includes("/login") ||
      error.config?.url?.includes("/user");

    // Só redireciona se der 401 em rotas de dados (posts, etc.) e NÃO em rotas de autenticação
    if (error.response?.status === 401 && !isAuthRequest) {
      window.location.href = "/login";
    }
    return Promise.reject(error);
  },
);

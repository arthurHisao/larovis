import { web } from "@/shared/api/axios";
import Echo from "laravel-echo";
import Pusher from "pusher-js";

declare global {
  interface Window {
    Pusher: typeof Pusher;
  }
}

type AppEcho = Echo<"reverb">;

let echo: AppEcho | null = null;

export function connectEcho(): AppEcho {
  if (!echo) {
    window.Pusher = Pusher;
    echo = new Echo({
      broadcaster: "reverb",
      // Fallbacks diretos para garantir que nunca vai buscar à Pusher Cloud
      key: import.meta.env.VITE_REVERB_APP_KEY || "minha-chave-local-aleatoria",
      wsHost: import.meta.env.VITE_REVERB_HOST || "127.0.0.1",
      wsPort: Number(import.meta.env.VITE_REVERB_PORT) || 8080,
      wssPort: Number(import.meta.env.VITE_REVERB_PORT) || 8080,
      forceTLS: (import.meta.env.VITE_REVERB_SCHEME ?? "http") === "https",
      enabledTransports: ["ws", "wss"],

      // Força a ignorar clusters da Pusher e desativa stats externas
      cluster: "",
      disableStats: true,

      authEndpoint: `${import.meta.env.VITE_WEB_URL || "http://localhost:8000"}/broadcasting/auth`,

      authorizer: (channel) => ({
        authorize: (socketId, callback) => {
          web
            .post("/broadcasting/auth", {
              socket_id: socketId,
              channel_name: channel.name,
            })
            .then((response) => {
              callback(null, response.data);
            })
            .catch((error) => {
              callback(
                error instanceof Error ? error : new Error(String(error)),
                null,
              );
            });
        },
      }),
    });
  } else {
    echo.connector.connect?.();
  }

  return echo;
}

export function disconnectEcho() {
  echo?.disconnect();
}

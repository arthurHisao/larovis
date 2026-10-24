import { onMounted, onUnmounted } from "vue";
import { useAuthStore } from "@/entities/user/model/auth.store";
import { useMessageStore } from "@/entities/message/model/message.store";

export function useChatSocket(receiverId: number) {
  const authStore = useAuthStore();
  const messageStore = useMessageStore();

  onMounted(() => {
    const userId = authStore.user?.id;
    if (!userId) return;

    // Escuta o canal privado do utilizador configurado no Laravel: 'chat.{id}'
    window.Echo.private(`chat.${userId}`).listen(
      ".message.sent",
      (event: { message: any }) => {
        console.log("📥 Mensagem recebida em tempo real via Reverb!", event);
        // Quando o Reverb recebe o evento, injeta direto na store
        messageStore.addIncomingMessage(event.message);
      },
    );
  });

  onUnmounted(() => {
    const userId = authStore.user?.id;
    if (userId) {
      window.Echo.leave(`chat.${userId}`);
    }
  });
}

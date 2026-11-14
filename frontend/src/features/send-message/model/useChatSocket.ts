import { useMessageStore } from "@/entities/message/model/message.store";
import type { ChatMessage, ChatUser } from "@/entities/message/model/types";
import type Echo from "laravel-echo";

// 1. Manter a presença de utilizadores online (Global)
export function subscribeToOnlineUsers(
  echo: Echo<"reverb">,
  currentUserId: number,
) {
  const messageStore = useMessageStore();

  echo
    .join("online-users")
    .here((users: ChatUser[]) => {
      messageStore.setOnlineUsers(
        users.filter((user) => user.id !== currentUserId),
      );
    })
    .joining((user: ChatUser) => {
      if (user.id !== currentUserId) {
        messageStore.addOnlineUser(user);
      }
    })
    .leaving((user: ChatUser) => {
      messageStore.removeOnlineUser(user.id);
    });

  return () => {
    echo.leave("online-users");
    messageStore.clearOnlineUsers();
  };
}

// 2. Inscrever-se na Sala de Chat (Mensagens em Tempo Real + Indicator Typing)
export function subscribeToChatRoom(
  echo: Echo<"reverb">,
  chatId: number,
  currentUserId: number,
  conversationUserId: number,
  onTyping: (user: { id: number; name?: string }, isTyping: boolean) => void,
) {
  const messageStore = useMessageStore();
  const channelName = `chat.${chatId}`;
  let typingTimeout: ReturnType<typeof setTimeout> | undefined;

  messageStore.markAsRead(chatId);

  const channel = echo.private(channelName);

  // Escuta novas mensagens gravadas no banco
  channel.listen(".message.sent", (event: { message: ChatMessage }) => {
    if (event.message.senderId === currentUserId) return;

    messageStore.addIncomingMessage(conversationUserId, event.message);

    messageStore.markAsRead(chatId);
  });

  // Listener para o emissor receber marcado como lido
  channel.listen(".messages.read", (event: { userId: number }) => {
    if (event.userId === currentUserId) return;

    messageStore.setMessageAsReadByChatId(chatId);
  });

  // Escuta o sinal de "a digitar..." (Whisper)
  channel.listenForWhisper(
    "typing",
    (event: { userId: number; userName?: string; isTyping?: boolean }) => {
      // Ignora se for o próprio utilizador logado
      if (event.userId === currentUserId) return;

      if (typingTimeout) clearTimeout(typingTimeout);

      if (event.isTyping === false) {
        onTyping({ id: event.userId, name: event.userName }, false);
        return;
      }

      onTyping({ id: event.userId, name: event.userName }, true);

      // Reset automático após 2 segundos sem novos eventos
      typingTimeout = setTimeout(() => {
        onTyping({ id: event.userId, name: event.userName }, false);
        typingTimeout = undefined;
      }, 2000);
    },
  );

  return () => {
    if (typingTimeout) clearTimeout(typingTimeout);
    echo.leave(channelName);
  };
}

// 3. Disparar o sinal de "a digitar..." para a sala
export function sendTypingSignal(
  echo: Echo<"reverb">,
  chatId: number,
  userId: number,
  userName: string,
  isTyping: boolean,
) {
  echo.private(`chat.${chatId}`).whisper("typing", {
    userId,
    userName,
    isTyping,
  });
}

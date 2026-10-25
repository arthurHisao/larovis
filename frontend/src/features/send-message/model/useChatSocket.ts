import { useMessageStore } from "@/entities/message/model/message.store";
import type { ChatMessage, ChatUser } from "@/entities/message/model/types";
import type Echo from "laravel-echo";

export function subscribeToChatSocket(echo: Echo<"reverb">, userId: number) {
  const messageStore = useMessageStore();

  echo
    .private(`chat.${userId}`)
    .listen(".message.sent", (event: { message: ChatMessage }) => {
      messageStore.addIncomingMessage(event.message);
    });

  echo
    .join("online-users")
    .here((users: ChatUser[]) => {
      messageStore.setOnlineUsers(users.filter((user) => user.id !== userId));
    })
    .joining((user: ChatUser) => {
      if (user.id !== userId) {
        messageStore.addOnlineUser(user);
      }
    })
    .leaving((user: ChatUser) => {
      messageStore.removeOnlineUser(user.id);
    });

  return () => {
    echo.leave(`chat.${userId}`);
    echo.leave("online-users");
    messageStore.clearOnlineUsers();
  };
}

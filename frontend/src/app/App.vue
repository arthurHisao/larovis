<script setup lang="ts">
import { onUnmounted, watch } from "vue";
import { RouterView } from "vue-router";
import { useAuthStore } from "@/entities/user/model/auth.store";
import { subscribeToChatSocket } from "@/features/send-message/model/useChatSocket";
import { connectEcho, disconnectEcho } from "./providers/echo";

const authStore = useAuthStore();
let stopChatSocket: (() => void) | undefined;

watch(
  () => [authStore.isInitialized, authStore.user?.id] as const,
  ([isInitialized, userId]) => {
    if (!isInitialized) return;

    stopChatSocket?.();
    stopChatSocket = undefined;
    disconnectEcho();

    if (userId !== undefined) {
      stopChatSocket = subscribeToChatSocket(connectEcho(), userId);
    }
  },
  { immediate: true },
);

onUnmounted(() => {
  stopChatSocket?.();
  disconnectEcho();
});
</script>

<template>
  <div class="min-h-screen bg-background font-sans text-foreground">
    <RouterView />
  </div>
</template>

<script setup lang="ts">
import { useMessageStore } from "@/entities/message/model/message.store";
import type { ChatUser } from "@/entities/message/model/types";
import { Avatar, AvatarFallback, AvatarImage } from "@/shared/ui/avatar";
import { Input } from "@/shared/ui/input";

defineProps<{
  isOpen: boolean;
  error: string;
}>();

const emit = defineEmits<{
  openChat: [user: ChatUser];
}>();

const messageStore = useMessageStore();
</script>

<template>
  <!-- Conteúdo da Lista -->
  <div
    v-show="isOpen"
    class="p-3 space-y-3 h-80 overflow-y-auto"
  >
    <Input
      type="search"
      placeholder="Buscar conversa..."
      class="h-8 text-xs bg-muted/40"
    />

    <div
      v-if="messageStore.isLoadingConversations"
      class="py-3 text-center text-xs text-muted-foreground"
    >
      A carregar conversas...
    </div>
    <p
      v-else-if="error"
      class="py-3 text-center text-xs text-destructive"
    >
      {{ error }}
    </p>
    <p
      v-else-if="messageStore.unifiedContacts.length === 0"
      class="py-3 text-center text-xs text-muted-foreground"
    >
      Nenhuma conversa encontrada.
    </p>

    <div
      v-else
      class="space-y-1"
    >
      <div
        v-for="user in messageStore.unifiedContacts"
        :key="user.id"
        @click="emit('openChat', user)"
        class="flex items-center justify-between p-2 rounded-lg hover:bg-muted/50 cursor-pointer transition-colors"
      >
        <div class="flex items-center gap-2.5">
          <div class="relative">
            <Avatar class="h-7 w-7">
              <AvatarImage
                :src="user.avatar ?? ''"
                :alt="user.name"
              />
              <AvatarFallback class="text-xs">
                {{ user.name?.substring(0, 2) }}
              </AvatarFallback>
            </Avatar>

            <!-- Bolinha dinâmica baseada diretamente na flag `isOnline` injetada pelo getter -->
            <span
              :class="[
                'absolute bottom-0 right-0 h-3 w-3 rounded-full border-2 border-card',
                user.isOnline ? 'bg-emerald-500' : 'bg-muted',
              ]"
            />
          </div>
          <div class="flex flex-col text-left">
            <span class="text-xs font-medium text-foreground leading-none">
              {{ user.name }}
            </span>
            <span
              v-if="user.role"
              class="text-[10px] text-muted-foreground mt-1"
            >
              {{ user.role }}
            </span>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

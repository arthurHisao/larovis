<script setup lang="ts">
import { computed, onBeforeUnmount, ref, watch } from "vue";
import { LoaderCircle, MoreVertical, Users } from "lucide-vue-next";
import { useMessageStore } from "@/entities/message/model/message.store";
import type { ChatUser } from "@/entities/message/model/types";
import { Avatar, AvatarFallback, AvatarImage } from "@/shared/ui/avatar";
import { Button } from "@/shared/ui/button";
import {
  DropdownMenu,
  DropdownMenuContent,
  DropdownMenuGroup,
  DropdownMenuItem,
  DropdownMenuTrigger,
} from "@/shared/ui/dropdown-menu";
import { Input } from "@/shared/ui/input";

defineProps<{
  isOpen: boolean;
  error: string;
}>();

const emit = defineEmits<{
  openChat: [user: ChatUser];
  search: [query: string];
  loadMore: [];
  createGroup: [];
}>();

const messageStore = useMessageStore();
const search = ref("");
const listContainerRef = ref<HTMLElement | null>(null);

const getItemKey = (item: ChatUser) =>
  item.key ?? (item.isGroup ? `group-${item.id}` : `user-${item.id}`);

const filteredContacts = computed(() => {
  const query = search.value.trim().toLocaleLowerCase();
  if (!query) return messageStore.unifiedContacts;

  return messageStore.unifiedContacts.filter((user) =>
    user.name.toLocaleLowerCase().includes(query),
  );
});

const handleScroll = (event: Event) => {
  const target = event.target as HTMLElement;
  if (!target) return;

  const { scrollTop, scrollHeight, clientHeight } = target;
  if (scrollTop + clientHeight >= scrollHeight - 40) {
    if (
      messageStore.hasMoreConversations &&
      !messageStore.isLoadingMoreConversations &&
      !messageStore.isLoadingConversations
    ) {
      emit("loadMore");
      messageStore.loadMoreConversations();
    }
  }
};

let searchTimeout: ReturnType<typeof setTimeout> | undefined;

watch(search, (query) => {
  if (searchTimeout) clearTimeout(searchTimeout);
  searchTimeout = setTimeout(() => emit("search", query), 300);
});

onBeforeUnmount(() => {
  if (searchTimeout) clearTimeout(searchTimeout);
});
</script>

<template>
  <div
    v-show="isOpen"
    ref="listContainerRef"
    class="p-3 space-y-3 h-96 overflow-y-auto"
    @scroll.passive="handleScroll"
  >
    <div class="flex justify-between gap-1 items-center">
      <Input
        type="search"
        placeholder="Buscar conversa..."
        v-model="search"
        class="h-8 text-xs bg-muted/40"
      />

      <DropdownMenu>
        <DropdownMenuTrigger as-child>
          <Button
            aria-label="Mais opções"
            variant="ghost"
            class="size-6 rounded-full p-0 cursor-pointer shrink-0"
          >
            <MoreVertical class="size-3.5" />
          </Button>
        </DropdownMenuTrigger>

        <DropdownMenuContent align="end">
          <DropdownMenuGroup>
            <DropdownMenuItem @click="emit('createGroup')">
              <Users class="h-3.5 w-3.5 mr-1.5" />
              Novo grupo
            </DropdownMenuItem>
          </DropdownMenuGroup>
        </DropdownMenuContent>
      </DropdownMenu>
    </div>

    <div
      v-if="messageStore.isLoadingConversations"
      class="py-3 text-center text-xs text-muted-foreground flex items-center justify-center gap-1.5"
    >
      <LoaderCircle class="h-3.5 w-3.5 animate-spin text-primary" />
      <span>A carregar conversas...</span>
    </div>
    <p
      v-else-if="error"
      class="py-3 text-center text-xs text-destructive"
    >
      {{ error }}
    </p>
    <p
      v-else-if="filteredContacts.length === 0"
      class="py-3 text-center text-xs text-muted-foreground"
    >
      Nenhuma conversa encontrada.
    </p>

    <div
      v-else
      class="space-y-1"
    >
      <div
        v-for="user in filteredContacts"
        :key="getItemKey(user)"
        @click="emit('openChat', user)"
        class="flex items-center justify-between p-2 rounded-lg hover:bg-muted/50 cursor-pointer transition-colors"
      >
        <div class="flex items-center gap-2.5 min-w-0">
          <div class="relative shrink-0">
            <Avatar class="h-7 w-7">
              <AvatarImage
                :src="user.avatar ?? ''"
                :alt="user.name"
              />
              <AvatarFallback class="text-xs">
                <Users
                  v-if="user.isGroup"
                  class="h-3.5 w-3.5"
                />
                <template v-else>{{ user.name?.substring(0, 2) }}</template>
              </AvatarFallback>
            </Avatar>

            <span
              v-if="!user.isGroup"
              :class="[
                'absolute bottom-0 right-0 h-3 w-3 rounded-full border-2 border-card',
                user.isOnline ? 'bg-emerald-500' : 'bg-muted',
              ]"
            />
          </div>

          <div class="flex flex-col text-left truncate">
            <span
              class="text-xs font-medium text-foreground leading-none truncate"
            >
              {{ user.name }}
            </span>
            <span
              v-if="user.department || user.role"
              class="text-[10px] text-muted-foreground mt-1 truncate"
            >
              {{ user.department || user.role }}
            </span>
          </div>
        </div>
      </div>

      <div
        v-if="messageStore.isLoadingMoreConversations"
        class="py-2 flex items-center justify-center gap-1.5 text-[11px] text-muted-foreground"
      >
        <LoaderCircle class="h-3.5 w-3.5 animate-spin text-primary" />
        <span>Carregando mais...</span>
      </div>
    </div>
  </div>
</template>

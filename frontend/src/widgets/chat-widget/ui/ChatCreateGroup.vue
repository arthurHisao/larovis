<script setup lang="ts">
import { computed, ref, watch } from "vue";
import { onKeyStroke } from "@vueuse/core";
import { Check, Users, X } from "lucide-vue-next";
import { useAuthStore } from "@/entities/user/model/auth.store";
import { useMessageStore } from "@/entities/message/model/message.store";
import { Avatar, AvatarFallback, AvatarImage } from "@/shared/ui/avatar";
import { Button } from "@/shared/ui/button";

const props = defineProps<{
  open: boolean;
  isSubmitting?: boolean;
}>();

const emit = defineEmits<{
  close: [];
  created: [payload: { name: string; memberIds: number[] }];
}>();

const authStore = useAuthStore();
const messageStore = useMessageStore();

const groupName = ref("");
const memberSearch = ref("");
const selectedUserIds = ref<number[]>([]);

onKeyStroke("Escape", (e) => {
  if (!props.open || props.isSubmitting) return;
  e.preventDefault();
  emit("close");
});

watch(
  () => props.open,
  (isOpen) => {
    if (isOpen) {
      groupName.value = "";
      memberSearch.value = "";
      selectedUserIds.value = [];
    }
  },
);

const availableUsers = computed(() => {
  const query = memberSearch.value.trim().toLowerCase();
  return messageStore.unifiedContacts
    .filter((user) => !user.isGroup)
    .filter((user) => user.id !== authStore.user?.id)
    .filter((user) => (query ? user.name.toLowerCase().includes(query) : true));
});

const isSelected = (userId: number) => selectedUserIds.value.includes(userId);

const toggleUser = (userId: number) => {
  if (props.isSubmitting) return;
  if (isSelected(userId)) {
    selectedUserIds.value = selectedUserIds.value.filter((id) => id !== userId);
  } else {
    selectedUserIds.value.push(userId);
  }
};

const canSubmit = computed(() => {
  return (
    groupName.value.trim().length > 0 &&
    selectedUserIds.value.length > 0 &&
    !props.isSubmitting
  );
});

function handleSubmit() {
  if (!canSubmit.value) return;

  emit("created", {
    name: groupName.value.trim(),
    memberIds: [...selectedUserIds.value],
  });
}
</script>

<template>
  <Teleport to="body">
    <Transition
      enter-active-class="transition duration-200 ease-out"
      enter-from-class="opacity-0"
      enter-to-class="opacity-100"
      leave-active-class="transition duration-150 ease-in"
      leave-from-class="opacity-100"
      leave-to-class="opacity-0"
    >
      <div
        v-if="props.open"
        class="fixed inset-0 z-50 flex items-center justify-center bg-black/70 backdrop-blur-xs p-4"
        @click.self="!props.isSubmitting && emit('close')"
      >
        <div
          class="w-full max-w-md rounded-xl border border-border bg-card text-card-foreground p-4 shadow-2xl space-y-3"
          @click.stop
        >
          <div class="flex items-center justify-between">
            <div class="flex items-center gap-2">
              <Users class="h-4 w-4 text-primary" />
              <h3 class="text-sm font-semibold">Novo grupo</h3>
            </div>

            <Button
              type="button"
              variant="ghost"
              size="icon"
              class="h-7 w-7 cursor-pointer rounded-full"
              :disabled="props.isSubmitting"
              @click="emit('close')"
            >
              <X class="h-4 w-4" />
            </Button>
          </div>

          <form
            @submit.prevent="handleSubmit"
            class="space-y-3"
          >
            <div class="space-y-1">
              <label class="text-[11px] font-medium text-muted-foreground">
                Nome do grupo
              </label>
              <input
                v-model="groupName"
                type="text"
                placeholder="Ex.: Squad Backend"
                :disabled="props.isSubmitting"
                class="w-full rounded-md border border-input bg-muted/40 px-3 py-2 text-xs focus:outline-none focus:ring-1 focus:ring-ring"
              />
            </div>

            <div class="space-y-1.5">
              <div
                class="flex items-center justify-between text-[11px] text-muted-foreground"
              >
                <span
                  >Participantes ({{
                    selectedUserIds.length
                  }}
                  selecionados)</span
                >
              </div>

              <input
                v-model="memberSearch"
                type="search"
                placeholder="Buscar membro..."
                :disabled="props.isSubmitting"
                class="w-full rounded-md border border-input bg-muted/40 px-3 py-1.5 text-xs focus:outline-none focus:ring-1 focus:ring-ring"
              />

              <div
                class="max-h-48 overflow-y-auto space-y-1 pr-1 border border-border/60 rounded-lg p-1.5"
              >
                <p
                  v-if="availableUsers.length === 0"
                  class="py-4 text-center text-xs text-muted-foreground"
                >
                  Nenhum usuário encontrado.
                </p>

                <button
                  v-for="user in availableUsers"
                  :key="user.id"
                  type="button"
                  @click="toggleUser(user.id)"
                  class="w-full flex items-center justify-between p-2 rounded-md text-left transition-colors cursor-pointer"
                  :class="
                    isSelected(user.id)
                      ? 'bg-primary/15 text-foreground'
                      : 'hover:bg-muted/50 text-foreground'
                  "
                >
                  <div class="flex items-center gap-2 min-w-0">
                    <Avatar class="h-7 w-7 shrink-0">
                      <AvatarImage
                        :src="user.avatar ?? ''"
                        :alt="user.name"
                      />
                      <AvatarFallback class="text-[10px]">
                        {{ user.name.substring(0, 2).toUpperCase() }}
                      </AvatarFallback>
                    </Avatar>

                    <div class="truncate">
                      <p class="text-xs font-medium truncate">
                        {{ user.name }}
                      </p>
                      <p
                        v-if="user.role"
                        class="text-[10px] text-muted-foreground truncate"
                      >
                        {{ user.role }}
                      </p>
                    </div>
                  </div>

                  <span
                    class="h-4 w-4 rounded border flex items-center justify-center shrink-0"
                    :class="
                      isSelected(user.id)
                        ? 'bg-primary border-primary text-primary-foreground'
                        : 'border-muted-foreground/40'
                    "
                  >
                    <Check
                      v-if="isSelected(user.id)"
                      class="h-3 w-3"
                    />
                  </span>
                </button>
              </div>
            </div>

            <div class="flex justify-end gap-2 pt-1">
              <Button
                type="button"
                variant="ghost"
                size="sm"
                :disabled="props.isSubmitting"
                @click="emit('close')"
              >
                Cancelar
              </Button>
              <Button
                type="submit"
                size="sm"
                :disabled="!canSubmit"
              >
                {{ props.isSubmitting ? "Criando..." : "Criar grupo" }}
              </Button>
            </div>
          </form>
        </div>
      </div>
    </Transition>
  </Teleport>
</template>

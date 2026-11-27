<script setup lang="ts">
import { computed, ref, watch } from "vue";
import { onKeyStroke } from "@vueuse/core";
import {
  Camera,
  Check,
  ImagePlus,
  LoaderCircle,
  Pencil,
  Trash2,
  Users,
  X,
} from "lucide-vue-next";
import { useAuthStore } from "@/entities/user/model/auth.store";
import { useMessageStore } from "@/entities/message/model/message.store";
import type { ChatUser, UpdateGroupPayload } from "@/entities/message/model/types";
import { Avatar, AvatarFallback, AvatarImage } from "@/shared/ui/avatar";
import { Button } from "@/shared/ui/button";

const props = defineProps<{
  open: boolean;
  group: ChatUser | null;
  isSubmitting?: boolean;
}>();

const emit = defineEmits<{
  close: [];
  updated: [payload: UpdateGroupPayload];
}>();

const authStore = useAuthStore();
const messageStore = useMessageStore();

const groupName = ref("");
const memberSearch = ref("");
const selectedUserIds = ref<number[]>([]);

// Imagem de capa
const fileInputRef = ref<HTMLInputElement | null>(null);
const coverImageFile = ref<File | null>(null);
const coverImagePreview = ref<string | null>(null);
const removeCoverImage = ref(false);
const imageError = ref<string | null>(null);

const ACCEPTED_TYPES = ["image/jpeg", "image/png", "image/gif", "image/webp"];
const MAX_SIZE_MB = 5;
const MAX_SIZE_BYTES = MAX_SIZE_MB * 1024 * 1024;

onKeyStroke("Escape", (e) => {
  if (!props.open || props.isSubmitting) return;
  e.preventDefault();
  emit("close");
});

const triggerFileInput = () => {
  if (props.isSubmitting) return;
  fileInputRef.value?.click();
};

const handleFileChange = (e: Event) => {
  const target = e.target as HTMLInputElement;
  const file = target.files?.[0];
  if (!file) return;

  imageError.value = null;

  if (!ACCEPTED_TYPES.includes(file.type)) {
    imageError.value = "Formato inválido. Use JPG, PNG, GIF ou WEBP.";
    target.value = "";
    return;
  }

  if (file.size > MAX_SIZE_BYTES) {
    imageError.value = `A imagem ultrapassa o limite de ${MAX_SIZE_MB}MB.`;
    target.value = "";
    return;
  }

  coverImageFile.value = file;
  removeCoverImage.value = false;
  coverImagePreview.value = URL.createObjectURL(file);
};

const handleRemoveCover = () => {
  coverImageFile.value = null;
  coverImagePreview.value = null;
  removeCoverImage.value = true;
  imageError.value = null;
  if (fileInputRef.value) {
    fileInputRef.value.value = "";
  }
};

const initFromGroup = async (group: ChatUser) => {
  groupName.value = group.name || "";
  memberSearch.value = "";
  coverImageFile.value = null;
  removeCoverImage.value = false;
  imageError.value = null;
  coverImagePreview.value = group.coverImage ?? group.avatar ?? null;

  const groupId = group.chatId ?? group.id;

  // Carregar os membros se ainda não tiver em cache
  if (!messageStore.groupMembers?.[groupId]?.length) {
    await messageStore.fetchGroupMembers(groupId);
  }

  const currentMembers = messageStore.groupMembers?.[groupId] || [];
  selectedUserIds.value = currentMembers
    .filter((m) => m.id !== authStore.user?.id)
    .map((m) => m.id);
};

watch(
  () => [props.open, props.group],
  ([isOpen]) => {
    if (isOpen && props.group) {
      initFromGroup(props.group);
    } else if (!isOpen) {
      groupName.value = "";
      memberSearch.value = "";
      selectedUserIds.value = [];
      coverImageFile.value = null;
      coverImagePreview.value = null;
      removeCoverImage.value = false;
      imageError.value = null;
    }
  },
  { immediate: true },
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
  if (!canSubmit.value || !props.group) return;

  emit("updated", {
    id: props.group.chatId ?? props.group.id,
    name: groupName.value.trim(),
    memberIds: [...selectedUserIds.value],
    coverImageFile: coverImageFile.value,
    removeCoverImage: removeCoverImage.value,
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
              <Pencil class="h-4 w-4 text-primary" />
              <h3 class="text-sm font-semibold">Editar grupo</h3>
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
            <!-- Imagem de Capa do Grupo -->
            <div class="space-y-1.5">
              <label class="text-[11px] font-medium text-muted-foreground">
                Imagem de capa do grupo
              </label>

              <input
                ref="fileInputRef"
                type="file"
                accept="image/jpeg,image/png,image/gif,image/webp"
                class="hidden"
                @change="handleFileChange"
              />

              <div
                class="relative w-full h-24 rounded-lg border border-dashed border-border/80 bg-muted/30 overflow-hidden flex flex-col items-center justify-center group hover:border-primary/60 transition-colors"
              >
                <!-- Preview da imagem caso exista -->
                <template v-if="coverImagePreview">
                  <img
                    :src="coverImagePreview"
                    alt="Capa do grupo"
                    class="w-full h-full object-cover"
                  />
                  <div
                    class="absolute inset-0 bg-black/50 opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center gap-2"
                  >
                    <Button
                      type="button"
                      variant="secondary"
                      size="sm"
                      class="h-7 text-xs px-2.5 cursor-pointer"
                      @click="triggerFileInput"
                    >
                      <Camera class="h-3.5 w-3.5 mr-1" />
                      Alterar
                    </Button>
                    <Button
                      type="button"
                      variant="destructive"
                      size="sm"
                      class="h-7 text-xs px-2 cursor-pointer"
                      @click="handleRemoveCover"
                    >
                      <Trash2 class="h-3.5 w-3.5" />
                    </Button>
                  </div>
                </template>

                <!-- Estado vazio: botão para adicionar -->
                <template v-else>
                  <button
                    type="button"
                    class="w-full h-full flex flex-col items-center justify-center gap-1.5 cursor-pointer text-muted-foreground hover:text-foreground transition-colors p-2"
                    @click="triggerFileInput"
                  >
                    <div class="h-8 w-8 rounded-full bg-muted flex items-center justify-center">
                      <ImagePlus class="h-4 w-4 text-primary" />
                    </div>
                    <span class="text-[11px] font-medium">
                      Clique para adicionar uma capa
                    </span>
                    <span class="text-[9px] text-muted-foreground/70">
                      JPG, PNG, GIF ou WEBP (máx. {{ MAX_SIZE_MB }}MB)
                    </span>
                  </button>
                </template>
              </div>

              <p
                v-if="imageError"
                class="text-[10px] text-destructive"
              >
                {{ imageError }}
              </p>
            </div>

            <!-- Nome do grupo -->
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

            <!-- Participantes -->
            <div class="space-y-1.5">
              <div
                class="flex items-center justify-between text-[11px] text-muted-foreground"
              >
                <span>
                  Participantes ({{ selectedUserIds.length }} selecionados)
                </span>
              </div>

              <input
                v-model="memberSearch"
                type="search"
                placeholder="Buscar membro..."
                :disabled="props.isSubmitting"
                class="w-full rounded-md border border-input bg-muted/40 px-3 py-1.5 text-xs focus:outline-none focus:ring-1 focus:ring-ring"
              />

              <div
                class="max-h-44 overflow-y-auto space-y-1 pr-1 border border-border/60 rounded-lg p-1.5"
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
                <LoaderCircle
                  v-if="props.isSubmitting"
                  class="h-3.5 w-3.5 animate-spin mr-1.5"
                />
                {{ props.isSubmitting ? "Salvando..." : "Salvar alterações" }}
              </Button>
            </div>
          </form>
        </div>
      </div>
    </Transition>
  </Teleport>
</template>

<script setup lang="ts">
import { computed } from "vue";
import { onKeyStroke } from "@vueuse/core";
import { AlertTriangle, LoaderCircle, ShieldAlert, Trash2, X } from "lucide-vue-next";
import { useAuthStore } from "@/entities/user/model/auth.store";
import type { ChatUser } from "@/entities/message/model/types";
import { Button } from "@/shared/ui/button";

const props = defineProps<{
  open: boolean;
  group: ChatUser | null;
  isSubmitting?: boolean;
}>();

const emit = defineEmits<{
  close: [];
  confirm: [groupId: number];
}>();

const authStore = useAuthStore();

onKeyStroke("Escape", (e) => {
  if (!props.open || props.isSubmitting) return;
  e.preventDefault();
  emit("close");
});

// Verifica se o usuário atual é o criador / admin do grupo
const isGroupAdmin = computed(() => {
  if (!props.group) return false;
  const currentUserId = authStore.user?.id;
  if (!currentUserId) return false;

  // 1. Flags booleanas diretas
  if (typeof props.group.isAdmin === "boolean") return props.group.isAdmin;
  if (typeof (props.group as any).is_admin === "boolean")
    return (props.group as any).is_admin;
  if (typeof props.group.isCreator === "boolean") return props.group.isCreator;
  if (typeof (props.group as any).is_creator === "boolean")
    return (props.group as any).is_creator;

  // 2. ID do criador
  const creatorId =
    props.group.creatorId ??
    (props.group as any).creator_id ??
    (props.group as any).created_by;

  if (creatorId !== undefined && creatorId !== null) {
    return Number(creatorId) === Number(currentUserId);
  }

  // 3. Fallback de desenvolvimento: se o backend ainda não retornar o campo de criador,
  // permite para testes no frontend
  return true;
});

const handleConfirm = () => {
  if (!props.group || props.isSubmitting || !isGroupAdmin.value) return;
  emit("confirm", props.group.chatId ?? props.group.id);
};
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
          class="w-full max-w-md rounded-xl border border-border bg-card text-card-foreground p-5 shadow-2xl space-y-4"
          @click.stop
        >
          <!-- Cabeçalho -->
          <div class="flex items-center justify-between">
            <div class="flex items-center gap-2.5">
              <div
                class="h-8 w-8 rounded-full flex items-center justify-center"
                :class="isGroupAdmin ? 'bg-destructive/15 text-destructive' : 'bg-amber-500/15 text-amber-500'"
              >
                <Trash2
                  v-if="isGroupAdmin"
                  class="h-4 w-4"
                />
                <ShieldAlert
                  v-else
                  class="h-4 w-4"
                />
              </div>
              <h3 class="text-sm font-semibold">
                {{ isGroupAdmin ? "Deletar grupo" : "Ação não permitida" }}
              </h3>
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

          <!-- Conteúdo Quando Tem Permissão -->
          <template v-if="isGroupAdmin">
            <div class="space-y-2 text-xs">
              <p class="text-foreground leading-relaxed">
                Deseja realmente deletar o grupo
                <strong class="font-semibold text-foreground">
                  "{{ props.group?.name }}"
                </strong>?
              </p>

              <div
                class="flex items-start gap-2 p-3 rounded-lg bg-destructive/10 border border-destructive/20 text-destructive text-[11px] leading-relaxed"
              >
                <AlertTriangle class="h-4 w-4 shrink-0 mt-0.5" />
                <span>
                  Esta ação não poderá ser desfeita. Todas as mensagens,
                  mídias e o histórico deste grupo serão excluídos permanentemente para todos os participantes.
                </span>
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
                type="button"
                variant="destructive"
                size="sm"
                :disabled="props.isSubmitting"
                @click="handleConfirm"
              >
                <LoaderCircle
                  v-if="props.isSubmitting"
                  class="h-3.5 w-3.5 animate-spin mr-1.5"
                />
                {{ props.isSubmitting ? "Deletando..." : "Deletar grupo" }}
              </Button>
            </div>
          </template>

          <!-- Conteúdo Quando NÃO Tem Permissão -->
          <template v-else>
            <div class="space-y-2 text-xs">
              <p class="text-muted-foreground leading-relaxed">
                A opção de deletar o grupo está restrita apenas ao
                <strong class="text-foreground font-semibold">criador / administrador</strong>
                do grupo.
              </p>
              <p class="text-[11px] text-muted-foreground/80">
                Se você não for o administrador, apenas pode sair da conversa ou arquivá-la.
              </p>
            </div>

            <div class="flex justify-end pt-1">
              <Button
                type="button"
                variant="secondary"
                size="sm"
                @click="emit('close')"
              >
                Entendido
              </Button>
            </div>
          </template>
        </div>
      </div>
    </Transition>
  </Teleport>
</template>

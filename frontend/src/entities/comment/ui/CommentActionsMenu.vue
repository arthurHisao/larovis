<script setup lang="ts">
import Button from "@/shared/ui/button/Button.vue";
import DropdownMenu from "@/shared/ui/dropdown-menu/DropdownMenu.vue";
import DropdownMenuContent from "@/shared/ui/dropdown-menu/DropdownMenuContent.vue";
import DropdownMenuGroup from "@/shared/ui/dropdown-menu/DropdownMenuGroup.vue";
import DropdownMenuItem from "@/shared/ui/dropdown-menu/DropdownMenuItem.vue";
import DropdownMenuTrigger from "@/shared/ui/dropdown-menu/DropdownMenuTrigger.vue";
import {
  MessageCircleWarning,
  MessageSquareReply,
  MoreHorizontal,
  Pencil,
  Trash,
} from "lucide-vue-next";

defineProps<{
  isAuthor: boolean;
}>();

const emit = defineEmits<{
  edit: [];
  delete: [];
  report: [];
  toggleReply: [];
}>();
</script>

<template>
  <DropdownMenu>
    <DropdownMenuTrigger as-child>
      <Button
        size="sm"
        aria-label="Expandir ações sob o comentário"
        variant="outline"
        class="rounded-full h-5.5! w-5.5! p-0 bg-muted hover:bg-muted/40 focus:outline-none border border-transparent outline-none data-[state=open]:ring-4 data-[state=open]:ring-gray-400/70 data-[state=open]:border-gray-300 focus-visible:ring-4 focus-visible:ring-gray-400/70 focus-visible:border-gray-300"
      >
        <MoreHorizontal class="size-3" />
      </Button>
    </DropdownMenuTrigger>

    <DropdownMenuContent
      class="w-40"
      align="start"
    >
      <!-- Editar -->
      <!-- <DropdownMenuGroup v-if="isAuthor">
        <DropdownMenuItem>
          <Pencil />
          Editar
        </DropdownMenuItem>
      </DropdownMenuGroup> -->

      <!-- Deletar -->
      <DropdownMenuGroup v-if="isAuthor">
        <DropdownMenuItem @click="emit('delete')">
          <Trash />
          Deletar
        </DropdownMenuItem>
      </DropdownMenuGroup>

      <!-- Denunciar -->
      <!-- <DropdownMenuGroup v-if="!isAuthor">
        <DropdownMenuItem @click="emit('report')">
          <MessageCircleWarning />
          Denunciar
        </DropdownMenuItem>
      </DropdownMenuGroup> -->

      <!-- Responder -->
      <DropdownMenuGroup>
        <DropdownMenuItem @click="emit('toggleReply')">
          <MessageSquareReply />
          Responder
        </DropdownMenuItem>
      </DropdownMenuGroup>
    </DropdownMenuContent>
  </DropdownMenu>
</template>

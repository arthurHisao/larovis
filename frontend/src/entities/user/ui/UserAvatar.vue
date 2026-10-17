<script setup lang="ts">
import Avatar from "@/shared/ui/avatar/Avatar.vue";
import AvatarFallback from "@/shared/ui/avatar/AvatarFallback.vue";
import AvatarImage from "@/shared/ui/avatar/AvatarImage.vue";
import { useAuthStore } from "../model/auth.store";

const authStore = useAuthStore();
</script>

<template>
  <Avatar class="h-10 w-10">
    <AvatarImage
      :src="authStore.user?.avatar"
      :alt="authStore.user?.name"
    />
    <AvatarFallback class="bg-primary/10 text-primary font-bold">
      {{ authStore.user?.name.substring(0, 2).toUpperCase() }}
    </AvatarFallback>
  </Avatar>
</template>

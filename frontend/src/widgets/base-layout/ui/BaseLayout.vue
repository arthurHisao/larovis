<script setup lang="ts">
import AppHeader from '@/widgets/app-header/ui/AppHeader.vue'
import AppSidebar from '@/widgets/app-sidebar/ui/AppSidebar.vue'
</script>

<template>
    <div class="flex min-h-screen flex-col bg-background text-foreground font-sans">
        <!-- Header / Navbar Superior -->
        <AppHeader />

        <div class="flex flex-1">
            <!-- Sidebar Lateral -->
            <aside class="sticky top-16 h-[calc(100vh-4rem)] w-64 flex flex-col gap-4 py-4 overflow-y-auto shadow-sm">
                <!-- <aside class="w-64 border-r border-border p-4 hidden md:block shadow-sm"> -->
                <AppSidebar />
            </aside>

            <!-- Conteúdo Principal Dinâmico -->
            <main class="flex-1 p-6">
                <div class="max-w-5xl mx-auto">
                    <RouterView />
                </div>
            </main>
        </div>
    </div>
</template>
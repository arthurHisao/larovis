<script setup lang="ts">
import { Calendar, Megaphone } from "@lucide/vue";
import { AppHeader } from "@/widgets/app-header";
import { AppSidebar } from "@/widgets/app-sidebar";
import { Card } from "@/shared/ui/card";
import ChatWidget from "@/widgets/chat-widget/ui/ChatWidget.vue";
</script>

<template>
  <div
    class="flex min-h-screen flex-col bg-background text-foreground font-sans"
  >
    <!-- Header / Navbar Superior -->
    <AppHeader />

    <div class="flex flex-1">
      <!-- Sidebar Lateral -->
      <aside
        class="sticky top-16 h-[calc(100vh-4rem)] w-64 flex flex-col gap-4 py-4 overflow-y-auto shadow-sm"
      >
        <!-- <aside class="w-64 border-r border-border p-4 hidden md:block shadow-sm"> -->
        <AppSidebar />
      </aside>

      <!-- Conteúdo Principal Dinâmico -->
      <main class="flex-1 p-6">
        <div class="max-w-5xl mx-auto">
          <RouterView />
        </div>
      </main>

      <!-- Coluna Lateral Direita: Destaques e Atividades -->
      <aside
        class="sticky top-16 h-[calc(100vh-4rem)] w-80 shrink-0 space-y-6 p-6 overflow-y-auto hidden lg:block"
      >
        <!-- Bloco: Comunicados Importantes / Fixados -->
        <Card class="p-4 border-border bg-card space-y-3">
          <div
            class="flex items-center gap-2 text-primary font-semibold text-sm"
          >
            <Megaphone class="h-4 w-4" />
            <span>Comunicados Oficiais</span>
          </div>
          <div class="space-y-3 text-xs divide-y divide-border/60">
            <div class="pt-2 first:pt-0 space-y-1">
              <span
                class="inline-block px-2 py-0.5 rounded bg-primary/10 text-primary font-medium text-[10px]"
              >
                RH
              </span>
              <p
                class="font-medium text-foreground hover:underline cursor-pointer"
              >
                Pesquisa de clima organizacional 2026
              </p>
              <p class="text-muted-foreground">Encerra em 3 dias</p>
            </div>
            <div class="pt-2 space-y-1">
              <span
                class="inline-block px-2 py-0.5 rounded bg-primary/10 text-primary font-medium text-[10px]"
              >
                Segurança
              </span>
              <p
                class="font-medium text-foreground hover:underline cursor-pointer"
              >
                Nova política de senhas fortes
              </p>
              <p class="text-muted-foreground">Obrigatório para todos</p>
            </div>
          </div>
        </Card>

        <!-- Bloco: Aniversariantes ou Eventos do Mês -->
        <Card class="p-4 border-border bg-card space-y-3">
          <div
            class="flex items-center gap-2 text-foreground font-semibold text-sm"
          >
            <Calendar class="h-4 w-4 text-primary" />
            <span>Eventos da Semana</span>
          </div>
          <ul class="space-y-2 text-xs text-muted-foreground">
            <li class="flex justify-between items-center">
              <span>Reunião Geral All-Hands</span>
              <span class="font-medium text-foreground">Sexta, 15h</span>
            </li>
          </ul>
        </Card>
      </aside>
    </div>

    <!-- Chat -->
    <ChatWidget />
  </div>
</template>

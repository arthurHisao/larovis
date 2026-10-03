<script setup lang="ts">
import { ref } from 'vue'
import {
    Home, Megaphone, Bookmark, ChevronDown,
    Building2, Users, Briefcase, HelpCircle
} from 'lucide-vue-next'

import { Button } from '@/shared/ui/button'
import { Collapsible, CollapsibleTrigger, CollapsibleContent } from '@/shared/ui/collapsible'

const isOpen = ref(true)
</script>

<template>
    <div class="flex flex-col h-full gap-4 py-4">
        <!-- Links Principais -->
        <div class="px-3 py-2">
            <h2 class="mb-2 px-4 text-xs font-semibold tracking-tight text-muted-foreground uppercase">
                Navegação
            </h2>
            <nav class="space-y-1">
                <Button variant="ghost" class="w-full justify-start gap-3 text-sm font-normal">
                    <Home class="h-4 w-4" />
                    Feed Principal
                </Button>
                <Button variant="ghost" class="w-full justify-start gap-3 text-sm font-normal">
                    <Megaphone class="h-4 w-4" />
                    Comunicados
                </Button>
                <Button variant="ghost" class="w-full justify-start gap-3 text-sm font-normal">
                    <Bookmark class="h-4 w-4" />
                    Salvos
                </Button>
            </nav>
        </div>

        <!-- Sanfona: Departamentos (Collapsible) -->
        <div class="px-3 py-2">
            <Collapsible v-model:open="isOpen">
                <CollapsibleTrigger as-child>
                    <Button variant="ghost"
                        class="w-full justify-between px-4 text-xs font-semibold tracking-tight text-muted-foreground uppercase hover:bg-transparent">
                        <span>Departamentos</span>
                        <ChevronDown class="h-4 w-4 transition-transform duration-200"
                            :class="{ 'rotate-180': isOpen }" />
                    </Button>
                </CollapsibleTrigger>

                <CollapsibleContent class="space-y-1 mt-1">
                    <Button variant="ghost" class="w-full justify-start gap-3 pl-8 text-sm font-normal">
                        <Building2 class="h-4 w-4 text-muted-foreground" />
                        Tecnologia (TI)
                    </Button>
                    <Button variant="ghost" class="w-full justify-start gap-3 pl-8 text-sm font-normal">
                        <Users class="h-4 w-4 text-muted-foreground" />
                        Recursos Humanos
                    </Button>
                    <Button variant="ghost" class="w-full justify-start gap-3 pl-8 text-sm font-normal">
                        <Briefcase class="h-4 w-4 text-muted-foreground" />
                        Comercial & Vendas
                    </Button>
                </CollapsibleContent>
            </Collapsible>
        </div>

        <!-- Links Úteis -->
        <div class="px-3 py-2 mt-auto">
            <h2 class="mb-2 px-4 text-xs font-semibold tracking-tight text-muted-foreground uppercase">
                Suporte
            </h2>
            <nav class="space-y-1">
                <Button variant="ghost" class="w-full justify-start gap-3 text-sm font-normal">
                    <HelpCircle class="h-4 w-4" />
                    Central de Ajuda
                </Button>
            </nav>
        </div>
    </div>
</template>

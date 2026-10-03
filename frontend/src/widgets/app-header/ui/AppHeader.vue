<script setup lang="ts">
import { RouterLink, useRouter } from 'vue-router'
import { Bell, Search, User, Settings, LogOut } from 'lucide-vue-next'

// Componentes da camada shared/ui do Shadcn
import { Button } from '@/shared/ui/button'
import { Input } from '@/shared/ui/input'
import { Separator } from '@/shared/ui/separator'
import { Avatar, AvatarImage, AvatarFallback } from '@/shared/ui/avatar'
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuLabel,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '@/shared/ui/dropdown-menu'
import { useAuthStore } from '@/entities/login/model/auth.store'
import logo from '@/shared/assets/images/larovis.svg'

const authStore = useAuthStore();
const router = useRouter()

const handleLogout = async () => {
    await authStore.logout();
    router.push({ name: "login" });
}
</script>

<template>
    <header
        class="sticky top-0 z-50 w-full border-b border-border/40 bg-background/95 backdrop-blur supports-[backdrop-filter]:bg-background/60 shadow-xs">
        <div class="flex h-16 items-center justify-between px-6">

            <!-- Marca / Logo -->
            <div class="flex items-center gap-6">
                <RouterLink to="/"
                    class="flex items-center gap-2 font-semibold text-lg tracking-tight hover:opacity-90 transition-opacity">
                    <!-- <div
                        class="flex h-8 w-8 items-center justify-center rounded-md bg-primary text-primary-foreground font-bold">
                        <span>L</span>
                        <Heart class="size-3 fill-current text-primary-foreground" />
                    </div>
                    <span class="text-primary">Larovis</span> -->
                    <div class="flex items-center justify-center text-primary-foreground font-bold">
                        <img :src="logo" class="w-14 h-auto p-1" alt="Logotipo" />
                        <span class="text-primary">Larovis</span>
                    </div>
                </RouterLink>

                <!-- Campo de Busca Rápida (Opcional para Intranet) -->
                <div class="relative hidden sm:block w-64">
                    <Search class="absolute left-2.5 top-2.5 h-4 w-4 text-muted-foreground" />
                    <Input type="search" placeholder="Buscar posts, pessoas..."
                        class="pl-9 h-9 bg-muted/50 text-sm focus-visible:bg-background/40" />
                </div>
            </div>

            <!-- Navegação Principal -->
            <nav class="flex items-center gap-6 text-sm font-medium">
                <ul class="flex items-center gap-1 sm:gap-2">
                    <li>
                        <Button variant="ghost" as-child class="text-muted-foreground hover:text-foreground">
                            <RouterLink to="/">Feed Principal</RouterLink>
                        </Button>
                    </li>
                    <li>
                        <Button variant="ghost" as-child class="text-muted-foreground hover:text-foreground">
                            <RouterLink to="/departamentos">Departamentos</RouterLink>
                        </Button>
                    </li>
                    <li>
                        <Button variant="ghost" as-child class="text-muted-foreground hover:text-foreground">
                            <RouterLink to="/comunicados">Comunicados</RouterLink>
                        </Button>
                    </li>
                </ul>
            </nav>

            <!-- Ações do Usuário e Perfil -->
            <div class="flex items-center gap-3">
                <!-- Notificações -->
                <Button variant="ghost" size="icon" class="relative text-muted-foreground hover:text-foreground">
                    <Bell class="h-5 w-5" />
                    <span class="absolute top-2 right-2 h-2 w-2 rounded-full bg-destructive" />
                </Button>

                <Separator orientation="vertical" class="h-6 hidden sm:block" />

                <!-- Dropdown do Usuário (Usando Shadcn Avatar + DropdownMenu) -->
                <DropdownMenu>
                    <DropdownMenuTrigger as-child>
                        <Button variant="ghost" class="relative h-9 w-9 rounded-full">
                            <Avatar class="h-9 w-9">
                                <AvatarImage src="https://github.com/shadcn.png" alt="Avatar" />
                                <AvatarFallback>LV</AvatarFallback>
                            </Avatar>
                        </Button>
                    </DropdownMenuTrigger>

                    <DropdownMenuContent align="end" class="w-56">
                        <DropdownMenuLabel class="font-normal">
                            <div class="flex flex-col space-y-1">
                                <p class="text-sm font-medium leading-none">Usuário Larovis</p>
                                <p class="text-xs leading-none text-muted-foreground">usuario@empresa.com</p>
                            </div>
                        </DropdownMenuLabel>
                        <DropdownMenuSeparator />
                        <DropdownMenuItem>
                            <User class="mr-2 h-4 w-4" />
                            <span>Meu Perfil</span>
                        </DropdownMenuItem>
                        <DropdownMenuItem>
                            <Settings class="mr-2 h-4 w-4" />
                            <span>Configurações</span>
                        </DropdownMenuItem>
                        <DropdownMenuSeparator />
                        <DropdownMenuItem @click="handleLogout" class="text-destructive focus:text-destructive">
                            <LogOut class="mr-2 h-4 w-4" />
                            <span>Sair</span>
                        </DropdownMenuItem>
                    </DropdownMenuContent>
                </DropdownMenu>
            </div>

        </div>
    </header>
</template>
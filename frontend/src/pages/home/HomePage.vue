<script setup lang="ts">
import { onMounted, ref } from 'vue'

import PostCard from '@/entities/post/ui/PostCard.vue'
// import { mockPosts } from '@/entities/post/model/mock-posts'
import type { Post } from '@/entities/post/model/types'

import { Card } from '@/shared/ui/card'
import { Button } from '@/shared/ui/button'

import { Calendar, Megaphone } from '@lucide/vue'
import ChatWidget from '@/widgets/chat-widget/ui/ChatWidget.vue'

import { postService } from '@/entities/post/api/post-service'

// Estado reativo dos posts mockados
// const posts = ref<Post[]>(mockPosts)
const posts = ref<Post[]>()
const isLoading = ref(true)
// const newPostContent = ref('')

// Ação de simulação para o Like
const handleToggleLike = (postId: number) => {
    const post = posts.value.find(p => p.id === postId)
    if (post) {
        post.isLiked = !post.isLiked
        post.likesCount += post.isLiked ? 1 : -1
    }
}

// Buscar posts do banco do Laravel ao carregar a página
const fetchPosts = async () => {
    try {
        isLoading.value = true
        posts.value = await postService.getAll()
    } catch (error) {
        console.error('Erro ao carregar publicações:', error)
    } finally {
        isLoading.value = false
    }
}

// Criar nova publicação no Laravel
// const handleCreatePost = async () => {
//     if (!newPostContent.value.trim()) return

//     try {
//         const createdPost = await postService.create(newPostContent.value)
//         posts.value.unshift(createdPost)
//         newPostContent.value = ''
//     } catch (error) {
//         console.error('Erro ao publicar:', error)
//     }
// }

// Toggle Like
// const handleToggleLike = async (postId: number) => {
//     // Atualização otimista na interface
//     const post = posts.value.find(p => p.id === postId)
//     if (post) {
//         post.isLiked = !post.isLiked
//         post.likesCount += post.isLiked ? 1 : -1
//     }

//     try {
//         await postService.toggleLike(postId)
//     } catch (error) {
//         // Reverte em caso de erro na API
//         if (post) {
//             post.isLiked = !post.isLiked
//             post.likesCount += post.isLiked ? 1 : -1
//         }
//     }
// }

onMounted(() => {
    fetchPosts()
})
</script>

<template>
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 items-start">

        <!-- Coluna Principal (2/3 no Desktop): Criação e Feed -->
        <section class="lg:col-span-2 space-y-6 ">

            <!-- Filtros Rápidos do Feed -->
            <div class="flex items-center justify-between border-b border-border pb-2">
                <h2 class="font-semibold text-lg text-foreground">Publicações Recentes</h2>
                <div class="flex gap-2">
                    <Button variant="secondary" size="sm" class="text-xs">Todos</Button>
                    <Button variant="ghost" size="sm" class="text-xs text-muted-foreground">Comunicados</Button>
                    <Button variant="ghost" size="sm" class="text-xs text-muted-foreground">Departamentos</Button>
                </div>
            </div>

            <!-- Lista de Posts (Esqueleto de Card do Post) -->
            <div class="space-y-4">
                <template v-if="isLoading">
                    <PostCard v-for="n in 3" :key="n" :loading="true" />
                </template>

                <template v-else>
                    <PostCard v-for="post in posts" :key="post.id" :post="post" @like="handleToggleLike" />
                </template>
            </div>
        </section>

        <!-- Coluna Lateral Direita (1/3 no Desktop): Destaques e Atividades -->
        <aside class="sticky top-20 space-y-6 hidden lg:block">

            <!-- Bloco: Comunicados Importantes / Fixados -->
            <Card class=" p-4 border-border bg-card space-y-3">
                <div class="flex items-center gap-2 text-primary font-semibold text-sm">
                    <Megaphone class="h-4 w-4" />
                    <span>Comunicados Oficiais</span>
                </div>
                <div class="space-y-3 text-xs divide-y divide-border/60">
                    <div class="pt-2 first:pt-0 space-y-1">
                        <span
                            class="inline-block px-2 py-0.5 rounded bg-primary/10 text-primary font-medium text-[10px]">RH</span>
                        <p class="font-medium text-foreground hover:underline cursor-pointer">Pesquisa de clima
                            organizacional 2026</p>
                        <p class="text-muted-foreground">Encerra em 3 dias</p>
                    </div>
                    <div class="pt-2 space-y-1">
                        <span
                            class="inline-block px-2 py-0.5 rounded bg-primary/10 text-primary font-medium text-[10px]">Segurança</span>
                        <p class="font-medium text-foreground hover:underline cursor-pointer">Nova política de senhas
                            fortes</p>
                        <p class="text-muted-foreground">Obrigatório para todos</p>
                    </div>
                </div>
            </Card>

            <!-- Bloco: Aniversariantes ou Eventos do Mês -->
            <Card class="p-4 border-border bg-card space-y-3">
                <div class="flex items-center gap-2 text-foreground font-semibold text-sm">
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

            <!-- Chat -->
            <ChatWidget />
        </aside>
    </div>
</template>

<script setup lang="ts">
import { ref, nextTick } from 'vue'
import { MessageCircle, ChevronUp, ChevronDown, Send, X } from 'lucide-vue-next'

import { Card } from '@/shared/ui/card'
import { Input } from '@/shared/ui/input'
import { Button } from '@/shared/ui/button'
import { Avatar, AvatarImage, AvatarFallback } from '@/shared/ui/avatar'

interface User {
    id: number
    name: string
    role: string
    avatar: string
}

interface Message {
    id: number
    senderId: number // 0 = Usuário logado
    text: string
    time: string
}

// Controle do Widget Principal (Lista de Contatos)
const isListOpen = ref(false)

// Controle das Janelas/Abas de Chat Abertas (Guarda os IDs dos Usuários)
const activeChatUserIds = ref<number[]>([])

// Mensagens digitadas por usuário { userId: "texto..." }
const draftMessages = ref<Record<number, string>>({})

// Referência dinâmica dos containers de chat para autoscroll
const chatContainers = ref<Record<number, HTMLElement | null>>({})

const onlineUsers = ref<User[]>([
    { id: 1, name: 'Ana Souza', role: 'Design', avatar: 'https://images.unsplash.com/photo-1494790108377-be9c29b29330?w=100' },
    { id: 2, name: 'Carlos Lima', role: 'Desenvolvedor', avatar: 'https://images.unsplash.com/photo-1507003211169-0a1dd7228f2d?w=100' },
    { id: 3, name: 'Beatriz Ramos', role: 'RH', avatar: 'https://images.unsplash.com/photo-1438761681033-6461ffad8d80?w=100' },
    { id: 5, name: 'Thay Sousa', role: 'RH', avatar: 'https://images.unsplash.com/photo-1544005313-94ddf0286df2?auto=format&fit=crop&w=200&q=80' },
    { id: 6, name: 'Alberto Whiska', role: 'T.I', avatar: 'https://images.unsplash.com/photo-1500648767791-00dcc994a43e?auto=format&fit=crop&w=200&q=80' },
])

// Histórico de conversas
const conversations = ref<Record<number, Message[]>>({
    1: [
        { id: 1, senderId: 1, text: 'Oi! Consegue dar uma olhada nos protótipos do Figma?', time: '14:20' },
        { id: 2, senderId: 0, text: 'Opa, Ana! Olho sim, me manda o link.', time: '14:22' }
    ],
    2: [
        { id: 1, senderId: 2, text: 'A API de posts já está pronta!', time: '10:05' }
    ]
})

// Abre a aba do chat se ainda não estiver aberta
const openChatWith = (user: User) => {
    if (!activeChatUserIds.value.includes(user.id)) {
        // Limite máximo de 3 chats abertos lado a lado para não quebrar a tela
        if (activeChatUserIds.value.length >= 3) {
            activeChatUserIds.value.shift()
        }
        activeChatUserIds.value.push(user.id)
    }

    if (!conversations.value[user.id]) {
        conversations.value[user.id] = []
    }

    scrollToBottom(user.id)
}

// Fecha uma aba específica
const closeChat = (userId: number) => {
    activeChatUserIds.value = activeChatUserIds.value.filter(id => id !== userId)
}

// Envia mensagem na aba específica
const sendMessage = (userId: number) => {
    const text = draftMessages.value[userId]?.trim()
    if (!text) return

    const now = new Date().toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' })

    conversations.value[userId].push({
        id: Date.now(),
        senderId: 0,
        text: text,
        time: now
    })

    draftMessages.value[userId] = ''
    scrollToBottom(userId)
}

const scrollToBottom = (userId: number) => {
    nextTick(() => {
        const el = chatContainers.value[userId]
        if (el) {
            el.scrollTop = el.scrollHeight
        }
    })
}

// Helper para pegar objeto do usuário por ID
const getUser = (id: number) => onlineUsers.value.find(u => u.id === id)
</script>

<template>
    <!-- Container Flutuante na Direita (Alinha as janelas lado a lado com flex-row-reverse) -->
    <div class="fixed bottom-0 right-6 z-40 flex flex-row-reverse items-end gap-3">

        <!-- 1. WIDGET PRINCIPAL: LISTA DE CONTATOS -->
        <div class="w-72 shadow-lg transition-all duration-300">
            <Card class="border-border bg-card overflow-hidden rounded-b-none border-b-0 py-0 gap-0">

                <!-- Cabeçalho Principal -->
                <button @click="isListOpen = !isListOpen"
                    class="w-full flex items-center justify-between p-3 bg-card hover:bg-muted/40 transition-colors cursor-pointer border-b border-border/60">
                    <div class="flex items-center gap-2">
                        <div class="relative">
                            <MessageCircle class="h-5 w-5 text-primary" />
                            <span class="absolute -top-0.5 -right-0.5 h-2 w-2 rounded-full bg-emerald-500" />
                        </div>
                        <span class="font-semibold text-xs text-foreground">Mensagens</span>
                    </div>

                    <div class="flex items-center gap-1 text-muted-foreground">
                        <ChevronUp v-if="!isListOpen" class="h-4 w-4" />
                        <ChevronDown v-else class="h-4 w-4" />
                    </div>
                </button>

                <!-- Conteúdo da Lista -->
                <div v-show="isListOpen" class="p-3 space-y-3 h-[320px] overflow-y-auto">
                    <Input type="search" placeholder="Buscar conversa..." class="h-8 text-xs bg-muted/40" />

                    <div class="space-y-1">
                        <div v-for="user in onlineUsers" :key="user.id" @click="openChatWith(user)"
                            class="flex items-center justify-between p-2 rounded-lg hover:bg-muted/50 cursor-pointer transition-colors">
                            <div class="flex items-center gap-2.5">
                                <div class="relative">
                                    <Avatar class="h-7 w-7">
                                        <AvatarImage :src="user.avatar" :alt="user.name" />
                                        <AvatarFallback class="text-xs">{{ user.name.substring(0, 2) }}</AvatarFallback>
                                    </Avatar>
                                    <span
                                        class="absolute bottom-0 right-0 h-2 w-2 rounded-full bg-emerald-500 border-2 border-card" />
                                </div>
                                <div class="flex flex-col text-left">
                                    <span class="text-xs font-medium text-foreground leading-none">{{ user.name
                                        }}</span>
                                    <span class="text-[10px] text-muted-foreground mt-1">{{ user.role }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </Card>
        </div>

        <!-- 2. JANELAS DE CHAT ABERTAS (LADO A LADO) -->
        <div v-for="userId in activeChatUserIds" :key="userId" class="w-72 shadow-lg transition-all duration-300">
            <Card v-if="getUser(userId)"
                class="border-border bg-card overflow-hidden rounded-b-none border-b-0 py-0 gap-0">

                <!-- Header da Janela Individual -->
                <div class="flex items-center justify-between p-2.5 bg-card border-b border-border/60">
                    <div class="flex items-center gap-2">
                        <Avatar class="h-6 w-6">
                            <AvatarImage :src="getUser(userId)?.avatar" />
                            <AvatarFallback>{{ getUser(userId)?.name.substring(0, 2) }}</AvatarFallback>
                        </Avatar>
                        <span class="font-semibold text-xs text-foreground truncate max-w-[120px]">{{
                            getUser(userId)?.name }}</span>
                    </div>

                    <div class="flex items-center gap-1">
                        <Button variant="ghost" size="icon" class="h-6 w-6 text-muted-foreground hover:text-foreground"
                            @click="closeChat(userId)">
                            <X class="h-3.5 w-3.5" />
                        </Button>
                    </div>
                </div>

                <!-- Corpo da Janela Individual -->
                <div class="h-[320px] flex flex-col justify-between">
                    <!-- Mensagens -->
                    <div :ref="(el) => { if (el) chatContainers[userId] = el as HTMLElement }"
                        class="p-3 space-y-2 overflow-y-auto flex-1">
                        <div v-for="msg in conversations[userId] || []" :key="msg.id" class="flex flex-col"
                            :class="msg.senderId === 0 ? 'items-end' : 'items-start'">
                            <div class="max-w-[85%] rounded-lg p-2 text-xs"
                                :class="msg.senderId === 0 ? 'bg-primary text-primary-foreground' : 'bg-muted text-foreground'">
                                {{ msg.text }}
                            </div>
                            <span class="text-[9px] text-muted-foreground mt-0.5">{{ msg.time }}</span>
                        </div>
                    </div>

                    <!-- Input individual -->
                    <form @submit.prevent="sendMessage(userId)"
                        class="p-2 border-t border-border flex items-center gap-1.5 bg-card">
                        <Input v-model="draftMessages[userId]" placeholder="Escreva algo..."
                            class="h-8 text-xs bg-muted/40 flex-1" />
                        <Button type="submit" size="icon" class="h-8 w-8 shrink-0">
                            <Send class="h-3.5 w-3.5" />
                        </Button>
                    </form>
                </div>

            </Card>
        </div>

    </div>
</template>
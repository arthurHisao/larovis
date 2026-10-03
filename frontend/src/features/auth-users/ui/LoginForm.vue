<script setup lang="ts">
import { Button } from '@/shared/ui/button'
import {
    Card,
    CardContent,
    CardDescription,
    CardFooter,
    CardHeader,
    CardTitle,
} from '@/shared/ui/card'
import { Input } from '@/shared/ui/input'
import { Label } from '@/shared/ui/label'
import { useLogin } from '../model/use-login'

const { email, password, isLoading, errorMessage, handleLogin } = useLogin()
</script>

<template>
    <Card class="w-full max-w-sm">
        <CardHeader>
            <CardTitle>Faça Login</CardTitle>
            <CardDescription>
                Entre com seu e-mail e senha corporativos
            </CardDescription>
        </CardHeader>

        <CardContent>
            <form @submit.prevent="handleLogin" id="login-form">
                <div class="grid w-full items-center gap-4">
                    <!-- Feedback de Erro -->
                    <div v-if="errorMessage" class="text-sm text-red-500 font-medium">
                        {{ errorMessage }}
                    </div>

                    <div class="flex flex-col space-y-2">
                        <Label for="email">Email</Label>
                        <Input id="email" type="email" v-model="email" placeholder="email@empresa.com" required
                            :disabled="isLoading" />
                    </div>

                    <div class="flex flex-col space-y-2">
                        <Label for="password">Senha</Label>
                        <Input id="password" type="password" v-model="password" placeholder="Informe a sua senha"
                            required :disabled="isLoading" />
                    </div>

                    <div class="flex items-center">
                        <a href="#" class="ml-auto inline-block text-sm underline">
                            Esqueceu a senha?
                        </a>
                    </div>
                </div>
            </form>
        </CardContent>

        <CardFooter class="flex flex-col gap-2">
            <Button type="submit" form="login-form" class="w-full" :disabled="isLoading">
                <span v-if="isLoading">Entrando...</span>
                <span v-else>Login</span>
            </Button>
        </CardFooter>
    </Card>
</template>
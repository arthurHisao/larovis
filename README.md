![Pŕevia da nossa intranet fictícia](Larovis-capture.png)



# Larovis

Rede social corporativa / intranet para comunicação interna, publicação de conteúdos por departamento e troca de mensagens em tempo real.

![Preview do Larovis](./caminho-da-sua-imagem.png)

## 🚀 Tecnologias

### Frontend
- **Vue 3** (Composition API) + **TypeScript**
- **Vite**
- **Pinia** (gerenciamento de estado) e **Vue Router**
- **Tailwind CSS v4** + **Reka UI** + **Lucide Icons**
- **Axios** + **Laravel Echo** (`pusher-js`) para comunicação em tempo real
- Arquitetura baseada em **Feature-Sliced Design (FSD)** (`app`, `pages`, `widgets`, `features`, `entities`, `shared`)

### Backend
- **PHP 8.3+** & **Laravel 13**
- **Laravel Sanctum** (autenticação)
- **Laravel Reverb** (WebSockets / presença online, digitação e mensagens em tempo real)
- **PostgreSQL**

## ✨ Funcionalidades

- **Autenticação:** Login e registro de usuários vinculados a departamentos.
- **Feed de Publicações:** Criação, edição e exclusão de posts com upload de múltiplas imagens, curtidas e comentários.
- **Chat em Tempo Real:**
  - Conversas individuais (1-a-1) e em grupo com status de presença online, indicador de *"digitando..."*, confirmação de leitura e resposta a mensagens.
  - Criação, edição (com foto de capa), visualização de membros e exclusão de grupos restrita ao criador.

## ⚙️ Como rodar localmente

### Backend
```bash
cd backend
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate --seed
php artisan storage:link
php artisan serve
```

> Para o chat em tempo real, inicie também o servidor WebSocket do Reverb:
> ```bash
> php artisan reverb:start
> ```

### Frontend
```bash
cd frontend
npm install
npm run dev
```

## 📄 Licença

Este projeto está sob a licença [MIT](./LICENSE).

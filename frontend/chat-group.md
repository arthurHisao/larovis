# Core de Chat — Mensagens Diretas (1-a-1) e Grupos

Documentação técnica da arquitetura implementada para o chat em tempo real (Vue 3 + Pinia + Laravel + Reverb).

---

## 1. Visão Geral da Arquitetura

O sistema suporta dois fluxos independentes que convivem na mesma interface de lista e janelas flutuantes:

1. **Conversas Diretas (1-a-1):** indexadas pelo `userId` do contato (`conversations[userId]`).
2. **Grupos:** indexados pelo `chatId` do grupo (`groupMessages[chatId]`).

> **Regra de Ouro (Separação de IDs):**  
> Como `users.id` e `chats.id` vêm de tabelas diferentes no banco de dados e podem ter o mesmo valor numérico, o frontend **nunca** mistura IDs de usuários e IDs de grupos no mesmo array ou mapa. Na lista unificada, cada item usa uma chave prefixada (`user-{id}` ou `group-{id}`).

---

## 2. Backend (Laravel)

### Modelos e Tabelas

- **`chats`:** armazena a sala (`id`, `is_group`, `name`).
- **`chat_user` (pivot):** vincula usuários à sala (`chat_id`, `user_id`, `last_read_at`).
- **`messages`:** mensagens da sala (`id`, `chat_id`, `sender_id`, `text`, `parent_id`).

### Endpoints (`routes/api.php` — `auth:sanctum`)

| Método | Rota                     | Controller                           | Descrição                                                          |
| :----- | :----------------------- | :----------------------------------- | :----------------------------------------------------------------- |
| `GET`  | `/messages`              | `MessageController@index`            | Lista unificada de contatos e grupos (paginada por cursor).        |
| `GET`  | `/messages/{user}`       | `MessageController@show`             | Histórico paginado de conversa 1-a-1 com `{user}`.                 |
| `POST` | `/messages`              | `MessageController@store`            | Envia mensagem privada (`receiver_id`, `text`, `parent_id`).       |
| `POST` | `/chats/ensure-room`     | `MessageController@ensureRoom`       | Garante ou cria a sala 1-a-1 e retorna o `chat_id`.                |
| `POST` | `/chats/{chat}/read`     | `MessageController@markAsRead`       | Atualiza `last_read_at` e dispara evento de leitura.               |
| `POST` | `/chats/groups`          | `MessageController@storeGroup`       | Cria um novo grupo (`name`, `member_ids`) e vincula participantes. |
| `GET`  | `/chats/{chat}/messages` | `MessageController@showChat`         | Histórico paginado de mensagens do grupo `{chat}`.                 |
| `POST` | `/chats/{chat}/messages` | `MessageController@sendGroupMessage` | Envia mensagem para o grupo `{chat}` (`text`, `parent_id`).        |

### Eventos em Tempo Real (Reverb / Broadcast)

- **`MessageSent` (`message.sent`):** transmitido no canal privado `chat.{chat_id}` via `broadcast(...)->toOthers()` com o payload formatado pelo `MessageResource`.
- **`MessageRead` (`messages.read`):** transmitido no canal privado `chat.{chat_id}` via `broadcastAs()` informando o `userId` que leu a conversa.

---

## 3. Frontend (Vue 3 + Pinia + Echo)

### Camada de API e Store (`entities/message`)

- **`message.api.ts`:**
  - `sendMessage` → `POST /messages`
  - `sendGroupMessage` → `POST /chats/${chatId}/messages`
  - `fetchGroupMessages` → `GET /chats/${chatId}/messages`
  - `createGroup` → `POST /chats/groups`
- **`message.store.ts`:**
  - Estado 1-a-1: `conversations`, `chatIdsByUser`, `messagesNextCursor`, `loadingMoreMessages`.
  - Estado Grupos: `groupMessages`, `groupMessagesNextCursor`, `loadingMoreGroupMessages`, `loadingGroupConversations`.

### WebSockets (`useChatSocket.ts`)

- **`subscribeToOnlineUsers`:** gerencia presença global no canal `online-users`.
- **`subscribeToChatRoom(echo, chatId, currentUserId, conversationUserId, onTyping, isGroup)`:**
  - Assina o canal privado `chat.{chatId}`.
  - Ao receber `.message.sent`:
    - Se `isGroup === true`: insere em `addIncomingGroupMessage(chatId, message)`.
    - Se `isGroup === false`: insere em `addIncomingMessage(conversationUserId, message)`.
  - Escuta `.messages.read` (atualiza leitura no 1-a-1) e `whisper("typing")` (indicador de digitação).

### Componentes de Interface (Widget)

- **`ChatWidget.vue`:** orquestrador principal. Mantém estados separados para janelas privadas (`activeChatUserIds`, `collapsedChatUserIds`) e janelas de grupo (`activeGroupIds`, `collapsedGroupIds`), limitando a até 3 janelas abertas por categoria.
- **`ChatOnlineUsers.vue`:** lista unificada com busca e scroll infinito. Usa `getItemKey` (`user-*` / `group-*`) e dispara a abertura do modal de criação de grupo.
- **`ChatCreateGroup.vue`:** modal para criação de grupo com busca de participantes, seleção múltipla e fechamento via `Esc`.
- **`ChatMessage.vue`:** renderiza as janelas flutuantes de grupo e 1-a-1 separadamente, gerenciando scroll infinito para o topo, respostas (`parent_id`), indicador de digitação e assinatura da sala no Reverb.

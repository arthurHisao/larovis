# 🐘 Larovis - Intranet de Portfólio & Chat

Bem-vindo ao **Larovis** (Laravel + Love), o projeto pessoal criado para recuperar o prazer de programar, longe da burocracia e da reinvenção de roda do ecossistema Node.js. Este sistema é uma intranet robusta com arquitetura de API REST no backend e foco em alta produtividade.

---

## 🛠️ Tecnologias do Ecossistema

- **Backend:** PHP 8.4 + Laravel 11/12/13 (API Mode)
- **Banco de Dados:** PostgreSQL (Rodando em container Docker)
- **Autenticação:** Laravel Sanctum (Tokens via JSON)
- **Tempo Real (Chat):** Laravel Reverb (WebSockets nativo e grátis)
- **Frontend Sugerido:** Vue.js 3 (Vite) ou React — Consumindo a API isoladamente.

---

## 🗺️ Roteiro de Desenvolvimento (Roadmap)

### ⏹️ Fase 1: Infraestrutura e Autenticação (Amanhã)

- [ ] Executar `php artisan install:api` para gerar o arquivo `routes/api.php` e tabelas do Sanctum.
- [ ] Criar o `AuthController` com dois endpoints principais:
    - `POST /api/register` (Cadastro de novos usuários na intranet).
    - `POST /api/login` (Validação de credenciais e retorno do Token Sanctum).
- [ ] Criar o endpoint protegido `GET /api/me` para o frontend validar o usuário logado usando o token Bearer.

### ⏹️ Fase 2: Módulo de Portfólio (CRUD de Projetos)

- [ ] Criar o Model e a Migration da tabela `portfolio_posts` no Postgres.
- [ ] Desenvolver o `PortfolioController` com os seguintes endpoints (protegidos por autenticação):
    - `GET /api/portfolio` (Listar todos os projetos da intranet).
    - `POST /api/portfolio` (Cadastrar um novo projeto/ideia).
    - `DELETE /api/portfolio/{id}` (Remover um projeto próprio).

### ⏹️ Fase 3: Módulo de Chat em Tempo Real (O Coração da Intranet)

- [ ] Instalar e configurar o **Laravel Reverb** executando `php artisan install:broadcasting`.
- [ ] Criar a Migration da tabela `chat_messages` para persistir o histórico de conversas.
- [ ] Configurar um _Broadcast Event_ (`MessageSent`) para disparar as mensagens via WebSockets.
- [ ] Criar os endpoints:
    - `GET /api/messages` (Carregar o histórico das últimas mensagens ao entrar no chat).
    - `POST /api/messages` (Enviar mensagem e disparar o evento em tempo real para os outros usuários).

### ⏹️ Fase 4: O Frontend (Consumindo a API)

Para manter o projeto divertido e visualmente recompensador, o frontend será construído em uma pasta separada (`/larovis-front`) consumindo nossa API do Laravel via Axios:

- [ ] Criar o app básico (Vue ou React) usando Vite.
- [ ] Montar a tela de Login/Cadastro salvando o token no `localStorage`.
- [ ] Criar o painel principal (Dashboard) listando os cards do portfólio.
- [ ] Integrar o chat no frontend usando o **Laravel Echo** (biblioteca JS levíssima para escutar o Reverb sem sofrimento).

---

## 🚀 Comandos Úteis do Projeto

- Subir o banco de dados: `docker compose up -d`
- Derrubar o banco de dados: `docker compose down`
- Rodar as migrações do banco: `php artisan migrate`
- Subir o servidor local da API: `php artisan serve`

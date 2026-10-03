# 🐘 Larovis - Documento de Contexto Histórico

## 📋 Perfil do Desenvolvedor & Visão de Mercado

- **Experiência:** Desenvolvedor com 5 anos de carreira sólida.
- **Momento Atual:** Desanimado com o mercado tradicional de tecnologia. O cenário corporativo atual desvalorizou o mérito direto em prol de processos seletivos internos hipercompetitivos (estilo "Big Brother"), onde profissionais experientes precisam disputar vagas com uma grande massa de novos entrantes e graduandos em ADS (Análise e Desenvolvimento de Sistemas).
- **Propósito do Projeto:** Recuperar o prazer de programar, focar em produtividade real e criar uma solução própria (Intranet) com potencial futuro de ser oferecida para empresas de forma descontraída, operando fora da bolha de pressão corporativa.

---

## 🛠️ Stack Tecnológica do Projeto (Larovis)

- **Nome do Projeto:** Larovis (Uma brincadeira interna que une _Laravel_ + _Love_ e homenageia um amigo).
- **Backend:** PHP 8.4 + Laravel 13 configurado estritamente no **Modo API**.
- **Banco de Dados:** PostgreSQL rodando localmente de forma isolada via contêiner Docker (`docker-compose.yml`), utilizando variáveis de ambiente integradas ao arquivo `.env` do Laravel.
- **Autenticação:** Laravel Sanctum (Emissão de Tokens via JSON / Bearer Token).
- **Frontend:** Aplicação isolada em Vue.js 3 alimentada por Vite e estilizada com os componentes modernos do **Shadcn**.

---

## 📍 Ponto Onde o Projeto Parou (Pronto para o próximo passo)

### 1. Ambiente Local Configurado

- PHP 8.4 configurado no Ubuntu 24.04 com o driver `php8.4-pgsql` ativado após resolução de conflitos de repositório.
- Banco de Dados PostgreSQL ativo e respondendo na porta `5432`.
- Executado o comando `php artisan install:api`, criando a estrutura do Laravel Sanctum e as rotas de API.
- Executado `php artisan migrate` com sucesso, estruturando as tabelas padrão no banco.

### 2. Autenticação Concluída do Zero

- **Model `User` atualizado:** Configurado com os novos padrões do Laravel 13 (`#[Fillable]`, `#[Hidden]` via PHP 8 Attributes) e com a trait `HasApiTokens` devidamente importada.
- **Controlador criado (`Api/AuthController`):** Possui as lógicas de `register()` (com código de status HTTP `201`) e `login()` (com código de status HTTP `200`), ambas gerando e retornando o Token do Sanctum em formato JSON.
- **Rotas mapeadas (`routes/api.php`):** Endpoints públicos `/api/register` e `/api/login` funcionando e validados com sucesso através de requisições JSON no corpo (Body) via Insomnia. Rota protegida `/api/me` configurada com o middleware do Sanctum.

---

## 🚀 Próximo Passo Planejado

Iniciar a **Fase de Posts e Notícias** (o feed da intranet). O escopo começará com a criação do modelo e migração para a tabela `posts`, que separará comunicados oficiais ("notícias") de conteúdos descontraídos ("resenhas/memes") do setor.

_Comando engatilhado para a próxima sessão:_ `php artisan make:model Post -m`

src/
├── app/
│ ├── providers/ # Vue Router, Pinia/State
│ ├── router/ # Definição e mapeamento das rotas
│ ├── styles/ # style.css do Tailwind v4
│ └── App.vue # Componente raiz
│
├── pages/ # Telas compostas por widgets
│ ├── home/ # ex: HomePage.vue
│ ├── login/ # ex: LoginPage.vue
│ └── department/ # ex: DepartmentPage.vue
│
├── widgets/ # Layouts e grandes seções funcionais
│ ├── feed/ # FeedWidget.vue (une a lista de posts + filtro)
│ ├── navbar/ # AppHeader.vue
│ └── sidebar/ # AppSidebar.vue
│
├── features/ # Interações e casos de uso do usuário
│ ├── auth/ # LoginForms, LogoutButton
│ ├── create-post/ # Formulario e Modal de criar Post
│ └── filter-by-dept/ # Seletor/Tabs de filtro de departamentos
│
├── entities/ # Modelos e regras puras do domínio
│ ├── post/ # PostCard.vue, post.types.ts, post.api.ts
│ ├── user/ # UserAvatar.vue, user.types.ts
│ └── department/ # DepartmentBadge.vue, department.types.ts
│
└── shared/ # Código reutilizável genérico
├── api/ # Instância do Axios/Fetch configurada
├── ui/ # Componentes puros do Shadcn (Button, Card, Input)
└── lib/ # utils.ts (cn helper)

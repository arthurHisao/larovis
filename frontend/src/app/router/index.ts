import {
  createRouter,
  createWebHistory,
  type RouteRecordRaw,
} from "vue-router";

// As páginas são importadas da camada 'pages'
import LoginPage from "@/pages/login/LoginPage.vue";
import { useAuthStore } from "@/entities/login/model/auth.store";

const routes: Array<RouteRecordRaw> = [
  {
    path: "/login",
    name: "login",
    component: LoginPage,
    meta: { requiresAuth: false },
  },
  {
    path: "/",
    component: () => import("@/widgets/base-layout/ui/BaseLayout.vue"), // Layout como wrapper
    meta: { requiresAuth: true },
    children: [
      {
        path: "",
        name: "home",
        component: () => import("@/pages/home/HomePage.vue"),
      },
      // {
      //     path: "departamento/:slug",
      //     name: "department",
      //     component: () =>
      //         import("@/pages/department/DepartmentPage.vue"),
      // },
    ],
  },
];

export const router = createRouter({
  history: createWebHistory(),
  routes,
});

// Middleware de autenticação simples
router.beforeEach(async (to, _from, next) => {
  const authStore = useAuthStore();

  // 1. Se é a primeira carga de página (F5 ou abertura), aguarda o Sanctum confirmar a sessão no backend
  if (!authStore.isInitialized) {
    await authStore.fetchUser();
  }

  const isAuth = authStore.isAuthenticated;
  const isLoginPage = to.name === "login" || to.path === "/login";

  // 2. Rota exige autenticação e o usuário NÃO está logado
  if (to.meta.requiresAuth && !isAuth) {
    return next({ name: "login" });
  }

  // 3. Usuário TENTA ir para a página de login mas JÁ ESTÁ logado
  if (isLoginPage && isAuth) {
    return next({ name: "home" });
  }

  // 4. Libera a navegação
  next();
});

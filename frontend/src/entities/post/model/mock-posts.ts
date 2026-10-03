import type { Post } from "./types";

export const mockPosts: Post[] = [
    {
        id: 1,
        author: {
            id: 101,
            name: "Equipe de Tecnologia",
            avatar: "https://github.com/shadcn.png",
            department: "Tecnologia (TI)",
        },
        content:
            "Manutenção programada nos servidores no próximo sábado às 22h. Os serviços de e-mail e VPN poderão sofrer instabilidades temporárias.",
        createdAt: "Há 2 horas",
        likesCount: 12,
        commentsCount: 4,
        isLiked: true,
    },
    {
        id: 2,
        author: {
            id: 102,
            name: "Mariana Silva",
            avatar: "https://images.unsplash.com/photo-1494790108377-be9c29b29330?w=150",
            department: "Recursos Humanos",
        },
        content:
            "Lembrete: A pesquisa de clima organizacional 2026 termina nesta sexta-feira! A sua opinião é fundamental para melhorarmos nosso ambiente de trabalho. Acesse o link enviado por e-mail.",
        createdAt: "Há 5 horas",
        likesCount: 28,
        commentsCount: 9,
    },
    {
        id: 3,
        author: {
            id: 103,
            name: "Lucas Mendes",
            avatar: "https://images.unsplash.com/photo-1507003211169-0a1dd7228f2d?w=150",
            department: "Comercial",
        },
        content:
            "Fechamos o mês com 115% da meta batida! Parabéns a todo o time de Vendas pelo empenho e dedicação extraordinários neste trimestre! 🚀🔥",
        createdAt: "Ontem às 16:30",
        likesCount: 45,
        commentsCount: 15,
    },
];

export const mockComments: Comment[] = [
  {
    id: 1,
    postId: 101,
    author: {
      id: 1,
      name: "Ana Souza",
      avatar: "https://unsplash.com",
    },
    content: "Gostei da publicação!",
    createdAt: "Há 15 min",
    replies: [
      {
        id: 3,
        postId: 101,
        parentId: 1,
        author: {
          id: 3,
          name: "Bruno Reis",
          avatar: "https://unsplash.com",
        },
        content: "Concordo com você, Ana! Conteúdo muito bom.",
        createdAt: "Há 10 min",
        replies: [
          {
            id: 4,
            postId: 101,
            parentId: 3,
            author: {
              id: 1,
              name: "Ana Souza",
              avatar: "https://unsplash.com",
            },
            content:
              "Você é um puxa saco! você só concorda porque gosta de mim!",
            createdAt: "Há 1 min",
            replies: [],
          },
        ],
      },
    ],
  },
  {
    id: 2,
    postId: 101,
    author: {
      id: 2,
      name: "Carlos Lima",
      avatar: "https://unsplash.com",
    },
    content: "Ficou ótimo! Parabéns ao time de UI/UX.",
    createdAt: "Há 5 min",
    replies: [],
  },
  {
    id: 5,
    postId: 101,
    author: {
      id: 99,
      name: "Valdir Fiscal do Óbvio",
      avatar: "https://unsplash.com",
    },
    content:
      "Postagem bonita, mas só pensa assim quem não é pai e quem não é mãe! Quem tem que acordar 5h pra fazer mamadeira sabe que essa Larovis não ajuda em nada no transporte da creche. Absurdo!",
    createdAt: "Há 3 min",
    replies: [
      {
        id: 6,
        postId: 101,
        parentId: 5,
        author: {
          id: 2,
          name: "Carlos Lima",
          avatar: "https://unsplash.com",
        },
        content:
          "Seu Valdir, isso é só o feed da intranet de TI... o que tem a ver com a creche?",
        createdAt: "Há 2 min",
        replies: [
          {
            id: 7,
            postId: 101,
            parentId: 6,
            author: {
              id: 99,
              name: "Valdir Fiscal do Óbvio",
              avatar: "https://unsplash.com",
            },
            content:
              "É o que eu falei!! Falta de empatia com a família brasileira. Quando o RH fizer a Pesquisa de Clima eu vou expor tudo lá!",
            createdAt: "Há 30 segundos",
            replies: [
              {
                id: 8,
                postId: 101,
                parentId: 7,
                author: {
                  id: 2,
                  name: "Carlos Lima",
                  avatar: "https://unsplash.com",
                },
                content:
                  "Mas a pesquisa é sobre o clima organizacional de 2026, Seu Valdir, não sobre o governo ou transporte público kkkk relaxa",
                createdAt: "Há 15 segundos",
                replies: [
                  {
                    id: 9,
                    postId: 101,
                    parentId: 8,
                    author: {
                      id: 99,
                      name: "Valdir Fiscal do Óbvio",
                      avatar: "https://unsplash.com",
                    },
                    content:
                      "Ah pronto! Sabia que você ia defender esse absurdo, Carlos. Com esse papinho de 'clima organizacional' aposto que você é petista e apoia essa pouca vergonha de comunismo na nossa TI!! Só quem não tem filho aceita uma palhaçada dessas!!",
                    createdAt: "Há 2 segundos",
                    replies: [
                      {
                        id: 10,
                        postId: 101,
                        parentId: 9,
                        author: {
                          id: 42,
                          name: "Lucas Dev Pipoca",
                          avatar: "https://unsplash.com",
                        },
                        content:
                          "🍿 Estou aqui só pelos commits e pela treta no feed. Alguém traz mais refri.",
                        createdAt: "Agora mesmo",
                        replies: [
                          {
                            id: 11,
                            postId: 101,
                            parentId: 10,
                            author: {
                              id: 88,
                              name: "Sr. Moacyr Almoxarifado",
                              avatar: "https://unsplash.com",
                            },
                            content:
                              "Tsc tsc... Tá na minha época a gente não tinha essa frescura de 'feed' ou Larovis não. A gente batia ponto no cartão de papel, se reclamasse de comunismo o chefe mandava carregar caixa no sol quente e ninguém chorava por mamadeira! Essa geração de hoje tá perdida.",
                            createdAt: "Agora mesmo",
                            replies: [
                              {
                                id: 12,
                                postId: 101,
                                parentId: 11,
                                author: {
                                  id: 99,
                                  name: "Valdir Fiscal do Óbvio",
                                  avatar: "https://unsplash.com",
                                },
                                content:
                                  "Falou tudo, Moacyr! Mas o Carlos ali acha bonito essa palhaçada que o RH inventou. Certeza que o layout desse sistema foi feito por comunista para confundir o trabalhador cristão!",
                                createdAt: "Agora mesmo",
                                replies: [],
                              },
                            ],
                          },
                        ],
                      },
                    ],
                  },
                ],
              },
            ],
          },
        ],
      },
    ],
  },
];

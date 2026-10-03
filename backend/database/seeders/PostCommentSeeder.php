<?php

namespace Database\Seeders;

use App\Models\Post;
use App\Models\User;
use App\Models\PostComment;
use Illuminate\Database\Seeder;

class PostCommentSeeder extends Seeder
{
    public function run(): void
    {
        // Alvo perfeito: busca especificamente o post da Pesquisa de Clima
        $postClima = Post::where('title', 'Pesquisa de Clima')->first();

        if (!$postClima) {
            $this->command->error('Post "Pesquisa de Clima" não encontrado para injetar a treta!');
            return;
        }

        // Mapeia os usuários criados no banco pelo nome para usarmos na árvore de respostas
        $users = User::pluck('id', 'name')->toArray();

        // O mock estruturado da discussão
        $mockComments = [
            [
                'author' => 'Ana Souza',
                'content' => 'Gostei da publicação!',
                'replies' => [
                    [
                        'author' => 'Bruno Reis',
                        'content' => 'Concordo com você, Ana! Conteúdo muito bom.',
                        'replies' => [
                            [
                                'author' => 'Ana Souza',
                                'content' => 'Você é um puxa saco! você só concorda porque gosta de mim!',
                                'replies' => []
                            ]
                        ]
                    ]
                ]
            ],
            [
                'author' => 'Carlos Lima',
                'content' => 'Ficou ótimo! Parabéns ao time de UI/UX.',
                'replies' => []
            ],
            [
                'author' => 'Valdir Fiscal do Óbvio',
                'content' => 'Postagem bonita, mas só pensa assim quem não é pai e quem não é mãe! Quem tem que acordar 5h pra fazer mamadeira sabe que essa Larovis não ajuda em nada no transporte da creche. Absurdo!',
                'replies' => [
                    [
                        'author' => 'Carlos Lima',
                        'content' => 'Seu Valdir, isso é só o feed da intranet de TI... o que tem a ver com a creche?',
                        'replies' => [
                            [
                                'author' => 'Valdir Fiscal do Óbvio',
                                'content' => 'É o que eu falei!! Falta de empatia com a família brasileira. Quando o RH fizer a Pesquisa de Clima eu vou expor tudo lá!',
                                'replies' => [
                                    [
                                        'author' => 'Carlos Lima',
                                        'content' => "Mas a pesquisa é sobre o clima organizacional de 2026, Seu Valdir, não sobre o governo ou transporte público kkkk relaxa",
                                        'replies' => [
                                            [
                                                'author' => 'Valdir Fiscal do Óbvio',
                                                'content' => "Ah pronto! Sabia que você ia defender esse absurdo, Carlos. Com esse papinho de 'clima organizacional' aposto que você é petista e apoia essa pouca vergonha de comunismo na nossa TI!! Só quem não tem filho aceita uma palhaçada dessas!!",
                                                'replies' => [
                                                    [
                                                        'author' => 'Lucas Dev Pipoca',
                                                        'content' => '🍿 Estou aqui só pelos commits e pela treta no feed. Alguém traz mais refri.',
                                                        'replies' => [
                                                            [
                                                                'author' => 'Sr. Moacyr Almoxarifado',
                                                                'content' => "Tsc tsc... Tá na minha época a gente não tinha essa frescura de 'feed' ou Larovis não. A gente batia ponto no cartão de papel, se reclamasse de comunismo o chefe mandava carregar caixa no sol quente e ninguém chorava por mamadeira! Essa geração de hoje tá perdida.",
                                                                'replies' => [
                                                                    [
                                                                        'author' => 'Valdir Fiscal do Óbvio',
                                                                        'content' => 'Falou tudo, Moacyr! Mas o Carlos ali acha bonito essa palhaçada que o RH inventou. Certeza que o layout desse sistema foi feito por comunista para confundir o trabalhador cristão!',
                                                                        'replies' => []
                                                                    ]
                                                                ]
                                                            ]
                                                        ]
                                                    ]
                                                ]
                                            ]
                                        ]
                                    ]
                                ]
                            ]
                        ]
                    ]
                ]
            ]
        ];

        // Injeta os dados recursivamente apenas no post do clima
        foreach ($mockComments as $commentData) {
            $this->createCommentRecursively($postClima->id, null, $commentData, $users);
        }

        $this->command->info('A treta da Pesquisa de Clima foi semeada com sucesso!');
    }

    private function createCommentRecursively(int $postId, ?int $parentId, array $commentData, array $users): void
    {
        $authorName = $commentData['author'];
        $userId = $users[$authorName] ?? reset($users);

        $comment = PostComment::create([
            'post_id'   => $postId,
            'parent_id' => $parentId,
            'user_id'   => $userId,
            'comment'   => $commentData['content']
        ]);

        if (!empty($commentData['replies'])) {
            foreach ($commentData['replies'] as $replyData) {
                $this->createCommentRecursively($postId, $comment->id, $replyData, $users);
            }
        }
    }
}

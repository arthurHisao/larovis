<?php

namespace Database\Seeders;

use App\Models\Post;
use App\Models\PostComment;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Faker\Factory as Faker;

class PostCommentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $faker = Faker::create('pt_BR');

        $userIds = User::pluck('id')->toArray();
        $postIds = Post::pluck('id')->toArray();

        if (empty($userIds) || empty($postIds)) {
            $this->command->error('Nenhum usuário encontrado! Cadastre pelo menos um usuário.');
            return;
        }


        foreach ($postIds as $postId) {
            for ($i = 0; $i < 2; $i++) {
                $mainComments = PostComment::create([
                    'post_id'   => $postId,
                    'parent_id' => null, // Comentário de nível superior
                    'user_id'   => $faker->randomElement($userIds),
                    'comment'   => $faker->sentence(10)
                ]);


                for ($j = 0; $j < 2; $j++) {
                    PostComment::create([
                        'post_id'   => $postId,
                        'parent_id' => $mainComments->id,
                        'user_id'   => $faker->randomElement($userIds),
                        'comment'   => $faker->sentence(6)
                    ]);
                }
            }
        }

        $this->command->info('Comentários e responstas vínculados aos posts existentes com sucesso!');
        
    }
}

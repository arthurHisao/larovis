<?php

namespace Database\Seeders;

use App\Models\Post;
use App\Models\PostImage;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

class PostImageSeeder extends Seeder
{
    public function run(): void
    {
        $posts = Post::all();

        if ($posts->isEmpty()) {
            $this->command->warn('Nenhum post encontrado.');
            return;
        }

        // Imagens aleatórias do Unsplash
        $images = [
            'https://images.unsplash.com/photo-1556761175-b413da4baf72?w=1200',
            'https://images.unsplash.com/photo-1521737711867-e3b97375f902?w=1200',
            'https://images.unsplash.com/photo-1497366754035-f200968a6e72?w=1200',
            'https://images.unsplash.com/photo-1497366811353-6870744d04b2?w=1200',
        ];

        // Seleciona apenas o segundo post para receber imagens.
        $post = $posts->skip(1)->first();

        if (!$post) {
            $this->command->warn('Não existe um segundo post para adicionar imagens.');
            return;
        }

        // Evita duplicar imagens caso o seeder seja executado novamente.
        if ($post->images()->exists()) {
            $this->command->info(
                "O post #{$post->id} já possui imagens. Nenhuma imagem foi adicionada."
            );

            return;
        }

        foreach ($images as $index => $url) {
            $response = Http::get($url);

            if (!$response->successful()) {
                $this->command->error("Não foi possível baixar a imagem: {$url}");
                continue;
            }

            $path = "posts/seed/post-{$post->id}-{$index}.jpg";

            Storage::disk('public')->put(
                $path,
                $response->body()
            );

            PostImage::create([
                'post_id' => $post->id,
                'path' => $path,
                'order' => $index,
            ]);
        }

        $this->command->info(
            "Imagens adicionadas ao post #{$post->id}."
        );
    }
}

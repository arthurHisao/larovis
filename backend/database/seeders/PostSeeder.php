<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Post;
use App\Models\Department;
use Illuminate\Database\Seeder;

class PostSeeder extends Seeder
{
    public function run(): void
    {
        // Pega os usuários criados no passo anterior
        $userRh = User::where('email', 'mariana.rh@larovis.com')->first();
        $userComercial = User::where('email', 'lucas.comercial@larovis.com')->first();
        
        $rh = Department::where('name', 'Recursos Humanos')->first();
        $comercial = Department::where('name', 'Comercial & Vendas')->first();

        // Seus posts originais salvos perfeitamente
        Post::create([
            'user_id' => $userRh->id,
            'department_id' => $rh->id,
            'title' => 'Pesquisa de Clima',
            'content' => 'Lembrete: A pesquisa de clima organizacional 2026 termina nesta sexta-feira! A sua opinião é fundamental para melhorarmos nosso ambiente de trabalho. Acesse o link enviado por e-mail.',
            'type' => 'official',
        ]);

        Post::create([
            'user_id' => $userComercial->id,
            'department_id' => $comercial->id,
            'title' => 'Meta Batida!',
            'content' => 'Fechamos o mês com 115% da meta batida! Parabéns a todo o time de Vendas pelo empenho e dedicação extraordinários neste trimestre! 🚀🔥',
            'type' => 'casual',
        ]);
    }
}

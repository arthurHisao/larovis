<?php
namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Message;
use Carbon\Carbon;

class ChatSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Buscar os usuários existentes criados pelo seu UserSeeder através do e-mail
        $authUser = User::where('email', 'dev@larovis.com')->first(); // Arthur (Usuário Logado)
        $ana = User::where('email', 'ana@larovis.com')->first();
        $carlos = User::where('email', 'carlos@larovis.com')->first();

        // Validação de segurança caso o UserSeeder não tenha sido rodado antes
        if (!$authUser || !$ana || !$carlos) {
            $this->command->error('Usuários do UserSeeder não foram encontrados. Certifique-se de rodar o UserSeeder primeiro!');
            return;
        }

        // 2. Criar o histórico de Mensagens usando os relacionamentos dinâmicos do banco
        
        // Conversa 1 (Com a Ana Souza)
        $msg1 = Message::create([
            'sender_id' => $ana->id,
            'receiver_id' => $authUser->id,
            'text' => 'Oi! Consegue dar uma olhada nos protótipos do Figma?',
            'created_at' => Carbon::today()->setTime(14, 20),
        ]);

        Message::create([
            'sender_id' => $authUser->id,
            'receiver_id' => $ana->id,
            'text' => 'Opa, Ana! Olho sim, me manda o link.',
            'parent_id' => $msg1->id, // Referência de resposta (refer)
            'created_at' => Carbon::today()->setTime(14, 22),
        ]);

        // Conversa 2 (Com o Carlos Lima)
        Message::create([
            'sender_id' => $carlos->id,
            'receiver_id' => $authUser->id,
            'text' => 'A API de posts já está pronta!',
            'created_at' => Carbon::today()->setTime(10, 05),
        ]);

        $this->command->info('Mensagens do chat populadas com sucesso!');
    }
}

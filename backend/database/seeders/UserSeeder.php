<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Department;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $rh = Department::where('name', 'Recursos Humanos')->first();
        $comercial = Department::where('name', 'Comercial & Vendas')->first();
        
        // Criando novos setores caso não existam para o resto da empresa
        $ti = Department::firstOrCreate(['name' => 'Tecnologia da Informação'], ['slug' => 'ti']);
        $financeiro = Department::firstOrCreate(['name' => 'Financeiro'], ['slug' => 'financeiro']);
        $almoxarifado = Department::firstOrCreate(['name' => 'Almoxarifado'], ['slug' => 'almoxarifado']);

        // 1. Seus usuários originais com os avatares reais das fotos
        User::create([
            'name' => 'Mariana Silva',
            'email' => 'mariana.rh@larovis.com',
            'password' => Hash::make('12345678'),
            'avatar' => 'https://unsplash.com', // Moça de vermelho
            'department_id' => $rh->id,
        ]);

        User::create([
            'name' => 'Arthur',
            'email' => 'dev@larovis.com',
            'password' => Hash::make('12345678'),
            'avatar' => null,
            'department_id' => $ti->id,
        ]);

        User::create([
            'name' => 'Lucas Mendes',
            'email' => 'lucas.comercial@larovis.com',
            'password' => Hash::make('12345678'),
            'avatar' => 'https://picsum.photos' . rand(1, 1000), // Rapaz de branco
            'department_id' => $comercial->id,
        ]);

        // 2. Os personagens secundários que vão participar da discussão com seus avatares dedicados
        User::create([
            'name' => 'Ana Souza', 
            'email' => 'ana@larovis.com', 
            'password' => Hash::make('12345678'), 
            'avatar' => 'https://picsum.photos' . rand(1, 1000), // Avatar feminino casual (RH)
            'department_id' => $rh->id
        ]);

        User::create([
            'name' => 'Bruno Reis', 
            'email' => 'bruno@larovis.com', 
            'password' => Hash::make('12345678'), 
            'avatar' => 'https://picsum.photos' . rand(1, 1000), // Homem jovem de terno (Financeiro)
            'department_id' => $financeiro->id
        ]);

        User::create([
            'name' => 'Carlos Lima', 
            'email' => 'carlos@larovis.com', 
            'password' => Hash::make('12345678'), 
            'avatar' => 'https://picsum.photos' . rand(1, 1000), // Jovem sorrindo (TI que defende o sistema)
            'department_id' => $ti->id
        ]);

        User::create([
            'name' => 'Valdir Fiscal do Óbvio', 
            'email' => 'valdir@larovis.com', 
            'password' => Hash::make('12345678'), 
            'avatar' => 'https://picsum.photos' . rand(1, 1000), // Homem de óculos sério corporativo (O Valdir)
            'department_id' => $almoxarifado->id
        ]);

        User::create([
            'name' => 'Lucas Dev Pipoca', 
            'email' => 'lucas.pipoca@larovis.com', 
            'password' => Hash::make('12345678'), 
            'avatar' => 'https://picsum.photos' . rand(1, 1000), // Jovem de camisa descontraído (TI da treta)
            'department_id' => $ti->id
        ]);

        User::create([
            'name' => 'Sr. Moacyr Almoxarifado', 
            'email' => 'moacyr@larovis.com', 
            'password' => Hash::make('12345678'), 
            'avatar' => 'https://picsum.photos' . rand(1, 1000), // Senhor mais velho (Moacyr do cartão de papel)
            'department_id' => $almoxarifado->id
        ]);

    }
}

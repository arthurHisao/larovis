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
            'avatar' => 'https://images.unsplash.com/photo-1534528741775-53994a69daeb?w=200&h=200&fit=crop&crop=faces',
            'department_id' => $rh->id,
        ]);

        User::create([
            'name' => 'Arthur',
            'email' => 'dev@larovis.com',
            'password' => Hash::make('12345678'),
            'avatar' => 'https://images.unsplash.com/photo-1500648767791-00dcc994a43e?w=200&h=200&fit=crop&crop=faces',
            'department_id' => $ti->id,
        ]);

        User::create([
            'name' => 'Lucas Mendes',
            'email' => 'lucas.comercial@larovis.com',
            'password' => Hash::make('12345678'),
            'avatar' => 'https://images.unsplash.com/photo-1506794778202-cad84cf45f1d?w=200&h=200&fit=crop&crop=faces',
            'department_id' => $comercial->id,
        ]);

        // 2. Os personagens secundários que vão participar da discussão com seus avatares dedicados
        User::create([
            'name' => 'Ana Souza', 
            'email' => 'ana@larovis.com', 
            'password' => Hash::make('12345678'), 
            'avatar' => 'https://images.unsplash.com/photo-1494790108377-be9c29b29330?w=100', // Avatar feminino casual (RH)
            'department_id' => $rh->id
        ]);

        User::create([
            'name' => 'Bruno Reis', 
            'email' => 'bruno@larovis.com', 
            'password' => Hash::make('12345678'), 
            'avatar' => 'https://images.unsplash.com/photo-1507003211169-0a1dd7228f2d?w=200&h=200&fit=crop&crop=faces',
            'department_id' => $financeiro->id
        ]);

        User::create([
            'name' => 'Carlos Lima', 
            'email' => 'carlos@larovis.com', 
            'password' => Hash::make('12345678'), 
            'avatar' => 'https://images.unsplash.com/photo-1535713875002-d1d0cf377fde?w=200&h=200&fit=crop&crop=faces',
            'department_id' => $ti->id
        ]);

        User::create([
            'name' => 'Valdir Fiscal do Óbvio', 
            'email' => 'valdir@larovis.com', 
            'password' => Hash::make('12345678'), 
            'avatar' => asset('storage/avatars/valdir.jpg'), // Valdir, homem mais velho
            'department_id' => $almoxarifado->id
        ]);

        User::create([
            'name' => 'Lucas Dev Pipoca', 
            'email' => 'lucas.pipoca@larovis.com', 
            'password' => Hash::make('12345678'), 
            'avatar' => 'https://images.unsplash.com/photo-1527980965255-d3b416303d12?w=200&h=200&fit=crop&crop=faces',
            'department_id' => $ti->id
        ]);

        User::create([
            'name' => 'Sr. Moacyr Almoxarifado', 
            'email' => 'moacyr@larovis.com', 
            'password' => Hash::make('12345678'), 
            'avatar' => asset('storage/avatars/moacyr.jpg'), // Moacyr, homem mais velho
            'department_id' => $almoxarifado->id
        ]);

    }
}

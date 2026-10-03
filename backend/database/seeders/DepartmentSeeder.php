<?php

namespace Database\Seeders;

use App\Models\Department;
use Illuminate\Database\Seeder;

class DepartmentSeeder extends Seeder
{
    public function run(): void
    {
        Department::create(['name' => 'Tecnologia da Informação', 'slug' => 'ti']);
        Department::create(['name' => 'Recursos Humanos', 'slug' => 'rh']);
        Department::create(['name' => 'Financeiro', 'slug' => 'financeiro']);
        Department::create(['name' => 'Almoxarifado', 'slug' => 'almoxarifado']);
        Department::create(['name' => 'Comercial & Vendas', 'slug' => 'comercial']);
    }
}

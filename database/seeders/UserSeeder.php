<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Enums\UserRole;
use App\Models\User;


class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        //Crea por única vez el usuario con credenciales manualmente
        User::UpdateorCreate([
            'name' => 'soysuper',
            'email' => 'usuario@super.com',
            'role' => UserRole::Super->value,
            'password' => '123456',
            //'id' => 1,
        ]);
        User::factory(20)->create();
    }
}

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
            'name' => 'Soy Super',
            'email' => 'usuario@super.com',
            'email_verified_at' => now(),
            'role' => UserRole::Super->value,
            'password' => '123456',
        ]);
        User::UpdateorCreate([
            'name' => 'Admin PJ',
            'email' => 'admin@usuario.com',
            'email_verified_at' => now(),
            'role' => UserRole::Administrador->value,
            'password' => '123456',
        ]);
        User::UpdateorCreate([
            'name' => 'Editor FK',
            'email' => 'editor@usuario.com',
            'email_verified_at' => now(),
            'role' => UserRole::Editor->value,
            'password' => '123456',
        ]);
        User::UpdateorCreate([
            'name' => 'Publisher Adrian',
            'email' => 'publicador@usuario.com',
            'email_verified_at' => now(),
            'role' => UserRole::Publicador->value,
            'password' => '123456',
        ]);
        User::factory(16)->create();
    }
}

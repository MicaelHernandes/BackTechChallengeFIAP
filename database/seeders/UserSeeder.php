<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $users = [
            [
                'name'     => 'Administrador',
                'email'    => 'admin@oficina.com',
                'password' => Hash::make('password'),
                'role'     => UserRole::Admin,
            ],
            [
                'name'     => 'Atendente',
                'email'    => 'atendente@oficina.com',
                'password' => Hash::make('password'),
                'role'     => UserRole::Attendant,
            ],
            [
                'name'     => 'Mecânico',
                'email'    => 'mecanico@oficina.com',
                'password' => Hash::make('password'),
                'role'     => UserRole::Mechanic,
            ],
            [
                'name'     => 'Almoxarife',
                'email'    => 'almoxarife@oficina.com',
                'password' => Hash::make('password'),
                'role'     => UserRole::Storekeeper,
            ],
            [
                'name'     => 'Compras',
                'email'    => 'compras@oficina.com',
                'password' => Hash::make('password'),
                'role'     => UserRole::Purchasing,
            ],
        ];

        foreach ($users as $data) {
            User::updateOrCreate(['email' => $data['email']], $data);
        }
    }
}

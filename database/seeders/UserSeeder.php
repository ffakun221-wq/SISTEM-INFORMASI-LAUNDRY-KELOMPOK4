<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Customer;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        // Admin
        User::updateOrCreate(
            ['username' => 'admin'],
            [
                'name' => 'Administrator',
                'email' => 'admin@gmail.com',
                'password' => Hash::make('123123123'),
                'role' => 'admin',
            ]
        );

        // Customer / Pelanggan
        $pelanggan = User::updateOrCreate(
            ['username' => 'pelanggan'],
            [
                'name' => 'Pelanggan',
                'email' => 'pelanggan@gmail.com',
                'password' => Hash::make('123123123'),
                'role' => 'pelanggan',
            ]
        );

        Customer::updateOrCreate(
            ['user_id' => $pelanggan->id],
            [
                'phone' => '081234567890',
                'address' => 'Jl. Pelanggan',
            ]
        );
    }
}

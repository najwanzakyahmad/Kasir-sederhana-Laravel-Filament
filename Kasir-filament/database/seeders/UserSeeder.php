<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $users = [
            ['name' => 'Admin',  'email' => 'admin@example.com'],
            ['name' => 'Kasir 1','email' => 'kasir1@example.com'],
            ['name' => 'Kasir 2','email' => 'kasir2@example.com'],
            ['name' => 'Staff 1','email' => 'staff1@example.com'],
            ['name' => 'Staff 2','email' => 'staff2@example.com'],
        ];

        foreach ($users as $u) {
            User::firstOrCreate(
                ['email' => $u['email']],              // kunci unik
                ['name' => $u['name'], 'password' => '12345'] // akan di-hash oleh casts
            );
        }
    }
}

<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::create([
            'name' => 'مدير عام',
            'username' => 'admin',
            'email' => 'admin@storeee.com',
            'password' => Hash::make('admin123'),
        ]);

        $admin->assignRole('admin');
    }
}

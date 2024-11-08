<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        User::create([
            'full_name' => 'System Admin',
            'email' => 'admin@gmail.com',
            'user_type' => 'admin',
            'password' => Hash::make('Abcd@1234'),
            'phone_number' => '02332313132'
        ]);


        // User::create([
        //     'full_name' => 'Alex',
        //     'email' => 'abc@getnada.com',
        //     'password' => Hash::make('Abcd@1234'),
        //     'phone_number' => '02332313132'
        // ]);

        // User::create([
        //     'full_name' => 'John',
        //     'email' => 'xyz@getnada.com',
        //     'password' => Hash::make('Abcd@1234'),
        //     'phone_number' => '02554466132'
        // ]);
    }
}

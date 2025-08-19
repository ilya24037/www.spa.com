<?php

namespace Database\Seeders;

use App\Domain\User\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UsersSeeder extends Seeder
{
    public function run(): void
    {
        $users = [
            [
                'name' => 'Анна Петрова',
                'email' => 'anna@spa.test',
                'password' => Hash::make('password'),
                'phone' => '+7 (925) 123-45-67',
                'folder_name' => 'anna_petrova',
                'status' => 'active',
            ],
            [
                'name' => 'Мария Иванова',
                'email' => 'maria@spa.test',
                'password' => Hash::make('password'),
                'phone' => '+7 (926) 234-56-78',
                'folder_name' => 'maria_ivanova',
                'status' => 'active',
            ],
            [
                'name' => 'Елена Смирнова',
                'email' => 'elena@spa.test',
                'password' => Hash::make('password'),
                'phone' => '+7 (927) 345-67-89',
                'folder_name' => 'elena_smirnova',
                'status' => 'active',
            ],
            [
                'name' => 'Виктория Козлова',
                'email' => 'viktoria@spa.test',
                'password' => Hash::make('password'),
                'phone' => '+7 (928) 456-78-90',
                'folder_name' => 'viktoria_kozlova',
                'status' => 'active',
            ],
            [
                'name' => 'Наталья Новикова',
                'email' => 'natalia@spa.test',
                'password' => Hash::make('password'),
                'phone' => '+7 (929) 567-89-01',
                'folder_name' => 'natalia_novikova',
                'status' => 'active',
            ]
        ];

        foreach ($users as $userData) {
            User::create($userData);
        }
        
        echo "✅ Создано пользователей: " . count($users) . "\n";
    }
}
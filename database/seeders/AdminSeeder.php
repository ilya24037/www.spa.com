<?php

namespace Database\Seeders;

use App\Domain\User\Models\User;
use App\Enums\UserRole;
use App\Enums\UserStatus;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Создаем администратора
        User::firstOrCreate(
            ['email' => 'admin@spa.com'],
            [
                'name' => 'Administrator',
                'password' => Hash::make('admin123'),
                'role' => UserRole::ADMIN->value,
                'status' => UserStatus::ACTIVE->value,
                'email_verified_at' => now(),
            ]
        );

        // Создаем модератора для тестирования
        User::firstOrCreate(
            ['email' => 'moderator@spa.com'],
            [
                'name' => 'Moderator',
                'password' => Hash::make('moderator123'),
                'role' => UserRole::MODERATOR->value,
                'status' => UserStatus::ACTIVE->value,
                'email_verified_at' => now(),
            ]
        );

        $this->command->info('Admin users created successfully!');
        $this->command->info('Admin: admin@spa.com / admin123');
        $this->command->info('Moderator: moderator@spa.com / moderator123');
    }
}
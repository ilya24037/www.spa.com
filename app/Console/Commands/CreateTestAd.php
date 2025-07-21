<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Ad;
use App\Models\User;

class CreateTestAd extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'test:create-ad';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Создать тестовое объявление';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        // Найдем пользователя test@example.com или создадим его
        $user = User::where('email', 'test@example.com')->first();
        if (!$user) {
            $user = User::create([
                'name' => 'Тестовый пользователь',
                'email' => 'test@example.com',
                'password' => bcrypt('password'),
                'email_verified_at' => now(),
            ]);
        }

        // Удалим старые объявления этого пользователя, чтобы не было дубликатов
        Ad::where('user_id', $user->id)->delete();

        // Создаем активное объявление
        $activeAd = Ad::create([
            'user_id' => $user->id,
            'title' => 'Массаж релаксирующий',
            'specialty' => 'massage',
            'clients' => ['women', 'men'],
            'service_location' => ['master_home', 'client_home'],
            'work_format' => 'private_master',
            'service_provider' => ['individual'],
            'experience' => 'less_year',
            'description' => 'Предлагаю качественный релаксирующий массаж. Работаю как у себя, так и с выездом.',
            'price' => 3000,
            'price_unit' => 'service',
            'is_starting_price' => false,
            'address' => 'Москва, ул. Тверская, 10',
            'travel_area' => 'Центральный район',
            'phone' => '+7 (999) 123-45-67',
            'contact_method' => 'phone',
            'status' => 'active'
        ]);

        // Создаем черновик
        $draftAd = Ad::create([
            'user_id' => $user->id,
            'title' => 'Черновик массажа',
            'specialty' => 'massage',
            'status' => 'draft'
        ]);

        // Создаем архивное объявление
        $archivedAd = Ad::create([
            'user_id' => $user->id,
            'title' => 'Старое объявление',
            'specialty' => 'massage',
            'clients' => ['women'],
            'service_location' => ['master_home'],
            'work_format' => 'private_master',
            'experience' => 'more_5_years',
            'description' => 'Архивное объявление.',
            'price' => 2500,
            'price_unit' => 'service',
            'address' => 'Москва, ул. Арбат, 5',
            'phone' => '+7 (999) 987-65-43',
            'contact_method' => 'messages',
            'status' => 'archived'
        ]);

        // Создаем неактивное объявление (статус 'paused' - ждет действий)
        $inactiveAd = Ad::create([
            'user_id' => $user->id,
            'title' => 'Неактивное объявление',
            'specialty' => 'massage',
            'clients' => ['women'],
            'service_location' => ['master_home'],
            'work_format' => 'private_master',
            'experience' => '1_3_years',
            'description' => 'Неактивное объявление.',
            'price' => 2000,
            'price_unit' => 'service',
            'address' => 'Москва, ул. Новый Арбат, 15',
            'phone' => '+7 (999) 555-55-55',
            'contact_method' => 'messages',
            'status' => 'paused'
        ]);

        $this->info('Созданы тестовые объявления для test@example.com:');
        $this->line("- Активное: {$activeAd->title} (ID: {$activeAd->id})");
        $this->line("- Черновик: {$draftAd->title} (ID: {$draftAd->id})");
        $this->line("- Архивное: {$archivedAd->title} (ID: {$archivedAd->id})");
        $this->line("- Ждет действий: {$inactiveAd->title} (ID: {$inactiveAd->id})");
        $this->line("Пользователь: {$user->name} (ID: {$user->id})");
    }
}

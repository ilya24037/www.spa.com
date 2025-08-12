<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use App\Models\Ad;

class CreateTestAdForEdit extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'ads:create-test-for-edit';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Создает тестовое объявление для редактирования';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Создаем тестовое объявление для редактирования...');

        // Находим тестового пользователя
        $user = User::where('email', 'test@example.com')->first();
        
        if (!$user) {
            $this->error('Пользователь test@example.com не найден!');
            return 1;
        }

        // Создаем тестовое объявление
        $ad = Ad::create([
            'user_id' => $user->id,
            'title' => 'Массаж релаксирующий',
            'specialty' => 'relaxing',
            'description' => 'Профессиональный релаксирующий массаж для снятия напряжения и стресса. Опыт работы 5 лет. Использую только натуральные масла и профессиональные техники.',
            'price' => 2000,
            'price_unit' => 'session',
            'address' => 'Москва, ул. Тверская, 1',
            'phone' => '+7 (999) 123-45-67',
            'contact_method' => 'messages',
            'status' => 'active',
            'category' => 'massage',
            'clients' => json_encode(['men', 'women']),
            'service_location' => json_encode(['home', 'salon']),
            'work_format' => 'individual',
            'experience' => '3-5_years',
            'travel_area' => 'Москва',
            'is_starting_price' => false
        ]);

        $this->info("✅ Объявление создано с ID: {$ad->id}");
        $this->info("📝 Название: {$ad->title}");
        $this->info("🔗 URL для редактирования: /ads/{$ad->id}/edit");
        
        return 0;
    }
}

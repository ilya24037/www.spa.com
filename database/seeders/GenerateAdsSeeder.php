<?php

namespace Database\Seeders;

use App\Domain\Ad\Models\Ad;
use App\Domain\User\Models\User;
use Illuminate\Database\Seeder;

class GenerateAdsSeeder extends Seeder
{
    public function run(): void
    {
        // Получаем всех обычных пользователей (не админов)
        $users = User::where('role', '!=', 'admin')->get();

        if ($users->isEmpty()) {
            echo "⚠️ Нет обычных пользователей. Создаю объявления без распределения.\n";
            // Создаем 30 объявлений
            Ad::factory(30)->create([
                'status' => 'pending_moderation'
            ]);

            echo "✅ Создано 30 объявлений на модерации\n";
            return;
        }

        echo "📋 Найдено пользователей: {$users->count()}\n";

        $totalAds = 50; // Общее количество объявлений
        $adsPerUser = intval($totalAds / $users->count());
        $remainder = $totalAds % $users->count();

        foreach ($users as $index => $user) {
            // Распределяем объявления равномерно
            $count = $adsPerUser + ($index < $remainder ? 1 : 0);

            // Создаем объявления для пользователя
            for ($i = 0; $i < $count; $i++) {
                // 60% на модерации, 30% активных, 10% черновики
                $rand = rand(1, 10);
                if ($rand <= 6) {
                    $status = 'pending_moderation';
                } elseif ($rand <= 9) {
                    $status = 'active';
                } else {
                    $status = 'draft';
                }

                Ad::factory()->create([
                    'user_id' => $user->id,
                    'status' => $status,
                    'is_paid' => $status === 'active' ? true : false,
                    'paid_at' => $status === 'active' ? now() : null,
                ]);
            }

            echo "  ✅ {$user->name}: создано {$count} объявлений\n";
        }

        // Показываем статистику
        $stats = Ad::selectRaw('status, COUNT(*) as count')
            ->groupBy('status')
            ->pluck('count', 'status')
            ->toArray();

        echo "\n📊 Статистика объявлений:\n";
        foreach ($stats as $status => $count) {
            echo "  - {$status}: {$count}\n";
        }

        // Статистика по пользователям
        echo "\n📊 Объявлений на модерации по пользователям:\n";
        $pendingByUser = Ad::where('status', 'pending_moderation')
            ->selectRaw('user_id, COUNT(*) as count')
            ->groupBy('user_id')
            ->get();

        foreach ($pendingByUser as $stat) {
            $userName = User::find($stat->user_id)->name;
            echo "  - {$userName}: {$stat->count}\n";
        }
    }
}
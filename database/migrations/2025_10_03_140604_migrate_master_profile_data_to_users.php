<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Копируем данные из master_profiles в users
     */
    public function up(): void
    {
        // Проверяем существует ли таблица master_profiles
        if (!DB::getSchemaBuilder()->hasTable('master_profiles')) {
            echo "⚠️ Таблица master_profiles не найдена, пропускаем миграцию\n";
            return;
        }

        echo "📊 Начинаем копирование данных из master_profiles в users...\n";

        // Копируем данные порциями по 100 записей для производительности
        $totalProfiles = DB::table('master_profiles')->count();
        echo "   Найдено профилей: {$totalProfiles}\n";

        $updated = 0;

        DB::table('master_profiles')->orderBy('id')->chunk(100, function ($profiles) use (&$updated) {
            foreach ($profiles as $profile) {
                // Проверяем существует ли пользователь
                $userExists = DB::table('users')->where('id', $profile->user_id)->exists();

                if (!$userExists) {
                    echo "   ⚠️ Пользователь ID {$profile->user_id} не найден, пропускаем профиль ID {$profile->id}\n";
                    continue;
                }

                // Обновляем данные пользователя
                DB::table('users')
                    ->where('id', $profile->user_id)
                    ->update([
                        'slug' => $profile->slug ?? null,
                        'rating' => $profile->rating ?? 0,
                        'reviews_count' => $profile->reviews_count ?? 0,
                        'views_count' => $profile->views_count ?? 0,
                        'is_verified' => $profile->is_verified ?? false,
                        'updated_at' => now(),
                    ]);

                $updated++;
            }
        });

        echo "   ✅ Обновлено пользователей: {$updated}\n";

        // Создаём slug для пользователей без slug (если не было в master_profiles)
        $usersWithoutSlug = DB::table('users')->whereNull('slug')->get();
        $slugsCreated = 0;

        foreach ($usersWithoutSlug as $user) {
            $slug = \Illuminate\Support\Str::slug($user->name) . '-' . $user->id;

            DB::table('users')
                ->where('id', $user->id)
                ->update(['slug' => $slug]);

            $slugsCreated++;
        }

        if ($slugsCreated > 0) {
            echo "   ✅ Создано slug для пользователей без профиля: {$slugsCreated}\n";
        }

        echo "✅ Миграция данных завершена!\n";
    }

    /**
     * Reverse the migrations.
     * Откат данных невозможен без потери информации
     */
    public function down(): void
    {
        echo "⚠️ Откат миграции данных не поддерживается.\n";
        echo "   Используйте Git backup или SQL dump для восстановления.\n";

        // Можем обнулить поля, но это опасно
        // DB::table('users')->update([
        //     'slug' => null,
        //     'rating' => 0,
        //     'reviews_count' => 0,
        //     'views_count' => 0,
        //     'is_verified' => false,
        // ]);
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Миграция для переноса outcall полей из prices в geo
     * 
     * ПРОБЛЕМА: Места выезда (outcall_apartment, outcall_hotel и т.д.) логически
     * относятся к географическим данным, но исторически хранились в поле prices.
     * 
     * РЕШЕНИЕ: Переносим эти поля из prices в geo для правильной архитектуры.
     */
    public function up(): void
    {
        // Получаем все записи с непустым полем prices
        $ads = DB::table('ads')
            ->whereNotNull('prices')
            ->get();
        
        foreach ($ads as $ad) {
            // Декодируем JSON поля
            $prices = json_decode($ad->prices, true);
            $geo = json_decode($ad->geo, true);
            
            // Проверяем что prices это массив
            if (!is_array($prices)) {
                continue;
            }
            
            // Если geo не массив или null - создаём пустой массив
            if (!is_array($geo)) {
                $geo = [];
            }
            
            // Поля для переноса из prices в geo
            $fieldsToMigrate = [
                'outcall_apartment',
                'outcall_hotel', 
                'outcall_house',
                'outcall_sauna',
                'outcall_office',
                'taxi_included'
            ];
            
            $updated = false;
            
            // Переносим поля из prices в geo
            foreach ($fieldsToMigrate as $field) {
                if (isset($prices[$field])) {
                    // Переносим значение в geo
                    $geo[$field] = $prices[$field];
                    // Удаляем из prices
                    unset($prices[$field]);
                    $updated = true;
                }
            }
            
            // Если были изменения, обновляем запись
            if ($updated) {
                DB::table('ads')
                    ->where('id', $ad->id)
                    ->update([
                        'prices' => json_encode($prices),
                        'geo' => json_encode($geo),
                        'updated_at' => now()
                    ]);
                    
                echo "✅ Migrated outcall fields for ad ID {$ad->id}\n";
            }
        }
        
        echo "🎉 Migration completed! Outcall fields moved from prices to geo.\n";
    }

    /**
     * Откат миграции - возвращаем поля обратно в prices
     */
    public function down(): void
    {
        // Получаем все записи с непустым полем geo
        $ads = DB::table('ads')
            ->whereNotNull('geo')
            ->get();
        
        foreach ($ads as $ad) {
            // Декодируем JSON поля
            $prices = json_decode($ad->prices, true) ?: [];
            $geo = json_decode($ad->geo, true);
            
            if (!is_array($geo)) {
                continue;
            }
            
            // Поля для переноса обратно из geo в prices
            $fieldsToMigrate = [
                'outcall_apartment',
                'outcall_hotel',
                'outcall_house',
                'outcall_sauna', 
                'outcall_office',
                'taxi_included'
            ];
            
            $updated = false;
            
            // Переносим поля обратно из geo в prices
            foreach ($fieldsToMigrate as $field) {
                if (isset($geo[$field])) {
                    // Переносим значение в prices
                    $prices[$field] = $geo[$field];
                    // Удаляем из geo
                    unset($geo[$field]);
                    $updated = true;
                }
            }
            
            // Если были изменения, обновляем запись
            if ($updated) {
                DB::table('ads')
                    ->where('id', $ad->id)
                    ->update([
                        'prices' => json_encode($prices),
                        'geo' => json_encode($geo),
                        'updated_at' => now()
                    ]);
                    
                echo "↩️ Rolled back outcall fields for ad ID {$ad->id}\n";
            }
        }
        
        echo "↩️ Rollback completed! Outcall fields moved back to prices.\n";
    }
};

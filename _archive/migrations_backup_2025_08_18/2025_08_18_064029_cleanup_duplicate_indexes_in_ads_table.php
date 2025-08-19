<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Очистка дублирующих индексов в таблице ads
     * 
     * Проблема: Найдены дублирующие индексы созданные разными миграциями:
     * - status индекс создавался трижды 
     * - user_id+status индекс создавался дважды
     */
    public function up(): void
    {
        Schema::table('ads', function (Blueprint $table) {
            // Сначала проверяем и удаляем старые дублирующие индексы
            $indexes = $this->getExistingIndexes();
            
            // Удаляем старые дублирующие индексы если они существуют
            foreach ($indexes as $index) {
                if (in_array($index, [
                    'ads_status_index',           // Из 2025_07_23_142115
                    'ads_user_status_index',      // Из 2025_07_23_142115  
                    'ads_user_status_created_index', // Из 2025_07_23_142115
                    'idx_status',                 // Из 2025_08_17_122007
                    'idx_user_status',            // Из 2025_08_17_122007
                    'idx_created',                // Из 2025_08_17_122007
                    'idx_status_expires',         // Из 2025_08_17_122007
                ])) {
                    try {
                        $table->dropIndex($index);
                        echo "✅ Удален дублирующий индекс: {$index}\n";
                    } catch (\Exception $e) {
                        echo "⚠️ Индекс {$index} не найден или уже удален\n";
                    }
                }
            }
        });
        
        // Создаем оптимальный набор индексов
        Schema::table('ads', function (Blueprint $table) {
            // Только необходимые оптимизированные индексы
            if (!$this->indexExists('ads_user_status_optimized')) {
                $table->index(['user_id', 'status'], 'ads_user_status_optimized');
                echo "✅ Создан оптимизированный индекс: ads_user_status_optimized\n";
            }
            
            if (!$this->indexExists('ads_status_expires_optimized')) {
                $table->index(['status', 'expires_at'], 'ads_status_expires_optimized');
                echo "✅ Создан оптимизированный индекс: ads_status_expires_optimized\n";
            }
            
            if (!$this->indexExists('ads_created_at_optimized')) {
                $table->index('created_at', 'ads_created_at_optimized');
                echo "✅ Создан оптимизированный индекс: ads_created_at_optimized\n";
            }
        });
        
        echo "\n🎯 Очистка дублирующих индексов завершена!\n";
        echo "Оставлены только 3 оптимизированных индекса вместо 7+ дублирующих\n";
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ads', function (Blueprint $table) {
            // Удаляем созданные оптимизированные индексы
            try {
                $table->dropIndex('ads_user_status_optimized');
                $table->dropIndex('ads_status_expires_optimized'); 
                $table->dropIndex('ads_created_at_optimized');
            } catch (\Exception $e) {
                // Индексы могут не существовать
            }
        });
        
        // Восстанавливать старые дублирующие индексы не будем - они были проблемой
        echo "⚠️ Старые дублирующие индексы НЕ восстановлены намеренно\n";
    }
    
    /**
     * Получить список существующих индексов
     */
    private function getExistingIndexes(): array
    {
        $indexes = [];
        try {
            $result = DB::select("SHOW INDEX FROM ads");
            foreach ($result as $index) {
                if ($index->Key_name !== 'PRIMARY') {
                    $indexes[] = $index->Key_name;
                }
            }
        } catch (\Exception $e) {
            // В случае ошибки возвращаем пустой массив
        }
        
        return array_unique($indexes);
    }
    
    /**
     * Проверить существование индекса
     */
    private function indexExists(string $indexName): bool
    {
        try {
            $result = DB::select("SHOW INDEX FROM ads WHERE Key_name = ?", [$indexName]);
            return !empty($result);
        } catch (\Exception $e) {
            return false;
        }
    }
};

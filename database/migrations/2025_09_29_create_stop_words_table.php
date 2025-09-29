<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('stop_words', function (Blueprint $table) {
            $table->id();
            $table->string('word')->unique()->index();
            $table->string('category', 50)->default('general');
            $table->integer('weight')->default(5);
            $table->enum('severity', ['low', 'medium', 'high', 'critical'])->default('medium');
            $table->enum('context', ['all', 'ads', 'reviews', 'messages'])->default('all');
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true)->index();
            $table->boolean('is_regex')->default(false);
            $table->integer('hits_count')->default(0);
            $table->integer('false_positives')->default(0);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            
            // Индексы для быстрого поиска
            $table->index(['is_active', 'context']);
            $table->index(['category', 'is_active']);
        });

        // Добавим начальные данные
        $this->seedInitialStopWords();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stop_words');
    }

    /**
     * Заполнить начальными стоп-словами
     */
    private function seedInitialStopWords(): void
    {
        $stopWords = [
            // Критические (сразу блокировать)
            ['word' => 'наркотики', 'category' => 'illegal', 'weight' => 10, 'severity' => 'critical'],
            ['word' => 'оружие', 'category' => 'illegal', 'weight' => 10, 'severity' => 'critical'],
            ['word' => 'взрывчатка', 'category' => 'illegal', 'weight' => 10, 'severity' => 'critical'],
            
            // Интим-услуги (для массажного сайта важно)
            ['word' => 'проституция', 'category' => 'adult', 'weight' => 8, 'severity' => 'high'],
            ['word' => 'интим', 'category' => 'adult', 'weight' => 8, 'severity' => 'high'],
            ['word' => 'секс', 'category' => 'adult', 'weight' => 8, 'severity' => 'high'],
            ['word' => 'эскорт', 'category' => 'adult', 'weight' => 8, 'severity' => 'high'],
            ['word' => 'релакс', 'category' => 'adult', 'weight' => 6, 'severity' => 'medium'],
            ['word' => 'досуг', 'category' => 'adult', 'weight' => 6, 'severity' => 'medium'],
            ['word' => 'приватный массаж', 'category' => 'adult', 'weight' => 7, 'severity' => 'high'],
            ['word' => 'массаж для мужчин', 'category' => 'adult', 'weight' => 5, 'severity' => 'medium'],
            ['word' => 'тантра', 'category' => 'adult', 'weight' => 5, 'severity' => 'medium'],
            ['word' => 'боди массаж', 'category' => 'adult', 'weight' => 5, 'severity' => 'medium'],
            
            // Медицинские манипуляции без лицензии
            ['word' => 'уколы', 'category' => 'medical', 'weight' => 7, 'severity' => 'high'],
            ['word' => 'инъекции', 'category' => 'medical', 'weight' => 7, 'severity' => 'high'],
            ['word' => 'капельницы', 'category' => 'medical', 'weight' => 7, 'severity' => 'high'],
            ['word' => 'лечение', 'category' => 'medical', 'weight' => 6, 'severity' => 'medium'],
            
            // Финансовые махинации
            ['word' => 'кредит', 'category' => 'financial', 'weight' => 4, 'severity' => 'low'],
            ['word' => 'займ', 'category' => 'financial', 'weight' => 4, 'severity' => 'low'],
            ['word' => 'криптовалюта', 'category' => 'financial', 'weight' => 4, 'severity' => 'low'],
            ['word' => 'bitcoin', 'category' => 'financial', 'weight' => 4, 'severity' => 'low'],
            ['word' => 'заработок', 'category' => 'financial', 'weight' => 5, 'severity' => 'medium'],
            ['word' => 'работа на дому', 'category' => 'financial', 'weight' => 5, 'severity' => 'medium'],
            ['word' => 'быстрые деньги', 'category' => 'financial', 'weight' => 6, 'severity' => 'medium'],
            
            // Мошенничество
            ['word' => 'без предоплаты', 'category' => 'scam', 'weight' => 3, 'severity' => 'low'],
            ['word' => 'гарантия 100%', 'category' => 'scam', 'weight' => 3, 'severity' => 'low'],
            ['word' => 'только сегодня', 'category' => 'scam', 'weight' => 3, 'severity' => 'low'],
            ['word' => 'документы', 'category' => 'scam', 'weight' => 5, 'severity' => 'medium'],
            ['word' => 'поддельные', 'category' => 'scam', 'weight' => 8, 'severity' => 'high'],
            
            // Оскорбления
            ['word' => 'дурак', 'category' => 'offensive', 'weight' => 2, 'severity' => 'low'],
            ['word' => 'идиот', 'category' => 'offensive', 'weight' => 2, 'severity' => 'low'],
            ['word' => 'мошенник', 'category' => 'offensive', 'weight' => 4, 'severity' => 'medium'],
            ['word' => 'кидала', 'category' => 'offensive', 'weight' => 4, 'severity' => 'medium'],
            ['word' => 'обманщик', 'category' => 'offensive', 'weight' => 4, 'severity' => 'medium'],
            
            // Спам
            ['word' => 'спам', 'category' => 'spam', 'weight' => 3, 'severity' => 'low'],
            ['word' => 'развод', 'category' => 'spam', 'weight' => 3, 'severity' => 'low'],
            ['word' => 'обман', 'category' => 'spam', 'weight' => 3, 'severity' => 'low'],
        ];

        foreach ($stopWords as $word) {
            DB::table('stop_words')->insert(array_merge($word, [
                'context' => 'all',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]));
        }
    }
};
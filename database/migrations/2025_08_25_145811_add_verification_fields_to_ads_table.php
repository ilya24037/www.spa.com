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
        Schema::table('ads', function (Blueprint $table) {
            // Поля для проверочного фото/видео
            $table->string('verification_photo')->nullable()->after('photos');
            $table->string('verification_video')->nullable()->after('verification_photo');
            
            // Статус верификации
            $table->enum('verification_status', ['none', 'pending', 'verified', 'rejected'])
                ->default('none')
                ->after('verification_video');
            
            // Тип верификации
            $table->enum('verification_type', ['photo', 'video', 'both'])
                ->nullable()
                ->after('verification_status');
            
            // Временные метки
            $table->timestamp('verified_at')->nullable()->after('verification_type');
            $table->timestamp('verification_expires_at')->nullable()->after('verified_at');
            
            // Комментарий модератора
            $table->text('verification_comment')->nullable()->after('verification_expires_at');
            
            // Метаданные (дополнительная информация)
            $table->json('verification_metadata')->nullable()->after('verification_comment');
            
            // Индексы для быстрого поиска
            $table->index('verification_status');
            $table->index('verification_expires_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ads', function (Blueprint $table) {
            $table->dropIndex(['verification_status']);
            $table->dropIndex(['verification_expires_at']);
            
            $table->dropColumn([
                'verification_photo',
                'verification_video',
                'verification_status',
                'verification_type',
                'verified_at',
                'verification_expires_at',
                'verification_comment',
                'verification_metadata'
            ]);
        });
    }
};

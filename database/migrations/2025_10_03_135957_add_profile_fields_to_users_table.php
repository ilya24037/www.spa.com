<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Добавляем поля из MasterProfile в users для объединения профилей
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Проверяем существование колонки перед добавлением
            if (!Schema::hasColumn('users', 'slug')) {
                $table->string('slug')->unique()->nullable()->after('name');
                $table->index('slug');
            }

            if (!Schema::hasColumn('users', 'rating')) {
                $table->decimal('rating', 3, 2)->default(0)->after('status');
                $table->index('rating');
            }

            if (!Schema::hasColumn('users', 'reviews_count')) {
                $table->unsignedInteger('reviews_count')->default(0)->after('rating');
            }

            if (!Schema::hasColumn('users', 'views_count')) {
                $table->unsignedInteger('views_count')->default(0)->after('reviews_count');
            }

            if (!Schema::hasColumn('users', 'is_verified')) {
                $table->boolean('is_verified')->default(false)->after('email_verified_at');
                $table->index('is_verified');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Удаляем индексы
            $table->dropIndex(['slug']);
            $table->dropIndex(['rating']);
            $table->dropIndex(['is_verified']);

            // Удаляем колонки
            $table->dropColumn([
                'slug',
                'rating',
                'reviews_count',
                'views_count',
                'is_verified',
            ]);
        });
    }
};

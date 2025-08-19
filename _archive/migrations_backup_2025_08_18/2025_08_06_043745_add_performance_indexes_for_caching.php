<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Индексы для таблицы ads
        Schema::table('ads', function (Blueprint $table) {
            // Проверяем и создаем индексы только если их нет
            if (!$this->indexExists('ads', 'idx_ads_user_status_created')) {
                $table->index(['user_id', 'status', 'created_at'], 'idx_ads_user_status_created');
            }
            
            if (!$this->indexExists('ads', 'idx_ads_category_status')) {
                $table->index(['category', 'status'], 'idx_ads_category_status');
            }
            
            if (!$this->indexExists('ads', 'idx_ads_status_created')) {
                $table->index(['status', 'created_at'], 'idx_ads_status_created');
            }
            
            if (!$this->indexExists('ads', 'idx_ads_active')) {
                $table->index(['status', 'expires_at'], 'idx_ads_active');
            }
        });

        // Индексы для таблицы master_profiles (только если таблица существует)
        if (Schema::hasTable('master_profiles')) {
            Schema::table('master_profiles', function (Blueprint $table) {
                if (!$this->indexExists('master_profiles', 'idx_masters_user_status')) {
                    $table->index(['user_id', 'status'], 'idx_masters_user_status');
                }
                
                if (!$this->indexExists('master_profiles', 'idx_masters_status_flags')) {
                    $table->index(['status', 'is_premium', 'is_verified'], 'idx_masters_status_flags');
                }
                
                if (!$this->indexExists('master_profiles', 'idx_masters_rating_status')) {
                    $table->index(['rating', 'status'], 'idx_masters_rating_status');
                }
                
                if (!$this->indexExists('master_profiles', 'idx_masters_created_status')) {
                    $table->index(['created_at', 'status'], 'idx_masters_created_status');
                }
            });
        }

        // Индексы для таблицы bookings
        Schema::table('bookings', function (Blueprint $table) {
            if (!$this->indexExists('bookings', 'idx_bookings_client_status')) {
                $table->index(['client_id', 'status'], 'idx_bookings_client_status');
            }
            
            if (!$this->indexExists('bookings', 'idx_bookings_master_profile_status')) {
                $table->index(['master_profile_id', 'status'], 'idx_bookings_master_profile_status');
            }
            
            if (!$this->indexExists('bookings', 'idx_bookings_date_status')) {
                $table->index(['booking_date', 'status'], 'idx_bookings_date_status');
            }
            
            if (!$this->indexExists('bookings', 'idx_bookings_status_created')) {
                $table->index(['status', 'created_at'], 'idx_bookings_status_created');
            }
        });

        // Индексы для таблицы reviews
        Schema::table('reviews', function (Blueprint $table) {
            if (!$this->indexExists('reviews', 'idx_reviews_client_created')) {
                $table->index(['client_id', 'created_at'], 'idx_reviews_client_created');
            }
            
            if (!$this->indexExists('reviews', 'idx_reviews_master_created')) {
                $table->index(['master_profile_id', 'created_at'], 'idx_reviews_master_created');
            }
            
            if (!$this->indexExists('reviews', 'idx_reviews_rating_created')) {
                $table->index(['rating_overall', 'created_at'], 'idx_reviews_rating_created');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Удаление индексов для ads
        Schema::table('ads', function (Blueprint $table) {
            if ($this->indexExists('ads', 'idx_ads_user_status_created')) {
                $table->dropIndex('idx_ads_user_status_created');
            }
            if ($this->indexExists('ads', 'idx_ads_category_status')) {
                $table->dropIndex('idx_ads_category_status');
            }
            if ($this->indexExists('ads', 'idx_ads_status_created')) {
                $table->dropIndex('idx_ads_status_created');
            }
            if ($this->indexExists('ads', 'idx_ads_active')) {
                $table->dropIndex('idx_ads_active');
            }
        });

        // Удаление индексов для master_profiles
        Schema::table('master_profiles', function (Blueprint $table) {
            if ($this->indexExists('master_profiles', 'idx_masters_user_status')) {
                $table->dropIndex('idx_masters_user_status');
            }
            if ($this->indexExists('master_profiles', 'idx_masters_status_flags')) {
                $table->dropIndex('idx_masters_status_flags');
            }
            if ($this->indexExists('master_profiles', 'idx_masters_rating_status')) {
                $table->dropIndex('idx_masters_rating_status');
            }
            if ($this->indexExists('master_profiles', 'idx_masters_created_status')) {
                $table->dropIndex('idx_masters_created_status');
            }
        });

        // Удаление индексов для bookings
        Schema::table('bookings', function (Blueprint $table) {
            if ($this->indexExists('bookings', 'idx_bookings_client_status')) {
                $table->dropIndex('idx_bookings_client_status');
            }
            if ($this->indexExists('bookings', 'idx_bookings_master_profile_status')) {
                $table->dropIndex('idx_bookings_master_profile_status');
            }
            if ($this->indexExists('bookings', 'idx_bookings_date_status')) {
                $table->dropIndex('idx_bookings_date_status');
            }
            if ($this->indexExists('bookings', 'idx_bookings_status_created')) {
                $table->dropIndex('idx_bookings_status_created');
            }
        });

        // Удаление индексов для reviews
        Schema::table('reviews', function (Blueprint $table) {
            if ($this->indexExists('reviews', 'idx_reviews_client_created')) {
                $table->dropIndex('idx_reviews_client_created');
            }
            if ($this->indexExists('reviews', 'idx_reviews_master_created')) {
                $table->dropIndex('idx_reviews_master_created');
            }
            if ($this->indexExists('reviews', 'idx_reviews_rating_created')) {
                $table->dropIndex('idx_reviews_rating_created');
            }
        });
    }

    /**
     * Проверка существования индекса
     */
    private function indexExists($table, $name): bool
    {
        $indexes = DB::select("SHOW INDEXES FROM $table WHERE Key_name = ?", [$name]);
        return count($indexes) > 0;
    }
};
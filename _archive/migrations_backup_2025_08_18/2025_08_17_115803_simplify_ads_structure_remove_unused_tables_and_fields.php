<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Упрощаем структуру - удаляем неиспользуемые таблицы и поля
     */
    public function up(): void
    {
        // Удаляем неиспользуемые таблицы
        Schema::dropIfExists('ad_locations');
        Schema::dropIfExists('ad_pricing');
        Schema::dropIfExists('ad_media');
        
        // Удаляем дублирующие и неиспользуемые поля из таблицы ads
        Schema::table('ads', function (Blueprint $table) {
            $table->dropColumn([
                'pricing_data',      // дублирует prices
                'payment_methods',   // не используется
                'education_level',   // не используется
                'amenities',         // дублирует services
                'user_folder',       // не используется
                'media_paths',       // дублирует photos и video
                'taxi_option',       // перенести в services если нужно
            ]);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Восстанавливаем таблицы (только если их нет)
        if (!Schema::hasTable('ad_locations')) {
            Schema::create('ad_locations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ad_id')->constrained()->onDelete('cascade');
            $table->string('work_format')->nullable();
            $table->json('service_location')->nullable();
            $table->json('outcall_locations')->nullable();
            $table->boolean('taxi_option')->default(false);
            $table->string('address')->nullable();
            $table->string('travel_area')->nullable();
            $table->string('phone')->nullable();
            $table->string('contact_method')->nullable();
            $table->json('schedule')->nullable();
            $table->text('schedule_notes')->nullable();
            $table->timestamps();
            });
        }

        if (!Schema::hasTable('ad_pricing')) {
            Schema::create('ad_pricing', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ad_id')->constrained()->onDelete('cascade');
            $table->decimal('price', 10, 2)->nullable();
            $table->string('price_unit')->nullable();
            $table->boolean('is_starting_price')->default(false);
            $table->json('pricing_data')->nullable();
            $table->integer('discount')->nullable();
            $table->integer('new_client_discount')->nullable();
            $table->string('gift')->nullable();
            $table->boolean('prepayment_required')->default(false);
            $table->integer('prepayment_percent')->nullable();
            $table->integer('contacts_per_hour')->nullable();
            $table->timestamps();
            });
        }

        if (!Schema::hasTable('ad_media')) {
            Schema::create('ad_media', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ad_id')->constrained()->onDelete('cascade');
            $table->json('photos')->nullable();
            $table->json('video')->nullable();
            $table->boolean('show_photos_in_gallery')->default(true);
            $table->boolean('allow_download_photos')->default(false);
            $table->boolean('watermark_photos')->default(false);
            $table->timestamps();
            });
        }
        
        // Восстанавливаем поля в ads (только если таблица существует)
        if (Schema::hasTable('ads')) {
            Schema::table('ads', function (Blueprint $table) {
                $table->json('pricing_data')->nullable();
                $table->json('payment_methods')->nullable();
                $table->string('education_level', 10)->nullable();
                $table->json('amenities')->nullable();
                $table->string('user_folder')->nullable();
                $table->json('media_paths')->nullable();
                $table->string('taxi_option', 20)->nullable();
            });
        }
    }
};

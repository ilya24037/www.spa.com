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
        Schema::create('ad_pricing', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ad_id')->constrained()->onDelete('cascade');
            $table->decimal('price', 10, 2)->nullable();
            $table->string('price_unit')->nullable();
            $table->boolean('is_starting_price')->default(false);
            $table->json('pricing_data')->nullable();
            $table->integer('discount')->default(0);
            $table->integer('new_client_discount')->default(0);
            $table->string('gift')->nullable();
            $table->boolean('prepayment_required')->default(false);
            $table->integer('prepayment_percent')->default(0);
            $table->integer('contacts_per_hour')->nullable();
            $table->timestamps();
            
            $table->unique('ad_id');
            $table->index(['price', 'price_unit']);
        });

        // Перенос данных из основной таблицы
        DB::statement("
            INSERT INTO ad_pricing (ad_id, price, price_unit, is_starting_price, pricing_data, discount, new_client_discount, gift, contacts_per_hour, created_at, updated_at)
            SELECT id, price, price_unit, COALESCE(is_starting_price, 0), pricing_data, COALESCE(discount, 0), COALESCE(new_client_discount, 0), gift, contacts_per_hour, created_at, updated_at
            FROM ads
            WHERE price IS NOT NULL
        ");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Возврат данных в основную таблицу
        DB::statement("
            UPDATE ads a
            JOIN ad_pricing p ON a.id = p.ad_id
            SET a.price = p.price,
                a.price_unit = p.price_unit,
                a.is_starting_price = p.is_starting_price,
                a.pricing_data = p.pricing_data,
                a.discount = p.discount,
                a.new_client_discount = p.new_client_discount,
                a.gift = p.gift,
                a.contacts_per_hour = p.contacts_per_hour
        ");

        Schema::dropIfExists('ad_pricing');
    }
};
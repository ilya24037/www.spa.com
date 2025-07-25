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
        Schema::table('bookings', function (Blueprint $table) {
            // Проверяем и добавляем недостающие поля
            if (!Schema::hasColumn('bookings', 'duration')) {
                $table->integer('duration')->after('end_time')->comment('Длительность в минутах');
            }
            
            if (!Schema::hasColumn('bookings', 'is_home_service')) {
                $table->boolean('is_home_service')->default(false)->after('address_details')->comment('Выездная услуга');
            }
            
            if (!Schema::hasColumn('bookings', 'address')) {
                $table->string('address')->nullable()->after('end_time')->comment('Адрес оказания услуги');
            }
            
            if (!Schema::hasColumn('bookings', 'address_details')) {
                $table->string('address_details')->nullable()->after('address')->comment('Детали адреса');
            }
            
            if (!Schema::hasColumn('bookings', 'service_price')) {
                $table->decimal('service_price', 10, 2)->after('is_home_service')->comment('Стоимость услуги');
            }
            
            if (!Schema::hasColumn('bookings', 'travel_fee')) {
                $table->decimal('travel_fee', 10, 2)->default(0)->after('service_price')->comment('Стоимость выезда');
            }
            
            if (!Schema::hasColumn('bookings', 'discount_amount')) {
                $table->decimal('discount_amount', 10, 2)->default(0)->after('travel_fee')->comment('Размер скидки');
            }
            
            if (!Schema::hasColumn('bookings', 'total_price')) {
                $table->decimal('total_price', 10, 2)->after('discount_amount')->comment('Общая стоимость');
            }
            
            if (!Schema::hasColumn('bookings', 'client_name')) {
                $table->string('client_name')->after('status')->comment('Имя клиента');
            }
            
            if (!Schema::hasColumn('bookings', 'client_phone')) {
                $table->string('client_phone')->after('client_name')->comment('Телефон клиента');
            }
            
            if (!Schema::hasColumn('bookings', 'client_email')) {
                $table->string('client_email')->nullable()->after('client_phone')->comment('Email клиента');
            }
            
            if (!Schema::hasColumn('bookings', 'client_comment')) {
                $table->text('client_comment')->nullable()->after('client_email')->comment('Комментарий клиента');
            }
            
            if (!Schema::hasColumn('bookings', 'source')) {
                $table->string('source')->default('website')->after('client_comment')->comment('Источник бронирования');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $columns = [
                'duration', 'is_home_service', 'address', 'address_details',
                'service_price', 'travel_fee', 'discount_amount', 'total_price',
                'client_name', 'client_phone', 'client_email', 'client_comment', 'source'
            ];
            
            foreach ($columns as $column) {
                if (Schema::hasColumn('bookings', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
}; 
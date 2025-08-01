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
            $table->string('contact_method')->default('phone');
            $table->json('schedule')->nullable();
            $table->text('schedule_notes')->nullable();
            $table->timestamps();
            
            $table->unique('ad_id');
            $table->index('work_format');
            $table->index('phone');
        });

        // Перенос данных из основной таблицы
        DB::statement("
            INSERT INTO ad_locations (ad_id, work_format, service_location, outcall_locations, taxi_option, address, travel_area, phone, contact_method, schedule, schedule_notes, created_at, updated_at)
            SELECT id, work_format, service_location, outcall_locations, COALESCE(taxi_option, 0), address, travel_area, phone, COALESCE(contact_method, 'phone'), schedule, schedule_notes, created_at, updated_at
            FROM ads
            WHERE address IS NOT NULL OR phone IS NOT NULL
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
            JOIN ad_locations l ON a.id = l.ad_id
            SET a.work_format = l.work_format,
                a.service_location = l.service_location,
                a.outcall_locations = l.outcall_locations,
                a.taxi_option = l.taxi_option,
                a.address = l.address,
                a.travel_area = l.travel_area,
                a.phone = l.phone,
                a.contact_method = l.contact_method,
                a.schedule = l.schedule,
                a.schedule_notes = l.schedule_notes
        ");

        Schema::dropIfExists('ad_locations');
    }
};
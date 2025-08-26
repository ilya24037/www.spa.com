<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bookings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ad_id')->nullable()->constrained()->onDelete('set null');
            $table->foreignId('client_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('master_id')->constrained('users')->onDelete('cascade');
            
            // Время бронирования
            $table->date('booking_date');
            $table->time('start_time');
            $table->time('end_time');
            $table->integer('duration_minutes')->default(60);
            
            // Статус
            $table->enum('status', ['pending', 'confirmed', 'completed', 'cancelled'])->default('pending');
            $table->timestamp('confirmed_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            
            // Оплата
            $table->decimal('price', 10, 2)->nullable();
            $table->enum('payment_status', ['pending', 'paid', 'refunded'])->nullable();
            $table->unsignedBigInteger('payment_id')->nullable();
            
            // Дополнительная информация
            $table->text('client_message')->nullable();
            $table->text('master_notes')->nullable();
            $table->text('cancellation_reason')->nullable();
            
            // Напоминания
            $table->timestamp('reminder_sent_at')->nullable();
            
            $table->timestamps();
            $table->softDeletes();
            
            $table->index(['master_id', 'booking_date']);
            $table->index(['client_id', 'status']);
            $table->index('status');
            $table->index('booking_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bookings');
    }
};
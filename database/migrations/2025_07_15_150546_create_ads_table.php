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
        Schema::create('ads', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('title');
            $table->string('specialty');
            $table->json('clients')->nullable();
            $table->json('service_location');
            $table->string('work_format');
            $table->json('service_provider')->nullable();
            $table->string('experience');
            $table->text('description');
            $table->decimal('price', 10, 2);
            $table->string('price_unit');
            $table->boolean('is_starting_price')->default(false);
            $table->integer('discount')->nullable();
            $table->string('gift', 500)->nullable();
            $table->string('address', 500);
            $table->string('travel_area');
            $table->string('phone');
            $table->string('contact_method');
            $table->enum('status', ['draft', 'active', 'paused', 'archived'])->default('draft');
            $table->timestamps();
            
            $table->index(['user_id', 'status']);
            $table->index('specialty');
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ads');
    }
};

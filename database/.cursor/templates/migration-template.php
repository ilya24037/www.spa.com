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
        Schema::create('table_name', function (Blueprint $table) {
            // Primary key
            $table->id();
            
            // Basic fields
            $table->string('title');
            $table->text('description')->nullable();
            $table->decimal('price', 10, 2)->default(0.00);
            $table->boolean('is_active')->default(true);
            
            // Foreign keys
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('category_id')->constrained();
            
            // JSON fields
            $table->json('metadata')->nullable();
            $table->json('settings')->nullable();
            
            // Date fields
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->time('start_time')->nullable();
            $table->time('end_time')->nullable();
            
            // Timestamps
            $table->timestamps();
            $table->softDeletes();
            
            // Indexes
            $table->index(['user_id', 'is_active']);
            $table->index(['category_id', 'is_active']);
            $table->index('price');
            $table->index('start_date');
            $table->index('created_at');
            
            // Unique constraints
            $table->unique(['user_id', 'title']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('table_name');
    }
};

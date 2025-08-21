<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('master_profiles', function (Blueprint $table) {
            // Добавляем поле slug если его нет
            if (!Schema::hasColumn('master_profiles', 'slug')) {
                $table->string('slug')->nullable()->after('display_name');
                $table->index('slug');
            }
            
            // Добавляем другие поля, которые могут отсутствовать
            if (!Schema::hasColumn('master_profiles', 'bio')) {
                $table->text('bio')->nullable()->after('slug');
            }
            
            if (!Schema::hasColumn('master_profiles', 'avatar')) {
                $table->string('avatar')->nullable()->after('bio');
            }
            
            if (!Schema::hasColumn('master_profiles', 'phone')) {
                $table->string('phone')->nullable();
            }
            
            if (!Schema::hasColumn('master_profiles', 'whatsapp')) {
                $table->string('whatsapp')->nullable();
            }
            
            if (!Schema::hasColumn('master_profiles', 'telegram')) {
                $table->string('telegram')->nullable();
            }
            
            if (!Schema::hasColumn('master_profiles', 'show_contacts')) {
                $table->boolean('show_contacts')->default(false);
            }
            
            if (!Schema::hasColumn('master_profiles', 'experience_years')) {
                $table->integer('experience_years')->nullable();
            }
            
            if (!Schema::hasColumn('master_profiles', 'certificates')) {
                $table->json('certificates')->nullable();
            }
            
            if (!Schema::hasColumn('master_profiles', 'education')) {
                $table->json('education')->nullable();
            }
            
            if (!Schema::hasColumn('master_profiles', 'views_count')) {
                $table->integer('views_count')->default(0);
            }
            
            if (!Schema::hasColumn('master_profiles', 'status')) {
                $table->string('status')->default('active');
            }
            
            if (!Schema::hasColumn('master_profiles', 'meta_title')) {
                $table->string('meta_title')->nullable();
            }
            
            if (!Schema::hasColumn('master_profiles', 'meta_description')) {
                $table->text('meta_description')->nullable();
            }
            
            if (!Schema::hasColumn('master_profiles', 'features')) {
                $table->json('features')->nullable();
            }
            
            if (!Schema::hasColumn('master_profiles', 'medical_certificate')) {
                $table->boolean('medical_certificate')->default(false);
            }
            
            if (!Schema::hasColumn('master_profiles', 'works_during_period')) {
                $table->boolean('works_during_period')->default(false);
            }
            
            if (!Schema::hasColumn('master_profiles', 'additional_features')) {
                $table->text('additional_features')->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::table('master_profiles', function (Blueprint $table) {
            $table->dropColumn([
                'slug', 'bio', 'avatar', 'phone', 'whatsapp', 'telegram',
                'show_contacts', 'experience_years', 'certificates', 'education',
                'views_count', 'status', 'meta_title', 'meta_description',
                'features', 'medical_certificate', 'works_during_period', 'additional_features'
            ]);
        });
    }
};
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Добавление поля description для описания мастера
     */
    public function up(): void
    {
        if (!Schema::hasColumn('master_profiles', 'description')) {
            Schema::table('master_profiles', function (Blueprint $table) {
                $table->text('description')->nullable()->after('bio');
            });
        }
    }

    /**
     * Откатить миграцию
     */
    public function down(): void
    {
        Schema::table('master_profiles', function (Blueprint $table) {
            $table->dropColumn('description');
        });
    }
};

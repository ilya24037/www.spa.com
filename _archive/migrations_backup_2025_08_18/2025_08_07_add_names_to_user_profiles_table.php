<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('user_profiles')) {
            Schema::table('user_profiles', function (Blueprint $table) {
                if (!Schema::hasColumn('user_profiles', 'first_name')) {
                    $table->string('first_name')->nullable()->after('user_id');
                }
                if (!Schema::hasColumn('user_profiles', 'last_name')) {
                    $table->string('last_name')->nullable()->after('first_name');
                }
                if (!Schema::hasColumn('user_profiles', 'phone')) {
                    $table->string('phone')->nullable()->after('last_name');
                }
                if (!Schema::hasColumn('user_profiles', 'birth_date')) {
                    $table->date('birth_date')->nullable()->after('phone');
                }
                if (!Schema::hasColumn('user_profiles', 'gender')) {
                    $table->enum('gender', ['male', 'female', 'other'])->nullable()->after('birth_date');
                }
                if (!Schema::hasColumn('user_profiles', 'avatar')) {
                    $table->string('avatar')->nullable()->after('gender');
                }
                if (!Schema::hasColumn('user_profiles', 'bio')) {
                    $table->text('bio')->nullable()->after('avatar');
                }
            });
        }
    }

    public function down(): void
    {
        Schema::table('user_profiles', function (Blueprint $table) {
            $table->dropColumn(['first_name', 'last_name', 'phone', 'birth_date', 'gender', 'avatar', 'bio']);
        });
    }
};
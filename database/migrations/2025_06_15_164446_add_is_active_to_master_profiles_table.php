<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('master_profiles', function (Blueprint $table) {
            $table->boolean('is_active')->default(true)->after('rating');
            $table->index('is_active');
        });
    }

    public function down()
    {
        Schema::table('master_profiles', function (Blueprint $table) {
            $table->dropColumn('is_active');
        });
    }
};
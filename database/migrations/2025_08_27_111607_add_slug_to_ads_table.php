<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use App\Domain\Ad\Models\Ad;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('ads', function (Blueprint $table) {
            $table->string('slug')->nullable()->after('title');
            $table->index('slug');
        });
        
        // Генерируем slugs для существующих записей
        Ad::whereNull('slug')->orWhere('slug', '')->chunk(100, function ($ads) {
            foreach ($ads as $ad) {
                $ad->slug = Str::slug($ad->title ?: 'ad-' . $ad->id);
                $ad->save();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ads', function (Blueprint $table) {
            $table->dropIndex(['slug']);
            $table->dropColumn('slug');
        });
    }
};

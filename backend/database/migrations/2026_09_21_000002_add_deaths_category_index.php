<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('deaths', function (Blueprint $table) {
            $table->index(
                ['channel_id', 'game_id', 'category_type', 'category_value'],
                'deaths_channel_game_category_index',
            );
        });
    }

    public function down(): void
    {
        Schema::table('deaths', function (Blueprint $table) {
            $table->dropIndex('deaths_channel_game_category_index');
        });
    }
};

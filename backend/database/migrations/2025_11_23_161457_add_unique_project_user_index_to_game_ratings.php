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
        if (DB::getDriverName() !== 'sqlite') {
            Schema::table('game_ratings', function (Blueprint $table) {
                if (Schema::hasColumn('game_ratings','project_id')) {
                    $table->unique(['project_id','user_id'],'game_ratings_project_user_unique');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (DB::getDriverName() !== 'sqlite') {
            Schema::table('game_ratings', function (Blueprint $table) {
                $table->dropUnique('game_ratings_project_user_unique');
            });
        }
    }
};

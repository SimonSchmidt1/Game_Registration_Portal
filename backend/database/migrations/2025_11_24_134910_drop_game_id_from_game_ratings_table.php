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
        // Use raw SQL to avoid Laravel's foreign key detection issues
        DB::statement('ALTER TABLE game_ratings DROP FOREIGN KEY game_ratings_game_id_foreign');
        DB::statement('ALTER TABLE game_ratings DROP INDEX game_ratings_game_id_user_id_unique');
        DB::statement('ALTER TABLE game_ratings DROP COLUMN game_id');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('game_ratings', function (Blueprint $table) {
            // Re-add game_id column
            $table->foreignId('game_id')->nullable()->after('id')->constrained('games')->onDelete('cascade');
            
            // Re-add the unique constraint
            $table->unique(['game_id', 'user_id']);
        });
    }
};

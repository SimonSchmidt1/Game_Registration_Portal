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
        // Copy all games to projects table with type='game'
        // Copy games into projects (tolerate missing optional columns)
        DB::table('games')->orderBy('id')->chunk(100, function ($games) {
            foreach ($games as $game) {
                DB::table('projects')->insert([
                    'team_id' => $game->team_id,
                    'academic_year_id' => $game->academic_year_id,
                    'title' => $game->title,
                    'description' => $game->description,
                    'type' => 'game',
                    'category' => $game->category,
                    'release_date' => $game->release_date,
                    'splash_screen_path' => $game->splash_screen_path,
                    'video_path' => $game->trailer_path,
                    'video_url' => property_exists($game, 'trailer_url') ? $game->trailer_url : null,
                    'files' => json_encode([
                        'export' => $game->export_path ?? null,
                        'source_code' => $game->source_code_path ?? null,
                    ]),
                    'rating' => $game->rating ?? 0,
                    'rating_count' => $game->rating_count ?? 0,
                    'views' => $game->views ?? 0,
                    'metadata' => json_encode([]),
                    'created_at' => $game->created_at,
                    'updated_at' => $game->updated_at,
                ]);
            }
        });

        // If legacy 'ratings' table exists perform rename, else assume game_ratings migration handles project_id
        if (Schema::hasTable('ratings')) {
            try {
                Schema::table('ratings', function (Blueprint $table) {
                    if (Schema::hasColumn('ratings', 'game_id')) {
                        $table->dropForeign(['game_id']);
                        $table->renameColumn('game_id', 'project_id');
                    }
                });
                Schema::table('ratings', function (Blueprint $table) {
                    if (Schema::hasColumn('ratings', 'project_id')) {
                        $table->foreign('project_id')->references('id')->on('projects')->onDelete('cascade');
                    }
                });
            } catch (Exception $e) {
                // Silent fail if structure differs
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Rename back
        Schema::table('ratings', function (Blueprint $table) {
            $table->dropForeign(['project_id']);
            $table->renameColumn('project_id', 'game_id');
        });

        Schema::table('ratings', function (Blueprint $table) {
            $table->foreign('game_id')->references('id')->on('games')->onDelete('cascade');
        });

        // Clear projects
        DB::table('projects')->truncate();
    }
};

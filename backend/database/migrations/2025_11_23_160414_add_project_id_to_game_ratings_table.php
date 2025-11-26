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
        Schema::table('game_ratings', function (Blueprint $table) {
            if (!Schema::hasColumn('game_ratings', 'project_id')) {
                $table->unsignedBigInteger('project_id')->nullable()->after('game_id');
            }
        });

        // Backfill project_id using games->projects migration assumptions: matching by team_id & title & created_at
        $ratings = DB::table('game_ratings')->whereNull('project_id')->get();
        foreach ($ratings as $rating) {
            $game = DB::table('games')->where('id', $rating->game_id)->first();
            if ($game) {
                // Find corresponding project (type game, same team, title, created timestamps close)
                $project = DB::table('projects')
                    ->where('team_id', $game->team_id)
                    ->where('type', 'game')
                    ->where('title', $game->title)
                    ->orderBy('id')
                    ->first();
                if ($project) {
                    DB::table('game_ratings')
                        ->where('id', $rating->id)
                        ->update(['project_id' => $project->id]);
                }
            }
        }

        // Add foreign key constraint if not exists
        Schema::table('game_ratings', function (Blueprint $table) {
            if (!DB::select("SELECT * FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE WHERE TABLE_NAME = 'game_ratings' AND COLUMN_NAME = 'project_id'")) {
                $table->foreign('project_id')->references('id')->on('projects')->onDelete('cascade');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('game_ratings', function (Blueprint $table) {
            if (Schema::hasColumn('game_ratings', 'project_id')) {
                $table->dropForeign(['project_id']);
                $table->dropColumn('project_id');
            }
        });
    }
};

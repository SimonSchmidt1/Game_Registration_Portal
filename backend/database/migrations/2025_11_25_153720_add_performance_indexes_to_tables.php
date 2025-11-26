<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Add indexes to projects table for frequently queried columns
        Schema::table('projects', function (Blueprint $table) {
            $table->index('type', 'idx_projects_type');
            $table->index('team_id', 'idx_projects_team_id');
            $table->index('created_at', 'idx_projects_created_at');
            $table->index('category', 'idx_projects_category');
            $table->index('academic_year_id', 'idx_projects_academic_year_id');
        });

        // Add indexes to game_ratings table
        Schema::table('game_ratings', function (Blueprint $table) {
            $table->index('project_id', 'idx_game_ratings_project_id');
            $table->index('user_id', 'idx_game_ratings_user_id');
        });

        // Add composite index to team_user pivot table
        Schema::table('team_user', function (Blueprint $table) {
            $table->index(['team_id', 'user_id', 'role_in_team'], 'idx_team_user_composite');
        });

        // Add indexes to teams table
        Schema::table('teams', function (Blueprint $table) {
            $table->index('academic_year_id', 'idx_teams_academic_year_id');
            $table->index('invite_code', 'idx_teams_invite_code');
        });

        // Add indexes to users table
        Schema::table('users', function (Blueprint $table) {
            $table->index('email', 'idx_users_email');
            $table->index('role', 'idx_users_role');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->dropIndex('idx_projects_type');
            $table->dropIndex('idx_projects_team_id');
            $table->dropIndex('idx_projects_created_at');
            $table->dropIndex('idx_projects_category');
            $table->dropIndex('idx_projects_academic_year_id');
        });

        Schema::table('game_ratings', function (Blueprint $table) {
            $table->dropIndex('idx_game_ratings_project_id');
            $table->dropIndex('idx_game_ratings_user_id');
        });

        Schema::table('team_user', function (Blueprint $table) {
            $table->dropIndex('idx_team_user_composite');
        });

        Schema::table('teams', function (Blueprint $table) {
            $table->dropIndex('idx_teams_academic_year_id');
            $table->dropIndex('idx_teams_invite_code');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex('idx_users_email');
            $table->dropIndex('idx_users_role');
        });
    }
};

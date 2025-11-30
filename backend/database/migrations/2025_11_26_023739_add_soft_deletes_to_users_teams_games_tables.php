<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Adds soft delete columns to users and teams tables.
     * Allows admin to recover deleted records instead of permanent deletion.
     * NOTE: Games table soft deletes removed - games table is legacy, use projects table instead.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->softDeletes();
        });

        Schema::table('teams', function (Blueprint $table) {
            $table->softDeletes();
        });

        // Games table removed - it's legacy, projects table is used instead
        // If games table still exists, add soft deletes for backward compatibility
        if (Schema::hasTable('games')) {
            Schema::table('games', function (Blueprint $table) {
                if (!Schema::hasColumn('games', 'deleted_at')) {
                    $table->softDeletes();
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });

        Schema::table('teams', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });

        // Only drop if games table exists
        if (Schema::hasTable('games')) {
            Schema::table('games', function (Blueprint $table) {
                $table->dropSoftDeletes();
            });
        }
    }
};

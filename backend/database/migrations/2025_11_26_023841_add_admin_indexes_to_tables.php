<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Adds indexes for common admin queries: filtering, sorting, searching.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->index('created_at'); // Sort by registration date
            $table->index('role'); // Filter by role (admin/user)
            $table->index('email_verified_at'); // Find unverified users
        });

        Schema::table('teams', function (Blueprint $table) {
            $table->index('created_at'); // Sort by creation date
            $table->index('academic_year_id'); // Filter by year
        });

        // Games table indexes removed - games table is legacy, use projects table instead
        // If games table still exists, add indexes for backward compatibility
        if (Schema::hasTable('games')) {
            Schema::table('games', function (Blueprint $table) {
                $table->index('created_at'); // Sort by upload date
                $table->index('academic_year_id'); // Filter by year
                $table->index(['team_id', 'created_at']); // Team game history
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['created_at']);
            $table->dropIndex(['role']);
            $table->dropIndex(['email_verified_at']);
        });

        Schema::table('teams', function (Blueprint $table) {
            $table->dropIndex(['created_at']);
            $table->dropIndex(['academic_year_id']);
        });

        // Only drop if games table exists
        if (Schema::hasTable('games')) {
            Schema::table('games', function (Blueprint $table) {
                $table->dropIndex(['created_at']);
                $table->dropIndex(['academic_year_id']);
                $table->dropIndex(['team_id', 'created_at']);
            });
        }
    }
};

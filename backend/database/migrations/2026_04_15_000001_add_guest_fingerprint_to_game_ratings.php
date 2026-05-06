<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Add the column if it doesn't already exist (may have been added manually)
        if (!Schema::hasColumn('game_ratings', 'guest_fingerprint')) {
            Schema::table('game_ratings', function (Blueprint $table) {
                $table->string('guest_fingerprint', 64)->nullable()->after('user_id');
            });
        }

        // Add unique index for deduplication.
        // MySQL/MariaDB allow multiple NULL values in a unique index, so
        // authenticated-user rows (guest_fingerprint = NULL) are unaffected.
        // Wrapped in try-catch in case the index already exists (manually created).
        try {
            Schema::table('game_ratings', function (Blueprint $table) {
                $table->unique(['project_id', 'guest_fingerprint'], 'game_ratings_project_fingerprint_unique');
            });
        } catch (\Exception $e) {
            // Index already exists — nothing to do.
        }
    }

    public function down(): void
    {
        Schema::table('game_ratings', function (Blueprint $table) {
            if (Schema::hasColumn('game_ratings', 'guest_fingerprint')) {
                $table->dropUnique('game_ratings_project_fingerprint_unique');
                $table->dropColumn('guest_fingerprint');
            }
        });
    }
};

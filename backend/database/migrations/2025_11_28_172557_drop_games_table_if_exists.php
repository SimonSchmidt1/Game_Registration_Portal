<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Drops the legacy games table since we're now using the projects table.
     * This is safe because all games were migrated to projects in migration 2025_11_23_155535.
     */
    public function up(): void
    {
        if (Schema::hasTable('games')) {
            Schema::dropIfExists('games');
        }
    }

    /**
     * Reverse the migrations.
     * Note: We don't recreate the games table as it's legacy.
     * If you need to rollback, restore from projects table.
     */
    public function down(): void
    {
        // Intentionally empty - games table is legacy and should not be recreated
    }
};

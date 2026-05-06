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
        // Altering ENUMs requires a raw DB statement in MySQL
        DB::statement("ALTER TABLE projects MODIFY COLUMN type ENUM('game', 'web_app', 'mobile_app', 'library', 'other', 'webgl') DEFAULT 'game'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement("ALTER TABLE projects MODIFY COLUMN type ENUM('game', 'web_app', 'mobile_app', 'library', 'other') DEFAULT 'game'");
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE projects MODIFY COLUMN type ENUM('game','web_app','mobile_app','library','other','webgl') NOT NULL DEFAULT 'game'");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE projects MODIFY COLUMN type ENUM('game','web_app','mobile_app','library','other') NOT NULL DEFAULT 'game'");
    }
};

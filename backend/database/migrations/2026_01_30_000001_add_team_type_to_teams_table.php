<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Adds team_type column to support international teams with non-UCM emails.
     */
    public function up(): void
    {
        Schema::table('teams', function (Blueprint $table) {
            $table->string('team_type', 20)->default('denny')->after('invite_code');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('teams', function (Blueprint $table) {
            $table->dropColumn('team_type');
        });
    }
};

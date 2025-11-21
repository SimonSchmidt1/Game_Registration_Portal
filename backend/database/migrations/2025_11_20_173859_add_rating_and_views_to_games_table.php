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
        Schema::table('games', function (Blueprint $table) {
            $table->decimal('rating', 2, 1)->default(0)->after('category'); // Rating 0.0 to 5.0
            $table->unsignedInteger('views')->default(0)->after('rating'); // View count
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('games', function (Blueprint $table) {
            $table->dropColumn(['rating', 'views']);
        });
    }
};

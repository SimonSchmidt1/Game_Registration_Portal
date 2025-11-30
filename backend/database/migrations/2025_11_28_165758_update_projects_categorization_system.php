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
        Schema::table('projects', function (Blueprint $table) {
            // Remove old category column
            $table->dropColumn('category');
            
            // Add new categorization columns
            $table->enum('school_type', ['zs', 'ss', 'vs'])->nullable()->after('type');
            $table->integer('year_of_study')->nullable()->after('school_type');
            $table->string('subject')->nullable()->after('year_of_study');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            // Restore old category column
            $table->string('category')->nullable()->after('type');
            
            // Remove new categorization columns
            $table->dropColumn(['school_type', 'year_of_study', 'subject']);
        });
    }
};

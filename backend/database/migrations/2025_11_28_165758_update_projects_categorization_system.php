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
            // Remove old category column only if it exists
            if (Schema::hasColumn('projects', 'category')) {
                $table->dropColumn('category');
            }
        });
        
        // Add new categorization columns (separate statement to avoid issues)
        Schema::table('projects', function (Blueprint $table) {
            // Add school_type only if it doesn't exist
            if (!Schema::hasColumn('projects', 'school_type')) {
                $table->enum('school_type', ['zs', 'ss', 'vs'])->nullable()->after('type');
            }
            
            // Add year_of_study only if it doesn't exist
            if (!Schema::hasColumn('projects', 'year_of_study')) {
                $table->integer('year_of_study')->nullable()->after('school_type');
            }
            
            // Add subject only if it doesn't exist
            if (!Schema::hasColumn('projects', 'subject')) {
                $table->string('subject')->nullable()->after('year_of_study');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            // Restore old category column only if it doesn't exist
            if (!Schema::hasColumn('projects', 'category')) {
                $table->string('category')->nullable()->after('type');
            }
            
            // Remove new categorization columns only if they exist
            $columnsToDrop = [];
            if (Schema::hasColumn('projects', 'school_type')) {
                $columnsToDrop[] = 'school_type';
            }
            if (Schema::hasColumn('projects', 'year_of_study')) {
                $columnsToDrop[] = 'year_of_study';
            }
            if (Schema::hasColumn('projects', 'subject')) {
                $columnsToDrop[] = 'subject';
            }
            
            if (!empty($columnsToDrop)) {
                $table->dropColumn($columnsToDrop);
            }
        });
    }
};

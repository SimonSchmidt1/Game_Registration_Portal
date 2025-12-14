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
            // Add education system fields
            $table->string('school_type', 10)->nullable()->after('category');
            $table->tinyInteger('year_of_study')->nullable()->after('school_type');
            $table->string('subject', 50)->nullable()->after('year_of_study');
            
            // Add indexes for filtering
            $table->index('school_type');
            $table->index('year_of_study');
            $table->index('subject');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->dropIndex(['school_type']);
            $table->dropIndex(['year_of_study']);
            $table->dropIndex(['subject']);
            
            $table->dropColumn(['school_type', 'year_of_study', 'subject']);
        });
    }
};

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
        Schema::create('projects', function (Blueprint $table) {
            $table->id();
            $table->foreignId('team_id')->constrained()->onDelete('cascade');
            $table->foreignId('academic_year_id')->nullable()->constrained();
            
            // Universal fields
            $table->string('title');
            $table->text('description')->nullable();
            $table->enum('type', ['game', 'web_app', 'mobile_app', 'library', 'other'])->default('game');
            $table->string('category')->nullable();
            $table->date('release_date')->nullable();
            
            // Media
            $table->string('splash_screen_path')->nullable();
            $table->string('video_path')->nullable();
            $table->string('video_url')->nullable();
            
            // Downloads (flexible JSON for type-specific files)
            $table->json('files')->nullable();
            
            // Ratings and views
            $table->decimal('rating', 3, 2)->default(0);
            $table->integer('rating_count')->default(0);
            $table->integer('views')->default(0);
            
            // Type-specific data (JSON for flexibility)
            $table->json('metadata')->nullable();
            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('projects');
    }
};

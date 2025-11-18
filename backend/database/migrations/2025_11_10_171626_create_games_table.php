<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('games', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('trailer_path')->nullable();
            $table->string('splash_screen_path')->nullable();
            $table->string('source_code_path')->nullable();
            $table->string('export_path')->nullable(); // .exe, WebGL, mobile
            $table->date('release_date')->nullable();
            $table->foreignId('team_id')->constrained()->onDelete('cascade');
            $table->foreignId('academic_year_id')->constrained()->onDelete('cascade');
            $table->timestamps();
            $table->string('category')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('games');
    }
};

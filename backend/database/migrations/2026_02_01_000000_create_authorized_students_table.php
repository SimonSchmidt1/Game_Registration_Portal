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
        Schema::create('authorized_students', function (Blueprint $table) {
            $table->id();
            $table->string('student_id', 7)->unique()->comment('UCM 7-digit student ID');
            $table->string('name');
            $table->string('email')->unique()->comment('UCM email address');
            $table->enum('student_type', ['denny', 'externy'])->default('denny');
            $table->boolean('is_active')->default(true)->comment('Can be toggled without deleting record');
            $table->timestamps();
            
            // Indexes for fast lookups
            $table->index('email');
            $table->index('student_id');
            $table->index(['is_active', 'email']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('authorized_students');
    }
};

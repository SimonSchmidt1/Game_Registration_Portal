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
        if (!Schema::hasTable('password_reset_tokens')) {
            return; // Table will be created by its own migration
        }

        // Ensure 'user_id' exists and is linked to users
        if (!Schema::hasColumn('password_reset_tokens', 'user_id')) {
            Schema::table('password_reset_tokens', function (Blueprint $table) {
                $table->unsignedBigInteger('user_id')->nullable();
            });
            Schema::table('password_reset_tokens', function (Blueprint $table) {
                $table->foreign('user_id', 'prt_user_id_fk')->references('id')->on('users')->onDelete('cascade');
            });
        }

        // Add columns expected by code if missing (legacy table support)
        if (!Schema::hasColumn('password_reset_tokens', 'type')) {
            Schema::table('password_reset_tokens', function (Blueprint $table) {
                $table->string('type')->default('reset');
            });
        }
        if (!Schema::hasColumn('password_reset_tokens', 'expires_at')) {
            Schema::table('password_reset_tokens', function (Blueprint $table) {
                $table->timestamp('expires_at')->nullable();
            });
        }
        if (!Schema::hasColumn('password_reset_tokens', 'used')) {
            Schema::table('password_reset_tokens', function (Blueprint $table) {
                $table->boolean('used')->default(false);
            });
        }
        if (!Schema::hasColumn('password_reset_tokens', 'used_at')) {
            Schema::table('password_reset_tokens', function (Blueprint $table) {
                $table->timestamp('used_at')->nullable();
            });
        }
        if (!Schema::hasColumn('password_reset_tokens', 'ip_address')) {
            Schema::table('password_reset_tokens', function (Blueprint $table) {
                $table->string('ip_address', 45)->nullable();
            });
        }
        if (!Schema::hasColumn('password_reset_tokens', 'created_at')) {
            Schema::table('password_reset_tokens', function (Blueprint $table) {
                $table->timestamps();
            });
        }

        // Best-effort backfill user_id from legacy 'email'
        if (Schema::hasColumn('password_reset_tokens', 'email') && Schema::hasColumn('password_reset_tokens', 'user_id')) {
            DB::statement(
                'UPDATE password_reset_tokens prt
                 JOIN users u ON u.email = prt.email
                 SET prt.user_id = u.id
                 WHERE prt.user_id IS NULL'
            );
        }

        // Add composite index if both columns exist
        if (Schema::hasColumn('password_reset_tokens', 'user_id') && Schema::hasColumn('password_reset_tokens', 'type')) {
            try {
                DB::statement('CREATE INDEX prt_user_id_type_idx ON password_reset_tokens (user_id, type)');
            } catch (\Throwable $e) {
                // Ignore if index already exists
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (!Schema::hasTable('password_reset_tokens')) {
            return;
        }
        // Drop composite index if we created it
        try {
            DB::statement('DROP INDEX prt_user_id_type_idx ON password_reset_tokens');
        } catch (\Throwable $e) {
            // ignore
        }
        if (Schema::hasColumn('password_reset_tokens', 'user_id')) {
            Schema::table('password_reset_tokens', function (Blueprint $table) {
                $table->dropForeign('prt_user_id_fk');
            });
            Schema::table('password_reset_tokens', function (Blueprint $table) {
                $table->dropColumn('user_id');
            });
        }
    }
};

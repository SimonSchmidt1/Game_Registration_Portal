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
            Schema::table('users', function (Blueprint $table) {
                // Pridáme nullable stĺpec team_id, ktorý je cudziný kľúč na tabuľku teams
                $table->foreignId('team_id')
                      ->nullable()
                      ->after('id') // Voliteľné: umiestni ho hneď za id
                      ->constrained('teams') // Vytvorí väzbu na tabuľku teams
                      ->onDelete('set null'); // Ak sa tím zmaže, team_id sa nastaví na null
            });
        }

        /**
         * Reverse the migrations.
         */
        public function down(): void
        {
            Schema::table('users', function (Blueprint $table) {
                // Najprv zmažeme cudzí kľúč
                $table->dropConstrainedForeignId('team_id');
                // Potom zmažeme samotný stĺpec
                $table->dropColumn('team_id');
            });
        }
    };
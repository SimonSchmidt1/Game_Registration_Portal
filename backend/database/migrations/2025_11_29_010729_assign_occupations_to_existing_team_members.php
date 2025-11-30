<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Available occupations
        $occupations = ['Programátor', 'Grafik 2D', 'Grafik 3D', 'Tester', 'Animátor'];
        
        // Get all team_user records without occupation
        $teamMembers = DB::table('team_user')
            ->whereNull('occupation')
            ->get();
        
        // Assign random occupations to each member
        foreach ($teamMembers as $member) {
            $randomOccupation = $occupations[array_rand($occupations)];
            DB::table('team_user')
                ->where('id', $member->id)
                ->update(['occupation' => $randomOccupation]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Set all occupations to null (optional - you might not want to reverse this)
        DB::table('team_user')
            ->whereNotNull('occupation')
            ->update(['occupation' => null]);
    }
};

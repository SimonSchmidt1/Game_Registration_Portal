<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;

class AcademicYearSeeder extends Seeder
{
    public function run(): void
    {
        $now = now();
        $years = [
            ['name' => '2023/2024'],
            ['name' => '2024/2025'],
            ['name' => '2025/2026'],
        ];

        foreach ($years as $year) {
            $exists = DB::table('academic_years')->where('name', $year['name'])->exists();
            if (!$exists) {
                DB::table('academic_years')->insert([
                    'name' => $year['name'],
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        }
    }
}

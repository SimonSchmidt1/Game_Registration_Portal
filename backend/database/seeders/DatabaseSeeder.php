<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        // Create admin user if it doesn't exist
        $adminEmail = config('admin.email') ?? 'admin@gameportal.dev';
        
        User::updateOrCreate(
            ['email' => $adminEmail],
            [
                'name' => 'Administrator',
                'password' => Hash::make('admin_temp_password'), // Password doesn't matter - uses config password
                'role' => 'admin',
                'email_verified_at' => now(),
                'failed_login_attempts' => 0,
            ]
        );
        
        $this->command->info('Admin user created/updated: ' . $adminEmail);
        
        // Seed academic years
        $this->call(AcademicYearSeeder::class);

        // Seed demo data (teams, users, projects)
        $this->call(DemoDataSeeder::class);
    }
}

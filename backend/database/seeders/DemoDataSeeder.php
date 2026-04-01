<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use App\Models\Team;
use App\Models\Project;
use App\Models\AcademicYear;

class DemoDataSeeder extends Seeder
{
    public function run(): void
    {
        $now = now();

        // Map academic years by name for quick lookup
        $academicYears = AcademicYear::pluck('id', 'name');

        // 6 users (students) - admin seeder remains untouched
        $usersData = [
            [
                'name' => 'Sarah Novak',
                'email' => 'sarah.novak@demo.test',
                'student_type' => 'denny',
                'role' => 'student',
                'password' => 'Password123!',
            ],
            [
                'name' => 'Lukas Dvorak',
                'email' => 'lukas.dvorak@demo.test',
                'student_type' => 'denny',
                'role' => 'student',
                'password' => 'Password123!',
            ],
            [
                'name' => 'Petra Vlkova',
                'email' => 'petra.vlkova@demo.test',
                'student_type' => 'externy',
                'role' => 'student',
                'password' => 'Password123!',
            ],
            [
                'name' => 'Tomas Hric',
                'email' => 'tomas.hric@demo.test',
                'student_type' => 'denny',
                'role' => 'student',
                'password' => 'Password123!',
            ],
            [
                'name' => 'Nina Urban',
                'email' => 'nina.urban@demo.test',
                'student_type' => 'externy',
                'role' => 'student',
                'password' => 'Password123!',
            ],
            [
                'name' => 'David Kral',
                'email' => 'david.kral@demo.test',
                'student_type' => 'denny',
                'role' => 'student',
                'password' => 'Password123!',
            ],
        ];

        $users = [];
        foreach ($usersData as $data) {
            $users[$data['email']] = User::updateOrCreate(
                ['email' => $data['email']],
                [
                    'name' => $data['name'],
                    'password' => Hash::make($data['password']),
                    'role' => $data['role'],
                    'student_type' => $data['student_type'],
                    'email_verified_at' => $now,
                    'failed_login_attempts' => 0,
                ]
            );
        }

        // Teams with varied statuses
        $teamsData = [
            [
                'name' => 'Nova Forge',
                'invite_code' => 'DEN-NOVA-001',
                'academic_year' => '2024/2025',
                'status' => 'active',
                'scrum_master_email' => 'sarah.novak@demo.test',
                'members' => [
                    ['email' => 'sarah.novak@demo.test', 'role' => 'scrum_master', 'occupation' => 'Design Lead'],
                    ['email' => 'lukas.dvorak@demo.test', 'role' => 'member', 'occupation' => 'Gameplay Developer'],
                    ['email' => 'petra.vlkova@demo.test', 'role' => 'member', 'occupation' => 'QA Analyst'],
                ],
            ],
            [
                'name' => 'Byte Builders',
                'invite_code' => 'DEN-BYTE-002',
                'academic_year' => '2024/2025',
                'status' => 'active',
                'scrum_master_email' => 'tomas.hric@demo.test',
                'members' => [
                    ['email' => 'tomas.hric@demo.test', 'role' => 'scrum_master', 'occupation' => 'Product Owner'],
                    ['email' => 'nina.urban@demo.test', 'role' => 'member', 'occupation' => 'QA Specialist'],
                    ['email' => 'david.kral@demo.test', 'role' => 'member', 'occupation' => 'Backend Engineer'],
                ],
            ],
            [
                'name' => 'Quantum Pixels',
                'invite_code' => 'EXT-QUANT-003',
                'academic_year' => '2025/2026',
                'status' => 'pending',
                'scrum_master_email' => 'david.kral@demo.test',
                'members' => [
                    ['email' => 'david.kral@demo.test', 'role' => 'scrum_master', 'occupation' => 'Tech Lead'],
                    ['email' => 'sarah.novak@demo.test', 'role' => 'member', 'occupation' => 'UX Researcher'],
                    ['email' => 'nina.urban@demo.test', 'role' => 'member', 'occupation' => 'Test Engineer'],
                ],
            ],
            [
                'name' => 'Atlas Labs',
                'invite_code' => 'DEN-ATLAS-004',
                'academic_year' => '2023/2024',
                'status' => 'suspended',
                'scrum_master_email' => 'lukas.dvorak@demo.test',
                'members' => [
                    ['email' => 'lukas.dvorak@demo.test', 'role' => 'scrum_master', 'occupation' => 'Lead Developer'],
                    ['email' => 'petra.vlkova@demo.test', 'role' => 'member', 'occupation' => 'Data Analyst'],
                ],
            ],
        ];

        $teams = [];
        foreach ($teamsData as $teamData) {
            $academicYearId = $academicYears[$teamData['academic_year']] ?? null;
            $scrumMaster = $users[$teamData['scrum_master_email']] ?? null;

            $team = Team::updateOrCreate(
                ['invite_code' => $teamData['invite_code']],
                [
                    'name' => $teamData['name'],
                    'academic_year_id' => $academicYearId,
                    'scrum_master_id' => $scrumMaster?->id,
                    'status' => $teamData['status'],
                ]
            );

            $pivotData = [];
            foreach ($teamData['members'] as $member) {
                $user = $users[$member['email']] ?? null;
                if ($user) {
                    $pivotData[$user->id] = [
                        'role_in_team' => $member['role'],
                        'occupation' => $member['occupation'] ?? 'Member',
                    ];
                }
            }

            if (!empty($pivotData)) {
                $team->members()->sync($pivotData);
            }

            $teams[$teamData['name']] = $team;
        }

        // Projects with file metadata placeholders
        $projectsData = [
            [
                'title' => 'Starfall Raiders',
                'team' => 'Nova Forge',
                'academic_year' => '2024/2025',
                'type' => 'game',
                'school_type' => 'vs',
                'year_of_study' => 2,
                'subject' => 'Computer Graphics',
                'predmet' => 'Počítačová grafika',
                'description' => 'Co-op space shooter with procedural missions.',
                'rating' => 4.6,
                'rating_count' => 12,
                'views' => 235,
                'release_date' => '2025-03-15',
                'files' => [
                    'documentation' => 'storage/projects/starfall-raiders/documentation.pdf',
                    'presentation' => 'storage/projects/starfall-raiders/presentation.pdf',
                    'source_code' => 'storage/projects/starfall-raiders/source_code.zip',
                    'export' => 'storage/projects/starfall-raiders/export.zip',
                ],
                'metadata' => [
                    'export_type' => 'standalone',
                    'tech_stack' => 'Unity, C#',
                    'play_url' => 'https://demo.example.com/starfall',
                ],
            ],
            [
                'title' => 'Campus Navigator',
                'team' => 'Byte Builders',
                'academic_year' => '2024/2025',
                'type' => 'web_app',
                'school_type' => 'vs',
                'year_of_study' => 1,
                'subject' => 'Web Applications',
                'predmet' => 'Webové aplikácie',
                'description' => 'Progressive web app for campus maps and events.',
                'rating' => 4.1,
                'rating_count' => 8,
                'views' => 180,
                'release_date' => '2025-02-01',
                'files' => [
                    'documentation' => 'storage/projects/campus-navigator/documentation.pdf',
                    'presentation' => 'storage/projects/campus-navigator/presentation.pdf',
                    'source_code' => 'storage/projects/campus-navigator/source_code.zip',
                    'export' => 'storage/projects/campus-navigator/export.zip',
                ],
                'metadata' => [
                    'export_type' => 'webgl',
                    'tech_stack' => 'Vue 3, PrimeVue, Laravel API',
                    'play_url' => 'https://demo.example.com/campus-navigator',
                ],
            ],
            [
                'title' => 'Solar Quest',
                'team' => 'Quantum Pixels',
                'academic_year' => '2025/2026',
                'type' => 'mobile_app',
                'school_type' => 'ss',
                'year_of_study' => 3,
                'subject' => 'Mobile Development',
                'predmet' => 'Mobilný vývoj',
                'description' => 'Educational AR mobile game about renewable energy.',
                'rating' => 3.9,
                'rating_count' => 5,
                'views' => 90,
                'release_date' => '2025-05-20',
                'files' => [
                    'documentation' => 'storage/projects/solar-quest/documentation.pdf',
                    'presentation' => 'storage/projects/solar-quest/presentation.pdf',
                    'source_code' => 'storage/projects/solar-quest/source_code.zip',
                    'export' => 'storage/projects/solar-quest/export.zip',
                ],
                'metadata' => [
                    'export_type' => 'mobile',
                    'tech_stack' => 'Flutter, Dart',
                    'play_url' => 'https://demo.example.com/solar-quest',
                ],
            ],
            [
                'title' => 'DataBridge Library',
                'team' => 'Atlas Labs',
                'academic_year' => '2023/2024',
                'type' => 'library',
                'school_type' => 'vs',
                'year_of_study' => 4,
                'subject' => 'Data Engineering',
                'predmet' => 'Dátové inžinierstvo',
                'description' => 'Reusable data ingestion and validation toolkit.',
                'rating' => 4.3,
                'rating_count' => 9,
                'views' => 145,
                'release_date' => '2024-11-30',
                'files' => [
                    'documentation' => 'storage/projects/databridge/documentation.pdf',
                    'presentation' => 'storage/projects/databridge/presentation.pdf',
                    'source_code' => 'storage/projects/databridge/source_code.zip',
                    'export' => 'storage/projects/databridge/export.zip',
                ],
                'metadata' => [
                    'export_type' => 'executable',
                    'tech_stack' => 'PHP 8.3, Laravel 11',
                    'play_url' => 'https://demo.example.com/databridge',
                ],
            ],
            [
                'title' => 'Echoes VR',
                'team' => 'Nova Forge',
                'academic_year' => '2024/2025',
                'type' => 'game',
                'school_type' => 'vs',
                'year_of_study' => 2,
                'subject' => 'Game Audio',
                'predmet' => 'Zvuk v hrách',
                'description' => 'Immersive VR rhythm experience with spatial audio.',
                'rating' => 4.2,
                'rating_count' => 6,
                'views' => 120,
                'release_date' => '2025-04-10',
                'files' => [
                    'documentation' => 'storage/projects/echoes-vr/documentation.pdf',
                    'presentation' => 'storage/projects/echoes-vr/presentation.pdf',
                    'source_code' => 'storage/projects/echoes-vr/source_code.zip',
                    'export' => 'storage/projects/echoes-vr/export.zip',
                ],
                'metadata' => [
                    'export_type' => 'standalone',
                    'tech_stack' => 'Unity, FMOD',
                    'play_url' => 'https://demo.example.com/echoes-vr',
                ],
            ],
        ];

        foreach ($projectsData as $projectData) {
            $team = $teams[$projectData['team']] ?? null;
            if (!$team) {
                continue;
            }

            $project = Project::updateOrCreate(
                ['title' => $projectData['title']],
                [
                    'team_id' => $team->id,
                    'academic_year_id' => $academicYears[$projectData['academic_year']] ?? null,
                    'type' => $projectData['type'],
                    'school_type' => $projectData['school_type'],
                    'year_of_study' => $projectData['year_of_study'],
                    'subject' => $projectData['subject'],
                    'predmet' => $projectData['predmet'],
                    'description' => $projectData['description'],
                    'rating' => $projectData['rating'],
                    'rating_count' => $projectData['rating_count'],
                    'views' => $projectData['views'],
                    'release_date' => $projectData['release_date'],
                    'files' => $projectData['files'],
                    'metadata' => $projectData['metadata'],
                ]
            );

            // Ensure splash/video placeholders empty for now
            $project->splash_screen_path ??= null;
            $project->video_path ??= null;
            $project->video_url ??= $projectData['metadata']['play_url'] ?? null;
            $project->save();
        }
    }
}

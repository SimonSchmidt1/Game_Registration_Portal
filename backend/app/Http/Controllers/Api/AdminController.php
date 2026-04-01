<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Team;
use App\Models\Project;
use App\Models\User;
use App\Models\AcademicYear;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;
use Illuminate\Support\Str;
use App\Enums\Occupation;

class AdminController extends Controller
{
    /**
     * Validate academic year name in YYYY/YYYY format and sequential years.
     */
    private function isValidAcademicYearName(string $name): bool
    {
        if (!preg_match('/^\d{4}\/\d{4}$/', $name)) {
            return false;
        }

        [$start, $end] = array_map('intval', explode('/', $name));
        return $end === ($start + 1);
    }

    /**
     * Safely count pending teams (handles missing status column)
     */
    private function safeCountPendingTeams()
    {
        // Check if status column exists
        if (!Schema::hasColumn('teams', 'status')) {
            return 0;
        }
        
        try {
            return Team::where('status', 'pending')->count();
        } catch (\Exception $e) {
            Log::warning('Status column error: ' . $e->getMessage());
            return 0;
        }
    }

    /**
     * Create a new team (admin-only).
     */
    public function createTeam(Request $request)
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255', Rule::unique('teams', 'name')],
            'academic_year_id' => ['required', 'integer', 'exists:academic_years,id'],
            'team_type' => ['required', 'string', Rule::in(['denny', 'externy', 'international'])],
            'status' => ['nullable', 'string', Rule::in(['active', 'pending', 'suspended'])],
            'scrum_master_id' => ['nullable', 'integer', 'exists:users,id'],
            'scrum_master_occupation' => ['nullable', 'string', Rule::in(Occupation::values())],
        ]);

        try {
            $team = DB::transaction(function () use ($request) {
                // Generate prefix based on team type (SPE for international)
                $prefix = match($request->team_type) {
                    'denny' => 'DEN',
                    'externy' => 'EXT',
                    'international' => 'SPE',
                    default => 'DEN'
                };

                // Generate unique invite code
                do {
                    $randomPart = strtoupper(Str::random(6));
                    $inviteCode = $prefix . $randomPart;
                } while (Team::where('invite_code', $inviteCode)->exists());

                $teamData = [
                    'name' => trim($request->name),
                    'academic_year_id' => (int) $request->academic_year_id,
                    'invite_code' => $inviteCode,
                    'team_type' => $request->team_type,
                ];

                // Optional status (fallback to default schema value)
                if ($request->filled('status') && Schema::hasColumn('teams', 'status')) {
                    $teamData['status'] = $request->status;
                }

                // Optional Scrum Master
                if ($request->filled('scrum_master_id')) {
                    $teamData['scrum_master_id'] = (int) $request->scrum_master_id;
                }

                $team = Team::create($teamData);

                // Attach Scrum Master pivot if provided
                if ($request->filled('scrum_master_id')) {
                    $occupation = $request->scrum_master_occupation;
                    if (!$occupation) {
                        // Default to first occupation value if not provided
                        $occupation = Occupation::values()[0] ?? 'programmer';
                    }
                    $team->members()->attach((int) $request->scrum_master_id, [
                        'role_in_team' => 'scrum_master',
                        'occupation' => $occupation,
                    ]);
                }

                $team->load(['members', 'academicYear']);
                return $team;
            });

            Log::info('Admin created team', [
                'team_id' => $team->id,
                'team_name' => $team->name,
                'admin_id' => auth()->id(),
            ]);

            return response()->json([
                'message' => "Tím '{$team->name}' bol úspešne vytvorený",
                'team' => [
                    'id' => $team->id,
                    'name' => $team->name,
                    'invite_code' => $team->invite_code,
                    'status' => $this->getTeamStatus($team),
                    'academic_year' => $team->academicYear ? [
                        'id' => $team->academicYear->id,
                        'name' => $team->academicYear->name,
                    ] : null,
                    'scrum_master_id' => $team->scrum_master_id,
                ]
            ], 201);
        } catch (\Exception $e) {
            Log::error('Admin create team error: ' . $e->getMessage());
            Log::error('Stack trace: ' . $e->getTraceAsString());
            return response()->json([
                'error' => 'Nepodarilo sa vytvoriť tím',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Create a new academic year (admin-only).
     */
    public function createAcademicYear(Request $request)
    {
        $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
                'regex:/^\d{4}\/\d{4}$/',
                Rule::unique('academic_years', 'name')
            ],
        ]);

        $name = trim($request->name);

        if (!$this->isValidAcademicYearName($name)) {
            return response()->json([
                'error' => 'Neplatný akademický rok',
                'message' => 'Akademický rok musí byť vo formáte YYYY/YYYY a druhý rok musí byť o 1 vyšší.'
            ], 422);
        }

        try {
            $academicYear = AcademicYear::create([
                'name' => $name,
            ]);

            Log::info('Admin created academic year', [
                'academic_year_id' => $academicYear->id,
                'name' => $academicYear->name,
                'admin_id' => auth()->id(),
            ]);

            return response()->json([
                'message' => 'Akademický rok bol úspešne vytvorený',
                'academic_year' => $academicYear,
            ], 201);
        } catch (\Exception $e) {
            Log::error('Admin create academic year error: ' . $e->getMessage());
            return response()->json([
                'error' => 'Nepodarilo sa vytvoriť akademický rok',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Check if project has export files
     */
    private function projectHasExport($project)
    {
        $files = $project->files ?? [];
        
        // Check for new universal export field structure
        $hasExportFile = !empty($files['export']) || !empty($files['source_code']) || 
                         !empty($files['documentation']);
        
        // Backward compatibility: check old file structure
        $hasLegacyFiles = !empty($files['apk_file']) || !empty($files['ios_file']);
        
        $hasMetaExport = !empty($project->getMeta('export_path')) || !empty($project->getMeta('source_code_path'));
        
        return $hasExportFile || $hasLegacyFiles || $hasMetaExport;
    }

    /**
     * Safely get team status (handles missing column)
     */
    private function getTeamStatus($team)
    {
        // Check if status column exists
        if (!Schema::hasColumn('teams', 'status')) {
            return 'active';
        }
        
        try {
            return $team->status ?? 'active';
        } catch (\Exception $e) {
            return 'active';
        }
    }

    /**
     * Get system statistics for admin dashboard.
     */
    public function stats()
    {
        try {
            // Get counts safely
            $totalTeams = Team::count();
            $totalProjects = Project::count();
            $totalUsers = User::where(function ($q) {
                $q->where('role', '!=', 'admin')->orWhereNull('role');
            })->count();
            
            // Check if projects relationship works
            $teamsWithProjects = 0;
            try {
                $teamsWithProjects = Team::whereHas('projects')->count();
            } catch (\Exception $e) {
                Log::warning('Teams with projects query failed: ' . $e->getMessage());
            }
            
            $projectsWithVideo = Project::where(function ($q) {
                $q->whereNotNull('video_path')->orWhereNotNull('video_url');
            })->count();
            
            $projectsWithSplash = Project::whereNotNull('splash_screen_path')->count();
            
            $stats = [
                'total_teams' => $totalTeams,
                'total_projects' => $totalProjects,
                'total_users' => $totalUsers,
                'teams_with_projects' => $teamsWithProjects,
                'projects_with_video' => $projectsWithVideo,
                'projects_with_splash' => $projectsWithSplash,
                'pending_teams' => $this->safeCountPendingTeams(),
            ];

            Log::info('Admin stats loaded successfully', $stats);
            return response()->json($stats);
        } catch (\Exception $e) {
            Log::error('Admin stats error: ' . $e->getMessage());
            Log::error('Stack trace: ' . $e->getTraceAsString());
            return response()->json([
                'error' => 'Nepodarilo sa načítať štatistiky',
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ], 500);
        }
    }

    /**
     * Get all users for admin management.
     */
    public function users(Request $request)
    {
        try {
            $users = User::where(function ($q) {
                    $q->where('role', '!=', 'admin')->orWhereNull('role');
                })
                ->select(['id', 'name', 'email', 'role', 'status', 'is_absolvent', 'student_type', 'email_verified_at', 'created_at'])
                ->orderBy('name')
                ->get();

            return response()->json([
                'users' => $users,
                'total' => $users->count()
            ]);
        } catch (\Exception $e) {
            Log::error('Admin users list error: ' . $e->getMessage());
            return response()->json([
                'error' => 'Nepodarilo sa načítať používateľov',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get all teams with their project status indicators.
     */
    public function teams(Request $request)
    {
        try {
            // Build query with safe relationship loading
            // SoftDeletes automatically excludes deleted teams, but we'll be explicit
            $query = Team::with(['members', 'academicYear'])
                ->withCount(['members', 'projects']);

            // Optional search filter
            if ($request->has('search') && $request->search) {
                $search = $request->search;
                $query->where('name', 'like', "%{$search}%");
            }

            // Optional status filter (only if status column exists)
            if ($request->has('status') && $request->status && Schema::hasColumn('teams', 'status')) {
                $query->where('status', $request->status);
            }

            $teams = $query->orderBy('created_at', 'desc')->get();

            // Transform teams (without indicators - they'll be fetched per team on-demand)
            $teamsData = $teams->map(function ($team) {
                // Get scrum master
                $scrumMaster = $team->members->firstWhere('pivot.role_in_team', 'scrum_master');
                if (!$scrumMaster && $team->scrum_master_id) {
                    $scrumMaster = User::find($team->scrum_master_id);
                }

                return [
                    'id' => $team->id,
                    'name' => $team->name,
                    'invite_code' => $team->invite_code,
                    'status' => $this->getTeamStatus($team),
                    'academic_year' => $team->academicYear ? [
                        'id' => $team->academicYear->id,
                        'name' => $team->academicYear->name,
                    ] : null,
                    'members_count' => $team->members_count ?? 0,
                    'projects_count' => $team->projects_count ?? 0,
                    'scrum_master' => $scrumMaster ? [
                        'id' => $scrumMaster->id,
                        'name' => $scrumMaster->name,
                        'email' => $scrumMaster->email,
                    ] : null,
                    'created_at' => $team->created_at,
                    'updated_at' => $team->updated_at,
                ];
            });

            return response()->json([
                'teams' => $teamsData,
                'total' => $teamsData->count(),
            ]);
        } catch (\Exception $e) {
            Log::error('Admin teams error: ' . $e->getMessage());
            Log::error('Stack trace: ' . $e->getTraceAsString());
            return response()->json([
                'error' => 'Nepodarilo sa načítať tímy',
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ], 500);
        }
    }

    /**
     * Get projects for a specific team (for admin accordion view).
     */
    public function teamProjects(Team $team)
    {
        try {
            $team->load(['projects.academicYear']);

            $projects = $team->projects->map(function ($project) {
                $files = $project->files ?? [];
                
                return [
                    'id' => $project->id,
                    'title' => $project->title,
                    'type' => $project->type,
                    'school_type' => $project->school_type,
                    'year_of_study' => $project->year_of_study,
                    'subject' => $project->subject,
                    'description' => $project->description,
                    'has_video' => (bool) ($project->video_path || $project->video_url),
                    'has_splash' => (bool) $project->splash_screen_path,
                    'has_documentation' => !empty($files['documentation']),
                    'has_presentation' => !empty($files['presentation']),
                    'has_source_code' => !empty($files['source_code']),
                    'has_export' => $this->projectHasExport($project),
                    'rating' => $project->rating,
                    'views' => $project->views,
                    'created_at' => $project->created_at,
                ];
            });

            return response()->json($projects);
        } catch (\Exception $e) {
            Log::error('Admin team projects error: ' . $e->getMessage());
            return response()->json(['error' => 'Nepodarilo sa načítať projekty tímu'], 500);
        }
    }

    /**
     * Get detailed information about a specific team.
     */
    public function showTeam(Team $team)
    {
        try {
            $team->load(['members', 'academicYear', 'projects']);

            // Get projects with details
            $projects = $team->projects->map(function ($project) {
                $files = $project->files ?? [];
                
                return [
                    'id' => $project->id,
                    'title' => $project->title,
                    'type' => $project->type,
                    'school_type' => $project->school_type,
                    'year_of_study' => $project->year_of_study,
                    'subject' => $project->subject,
                    'has_video' => (bool) ($project->video_path || $project->video_url),
                    'has_splash' => (bool) $project->splash_screen_path,
                    'has_documentation' => !empty($files['documentation']),
                    'has_presentation' => !empty($files['presentation']),
                    'has_source_code' => !empty($files['source_code']),
                    'has_export' => $this->projectHasExport($project),
                    'rating' => $project->rating,
                    'views' => $project->views,
                    'created_at' => $project->created_at,
                ];
            });

            // Get members with details
            $members = $team->members->map(function ($member) {
                return [
                    'id' => $member->id,
                    'name' => $member->name,
                    'email' => $member->email,
                    'student_type' => $member->student_type,
                    'status' => $member->status ?? 'active',
                    'is_absolvent' => (bool) ($member->is_absolvent ?? false),
                    'role_in_team' => $member->pivot->role_in_team ?? 'member',
                    'occupation' => $member->pivot->occupation ?? null,
                ];
            });

            return response()->json([
                'team' => [
                    'id' => $team->id,
                    'name' => $team->name,
                    'invite_code' => $team->invite_code,
                    'status' => $this->getTeamStatus($team),
                    'academic_year' => $team->academicYear,
                    'scrum_master_id' => $team->scrum_master_id,
                    'created_at' => $team->created_at,
                    'updated_at' => $team->updated_at,
                ],
                'members' => $members,
                'projects' => $projects,
            ]);
        } catch (\Exception $e) {
            Log::error('Admin show team error: ' . $e->getMessage());
            return response()->json(['error' => 'Nepodarilo sa načítať detail tímu'], 500);
        }
    }

    /**
     * Update a team (admin can edit any team).
     */
    public function updateTeam(Request $request, Team $team)
    {
        $request->validate([
            'name' => 'sometimes|string|max:255',
            'status' => 'sometimes|string|in:active,pending,suspended',
        ]);

        try {
            DB::transaction(function () use ($request, $team) {
                if ($request->has('name')) {
                    $team->name = $request->name;
                }
                if ($request->has('status') && Schema::hasColumn('teams', 'status')) {
                    $team->status = $request->status;
                }
                $team->save();
            });

            Log::info('Admin updated team', ['team_id' => $team->id, 'admin_id' => auth()->id()]);

            return response()->json([
                'message' => 'Tím bol úspešne aktualizovaný',
                'team' => $team->fresh(['members', 'academicYear']),
            ]);
        } catch (\Exception $e) {
            Log::error('Admin update team error: ' . $e->getMessage());
            return response()->json(['error' => 'Nepodarilo sa aktualizovať tím'], 500);
        }
    }

    /**
     * Delete a team (soft delete).
     */
    public function deleteTeam(Team $team)
    {
        try {
            $teamName = $team->name;
            $teamId = $team->id;
            
            // Check if team is already deleted
            if ($team->trashed()) {
                return response()->json([
                    'message' => "Tím '{$teamName}' už bol zmazaný",
                    'deleted' => true
                ]);
            }

            DB::transaction(function () use ($team, $teamId, $teamName) {
                // Detach all members first
                $detached = $team->members()->detach();
                Log::info('Members detached from team', [
                    'team_id' => $teamId,
                    'members_detached' => $detached
                ]);
                
                // Soft delete the team
                $deleted = $team->delete();
                
                if (!$deleted) {
                    throw new \Exception('delete() returned false - team was not deleted');
                }
                
                // Refresh to get deleted_at timestamp
                $team->refresh();
                
                Log::info('Team soft deleted successfully', [
                    'team_id' => $teamId,
                    'team_name' => $teamName,
                    'deleted_at' => $team->deleted_at,
                    'is_trashed' => $team->trashed()
                ]);
            });

            // Verify deletion by checking if team is trashed
            $team->refresh();
            if (!$team->trashed()) {
                throw new \Exception('Team deletion verification failed - team is not trashed');
            }

            Log::info('Admin deleted team', [
                'team_id' => $teamId,
                'team_name' => $teamName,
                'admin_id' => auth()->id(),
                'deleted_at' => $team->deleted_at
            ]);

            return response()->json([
                'message' => "Tím '{$teamName}' bol úspešne zmazaný",
                'deleted' => true,
                'team_id' => $teamId
            ]);
        } catch (\Exception $e) {
            Log::error('Admin delete team error: ' . $e->getMessage());
            Log::error('Stack trace: ' . $e->getTraceAsString());
            Log::error('Team details', [
                'team_id' => $team->id ?? 'unknown',
                'team_name' => $team->name ?? 'unknown',
                'is_trashed' => $team->trashed() ?? 'unknown'
            ]);
            
            return response()->json([
                'error' => 'Nepodarilo sa zmazať tím',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Approve a pending team.
     */
    public function approveTeam(Team $team)
    {
        try {
            $currentStatus = $this->getTeamStatus($team);
            if ($currentStatus !== 'pending') {
                return response()->json([
                    'message' => 'Tím nie je v stave čakajúci na schválenie',
                ], 400);
            }

            try {
                $team->status = 'active';
                $team->save();
            } catch (\Exception $e) {
                Log::warning('Status column not found, cannot approve team: ' . $e->getMessage());
                return response()->json([
                    'error' => 'Stĺpec status neexistuje. Spustite migráciu.',
                ], 500);
            }

            Log::info('Admin approved team', ['team_id' => $team->id, 'admin_id' => auth()->id()]);

            // TODO: Optionally send notification to Scrum Master

            return response()->json([
                'message' => "Tím '{$team->name}' bol schválený",
                'team' => $team,
            ]);
        } catch (\Exception $e) {
            Log::error('Admin approve team error: ' . $e->getMessage());
            return response()->json(['error' => 'Nepodarilo sa schváliť tím'], 500);
        }
    }

    /**
     * Reject a pending team.
     */
    public function rejectTeam(Request $request, Team $team)
    {
        $request->validate([
            'reason' => 'nullable|string|max:500',
        ]);

        try {
            if ($team->status !== 'pending') {
                return response()->json([
                    'message' => 'Tím nie je v stave čakajúci na schválenie',
                ], 400);
            }

            $teamName = $team->name;
            $reason = $request->reason ?? 'Bez udania dôvodu';

            // Delete the rejected team
            DB::transaction(function () use ($team) {
                $team->members()->detach();
                $team->delete();
            });

            Log::info('Admin rejected team', [
                'team_name' => $teamName,
                'reason' => $reason,
                'admin_id' => auth()->id()
            ]);

            // TODO: Optionally send notification to Scrum Master with reason

            return response()->json([
                'message' => "Tím '{$teamName}' bol zamietnutý",
                'reason' => $reason,
            ]);
        } catch (\Exception $e) {
            Log::error('Admin reject team error: ' . $e->getMessage());
            return response()->json(['error' => 'Nepodarilo sa zamietnuť tím'], 500);
        }
    }

    /**
     * Get all projects for admin overview.
     */
    public function projects(Request $request)
    {
        try {
            $query = Project::with(['team', 'academicYear']);

            // Optional search filter
            if ($request->has('search') && $request->search) {
                $search = $request->search;
                $query->where('title', 'like', "%{$search}%");
            }

            // Optional type filter
            if ($request->has('type') && $request->type) {
                $query->where('type', $request->type);
            }

            $projects = $query->orderBy('created_at', 'desc')->get();

            $projectsData = $projects->map(function ($project) {
                return [
                    'id' => $project->id,
                    'title' => $project->title,
                    'type' => $project->type,
                    'school_type' => $project->school_type,
                    'subject' => $project->subject,
                    'team' => $project->team ? [
                        'id' => $project->team->id,
                        'name' => $project->team->name,
                    ] : null,
                    'academic_year' => $project->academicYear ? [
                        'id' => $project->academicYear->id,
                        'name' => $project->academicYear->name,
                    ] : null,
                    'has_video' => (bool) ($project->video_path || $project->video_url),
                    'has_splash' => (bool) $project->splash_screen_path,
                    'has_export' => $this->projectHasExport($project),
                    'rating' => $project->rating,
                    'views' => $project->views,
                    'created_at' => $project->created_at,
                ];
            });

            return response()->json([
                'projects' => $projectsData,
                'total' => $projectsData->count(),
            ]);
        } catch (\Exception $e) {
            Log::error('Admin projects error: ' . $e->getMessage());
            return response()->json(['error' => 'Nepodarilo sa načítať projekty'], 500);
        }
    }

    /**
     * Remove a member from a team (admin bypass - no restrictions).
     */
    public function removeMember(Team $team, User $user)
    {
        try {
            // Check if user is actually a member
            if (!$team->members()->where('user_id', $user->id)->exists()) {
                return response()->json([
                    'error' => 'Používateľ nie je členom tohto tímu',
                ], 404);
            }

            $userName = $user->name;
            $teamName = $team->name;
            
            // Admin can remove anyone, including Scrum Master
            DB::transaction(function () use ($team, $user) {
                // Detach the member
                $team->members()->detach($user->id);
                
                // If the removed user was Scrum Master, set scrum_master_id to null
                if ($team->scrum_master_id == $user->id) {
                    $team->scrum_master_id = null;
                    $team->save();
                }
            });

            Log::info('Admin removed team member', [
                'team_id' => $team->id,
                'team_name' => $teamName,
                'user_id' => $user->id,
                'user_name' => $userName,
                'admin_id' => auth()->id()
            ]);

            return response()->json([
                'message' => "Člen '{$userName}' bol odstránený z tímu '{$teamName}'",
                'removed' => true
            ]);
        } catch (\Exception $e) {
            Log::error('Admin remove member error: ' . $e->getMessage());
            return response()->json([
                'error' => 'Nepodarilo sa odstrániť člena',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Change the Scrum Master of a team.
     */
    public function changeScrumMaster(Request $request, Team $team)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
        ]);

        try {
            $newScrumMaster = User::findOrFail($request->user_id);
            
            // Check if new SM is a member of the team
            if (!$team->members()->where('user_id', $newScrumMaster->id)->exists()) {
                return response()->json([
                    'error' => 'Používateľ nie je členom tohto tímu',
                ], 400);
            }

            $oldScrumMasterId = $team->scrum_master_id;

            if ($oldScrumMasterId && (int) $oldScrumMasterId === (int) $newScrumMaster->id) {
                return response()->json([
                    'error' => 'Vybraný používateľ je už Scrum Masterom tohto tímu',
                ], 422);
            }
            
            DB::transaction(function () use ($team, $newScrumMaster, $oldScrumMasterId) {
                // Update old SM role to member (if exists)
                if ($oldScrumMasterId) {
                    $team->members()->updateExistingPivot($oldScrumMasterId, [
                        'role_in_team' => 'member'
                    ]);
                }
                
                // Update new SM role
                $team->members()->updateExistingPivot($newScrumMaster->id, [
                    'role_in_team' => 'scrum_master'
                ]);
                
                // Update team's scrum_master_id
                $team->scrum_master_id = $newScrumMaster->id;
                $team->save();
            });

            Log::info('Admin changed Scrum Master', [
                'team_id' => $team->id,
                'team_name' => $team->name,
                'old_sm_id' => $oldScrumMasterId,
                'new_sm_id' => $newScrumMaster->id,
                'new_sm_name' => $newScrumMaster->name,
                'admin_id' => auth()->id()
            ]);

            return response()->json([
                'message' => "Scrum Master bol zmenený na '{$newScrumMaster->name}'",
                'scrum_master' => [
                    'id' => $newScrumMaster->id,
                    'name' => $newScrumMaster->name,
                    'email' => $newScrumMaster->email,
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('Admin change SM error: ' . $e->getMessage());
            return response()->json([
                'error' => 'Nepodarilo sa zmeniť Scrum Mastera',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Delete a project (admin can delete any project).
     */
    public function deleteProject(Project $project)
    {
        try {
            $projectTitle = $project->title;
            $projectId = $project->id;
            
            DB::transaction(function () use ($project, $projectId, $projectTitle) {
                // Delete associated ratings
                $project->ratings()->delete();
                
                // Delete the project
                $deleted = $project->delete();
                
                if (!$deleted) {
                    throw new \Exception('delete() returned false - project was not deleted');
                }
                
                Log::info('Project deleted successfully', [
                    'project_id' => $projectId,
                    'project_title' => $projectTitle,
                ]);
            });

            Log::info('Admin deleted project', [
                'project_id' => $projectId,
                'project_title' => $projectTitle,
                'admin_id' => auth()->id()
            ]);

            return response()->json([
                'message' => "Projekt '{$projectTitle}' bol úspešne zmazaný",
                'deleted' => true,
                'project_id' => $projectId
            ]);
        } catch (\Exception $e) {
            Log::error('Admin delete project error: ' . $e->getMessage());
            Log::error('Stack trace: ' . $e->getTraceAsString());
            
            return response()->json([
                'error' => 'Nepodarilo sa zmazať projekt',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Create a new user (admin can register users with auto-verified email).
     */
    public function createUser(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:8',
            'student_type' => 'required|in:denny,externy',
        ]);

        try {
            $user = DB::transaction(function () use ($request) {
                $user = User::create([
                    'name' => $request->name,
                    'email' => $request->email,
                    'password' => Hash::make($request->password),
                    'student_type' => $request->student_type,
                    'role' => 'student', // Admin can only create students
                    'email_verified_at' => now(), // Auto-verify email
                    'failed_login_attempts' => 0,
                ]);

                return $user;
            });

            Log::info('Admin created new user', [
                'user_id' => $user->id,
                'user_email' => $user->email,
                'user_role' => $user->role,
                'admin_id' => auth()->id()
            ]);

            return response()->json([
                'message' => "Používateľ '{$user->name}' bol úspešne registrovaný",
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'student_type' => $user->student_type,
                    'role' => $user->role,
                    'email_verified_at' => $user->email_verified_at,
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('Admin create user error: ' . $e->getMessage());
            Log::error('Stack trace: ' . $e->getTraceAsString());
            
            return response()->json([
                'error' => 'Nepodarilo sa vytvoriť používateľa',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Move a user from one team to another (admin-only).
     * Auto-demotes user if they are Scrum Master in source team.
     */
    public function moveUserBetweenTeams(Request $request, User $user)
    {
        $request->validate([
            'from_team_id' => ['required', 'integer', 'exists:teams,id'],
            'to_team_id' => ['required', 'integer', 'exists:teams,id'],
            'occupation' => ['required', 'string', Rule::in(Occupation::values())],
        ]);

        try {
            $fromTeamId = (int) $request->from_team_id;
            $toTeamId = (int) $request->to_team_id;
            $occupation = $request->occupation;

            // Load teams
            $fromTeam = Team::findOrFail($fromTeamId);
            $toTeam = Team::findOrFail($toTeamId);

            // Validate user is in source team
            if (!$fromTeam->members()->where('user_id', $user->id)->exists()) {
                return response()->json([
                    'error' => 'Používateľ nie je členom zdrojového tímu',
                ], 404);
            }

            // Validate user is not already in target team
            if ($toTeam->members()->where('user_id', $user->id)->exists()) {
                return response()->json([
                    'error' => 'Používateľ je už členom cieľového tímu',
                ], 409);
            }

            // Validate target team has capacity
            if ($toTeam->members()->count() >= 10) {
                return response()->json([
                    'error' => 'Cieľový tím je plný (maximálne 10 členov)',
                ], 403);
            }

            // Validate student type matches target team type
            $toCodePrefix = substr($toTeam->invite_code, 0, 3);
            $targetType = ($toCodePrefix === 'DEN') ? 'denny' : (($toCodePrefix === 'EXT') ? 'externy' : null);
            
            if ($targetType && $user->student_type !== $targetType) {
                return response()->json([
                    'error' => 'Typ študenta používateľa sa nezhoduje s typom cieľového tímu',
                    'user_type' => $user->student_type,
                    'target_type' => $targetType,
                ], 400);
            }

            // Execute move in transaction
            $result = DB::transaction(function () use ($user, $fromTeam, $toTeam, $occupation) {
                // Get current role in source team
                $sourcePivot = $fromTeam->members()->where('user_id', $user->id)->first();
                $isSourceScrumMaster = $sourcePivot && $sourcePivot->pivot->role_in_team === 'scrum_master';
                $isSourceScrumMasterById = (int) $fromTeam->scrum_master_id === (int) $user->id;

                // Auto-demote if Scrum Master
                if ($isSourceScrumMaster || $isSourceScrumMasterById) {
                    // Find next member to promote to SM (oldest member by pivot created_at)
                    $nextSM = $fromTeam->members()
                        ->where('user_id', '!=', $user->id)
                        ->orderBy('team_user.created_at', 'asc')
                        ->first();

                    if ($nextSM) {
                        // Promote next member to SM
                        $fromTeam->members()->updateExistingPivot($nextSM->id, [
                            'role_in_team' => 'scrum_master'
                        ]);
                        $fromTeam->scrum_master_id = $nextSM->id;
                    } else {
                        // No other members - clear SM
                        $fromTeam->scrum_master_id = null;
                    }
                    $fromTeam->save();
                }

                // Detach from source team
                $fromTeam->members()->detach($user->id);

                // Attach to target team as member
                $toTeam->members()->attach($user->id, [
                    'role_in_team' => 'member',
                    'occupation' => $occupation,
                ]);

                return [
                    'from_team' => $fromTeam->fresh(['members']),
                    'to_team' => $toTeam->fresh(['members']),
                    'was_scrum_master' => $isSourceScrumMaster || $isSourceScrumMasterById,
                ];
            });

            Log::info('Admin moved user between teams', [
                'user_id' => $user->id,
                'user_name' => $user->name,
                'from_team_id' => $fromTeamId,
                'from_team_name' => $fromTeam->name,
                'to_team_id' => $toTeamId,
                'to_team_name' => $toTeam->name,
                'was_scrum_master' => $result['was_scrum_master'],
                'admin_id' => auth()->id(),
            ]);

            return response()->json([
                'message' => "Používateľ '{$user->name}' bol presunul z tímu '{$fromTeam->name}' do tímu '{$toTeam->name}'",
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                ],
                'was_scrum_master_in_source' => $result['was_scrum_master'],
                'new_sm_in_source' => $result['from_team']->scrum_master_id ? [
                    'id' => $result['from_team']->scrum_master_id,
                    'name' => $result['from_team']->members->firstWhere('id', $result['from_team']->scrum_master_id)->name ?? 'Unknown',
                ] : null,
            ]);
        } catch (\Exception $e) {
            Log::error('Admin move user error: ' . $e->getMessage());
            Log::error('Stack trace: ' . $e->getTraceAsString());

            return response()->json([
                'error' => 'Nepodarilo sa presunúť používateľa',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Deactivate a user account (admin-only).
     * Inactive users cannot log in or access the portal.
     */
    public function deactivateUser(User $user)
    {
        // Prevent deactivating admin accounts
        if ($user->isAdmin()) {
            return response()->json([
                'error' => 'Nie je možné deaktivovať administrátorský účet'
            ], 403);
        }

        // Already inactive
        if ($user->status === 'inactive') {
            return response()->json([
                'message' => 'Používateľ je už neaktívny',
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'status' => $user->status,
                ]
            ]);
        }

        try {
            $user->status = 'inactive';
            $user->save();

            // Revoke all active tokens to force immediate logout
            $user->tokens()->delete();

            Log::info('Admin deactivated user', [
                'user_id' => $user->id,
                'user_email' => $user->email,
                'admin_id' => auth()->id(),
            ]);

            return response()->json([
                'message' => "Používateľ '{$user->name}' bol deaktivovaný",
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'status' => $user->status,
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('Admin deactivate user error: ' . $e->getMessage());
            Log::error('Stack trace: ' . $e->getTraceAsString());

            return response()->json([
                'error' => 'Nepodarilo sa deaktivovať používateľa',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Activate a user account (admin-only).
     * Restores the user's ability to log in and access the portal.
     */
    public function activateUser(User $user)
    {
        // Already active
        if ($user->status === 'active') {
            return response()->json([
                'message' => 'Používateľ je už aktívny',
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'status' => $user->status,
                ]
            ]);
        }

        try {
            $user->status = 'active';
            $user->save();

            Log::info('Admin activated user', [
                'user_id' => $user->id,
                'user_email' => $user->email,
                'admin_id' => auth()->id(),
            ]);

            return response()->json([
                'message' => "Používateľ '{$user->name}' bol aktivovaný",
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'status' => $user->status,
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('Admin activate user error: ' . $e->getMessage());
            Log::error('Stack trace: ' . $e->getTraceAsString());

            return response()->json([
                'error' => 'Nepodarilo sa aktivovať používateľa',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Upload and import authorized students from CSV
     * 
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function importAuthorizedStudents(Request $request)
    {
        $request->validate([
            'csv_file' => ['required', 'file', 'mimes:csv,txt', 'max:5120'], // 5MB max
        ]);

        try {
            $file = $request->file('csv_file');
            $path = $file->getRealPath();
            
            $csv = array_map('str_getcsv', file($path));
            $header = array_shift($csv); // Remove header row
            $header = array_map(function ($h) {
                $h = (string) $h;
                $h = preg_replace('/^\xEF\xBB\xBF/', '', $h); // strip UTF-8 BOM
                return trim($h);
            }, $header ?? []);
            
            // Validate CSV headers
            $requiredHeaders = ['student_id', 'name', 'email', 'student_type'];
            $missingHeaders = array_diff($requiredHeaders, $header);
            
            if (!empty($missingHeaders)) {
                return response()->json([
                    'error' => 'Chýbajúce stĺpce v CSV: ' . implode(', ', $missingHeaders),
                    'required' => $requiredHeaders,
                ], 400);
            }
            
            $imported = 0;
            $updated = 0;
            $errors = [];
            $absolventCount = 0;
            $reactivatedCount = 0;
            
            DB::transaction(function () use ($csv, $header, &$imported, &$updated, &$errors, &$absolventCount, &$reactivatedCount) {
                foreach ($csv as $index => $row) {
                    $lineNumber = $index + 2; // +2 because header is row 1
                    
                    // Skip empty rows
                    if (empty(array_filter($row))) {
                        continue;
                    }

                    if (count($row) !== count($header)) {
                        $errors[] = "Riadok {$lineNumber}: Neplatný počet stĺpcov (očakávané " . count($header) . ", prijaté " . count($row) . ")";
                        continue;
                    }
                    
                    $data = array_combine($header, $row);
                    if ($data === false) {
                        $errors[] = "Riadok {$lineNumber}: Nepodarilo sa spracovať riadok CSV";
                        continue;
                    }
                    
                    // Validate row data
                    if (empty($data['student_id']) || empty($data['email'])) {
                        $errors[] = "Riadok {$lineNumber}: Chýba student_id alebo email";
                        continue;
                    }
                    
                    // Validate student_id format (7 digits)
                    if (!preg_match('/^\d{7}$/', $data['student_id'])) {
                        $errors[] = "Riadok {$lineNumber}: Neplatný formát student_id (musí mať 7 číslic)";
                        continue;
                    }
                    
                    // Validate email format
                    if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
                        $errors[] = "Riadok {$lineNumber}: Neplatný email formát";
                        continue;
                    }
                    
                    // Validate student_type
                    if (!in_array($data['student_type'], ['denny', 'externy'])) {
                        $errors[] = "Riadok {$lineNumber}: Neplatný student_type (musí byť 'denny' alebo 'externy')";
                        continue;
                    }
                    
                    // Insert or update
                    $student = \App\Models\AuthorizedStudent::updateOrCreate(
                        ['email' => trim($data['email'])],
                        [
                            'student_id' => trim($data['student_id']),
                            'name' => trim($data['name']),
                            'student_type' => $data['student_type'],
                            'is_active' => true,
                        ]
                    );
                    
                    if ($student->wasRecentlyCreated) {
                        $imported++;
                    } else {
                        $updated++;
                    }
                }

                // --- Absolvent marking logic ---
                // Collect all authorized student emails from the CSV
                $authorizedEmails = \App\Models\AuthorizedStudent::where('is_active', true)
                    ->pluck('email')
                    ->map(fn($e) => strtolower(trim($e)))
                    ->toArray();

                // Get all non-admin users
                $allUsers = User::where('role', '!=', 'admin')->get();

                foreach ($allUsers as $user) {
                    $userEmail = strtolower(trim($user->email));

                    if (!in_array($userEmail, $authorizedEmails)) {
                        // User NOT in CSV → mark as absolvent
                        if (!$user->is_absolvent) {
                            $user->is_absolvent = true;
                            $user->save();
                            $absolventCount++;
                        }
                    } else {
                        // User IS in CSV → clear absolvent flag if set
                        if ($user->is_absolvent) {
                            $user->is_absolvent = false;
                            $user->save();
                            $reactivatedCount++;
                        }
                    }
                }
            });
            
            Log::info('Authorized students imported', [
                'admin_id' => auth()->id(),
                'imported' => $imported,
                'updated' => $updated,
                'absolvents_marked' => $absolventCount ?? 0,
                'absolvents_cleared' => $reactivatedCount ?? 0,
                'errors_count' => count($errors),
            ]);
            
            $absolventMsg = '';
            if (($absolventCount ?? 0) > 0 || ($reactivatedCount ?? 0) > 0) {
                $absolventMsg = ", {$absolventCount} označených ako absolvent, {$reactivatedCount} obnovených";
            }

            return response()->json([
                'message' => "Import dokončený: {$imported} nových, {$updated} aktualizovaných" . $absolventMsg,
                'imported' => $imported,
                'updated' => $updated,
                'absolvents_marked' => $absolventCount ?? 0,
                'absolvents_cleared' => $reactivatedCount ?? 0,
                'errors' => $errors,
            ]);
            
        } catch (\Exception $e) {
            Log::error('CSV import failed', [
                'admin_id' => auth()->id(),
                'error' => $e->getMessage(),
            ]);
            
            return response()->json([
                'error' => 'Import zlyhal: ' . $e->getMessage(),
            ], 500);
        }
    }
    
    /**
     * Get list of all authorized students
     * 
     * @return \Illuminate\Http\JsonResponse
     */
    public function authorizedStudents()
    {
        $students = \App\Models\AuthorizedStudent::orderBy('name')
            ->paginate(50);
            
        return response()->json($students);
    }
    
    /**
     * Toggle authorized student active status
     * 
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function toggleAuthorizedStudent($id)
    {
        $student = \App\Models\AuthorizedStudent::findOrFail($id);
        $student->is_active = !$student->is_active;
        $student->save();
        
        Log::info('Authorized student toggled', [
            'admin_id' => auth()->id(),
            'student_id' => $student->id,
            'email' => $student->email,
            'is_active' => $student->is_active,
        ]);
        
        return response()->json([
            'message' => $student->is_active 
                ? "Študent '{$student->name}' bol aktivovaný" 
                : "Študent '{$student->name}' bol deaktivovaný",
            'student' => $student,
        ]);
    }

    /**
     * Get admin panel configuration
     * 
     * @return \Illuminate\Http\JsonResponse
     */
    public function getConfig()
    {
        return response()->json([
            'authorizationEnabled' => config('app.require_authorized_students'),
        ]);
    }
}


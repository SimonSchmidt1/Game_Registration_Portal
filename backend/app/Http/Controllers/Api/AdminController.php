<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Team;
use App\Models\Project;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class AdminController extends Controller
{
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
     * Check if project has export files
     */
    private function projectHasExport($project)
    {
        $files = $project->files ?? [];
        $hasExportFile = !empty($files['export']) || !empty($files['source_code']) || 
                         !empty($files['apk_file']) || !empty($files['ios_file']) ||
                         !empty($files['documentation']);
        $hasMetaExport = !empty($project->getMeta('export_path')) || !empty($project->getMeta('source_code_path'));
        return $hasExportFile || $hasMetaExport;
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
     * Get all teams with their project status indicators.
     */
    public function teams(Request $request)
    {
        try {
            // Build query with safe relationship loading
            // SoftDeletes automatically excludes deleted teams, but we'll be explicit
            $query = Team::with(['members', 'academicYear'])
                ->withCount('members');
            
            // Only load projects if relationship exists
            try {
                $query->with('projects')->withCount('projects');
            } catch (\Exception $e) {
                Log::warning('Projects relationship not available: ' . $e->getMessage());
            }

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

            // Transform teams with indicators
            $teamsWithIndicators = $teams->map(function ($team) {
                // Safely get projects (might not be loaded if relationship doesn't exist)
                $projects = collect([]);
                try {
                    if ($team->relationLoaded('projects')) {
                        $projects = $team->projects;
                    }
                } catch (\Exception $e) {
                    Log::warning('Could not load projects for team ' . $team->id . ': ' . $e->getMessage());
                }
                
                $hasProject = $projects->count() > 0;
                $hasVideo = $projects->filter(function ($p) {
                    return $p->video_path || $p->video_url;
                })->count() > 0;
                $hasSplash = $projects->filter(function ($p) {
                    return $p->splash_screen_path;
                })->count() > 0;
                $hasExport = $projects->filter(function ($p) {
                    $files = $p->files ?? [];
                    $hasExportFile = !empty($files['export']) || !empty($files['source_code']) || 
                                     !empty($files['apk_file']) || !empty($files['ios_file']) ||
                                     !empty($files['documentation']);
                    $hasMetaExport = !empty($p->getMeta('export_path')) || !empty($p->getMeta('source_code_path'));
                    return $hasExportFile || $hasMetaExport;
                })->count() > 0;

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
                    'projects_count' => $team->projects_count ?? $projects->count(),
                    'scrum_master' => $scrumMaster ? [
                        'id' => $scrumMaster->id,
                        'name' => $scrumMaster->name,
                        'email' => $scrumMaster->email,
                    ] : null,
                    'has_project' => $hasProject,
                    'has_video' => $hasVideo,
                    'has_splash' => $hasSplash,
                    'has_export' => $hasExport,
                    'created_at' => $team->created_at,
                    'updated_at' => $team->updated_at,
                ];
            });

            return response()->json([
                'teams' => $teamsWithIndicators,
                'total' => $teamsWithIndicators->count(),
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
     * Get detailed information about a specific team.
     */
    public function showTeam(Team $team)
    {
        try {
            $team->load(['members', 'academicYear', 'projects']);

            // Get projects with details
            $projects = $team->projects->map(function ($project) {
                return [
                    'id' => $project->id,
                    'title' => $project->title,
                    'type' => $project->type,
                    'school_type' => $project->school_type,
                    'subject' => $project->subject,
                    'has_video' => (bool) ($project->video_path || $project->video_url),
                    'has_splash' => (bool) $project->splash_screen_path,
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
}


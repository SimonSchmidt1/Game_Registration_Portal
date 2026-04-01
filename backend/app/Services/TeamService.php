<?php

namespace App\Services;

use App\Models\Team;
use App\Models\User;
use App\Enums\Occupation;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Schema;

class TeamService
{
    public function getTeamsStatusForUser(User $user)
    {
        $teams = $user->teams()->with(['members', 'academicYear'])->get();
        return $teams->map(function ($team) use ($user) {
            $pivot = $team->members()->where('user_id', $user->id)->first()?->pivot;
            $isScrumMaster = $pivot && $pivot->role_in_team === 'scrum_master';
            
            // Get team status (default to 'active' if column doesn't exist)
            $status = 'active';
            if (Schema::hasColumn('teams', 'status')) {
                $status = $team->status ?? 'active';
            }
            
            return [
                'id' => $team->id,
                'name' => $team->name,
                'invite_code' => $team->invite_code,
                'academic_year' => $team->academicYear,
                'members' => $team->members,
                'is_scrum_master' => $isScrumMaster,
                'status' => $status,
            ];
        });
    }

    public function createTeam(User $user, array $data): Team
    {
        // Defensive: validate inputs
        if (!$user || !$user->id) {
            throw new \InvalidArgumentException('Invalid user');
        }
        
        // Validate student type is set
        if (empty($user->student_type)) {
            throw new \InvalidArgumentException('Student type is required to create a team');
        }
        
        $data['name'] = trim($data['name'] ?? '');
        if (empty($data['name'])) {
            throw new \InvalidArgumentException('Team name is required');
        }
        
        if (empty($data['academic_year_id'])) {
            throw new \InvalidArgumentException('Academic year is required');
        }
        
        if (empty($data['occupation'])) {
            throw new \InvalidArgumentException('Occupation is required');
        }
        
        // Validate occupation
        $validOccupations = Occupation::values();
        if (!in_array($data['occupation'], $validOccupations)) {
            throw new \InvalidArgumentException('Invalid occupation. Must be one of: ' . implode(', ', $validOccupations));
        }
        
        // Use transaction to ensure atomic operation
        return \DB::transaction(function () use ($user, $data) {
            // Determine prefix based on student type
            $prefix = ($user->student_type === 'denny') ? 'DEN' : 'EXT';
            
            // Generate unique invite code with prefix (e.g., DENABC123 or EXTXYZ789)
            do {
                $randomPart = strtoupper(Str::random(6));
                $inviteCode = $prefix . $randomPart;
            } while (Team::where('invite_code', $inviteCode)->exists());

            $teamData = [
                'name' => $data['name'],
                'academic_year_id' => $data['academic_year_id'],
                'invite_code' => $inviteCode,
                'scrum_master_id' => $user->id,
            ];
            
            // Set status to 'pending' if status column exists (requires admin approval)
            if (Schema::hasColumn('teams', 'status')) {
                $teamData['status'] = 'pending';
            }
            
            $team = Team::create($teamData);
            
            $user->teams()->attach($team->id, [
                'role_in_team' => 'scrum_master',
                'occupation' => $data['occupation']
            ]);
            $team->load('members');
            
            \Log::info('Team created', ['team_id' => $team->id, 'user_id' => $user->id, 'occupation' => $data['occupation'], 'student_type' => $user->student_type, 'invite_code' => $inviteCode]);
            return $team;
        });
    }

    public function joinTeam(User $user, string $inviteCode, ?string $occupation = null)
    {
        // Defensive: validate inputs
        if (!$user || !$user->id) {
            return ['error' => 'invalid_user'];
        }
        
        // Validate user has student type set
        if (empty($user->student_type)) {
            return ['error' => 'student_type_required'];
        }
        
        $inviteCode = trim(strtoupper($inviteCode));
        if (empty($inviteCode)) {
            return ['error' => 'invalid_code'];
        }
        
        if (empty($occupation)) {
            return ['error' => 'occupation_required'];
        }
        
        // Validate occupation
        $validOccupations = Occupation::values();
        if (!in_array($occupation, $validOccupations)) {
            return ['error' => 'invalid_occupation'];
        }
        
        $team = Team::where('invite_code', $inviteCode)->first();
        if (!$team) {
            \Log::warning('Team join failed: team not found', ['code' => $inviteCode]);
            return ['error' => 'not_found'];
        }

        // Check if team is active - pending teams cannot accept new members
        if (Schema::hasColumn('teams', 'status')) {
            $teamStatus = $team->status ?? 'active';
            if ($teamStatus !== 'active') {
                \Log::info('Team join failed: team not active', ['team_id' => $team->id, 'status' => $teamStatus]);
                return ['error' => 'team_not_active', 'status' => $teamStatus];
            }
        }

        // Validate student type matches team type from invite code
        // International teams (SPE prefix) accept any student type
        $codePrefix = substr($inviteCode, 0, 3);
        $teamType = match($codePrefix) {
            'DEN' => 'denny',
            'EXT' => 'externy',
            'SPE' => 'international',
            default => null
        };
        
        // Skip student type check for international teams
        if ($teamType && $teamType !== 'international' && $user->student_type !== $teamType) {
            \Log::info('Team join failed: student type mismatch', [
                'user_id' => $user->id,
                'user_type' => $user->student_type,
                'team_type' => $teamType,
                'code' => $inviteCode
            ]);
            return [
                'error' => 'student_type_mismatch',
                'user_type' => $user->student_type,
                'team_type' => $teamType
            ];
        }

        if ($user->teams()->where('team_id', $team->id)->exists()) {
            return ['error' => 'already_member'];
        }

        $maxMembers = 10;
        if ($team->members()->count() >= $maxMembers) {
            \Log::info('Team join failed: team full', ['team_id' => $team->id]);
            return ['error' => 'full', 'max' => $maxMembers];
        }

        // Use transaction for atomic operation
        \DB::transaction(function () use ($user, $team, $occupation) {
            $user->teams()->attach($team->id, [
                'role_in_team' => 'member',
                'occupation' => $occupation
            ]);
        });
        
        $team->load('members');
        \Log::info('User joined team', ['user_id' => $user->id, 'team_id' => $team->id, 'occupation' => $occupation]);
        return ['team' => $team];
    }

    public function removeMember(User $authUser, Team $team, User $target)
    {
        // Defensive: validate inputs
        if (!$authUser || !$authUser->id || !$team || !$team->id || !$target || !$target->id) {
            return ['error' => 'invalid_input'];
        }

        // Check if team is active - pending teams cannot modify members
        if (Schema::hasColumn('teams', 'status')) {
            $teamStatus = $team->status ?? 'active';
            if ($teamStatus !== 'active') {
                \Log::info('Member removal failed: team not active', ['team_id' => $team->id, 'status' => $teamStatus]);
                return ['error' => 'team_not_active', 'status' => $teamStatus];
            }
        }
        
        $isScrumMasterByOwner = $team->scrum_master_id && ((int)$team->scrum_master_id === (int)$authUser->id);
        $isScrumMasterByPivot = $team->members()
            ->where('users.id', $authUser->id)
            ->where('team_user.role_in_team', 'scrum_master')
            ->exists();
        if (!($isScrumMasterByOwner || $isScrumMasterByPivot)) {
            \Log::warning('Unauthorized member removal attempt', ['auth_user' => $authUser->id, 'team' => $team->id]);
            return ['error' => 'forbidden'];
        }

        $pivot = $team->members()->where('users.id', $target->id)->first();
        if (!$pivot) {
            return ['error' => 'not_member'];
        }

        $isTargetScrumMaster = $team->members()
            ->where('users.id', $target->id)
            ->where('team_user.role_in_team', 'scrum_master')
            ->exists();
        if ($isTargetScrumMaster || ((int)$team->scrum_master_id === (int)$target->id)) {
            return ['error' => 'cannot_remove_scrum'];
        }

        // Use transaction for atomic operation
        \DB::transaction(function () use ($team, $target) {
            $team->members()->detach($target->id);
        });
        
        $team->load('members');
        \Log::info('Member removed from team', ['target_user' => $target->id, 'team' => $team->id]);
        return ['team' => $team];
    }

    public function leaveTeam(User $user, Team $team)
    {
        // Defensive: validate inputs
        if (!$user || !$user->id || !$team || !$team->id) {
            return ['error' => 'invalid_input'];
        }

        // Check if team is active - pending teams cannot modify members
        if (Schema::hasColumn('teams', 'status')) {
            $teamStatus = $team->status ?? 'active';
            if ($teamStatus !== 'active') {
                \Log::info('Leave team failed: team not active', ['team_id' => $team->id, 'status' => $teamStatus]);
                return ['error' => 'team_not_active', 'status' => $teamStatus];
            }
        }
        
        $pivot = $team->members()->where('users.id', $user->id)->first();
        if (!$pivot) {
            return ['error' => 'not_member'];
        }

        $isScrumMaster = $team->members()
            ->where('users.id', $user->id)
            ->where('team_user.role_in_team', 'scrum_master')
            ->exists();
        
        if ($isScrumMaster || ((int)$team->scrum_master_id === (int)$user->id)) {
            return ['error' => 'cannot_leave_as_scrum'];
        }

        // Use transaction for atomic operation
        \DB::transaction(function () use ($team, $user) {
            $team->members()->detach($user->id);
        });
        
        \Log::info('User left team', ['user_id' => $user->id, 'team_id' => $team->id]);
        return ['success' => true];
    }
}

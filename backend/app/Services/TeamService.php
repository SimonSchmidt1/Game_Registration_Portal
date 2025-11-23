<?php

namespace App\Services;

use App\Models\Team;
use App\Models\User;
use Illuminate\Support\Str;

class TeamService
{
    public function getTeamsStatusForUser(User $user)
    {
        $teams = $user->teams()->with(['members', 'academicYear'])->get();
        return $teams->map(function ($team) use ($user) {
            $pivot = $team->members()->where('user_id', $user->id)->first()?->pivot;
            $isScrumMaster = $pivot && $pivot->role_in_team === 'scrum_master';
            return [
                'id' => $team->id,
                'name' => $team->name,
                'invite_code' => $team->invite_code,
                'academic_year' => $team->academicYear,
                'members' => $team->members,
                'is_scrum_master' => $isScrumMaster,
            ];
        });
    }

    public function createTeam(User $user, array $data): Team
    {
        do {
            $inviteCode = Str::random(6);
        } while (Team::where('invite_code', $inviteCode)->exists());

        $team = Team::create([
            'name' => $data['name'],
            'academic_year_id' => $data['academic_year_id'],
            'invite_code' => strtoupper($inviteCode),
            'scrum_master_id' => $user->id,
        ]);
        $user->teams()->attach($team->id, ['role_in_team' => 'scrum_master']);
        $team->load('members');
        return $team;
    }

    public function joinTeam(User $user, string $inviteCode)
    {
        $team = Team::where('invite_code', $inviteCode)->first();
        if (!$team) {
            return ['error' => 'not_found'];
        }

        if ($user->teams()->where('team_id', $team->id)->exists()) {
            return ['error' => 'already_member'];
        }

        $maxMembers = 4;
        if ($team->members()->count() >= $maxMembers) {
            return ['error' => 'full', 'max' => $maxMembers];
        }

        $user->teams()->attach($team->id, ['role_in_team' => 'member']);
        $team->load('members');
        return ['team' => $team];
    }

    public function removeMember(User $authUser, Team $team, User $target)
    {
        $isScrumMasterByOwner = $team->scrum_master_id && ((int)$team->scrum_master_id === (int)$authUser->id);
        $isScrumMasterByPivot = $team->members()
            ->where('users.id', $authUser->id)
            ->where('team_user.role_in_team', 'scrum_master')
            ->exists();
        if (!($isScrumMasterByOwner || $isScrumMasterByPivot)) {
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

        $team->members()->detach($target->id);
        $team->load('members');
        return ['team' => $team];
    }
}

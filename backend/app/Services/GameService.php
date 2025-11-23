<?php

namespace App\Services;

use App\Models\Game;
use App\Models\Team;
use App\Models\GameRating;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;

class GameService
{
    public function createGame(User $user, Team $team, array $data, array $files): array
    {
        $pivotRow = DB::table('team_user')
            ->where('team_id', $team->id)
            ->where('user_id', $user->id)
            ->first();
        $isScrumMasterPivot = $pivotRow && $pivotRow->role_in_team === 'scrum_master';
        $isScrumMasterOwner = (int)$team->scrum_master_id === (int)$user->id;
        $pivotCorrection = null;

        // Auto-correct scenarios: owner is scrum master but pivot missing or wrong
        if ($isScrumMasterOwner && (!$pivotRow)) {
            // Attach pivot with scrum_master role
            $user->teams()->attach($team->id, ['role_in_team' => 'scrum_master']);
            $pivotCorrection = 'attached_scrum_master_pivot';
            $pivotRow = DB::table('team_user')
                ->where('team_id', $team->id)
                ->where('user_id', $user->id)
                ->first();
            $isScrumMasterPivot = true;
        } elseif ($isScrumMasterOwner && $pivotRow && $pivotRow->role_in_team !== 'scrum_master') {
            DB::table('team_user')
                ->where('team_id', $team->id)
                ->where('user_id', $user->id)
                ->update(['role_in_team' => 'scrum_master']);
            $pivotCorrection = 'updated_pivot_role_to_scrum_master';
            $isScrumMasterPivot = true;
        }

        $isAuthorized = $isScrumMasterPivot || $isScrumMasterOwner;
        if (!$isAuthorized) {
            return ['error' => 'not_scrum', 'debug' => [
                'user_id' => $user->id,
                'team_id' => $team->id,
                'is_scrum_master_pivot' => $isScrumMasterPivot,
                'is_scrum_master_owner' => $isScrumMasterOwner,
                'pivot_row_found' => (bool)$pivotRow,
                'pivot_role' => $pivotRow?->role_in_team,
                'team_scrum_master_id' => $team->scrum_master_id,
            ]];
        }
        if ($team->games()->exists()) {
            return ['error' => 'already_has_game'];
        }

        $game = new Game();
        $game->title = $data['title'];
        $game->description = $data['description'] ?? null;
        $game->release_date = $data['release_date'] ?? null;
        $game->team_id = $team->id;
        $game->academic_year_id = $team->academic_year_id;
        $game->category = $data['category'];

        if (isset($files['trailer']) && $files['trailer'] instanceof UploadedFile) {
            $game->trailer_path = $files['trailer']->store('games/trailers', 'public');
        } elseif (!empty($data['trailer_url'])) {
            $game->trailer_path = $data['trailer_url'];
        }
        if (isset($files['splash_screen']) && $files['splash_screen'] instanceof UploadedFile) {
            $game->splash_screen_path = $files['splash_screen']->store('games/splash_screens', 'public');
        }
        if (isset($files['source_code']) && $files['source_code'] instanceof UploadedFile) {
            $game->source_code_path = $files['source_code']->store('games/source_codes', 'public');
        }
        if (isset($files['export']) && $files['export'] instanceof UploadedFile) {
            $game->export_path = $files['export']->store('games/exports', 'public');
        }

        $game->save();
        return ['game' => $game];
    }

    public function listGames()
    {
        return Game::with('team.members', 'academicYear')
            ->get()
            ->map(function ($game) {
                $game->rating = $game->rating_count > 0 ? (float)$game->rating : 0.0;
                return $game;
            });
    }

    public function findGameWithRelations(int $id)
    {
        return Game::with('team.members', 'academicYear')->find($id);
    }

    public function getUserTeamGames(User $user)
    {
        $team = $user->teams()->first();
        if (!$team) {
            return ['games' => [], 'message' => 'Nie si v tíme.'];
        }
        return ['games' => $team->games()->get()];
    }

    public function incrementViews(Game $game): int
    {
        $game->increment('views');
        return $game->views;
    }

    public function rateGame(User $user, Game $game, int $rating)
    {
        $alreadyRated = GameRating::where('game_id', $game->id)
            ->where('user_id', $user->id)
            ->exists();
        if ($alreadyRated) {
            return ['error' => 'already_rated'];
        }
        GameRating::create([
            'game_id' => $game->id,
            'user_id' => $user->id,
            'rating' => $rating,
        ]);
        $avg = GameRating::where('game_id', $game->id)->avg('rating');
        $count = GameRating::where('game_id', $game->id)->count();
        $game->rating = round($avg, 1);
        $game->rating_count = $count;
        $game->save();
        return ['rating' => $game->rating, 'rating_count' => $game->rating_count];
    }

    public function getUserRating(User $user, Game $game)
    {
        $rating = GameRating::where('game_id', $game->id)
            ->where('user_id', $user->id)
            ->first();
        return [
            'hasRated' => (bool)$rating,
            'rating' => $rating?->rating,
            'average' => $game->rating,
            'rating_count' => $game->rating_count,
        ];
    }
}

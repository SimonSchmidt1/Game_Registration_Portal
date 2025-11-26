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
        // Defensive: validate inputs
        if (!$user || !$user->id) {
            return ['error' => 'invalid_user'];
        }
        
        if (!$team || !$team->id) {
            return ['error' => 'invalid_team'];
        }
        
        $data['title'] = trim($data['title'] ?? '');
        if (empty($data['title'])) {
            return ['error' => 'invalid_title'];
        }
        
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

        // Use transaction for atomic operation with file uploads
        try {
            $game = DB::transaction(function () use ($user, $team, $data, $files) {
                $game = new Game();
                $game->title = $data['title'];
                $game->description = trim($data['description'] ?? '') ?: null;
                $game->release_date = $data['release_date'] ?? null;
                $game->team_id = $team->id;
                $game->academic_year_id = $team->academic_year_id;
                $game->category = trim($data['category'] ?? '') ?: null;

                // Handle file uploads with enhanced validation
                if (isset($files['trailer']) && $files['trailer'] instanceof UploadedFile && $files['trailer']->isValid()) {
                    // Verify actual mime type from file content
                    $finfo = finfo_open(FILEINFO_MIME_TYPE);
                    $realMime = finfo_file($finfo, $files['trailer']->getRealPath());
                    finfo_close($finfo);
                    
                    $allowedVideoMimes = ['video/mp4', 'video/quicktime', 'video/x-msvideo', 'video/avi'];
                    if (!in_array($realMime, $allowedVideoMimes)) {
                        throw new \Exception('Invalid trailer file type: ' . $realMime);
                    }
                    
                    $game->trailer_path = $files['trailer']->store('games/trailers', 'public');
                } elseif (!empty($data['trailer_url'])) {
                    $game->trailer_path = filter_var($data['trailer_url'], FILTER_SANITIZE_URL);
                }
                
                if (isset($files['splash_screen']) && $files['splash_screen'] instanceof UploadedFile && $files['splash_screen']->isValid()) {
                    // Verify image dimensions and actual mime type
                    $imageInfo = getimagesize($files['splash_screen']->getRealPath());
                    if (!$imageInfo) {
                        throw new \Exception('Invalid splash screen: not a valid image');
                    }
                    
                    $allowedImageMimes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
                    if (!in_array($imageInfo['mime'], $allowedImageMimes)) {
                        throw new \Exception('Invalid splash screen mime type: ' . $imageInfo['mime']);
                    }
                    
                    // Check reasonable dimensions (e.g., not bigger than 8000x8000)
                    if ($imageInfo[0] > 8000 || $imageInfo[1] > 8000) {
                        throw new \Exception('Splash screen dimensions too large: ' . $imageInfo[0] . 'x' . $imageInfo[1]);
                    }
                    
                    $game->splash_screen_path = $files['splash_screen']->store('games/splash_screens', 'public');
                }
                
                if (isset($files['source_code']) && $files['source_code'] instanceof UploadedFile && $files['source_code']->isValid()) {
                    $game->source_code_path = $files['source_code']->store('games/source_codes', 'public');
                }
                
                if (isset($files['export']) && $files['export'] instanceof UploadedFile && $files['export']->isValid()) {
                    $game->export_path = $files['export']->store('games/exports', 'public');
                }

                $game->save();
                return $game;
            });
            
            \Log::info('Game created', ['game_id' => $game->id, 'user_id' => $user->id, 'team_id' => $team->id]);
            return ['game' => $game];
        } catch (\Exception $e) {
            \Log::error('Game creation failed', ['error' => $e->getMessage(), 'user_id' => $user->id, 'team_id' => $team->id]);
            return ['error' => 'creation_failed', 'message' => 'Failed to create game: ' . $e->getMessage()];
        }
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
        // Defensive: validate inputs
        if (!$user || !$user->id || !$game || !$game->id) {
            return ['error' => 'invalid_input'];
        }
        
        if ($rating < 1 || $rating > 5) {
            return ['error' => 'invalid_rating'];
        }
        
        $alreadyRated = GameRating::where('game_id', $game->id)
            ->where('user_id', $user->id)
            ->exists();
        if ($alreadyRated) {
            return ['error' => 'already_rated'];
        }
        
        // Use transaction for atomic rating update
        try {
            DB::transaction(function () use ($user, $game, $rating) {
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
            });
            
            \Log::info('Game rated', ['game_id' => $game->id, 'user_id' => $user->id, 'rating' => $rating]);
            return ['rating' => $game->rating, 'rating_count' => $game->rating_count];
        } catch (\Exception $e) {
            \Log::error('Rating failed', ['error' => $e->getMessage(), 'game_id' => $game->id, 'user_id' => $user->id]);
            return ['error' => 'rating_failed'];
        }
    }

    public function getUserRating(User $user, Game $game)
    {
        // Defensive: validate inputs
        if (!$user || !$user->id || !$game || !$game->id) {
            return [
                'hasRated' => false,
                'rating' => null,
                'average' => 0.0,
                'rating_count' => 0,
            ];
        }
        
        $rating = GameRating::where('game_id', $game->id)
            ->where('user_id', $user->id)
            ->first();
        return [
            'hasRated' => (bool)$rating,
            'rating' => $rating?->rating,
            'average' => $game->rating ?? 0.0,
            'rating_count' => $game->rating_count ?? 0,
        ];
    }
}

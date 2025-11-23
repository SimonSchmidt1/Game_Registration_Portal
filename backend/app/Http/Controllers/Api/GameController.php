<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Game;
use App\Models\Team;
use App\Models\GameRating;
use Illuminate\Support\Facades\Storage; // Pridávame pre prácu so súbormi
use App\Services\GameService;

class GameController extends Controller
{
    protected GameService $gameService;

    public function __construct(GameService $gameService)
    {
        $this->gameService = $gameService;
    }
    /**
     * Uloží novú hru. Dostupné len pre Scrum Mastera, pokiaľ tím ešte nemá hru.
     */
    public function store(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'release_date' => 'nullable|date',
            'team_id' => 'required|exists:teams,id',
            'category' => 'required|string|max:255',
            'trailer' => 'nullable|file|mimes:mp4,mov,avi|max:20480',
            'trailer_url' => 'nullable|url|max:255',
            'splash_screen' => 'nullable|image|max:5120',
            'source_code' => 'nullable|file|mimes:zip,rar,7z|max:51200',
            'export' => 'nullable|file|mimes:zip,exe,apk|max:51200',
        ]);

        $user = $request->user();
        $team = Team::findOrFail($request->team_id);

        $files = [
            'trailer' => $request->file('trailer'),
            'splash_screen' => $request->file('splash_screen'),
            'source_code' => $request->file('source_code'),
            'export' => $request->file('export'),
        ];

        $result = $this->gameService->createGame($user, $team, $request->all(), $files);

        if (isset($result['error'])) {
            if ($result['error'] === 'not_scrum') {
                return response()->json([
                    'message' => 'Hru môže pridať iba Scrum Master tímu.',
                    'debug' => $result['debug'] ?? []
                ], 403);
            }
            if ($result['error'] === 'already_has_game') {
                return response()->json(['message' => 'Tím už má pridelenú hru. Na zmenu použite úpravu (edit).'], 422);
            }
        }

        return response()->json(['game' => $result['game']], 201);
    }

    public function index(Request $request)
    {
        $games = $this->gameService->listGames();
        return response()->json($games);
    }

    // 🔹 Jedna konkrétna hra podľa ID
    public function show(Request $request, $id)
    {
        $game = $this->gameService->findGameWithRelations((int)$id);
        if (!$game) {
            return response()->json(['message' => 'Hra nebola nájdená.'], 404);
        }
        return response()->json(['game' => $game]);
    }

    // 🔹 Získať hry tímu (pre prihláseného člena)
    public function myGames(Request $request)
    {
        $user = $request->user();
        $result = $this->gameService->getUserTeamGames($user);
        return response()->json($result, 200);
    }

    // 🔹 Zvýšiť počet zobrazení hry
    public function incrementViews(Request $request, $id)
    {
        $game = Game::find($id);
        if (!$game) {
            return response()->json(['message' => 'Hra nebola nájdená.'], 404);
        }
        $views = $this->gameService->incrementViews($game);
        return response()->json(['views' => $views], 200);
    }

    // 🔹 Ohodnotenie hry používateľom (iba raz)
    public function rate(Request $request, $id)
    {
        $game = Game::find($id);
        if (!$game) {
            return response()->json(['message' => 'Hra nebola nájdená.'], 404);
        }
        $request->validate([
            'rating' => 'required|integer|min:1|max:5'
        ]);
        $user = $request->user();
        $result = $this->gameService->rateGame($user, $game, (int)$request->rating);
        if (isset($result['error']) && $result['error'] === 'already_rated') {
            return response()->json(['message' => 'Túto hru už nemôžeš znovu hodnotiť.'], 422);
        }
        return response()->json([
            'message' => 'Hodnotenie uložené.',
            'rating' => $result['rating'],
            'rating_count' => $result['rating_count']
        ], 201);
    }

    // 🔹 Zistenie či používateľ už hodnotil hru
    public function userRating(Request $request, $id)
    {
        $game = Game::find($id);
        if (!$game) {
            return response()->json(['message' => 'Hra nebola nájdená.'], 404);
        }
        $user = $request->user();
        $result = $this->gameService->getUserRating($user, $game);
        return response()->json($result);
    }
}
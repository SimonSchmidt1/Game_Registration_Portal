<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Game;
use App\Models\Team;
use App\Models\GameRating;
use Illuminate\Support\Facades\Storage; // Pridávame pre prácu so súbormi

class GameController extends Controller
{
    /**
     * Uloží novú hru. Dostupné len pre Scrum Mastera, pokiaľ tím ešte nemá hru.
     */
    public function store(Request $request)
    {
        // 1. Validácia vstupu
        $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'release_date' => 'nullable|date',
            'team_id' => 'required|exists:teams,id',
            'category' => 'required|string|max:255',
            
            'trailer' => 'nullable|file|mimes:mp4,mov,avi|max:20480', // 20MB
            'trailer_url' => 'nullable|url|max:255',
            'splash_screen' => 'nullable|image|max:5120', // 5MB
            'source_code' => 'nullable|file|mimes:zip,rar,7z|max:51200', // 50MB
            'export' => 'nullable|file|mimes:zip,exe,apk|max:51200', // 50MB
        ]);

        $user = $request->user();
        
        // Zistenie tímu, ku ktorému sa má hra priradiť
        $team = Team::findOrFail($request->team_id);

        // --- KONTROLA OPRÁVNENIA (Scrum Master) ---
        // Používame DB query pre spoľahlivé overenie role v pivotnej tabuľke
        $isScrumMaster = \DB::table('team_user')
            ->where('team_id', $team->id)
            ->where('user_id', $user->id)
            ->where('role_in_team', 'scrum_master')
            ->exists();

        if (!$isScrumMaster) {
            // Ak zlyhá, vrátime chybu
            return response()->json([
                'message' => 'Hru môže pridať iba Scrum Master tímu.',
                'debug' => [
                    'user_id' => $user->id,
                    'team_id' => $team->id,
                    'is_scrum_master' => $isScrumMaster
                ]
            ], 403);
        }
        // -----------------------------------------------------

        // 3. Kontrola, či tím už má hru (Tím môže mať len jednu registrovanú hru)
        if ($team->games()->exists()) {
            return response()->json(['message' => 'Tím už má pridelenú hru. Na zmenu použite úpravu (edit).'], 422);
        }

        // 4. Vytvorenie inštancie hry
        $game = new Game();
        $game->title = $request->title;
        $game->description = $request->description;
        $game->release_date = $request->release_date;
        $game->team_id = $team->id;
        // Uistite sa, že academic_year_id je správne, ak ho model Team nemá, musíte ho poslať cez request.
        // Predpokladáme, že Team ho má.
        $game->academic_year_id = $team->academic_year_id; 
        $game->category = $request->input('category');

        // 5. Spracovanie traileru (súbor má prednosť pred URL)
        if ($request->hasFile('trailer')) {
            $game->trailer_path = $request->file('trailer')->store('games/trailers', 'public');
        } elseif ($request->filled('trailer_url')) {
            $game->trailer_path = $request->input('trailer_url');
        }

        // 6. Spracovanie ostatných súborov
        if ($request->hasFile('splash_screen')) {
            $game->splash_screen_path = $request->file('splash_screen')->store('games/splash_screens', 'public');
        }
        if ($request->hasFile('source_code')) {
            $game->source_code_path = $request->file('source_code')->store('games/source_codes', 'public');
        }
        if ($request->hasFile('export')) {
            $game->export_path = $request->file('export')->store('games/exports', 'public');
        }

        $game->save();

        return response()->json(['game' => $game], 201);
    }

    public function index(Request $request)
    {
        $games = Game::with('team.members', 'academicYear')
            ->get()
            ->map(function ($game) {
                // Ensure rating and rating_count reflect current data if ratings exist
                $game->rating = $game->rating_count > 0 ? (float) $game->rating : 0.0;
                return $game;
            });
        return response()->json($games);
    }

    // 🔹 Jedna konkrétna hra podľa ID
    public function show(Request $request, $id)
    {
        $game = Game::with('team.members','academicYear')->find($id);
        if (!$game) {
            return response()->json(['message' => 'Hra nebola nájdená.'], 404);
        }
        // rating už cache-ovaný v stĺpci rating, rating_count počty hlasov
        return response()->json(['game' => $game]);
    }

    // 🔹 Získať hry tímu (pre prihláseného člena)
    public function myGames(Request $request)
    {
        $user = $request->user();
        $team = $user->teams()->first();

        if (!$team) {
            // Používateľ nie je v tíme, vrátime prázdny zoznam alebo vhodnú správu
            return response()->json(['games' => [], 'message' => 'Nie si v tíme.'], 200); 
        }

        // Načítame hru tímu (Tím by mal mať len jednu hru, ale relácia je one-to-many)
        $games = $team->games()->get();

        return response()->json(['games' => $games], 200);
    }

    // 🔹 Zvýšiť počet zobrazení hry
    public function incrementViews(Request $request, $id)
    {
        $game = Game::find($id);

        if (!$game) {
            return response()->json(['message' => 'Hra nebola nájdená.'], 404);
        }

        $game->increment('views');

        return response()->json(['views' => $game->views], 200);
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

        $alreadyRated = GameRating::where('game_id', $game->id)
            ->where('user_id', $user->id)
            ->exists();

        if ($alreadyRated) {
            return response()->json(['message' => 'Túto hru už nemôžeš znovu hodnotiť.'], 422);
        }

        GameRating::create([
            'game_id' => $game->id,
            'user_id' => $user->id,
            'rating' => (int) $request->rating
        ]);

        // Recalculate average and update cached columns
        $avg = GameRating::where('game_id', $game->id)->avg('rating');
        $count = GameRating::where('game_id', $game->id)->count();
        $game->rating = round($avg, 1);
        $game->rating_count = $count;
        $game->save();

        return response()->json([
            'message' => 'Hodnotenie uložené.',
            'rating' => $game->rating,
            'rating_count' => $game->rating_count
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
        $rating = GameRating::where('game_id', $game->id)
            ->where('user_id', $user->id)
            ->first();
        return response()->json([
            'hasRated' => (bool) $rating,
            'rating' => $rating?->rating,
            'average' => $game->rating,
            'rating_count' => $game->rating_count
        ]);
    }
}
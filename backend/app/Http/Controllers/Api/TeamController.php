<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Team;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule; 

class TeamController extends Controller
{
    /**
     * Vráti informácie o tíme, v ktorom je prihlásený používateľ.
     * Využíva sa pre frontend endpoint /api/user/team.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getTeamStatus(Request $request)
    {
        // Kód zostáva rovnaký
        $user = $request->user();
        $team = $user->teams()->first();
        if ($team) {
            // Načítame aj členov, aby bol frontend konzistentný
            $team->load('members:id,name');
            // Zistíme, či je používateľ Scrum Master (z pivot tabuľky)
            $pivot = $team->members()->where('user_id', $user->id)->first()?->pivot;
            $isScrumMaster = $pivot && strtolower($pivot->role_in_team) === 'scrum master';
            return response()->json([
                'team' => $team,
                'is_scrum_master' => $isScrumMaster,
            ]);
        }
        return response()->json(['team' => null, 'is_scrum_master' => false]);
    }


    /**
     * Vytvorí nový tím. (Implementácia store)
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        // Kód s pridaným importom Rule je teraz funkčný.
        $request->validate([
            'name' => ['required', 'string', 'max:255', Rule::unique('teams', 'name')],
            'academic_year_id' => 'required|exists:academic_years,id',
        ]);

        $user = $request->user();

        // 1. Kontrola, či už je používateľ v nejakom tíme (v pivot tabuľke)
        if ($user->teams()->exists()) {
            return response()->json(['message' => 'Už ste členom tímu.'], 409);
        }

        // 2. Vytvorenie unikátneho pozývacieho kódu
        do {
            $inviteCode = \Illuminate\Support\Str::random(6);
        } while (Team::where('invite_code', $inviteCode)->exists());

        // 3. Vytvorenie tímu
        $team = Team::create([
            'name' => $request->name,
            'academic_year_id' => $request->academic_year_id,
            'invite_code' => strtoupper($inviteCode),
            'scrum_master_id' => $user->id, // Zakladateľ je Scrum Master
        ]);

        // 4. Správne priradenie používateľa k tímu pomocou PIVOT TABUĽKY
        $user->teams()->attach($team->id, ['role_in_team' => 'scrum_master']);

        // Eager loading členov pre konzistenciu s metódou join
        $team->load('members:id,name');

        return response()->json([
            'message' => 'Tím bol úspešne vytvorený.',
            'team' => $team
        ], 201);
    }


    /**
     * Pripojí užívateľa k tímu pomocou kódu. (Implementácia join)
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function join(Request $request)
    {
        $request->validate([
            'invite_code' => 'required|string|size:6|exists:teams,invite_code',
        ]);

        $user = $request->user();
        $team = Team::where('invite_code', $request->invite_code)->first();
        
        // 1. Kontrola, či už je používateľ v nejakom tíme
        if ($user->teams()->exists()) {
            return response()->json(['message' => 'Už ste členom tímu.'], 409);
        }

        // 2. Kontrola limitu členov (MAX 4)
        $maxMembers = 4;
        // Táto kontrola teraz spoľahlivo funguje vďaka oprave v Team.php
        if ($team->members()->count() >= $maxMembers) { 
            return response()->json(['message' => 'Tím je plný. Maximálny počet členov je ' . $maxMembers . '.'], 403);
        }

        // 3. Správne priradenie používateľa k tímu pomocou PIVOT TABUĽKY
        $user->teams()->attach($team->id, ['role_in_team' => 'member']);

        // ✅ KRITICKÁ OPRAVA: Načítame členov tímu (iba ID a meno) pre frontend
        // To zaručí, že Vue komponent bude mať objekt members a nehavaruje.
        $team->load('members:id,name'); 

        return response()->json([
            'message' => 'Úspešne ste sa pripojili k tímu ' . $team->name . '.',
            'team' => $team, // Objekt $team teraz obsahuje zoznam členov
        ]);
    }
}
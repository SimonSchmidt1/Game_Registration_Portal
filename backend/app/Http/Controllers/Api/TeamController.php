<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Team;
use App\Models\User;
use App\Services\TeamService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule; 

class TeamController extends Controller
{
    public function __construct(private TeamService $teamService) {}
    /**
     * Vráti informácie o všetkých tímoch, v ktorých je prihlásený používateľ.
     * Využíva sa pre frontend endpoint /api/user/team.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getTeamStatus(Request $request)
    {
        $user = $request->user();
        $teamsWithRole = $this->teamService->getTeamsStatusForUser($user);
        return response()->json(['teams' => $teamsWithRole]);
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
        $team = $this->teamService->createTeam($user, $request->only('name','academic_year_id'));
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
        $result = $this->teamService->joinTeam($user, $request->invite_code);
        if (isset($result['error'])) {
            return match($result['error']) {
                'already_member' => response()->json(['message' => 'Už ste členom tohto tímu.'], 409),
                'full' => response()->json(['message' => 'Tím je plný. Maximálny počet členov je ' . $result['max'] . '.'], 403),
                'not_found' => response()->json(['message' => 'Tím nebol nájdený.'], 404),
                default => response()->json(['message' => 'Chyba pri pripájaní k tímu.'], 400),
            };
        }
        $team = $result['team'];
        return response()->json([
            'message' => 'Úspešne ste sa pripojili k tímu ' . $team->name . '.',
            'team' => $team,
        ]);
    }

    /**
     * Odstráni člena z tímu (len pre Scrum Mastera daného tímu).
     */
    public function removeMember(Request $request, Team $team, User $user)
    {
        $authUser = $request->user();
        $result = $this->teamService->removeMember($authUser, $team, $user);
        if (isset($result['error'])) {
            return match($result['error']) {
                'forbidden' => response()->json(['message' => 'Nemáte oprávnenie spravovať členov tohto tímu.'], 403),
                'not_member' => response()->json(['message' => 'Používateľ nie je členom tímu.'], 404),
                'cannot_remove_scrum' => response()->json(['message' => 'Scrum Mastera nie je možné odstrániť.'], 422),
                default => response()->json(['message' => 'Chyba pri odstraňovaní člena.'], 400),
            };
        }
        $team = $result['team'];
        return response()->json([
            'message' => 'Člen bol odstránený z tímu.',
            'team' => $team,
        ]);
    }
}
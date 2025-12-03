<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Team;
use App\Models\User;
use App\Services\TeamService;
use App\Enums\Occupation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Schema;
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
            'occupation' => ['required', 'string', Rule::in(Occupation::values())],
        ]);

        $user = $request->user();
        try {
            $team = $this->teamService->createTeam($user, $request->only('name', 'academic_year_id', 'occupation'));
            
            // Check if team requires approval
            $requiresApproval = Schema::hasColumn('teams', 'status') && ($team->status === 'pending');
            $message = $requiresApproval 
                ? 'Tím bol úspešne vytvorený a čaká na schválenie administrátorom. Po schválení budete môcť publikovať projekty.'
                : 'Tím bol úspešne vytvorený.';
            
            return response()->json([
                'message' => $message,
                'team' => $team,
                'requires_approval' => $requiresApproval
            ], 201);
        } catch (\InvalidArgumentException $e) {
            return response()->json([
                'message' => $e->getMessage()
            ], 400);
        }
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
            'invite_code' => 'required|string|size:6',
            'occupation' => ['required', 'string', Rule::in(Occupation::values())],
        ]);

        $user = $request->user();
        $result = $this->teamService->joinTeam($user, $request->invite_code, $request->occupation);
        if (isset($result['error'])) {
            return match($result['error']) {
                'already_member' => response()->json(['message' => 'Už ste členom tohto tímu.'], 409),
                'full' => response()->json(['message' => 'Tím je plný. Maximálny počet členov je ' . ($result['max'] ?? 10) . '.'], 403),
                'not_found' => response()->json(['message' => 'Tím s týmto kódom nebol nájdený. Skontrolujte správnosť kódu.'], 404),
                'invalid_code' => response()->json(['message' => 'Neplatný kód tímu.'], 400),
                'invalid_user' => response()->json(['message' => 'Neplatný používateľ.'], 400),
                'occupation_required' => response()->json(['message' => 'Povinné je zadať povolanie.'], 400),
                'invalid_occupation' => response()->json(['message' => 'Neplatné povolanie. Musí byť jedno z: ' . implode(', ', Occupation::values())], 400),
                default => response()->json(['message' => 'Chyba pri pripájaní k tímu. Skúste to znova.'], 500),
            };
        }
        $team = $result['team'] ?? null;
        if (!$team) {
            return response()->json(['message' => 'Nepodarilo sa načítať informácie o tíme.'], 500);
        }
        return response()->json([
            'message' => 'Úspešne ste sa pripojili k tímu ' . $team->name . '.',
            'team' => $team,
        ]);
    }

    /**
     * Zobrazí detail tímu (vrátane členov a akademického roka).
     */
    public function show(Team $team)
    {
        $team->load(['members', 'academicYear']);
        
        // Ensure pivot data and student_type are included in the response
        $teamData = $team->toArray();
        if (isset($teamData['members'])) {
            $teamData['members'] = $team->members->map(function ($member) {
                $memberArray = $member->toArray();
                // Explicitly ensure student_type is included
                $memberArray['student_type'] = $member->student_type;
                if ($member->pivot) {
                    $memberArray['pivot'] = [
                        'role_in_team' => $member->pivot->role_in_team,
                        'occupation' => $member->pivot->occupation,
                    ];
                }
                return $memberArray;
            })->toArray();
        }
        
        return response()->json($teamData);
    }

    /**
     * Odstráni člena z tímu (len pre Scrum Mastera daného tímu).
     */
    public function removeMember(Request $request, Team $team, User $user)
    {
        $authUser = $request->user();
        
        return \DB::transaction(function () use ($authUser, $team, $user) {
            $result = $this->teamService->removeMember($authUser, $team, $user);
            if (isset($result['error'])) {
                return match($result['error']) {
                    'forbidden' => response()->json(['message' => 'Nemáte oprávnenie spravovať členov tohto tímu. Musíte byť Scrum Master.'], 403),
                    'not_member' => response()->json(['message' => 'Používateľ nie je členom tímu.'], 404),
                    'cannot_remove_scrum' => response()->json(['message' => 'Scrum Mastera nie je možné odstrániť. Najprv musí byť prenesená rola.'], 422),
                    'invalid_input' => response()->json(['message' => 'Neplatné vstupné údaje.'], 400),
                    default => response()->json(['message' => 'Chyba pri odstraňovaní člena. Skúste to znova.'], 500),
                };
            }
            $team = $result['team'] ?? null;
            if (!$team) {
                return response()->json(['message' => 'Nepodarilo sa načítať informácie o tíme.'], 500);
            }
            return response()->json([
                'message' => 'Člen bol odstránený z tímu.',
                'team' => $team,
            ]);
        });
    }

    /**
     * Používateľ opustí tím (len ak nie je Scrum Master).
     */
    public function leave(Request $request, Team $team)
    {
        $user = $request->user();
        
        return \DB::transaction(function () use ($user, $team) {
            $result = $this->teamService->leaveTeam($user, $team);
            if (isset($result['error'])) {
                return match($result['error']) {
                    'not_member' => response()->json(['message' => 'Nie ste členom tohto tímu.'], 404),
                    'cannot_leave_as_scrum' => response()->json(['message' => 'Scrum Master nemôže opustiť tím. Najprv musíte preniesť rolu alebo zrušiť tím.'], 422),
                    'invalid_input' => response()->json(['message' => 'Neplatné vstupné údaje.'], 400),
                    default => response()->json(['message' => 'Chyba pri opúšťaní tímu. Skúste to znova.'], 500),
                };
            }
            return response()->json([
                'message' => 'Úspešne ste opustili tím.',
            ]);
        });
    }
}
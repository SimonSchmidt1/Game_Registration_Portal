<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\TeamController;
use App\Http\Controllers\Api\GameController;
use App\Http\Controllers\Api\ProjectController;
use App\Models\AcademicYear;

// 🟢 Verejné routy – dostupné bez tokenu
Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:login');
Route::post('/admin/login', [AuthController::class, 'adminLogin'])->middleware('throttle:login'); // Special admin login
Route::post('/register', [AuthController::class, 'register'])->middleware('throttle:register');
Route::post('/verify-email', [AuthController::class, 'verifyEmail']); // Verifikácia e-mailu
Route::post('/forgot-password', [AuthController::class, 'forgotPassword']); // Zabudnuté heslo
Route::post('/reset-password', [AuthController::class, 'resetPassword']); // Reset hesla s tokenom
Route::post('/login-temporary', [AuthController::class, 'loginWithTemporaryPassword']); // Prihlásenie s dočasným heslom

// 🟡 Chránené routy – vyžadujú autentifikáciu
Route::middleware('auth:sanctum')->group(function () {
    
    Route::get('/user', [AuthController::class, 'user']);
    Route::post('/user/avatar', [AuthController::class, 'updateAvatar']);
    Route::put('/user', [AuthController::class, 'updateProfile']);
    Route::put('/user/password', [AuthController::class, 'updatePassword']);

    Route::post('/logout', [AuthController::class, 'logout']);

    Route::get('/user/team', [TeamController::class, 'getTeamStatus']);
    Route::post('/teams', [TeamController::class, 'store']);     // založenie tímu (Scrum Master)
    Route::post('/teams/join', [TeamController::class, 'join']);  // pripojenie k tímu
    
    // 🎮 Projects (nové - nahrádzajú games)
    Route::get('/projects', [ProjectController::class, 'index']);  // Získanie všetkých projektov (s filtrami)
    Route::post('/projects', [ProjectController::class, 'store']) ->middleware('throttle:projects'); // Pridanie projektu
    Route::get('/projects/my', [ProjectController::class, 'my']); // Projekty aktivneho timu (MUST be before {id})
    Route::get('/projects/{id}', [ProjectController::class, 'show']); // Jeden projekt podľa ID
    Route::match(['PUT', 'POST'], '/projects/{id}', [ProjectController::class, 'update'])->middleware('throttle:projects'); // Aktualizácia projektu (Scrum Master) - accepts both PUT and POST (with _method=PUT)
    Route::post('/projects/{id}/views', [ProjectController::class, 'incrementViews']);  // Zvýšenie počtu zobrazení
    Route::post('/projects/{id}/rate', [ProjectController::class, 'rate'])->middleware('throttle:ratings'); // Hodnotenie projektu
    Route::get('/projects/{id}/user-rating', [ProjectController::class, 'getUserRating']); // Hodnotenie používateľa
    
    /**
     * LEGACY GAME ROUTES DEPRECATED (2025-11-23)
     * Multi-project system supersedes these endpoints. Uncomment only if rollback needed.
     */
    // Route::post('/games', [GameController::class, 'store']);
    // Route::get('/games/my', [GameController::class, 'myGames']);
    // Route::get('/games', [GameController::class, 'index']);
    // Route::get('/games/{id}', [GameController::class, 'show']);
    // Route::post('/games/{id}/views', [GameController::class, 'incrementViews']);
    // Route::post('/games/{id}/rate', [GameController::class, 'rate']);
    // Route::get('/games/{id}/user-rating', [GameController::class, 'userRating']);

    // 🔹 Endpoint na získanie akademických rokov
    Route::get('/academic-years', function() {
        return AcademicYear::all();
    });

    // 🔹 Detail tímu (vrátane členov)
    Route::get('/teams/{team}', [TeamController::class, 'show']);

    // 🔹 Správa členov tímu (iba Scrum Master)
    Route::delete('/teams/{team}/members/{user}', [TeamController::class, 'removeMember']);
    
    // 🔹 Opustenie tímu (ak nie si Scrum Master)
    Route::post('/teams/{team}/leave', [TeamController::class, 'leave']);
});

// 🔴 Admin Routes - Protected by admin middleware
// Ready for any future admin functionality (user management, analytics, etc.)
Route::prefix('admin')->middleware(['auth:sanctum', 'admin'])->group(function () {
    // Placeholder endpoint - replace/expand based on actual admin requirements
    Route::get('/dashboard', function (Request $request) {
        return response()->json([
            'message' => 'Admin prístup aktívny',
            'user' => $request->user()->only(['id', 'name', 'email', 'role']),
        ]);
    });
    
    // Future admin endpoints go here:
    // Route::get('/users', [AdminUserController::class, 'index']);
    // Route::get('/teams', [AdminTeamController::class, 'index']);
    // Route::get('/projects', [AdminProjectController::class, 'index']);
    // Route::get('/analytics', [AdminAnalyticsController::class, 'index']);
});

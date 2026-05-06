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
Route::post('/verify-email', [AuthController::class, 'verifyEmail'])->middleware('throttle:6,1');
Route::post('/resend-verification', [AuthController::class, 'resendVerification'])->middleware('throttle:resend-verification'); // Verifikácia e-mailu
Route::post('/forgot-password', [AuthController::class, 'forgotPassword'])->middleware('throttle:forgot-password'); // Zabudnuté heslo (rate-limited)
Route::post('/reset-password', [AuthController::class, 'resetPassword'])->middleware('throttle:6,1'); // Reset hesla s tokenom
Route::post('/login-temporary', [AuthController::class, 'loginWithTemporaryPassword'])->middleware('throttle:login'); // Prihlásenie s dočasným heslom

// 🟢 Verejné guest routy – dostupné bez tokenu
Route::get('/public/projects', [ProjectController::class, 'publicIndex']);
Route::get('/public/projects/top-rated', [ProjectController::class, 'topRated']);
Route::get('/public/projects/{id}', [ProjectController::class, 'publicShow']);
Route::post('/public/projects/{id}/views', [ProjectController::class, 'incrementViews'])->middleware('throttle:views');
Route::post('/public/projects/{id}/rate', [ProjectController::class, 'ratePublic'])->middleware('throttle:ratings');

// 🟡 Chránené routy – vyžadujú autentifikáciu
Route::middleware('auth:sanctum')->group(function () {
    
    Route::get('/user', [AuthController::class, 'user']);
    Route::post('/user/avatar', [AuthController::class, 'updateAvatar']);
    Route::post('/user/avatar/remove', [AuthController::class, 'removeAvatar']);
    Route::put('/user', [AuthController::class, 'updateProfile']);
    Route::put('/user/password', [AuthController::class, 'updatePassword']);

    Route::post('/logout', [AuthController::class, 'logout']);

    Route::get('/user/team', [TeamController::class, 'getTeamStatus']);
    Route::post('/teams', [TeamController::class, 'store']);     // založenie tímu (Scrum Master)
    Route::post('/teams/join', [TeamController::class, 'join']);  // pripojenie k tímu
    Route::put('/teams/{team}/rename', [TeamController::class, 'rename']); // Zmena nazvu timu
        
    // 🎮 Projects (nové - nahrádzajú games)
    Route::get('/projects', [ProjectController::class, 'index']);  // Získanie všetkých projektov (s filtrami)
    Route::post('/projects', [ProjectController::class, 'store']) ->middleware('throttle:projects'); // Pridanie projektu
    Route::get('/projects/top-rated', [ProjectController::class, 'topRated']); // Najlepšie hodnotené projekty
    Route::get('/projects/my', [ProjectController::class, 'my']); // Projekty aktivneho timu (MUST be before {id})
    Route::get('/projects/{id}', [ProjectController::class, 'show']); // Jeden projekt podľa ID
    Route::match(['PUT', 'POST'], '/projects/{id}', [ProjectController::class, 'update'])->middleware('throttle:projects'); // Aktualizácia projektu (Scrum Master) - accepts both PUT and POST (with _method=PUT)
    Route::post('/projects/{id}/views', [ProjectController::class, 'incrementViews']); // Zvýšenie počtu zobrazení
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
Route::prefix('admin')->middleware(['auth:sanctum', 'admin'])->group(function () {
    // Dashboard stats
    Route::get('/stats', [App\Http\Controllers\Api\AdminController::class, 'stats']);
    
    // Teams management
    Route::get('/teams', [App\Http\Controllers\Api\AdminController::class, 'teams']);
    Route::post('/teams', [App\Http\Controllers\Api\AdminController::class, 'createTeam']);
    Route::get('/teams/{team}', [App\Http\Controllers\Api\AdminController::class, 'showTeam']);
    Route::get('/teams/{team}/projects', [App\Http\Controllers\Api\AdminController::class, 'teamProjects']);
    Route::put('/teams/{team}', [App\Http\Controllers\Api\AdminController::class, 'updateTeam']);
    Route::delete('/teams/{team}', [App\Http\Controllers\Api\AdminController::class, 'deleteTeam']);
    Route::post('/teams/{team}/approve', [App\Http\Controllers\Api\AdminController::class, 'approveTeam']);
    Route::post('/teams/{team}/reject', [App\Http\Controllers\Api\AdminController::class, 'rejectTeam']);
    
    // Team member management (admin bypass)
    Route::delete('/teams/{team}/members/{user}', [App\Http\Controllers\Api\AdminController::class, 'removeMember']);
    Route::post('/teams/{team}/scrum-master', [App\Http\Controllers\Api\AdminController::class, 'changeScrumMaster']);
    Route::post('/teams/{team}/members/{user}/occupation', [App\Http\Controllers\Api\AdminController::class, 'changeMemberOccupation']);

    // Academic years (admin)
    Route::post('/academic-years', [App\Http\Controllers\Api\AdminController::class, 'createAcademicYear']);
    
    // Projects overview
    Route::get('/projects', [App\Http\Controllers\Api\AdminController::class, 'projects']);
    Route::delete('/projects/{project}', [App\Http\Controllers\Api\AdminController::class, 'deleteProject']);
    
    // User management
    Route::get('/users', [App\Http\Controllers\Api\AdminController::class, 'users']);
    Route::post('/users', [App\Http\Controllers\Api\AdminController::class, 'createUser']);
    Route::post('/users/{user}/move-team', [App\Http\Controllers\Api\AdminController::class, 'moveUserBetweenTeams']);
    Route::post('/users/{user}/add-to-team', [App\Http\Controllers\Api\AdminController::class, 'addUserToTeam']);
    Route::post('/users/{user}/deactivate', [App\Http\Controllers\Api\AdminController::class, 'deactivateUser']);
    Route::post('/users/{user}/activate', [App\Http\Controllers\Api\AdminController::class, 'activateUser']);
    Route::post('/users/bulk-deactivate', [App\Http\Controllers\Api\AdminController::class, 'bulkDeactivateUsers']);
    
    // Authorized students management (CSV import system)
    Route::post('/authorized-students/import', [App\Http\Controllers\Api\AdminController::class, 'importAuthorizedStudents']);
    Route::get('/authorized-students', [App\Http\Controllers\Api\AdminController::class, 'authorizedStudents']);
    Route::post('/authorized-students/{id}/toggle', [App\Http\Controllers\Api\AdminController::class, 'toggleAuthorizedStudent']);
    
    // Admin configuration
    Route::get('/config', [App\Http\Controllers\Api\AdminController::class, 'getConfig']);
});

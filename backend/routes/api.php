<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\TeamController;
use App\Http\Controllers\Api\GameController;
use App\Models\AcademicYear;

// 🟢 Verejné routy – dostupné bez tokenu
Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:login');
Route::post('/register', [AuthController::class, 'register'])->middleware('throttle:register');
Route::post('/verify-email', [AuthController::class, 'verifyEmail']); // Verifikácia e-mailu

// 🟡 Chránené routy – vyžadujú autentifikáciu
Route::middleware('auth:sanctum')->group(function () {
    
    Route::get('/user', [AuthController::class, 'user']);
    Route::post('/user/avatar', [AuthController::class, 'updateAvatar']);

    Route::post('/logout', [AuthController::class, 'logout']);

    // 🔵 Admin routy – tu pridaj svoje admin funkcie/controllers
    Route::middleware('role:admin')->group(function () {
        // príklad: Route::post('/admin-action', [AdminController::class, 'action']);
    });

    // 🟢 Štandardný používateľ routy – tu pridaj svoje user funkcie/controllers
    Route::middleware('role:user')->group(function () {
        // príklad: Route::get('/user-action', [UserController::class, 'action']);
    });

    Route::get('/user/team', [TeamController::class, 'getTeamStatus']);
    Route::post('/teams', [TeamController::class, 'store']);     // založenie tímu (Scrum Master)
    Route::post('/teams/join', [TeamController::class, 'join']);  // pripojenie k tímu
    Route::post('/games', [GameController::class, 'store']);      // Pridanie hry
    Route::get('/games/my', [GameController::class, 'myGames']);  //Vratenie hier timu
    // Route::get('/user/team', [TeamController::class, 'getUserTeamStatus']);  //zobrazovanie 
    Route::get('/games', [GameController::class, 'index']);  // Získanie všetkých hier
    Route::get('/games/{id}', [GameController::class, 'show']); // Jedna hra podľa ID
    Route::post('/games/{id}/views', [GameController::class, 'incrementViews']);  // Zvýšenie počtu zobrazení
    Route::post('/games/{id}/rate', [GameController::class, 'rate']); // Hodnotenie hry (iba raz)
    Route::get('/games/{id}/user-rating', [GameController::class, 'userRating']); // Informácia či používateľ hodnotil

    // 🔹 Endpoint na získanie akademických rokov
    Route::get('/academic-years', function() {
        return AcademicYear::all();
    });

    // 🔹 Správa členov tímu (iba Scrum Master)
    Route::delete('/teams/{team}/members/{user}', [TeamController::class, 'removeMember']);
});
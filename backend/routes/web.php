<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\VideoStreamController;

Route::get('/', function () {
    return ['Laravel' => app()->version()];
});

// Video streaming route with proper byte-range support for seeking
Route::get('/video/{path}', [VideoStreamController::class, 'stream'])
    ->where('path', '.*')
    ->name('video.stream');

//require __DIR__.'/auth.php';

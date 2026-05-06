<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\VideoStreamController;

Route::get('/', function () {
    return response()->json(['status' => 'ok']);
});

// Video streaming route with proper byte-range support for seeking
Route::get('/video/{path}', [VideoStreamController::class, 'stream'])
    ->where('path', '.*')
    ->name('video.stream');

// Storage file serving - required on shared hosting where storage symlink cannot be created
Route::get('/storage/{path}', function (string $path) {
    $path = urldecode($path);
    $segments = explode('/', $path);
    if (in_array('..', $segments, true) || in_array('.', $segments, true)) {
        abort(404);
    }
    
    // Dynamically get the real physical path of the 'public' disk regardless of environment
    $fullPath = \Illuminate\Support\Facades\Storage::disk('public')->path($path);

if (!file_exists($fullPath) || !is_file($fullPath)) {
        abort(404);
    }

    // WebGL requires specific MIME types + Content-Encoding for compressed files
    $filename = basename($fullPath);
    $headers = [];

    // Detect double extensions for compressed WebGL files (.wasm.gz, .data.br etc.)
    if (str_ends_with($filename, '.gz')) {
        $headers['Content-Encoding'] = 'gzip';
        $inner = pathinfo(substr($filename, 0, -3), PATHINFO_EXTENSION);
    } elseif (str_ends_with($filename, '.br')) {
        $headers['Content-Encoding'] = 'br';
        $inner = pathinfo(substr($filename, 0, -3), PATHINFO_EXTENSION);
    } else {
        $inner = null;
    }

    $ext = strtolower($inner ?? pathinfo($fullPath, PATHINFO_EXTENSION));
    $webglMimes = [
        'wasm'      => 'application/wasm',
        'js'        => 'application/javascript',
        'unityweb'  => 'application/octet-stream',
        'data'      => 'application/octet-stream',
        'mem'       => 'application/octet-stream',
        'symbols'   => 'application/octet-stream',
        'loader'    => 'application/javascript',
        'framework' => 'application/javascript',
    ];

    if (isset($webglMimes[$ext])) {
        $headers['Content-Type'] = $webglMimes[$ext];
    }

    if (!empty($headers)) {
        return response()->file($fullPath, $headers);
    }

    return response()->file($fullPath);
})->where('path', '.*');

//require __DIR__.'/auth.php';

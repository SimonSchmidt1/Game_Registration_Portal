<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class VideoStreamController extends Controller
{
    public function stream(Request $request, $path)
    {
        // Decode URL components (e.g. convert %20 back to spaces)
        $path = urldecode($path);
        
        // Normalize and validate user path to prevent traversal
        $relativePath = trim(str_replace('\\', '/', (string) $path), '/');
        $segments = explode('/', $relativePath);
        if ($relativePath === '' || in_array('..', $segments, true) || in_array('.', $segments, true)) {
            abort(404, 'File not found');
        }

        $storageRoot = realpath(storage_path('app/public'));
        
        // Fallback for WinSCP shared hosting where files might be in web/storage
        if ($storageRoot === false || !file_exists($storageRoot . DIRECTORY_SEPARATOR . $relativePath)) {
            $altRoot = realpath(base_path('../storage'));
            if ($altRoot !== false && file_exists($altRoot . DIRECTORY_SEPARATOR . $relativePath)) {
                $storageRoot = $altRoot;
            }
        }

        if ($storageRoot === false) {
            \Log::error('[VideoStream] Storage path misconfigured or file missing for: ' . $relativePath);
            abort(500, 'Storage path misconfigured');
        }

        $fullPath = realpath($storageRoot . DIRECTORY_SEPARATOR . $relativePath);
        if ($fullPath === false) {
            abort(404, 'File not found');
        }

        $allowedPrefix = rtrim($storageRoot, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
        if ($fullPath !== $storageRoot && !str_starts_with($fullPath, $allowedPrefix)) {
            abort(404, 'File not found');
        }
        
        \Log::info('[VideoStream] Request for: ' . $path);
        \Log::info('[VideoStream] Range header: ' . ($request->header('Range') ?? 'NONE'));
        
        if (!is_file($fullPath)) {
            \Log::error('[VideoStream] File not found: ' . $fullPath);
            abort(404, 'File not found');
        }
        
        $fileSize = filesize($fullPath);
        $mimeType = mime_content_type($fullPath) ?: 'application/octet-stream';
        
        \Log::info('[VideoStream] File size: ' . $fileSize . ' bytes, MIME: ' . $mimeType);
        
        $headers = [
            'Content-Type' => $mimeType,
            'Accept-Ranges' => 'bytes',
            'Cache-Control' => 'public, max-age=604800',
        ];
        
        // Check if client requested a byte range
        $rangeHeader = $request->header('Range');
        
        if ($rangeHeader) {
            // Parse the Range header: "bytes=start-end"
            if (preg_match('/bytes=(\d+)-(\d*)/', $rangeHeader, $matches)) {
                $start = (int) $matches[1];
                $end = $matches[2] !== '' ? (int) $matches[2] : $fileSize - 1;
                
                \Log::info('[VideoStream] Parsed range - Start: ' . $start . ', End: ' . $end);
                
                // Ensure valid range
                if ($start > $end || $start >= $fileSize) {
                    \Log::error('[VideoStream] Invalid range requested');
                    return response('Requested Range Not Satisfiable', 416)
                        ->header('Content-Range', "bytes */$fileSize");
                }
                
                $end = min($end, $fileSize - 1);
                $length = $end - $start + 1;
                
                $headers['Content-Length'] = $length;
                $headers['Content-Range'] = "bytes $start-$end/$fileSize";
                
                \Log::info('[VideoStream] Sending 206 response with range: ' . $start . '-' . $end . '/' . $fileSize);
                
                return response()->stream(function() use ($fullPath, $start, $length) {
                    $handle = fopen($fullPath, 'rb');
                    fseek($handle, $start);
                    
                    $bufferSize = 1024 * 8; // 8KB chunks
                    $bytesRemaining = $length;
                    
                    while ($bytesRemaining > 0 && !feof($handle)) {
                        $bytesToRead = min($bufferSize, $bytesRemaining);
                        echo fread($handle, $bytesToRead);
                        $bytesRemaining -= $bytesToRead;
                        flush();
                    }
                    
                    fclose($handle);
                }, 206, $headers);
            }
        }
        
        // No range requested - send entire file
        \Log::info('[VideoStream] Sending full file (200) - ' . $fileSize . ' bytes');
        $headers['Content-Length'] = $fileSize;
        
        return response()->stream(function() use ($fullPath) {
            $handle = fopen($fullPath, 'rb');
            
            while (!feof($handle)) {
                echo fread($handle, 1024 * 8);
                flush();
            }
            
            fclose($handle);
        }, 200, $headers);
    }
}

<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class VideoStreamController extends Controller
{
    public function stream(Request $request, $path)
    {
        // Construct full path: storage/app/public/{path}
        $fullPath = storage_path('app/public/' . $path);
        
        \Log::info('[VideoStream] Request for: ' . $path);
        \Log::info('[VideoStream] Range header: ' . ($request->header('Range') ?? 'NONE'));
        
        if (!file_exists($fullPath)) {
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

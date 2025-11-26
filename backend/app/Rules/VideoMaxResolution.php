<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Http\UploadedFile;

class VideoMaxResolution implements ValidationRule
{
    protected int $maxWidth;
    protected int $maxHeight;

    public function __construct(int $maxWidth = 1920, int $maxHeight = 1080)
    {
        $this->maxWidth = $maxWidth;
        $this->maxHeight = $maxHeight;
    }

    /**
     * Run the validation rule.
     *
     * @param  \Closure(string, ?string=): \Illuminate\Translation\PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (!$value instanceof UploadedFile) {
            return;
        }

        // Check if ffprobe is available (requires ffmpeg installation)
        if (!function_exists('shell_exec') || stripos(ini_get('disable_functions'), 'shell_exec') !== false) {
            // Cannot validate - skip check if shell_exec is disabled
            return;
        }

        $path = $value->getRealPath();
        
        // Use ffprobe to get video dimensions
        $command = "ffprobe -v error -select_streams v:0 -show_entries stream=width,height -of csv=s=x:p=0 " . escapeshellarg($path) . " 2>&1";
        $output = shell_exec($command);
        
        if (empty($output)) {
            // Cannot determine dimensions - allow upload but log warning
            \Log::warning("Could not determine video dimensions for file: {$value->getClientOriginalName()}");
            return;
        }

        // Parse dimensions (format: "1920x1080")
        $dimensions = explode('x', trim($output));
        
        if (count($dimensions) !== 2) {
            return;
        }

        $width = (int) $dimensions[0];
        $height = (int) $dimensions[1];

        if ($width > $this->maxWidth || $height > $this->maxHeight) {
            $fail("Video rozlíšenie ({$width}x{$height}) prekračuje maximálne povolené rozlíšenie ({$this->maxWidth}x{$this->maxHeight}).");
        }
    }
}

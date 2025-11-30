<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Project;
use App\Models\GameRating;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use App\Rules\VideoMaxResolution;
use App\Models\AcademicYear;

class ProjectController extends Controller
{
    public function index(Request $request)
    {
        $query = Project::with(['team.members', 'academicYear']);

        if ($request->has('type') && $request->type !== 'all') {
            $query->where('type', $request->type);
        }

        if ($request->has('search') && $request->search) {
            $query->where(function($q) use ($request) {
                $q->where('title', 'like', '%' . $request->search . '%')
                  ->orWhere('description', 'like', '%' . $request->search . '%');
            });
        }

        // New categorization filters
        if ($request->has('school_type') && $request->school_type !== 'all') {
            $query->where('school_type', $request->school_type);
        }

        if ($request->has('year_of_study') && $request->year_of_study !== 'all' && $request->year_of_study !== null) {
            $query->where('year_of_study', $request->year_of_study);
        }

        if ($request->has('subject') && $request->subject !== 'all' && $request->subject !== null) {
            $query->where('subject', $request->subject);
        }

        if ($request->has('academic_year_id') && $request->academic_year_id !== 'all') {
            $query->where('academic_year_id', $request->academic_year_id);
        }

        $projects = $query->orderBy('created_at', 'desc')->get();
        return response()->json($projects);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'type' => 'required|in:game,web_app,mobile_app,library,other',
            'school_type' => 'required|in:zs,ss,vs',
            'year_of_study' => 'nullable|integer|min:1|max:9',
            'subject' => 'required|string|max:255',
            'release_date' => 'nullable|date',
            'academic_year_id' => 'nullable|exists:academic_years,id',
            'team_id' => 'required|exists:teams,id',
            'splash_screen' => 'nullable|image|max:10240',
            'video' => ['nullable', 'file', 'mimes:mp4,webm,mov', 'max:51200', new VideoMaxResolution(1920, 1080)],
            'video_url' => 'nullable|url',
        ]);

        // Validate year_of_study range based on school_type
        if (isset($validated['year_of_study']) && $validated['year_of_study'] !== null) {
            $schoolType = $validated['school_type'];
            $year = $validated['year_of_study'];
            
            if ($schoolType === 'zs' && ($year < 1 || $year > 9)) {
                return response()->json(['message' => 'Pre základnú školu (ZŠ) musí byť ročník v rozsahu 1-9.'], 422);
            } elseif (in_array($schoolType, ['ss', 'vs']) && ($year < 1 || $year > 5)) {
                return response()->json(['message' => 'Pre strednú školu (SŠ) a vysokú školu (VŠ) musí byť ročník v rozsahu 1-5.'], 422);
            }
        }

        $typeValidation = $this->getTypeSpecificValidation($request->type);
        $request->validate($typeValidation);

        $team = $request->user()->teams()
            ->wherePivot('role_in_team', 'scrum_master')
            ->where('teams.id', $validated['team_id'])
            ->first();

        if (!$team) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        // Verify image file is actually an image (content verification)
        if ($request->hasFile('splash_screen')) {
            $imageInfo = @getimagesize($request->file('splash_screen')->getPathname());
            if (!$imageInfo) {
                return response()->json(['message' => 'Neplatný obrázok. Súbor nie je platný obrazový formát.'], 422);
            }
        }

        try {
            return DB::transaction(function () use ($request, $validated, $team) {
                $files = [];
                $splashPath = null;
                $videoPath = null;

                if ($request->hasFile('splash_screen')) {
                    $splashPath = $request->file('splash_screen')->store("projects/{$request->type}/splash_screens", 'public');
                }

                if ($request->hasFile('video')) {
                    $videoPath = $request->file('video')->store("projects/{$request->type}/videos", 'public');
                }

                foreach ($this->getFileFieldsForType($request->type) as $field => $config) {
                    if ($request->hasFile($field)) {
                        $files[$field] = $request->file($field)->store("projects/{$request->type}/{$config['folder']}", 'public');
                    }
                }

                $metadata = $this->extractMetadata($request);

                // Ensure academic_year_id is set: use latest academic year (by id) if not provided
                $academicYearId = $validated['academic_year_id'] ?? AcademicYear::orderByDesc('id')->value('id');

                // If still null (no academic years exist), return clear error
                if (!$academicYearId) {
                    return response()->json(['message' => 'Nie je definovaný žiadny akademický rok. Najprv vytvorte akademický rok.'], 422);
                }

                $project = Project::create([
                    'team_id' => $team->id,
                    'title' => $validated['title'],
                    'description' => $validated['description'] ?? null,
                    'type' => $validated['type'],
                    'school_type' => $validated['school_type'],
                    'year_of_study' => $validated['year_of_study'] ?? null,
                    'subject' => $validated['subject'],
                    'release_date' => $validated['release_date'] ?? null,
                    'academic_year_id' => $academicYearId,
                    'splash_screen_path' => $splashPath,
                    'video_path' => $videoPath,
                    'video_url' => $validated['video_url'] ?? null,
                    'files' => $files,
                    'metadata' => $metadata,
                ]);

                return response()->json(['project' => $project, 'message' => 'Projekt bol úspešne vytvorený!'], 201);
            });
        } catch (\Exception $e) {
            \Log::error('Project creation failed', ['error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
            return response()->json(['message' => 'Chyba pri vytváraní projektu. Skúste znova.'], 500);
        }
    }

    public function show($id)
    {
        $project = Project::with(['team.members', 'academicYear'])->findOrFail($id);
        return response()->json(['project' => $project]);
    }

    public function incrementViews($id)
    {
        $project = Project::findOrFail($id);
        $project->increment('views');
        return response()->json(['views' => $project->views]);
    }

    public function rate(Request $request, $id)
    {
        $request->validate(['rating' => 'required|integer|min:1|max:5']);
        $project = Project::findOrFail($id);
        $user = $request->user();

        try {
            // Use transaction with pessimistic locking to prevent race condition
            return DB::transaction(function () use ($id, $user, $request, $project) {
                // Lock and check for existing rating atomically
                $existingRating = GameRating::where('project_id', $id)
                    ->where('user_id', $user->id)
                    ->lockForUpdate()
                    ->first();
                    
                if ($existingRating) {
                    return response()->json(['message' => 'Projekt už bol hodnotený týmto používateľom.'], 422);
                }
                
                GameRating::create(['project_id' => $id, 'user_id' => $user->id, 'rating' => $request->rating]);

                $avgRating = GameRating::where('project_id', $id)->avg('rating');
                $ratingCount = GameRating::where('project_id', $id)->count();
                $project->update(['rating' => round($avgRating, 2), 'rating_count' => $ratingCount]);

                return response()->json(['message' => 'Hodnotenie uložené', 'rating' => $project->rating, 'rating_count' => $project->rating_count]);
            });
        } catch (\Illuminate\Database\QueryException $e) {
            // Handle unique constraint violation (if db has unique index on project_id, user_id)
            if ($e->getCode() === '23000') {
                return response()->json(['message' => 'Projekt už bol hodnotený týmto používateľom.'], 422);
            }
            throw $e;
        }
    }

    public function getUserRating(Request $request, $id)
    {
        $rating = GameRating::where('project_id', $id)->where('user_id', $request->user()->id)->first();
        return response()->json(['rating' => $rating ? $rating->rating : null]);
    }

    public function my(Request $request)
    {
        $teamId = $request->query('team_id');
        if (!$teamId) {
            return response()->json(['message' => 'team_id query parameter required'], 422);
        }
        $projects = Project::where('team_id', $teamId)->with(['academicYear','team'])->orderBy('created_at','desc')->get();
        return response()->json(['projects' => $projects, 'count' => $projects->count()], 200);
    }

    public function update(Request $request, $id)
    {
        $project = Project::with('team.members')->findOrFail($id);
        $user = $request->user();

        // Check if user is Scrum Master of the team that owns this project
        $isScrumMaster = $project->team->members()
            ->where('users.id', $user->id)
            ->where('team_user.role_in_team', 'scrum_master')
            ->exists();

        if (!$isScrumMaster) {
            return response()->json(['message' => 'Iba Scrum Master tímu môže upravovať projekt.'], 403);
        }

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'type' => 'required|in:game,web_app,mobile_app,library,other',
            'school_type' => 'required|in:zs,ss,vs',
            'year_of_study' => 'nullable|integer|min:1|max:9',
            'subject' => 'required|string|max:255',
            'release_date' => 'nullable|date',
            'academic_year_id' => 'nullable|exists:academic_years,id',
            'splash_screen' => 'nullable|image|max:10240',
            'video' => ['nullable', 'file', 'mimes:mp4,webm,mov', 'max:51200', new VideoMaxResolution(1920, 1080)],
            'video_url' => 'nullable|url',
        ]);

        // Validate year_of_study range if provided
        if (isset($validated['year_of_study']) && $validated['year_of_study'] !== null) {
            $schoolType = $validated['school_type'] ?? $project->school_type;
            $year = $validated['year_of_study'];
            
            if ($schoolType === 'zs' && ($year < 1 || $year > 9)) {
                return response()->json(['message' => 'Pre základnú školu (ZŠ) musí byť ročník v rozsahu 1-9.'], 422);
            } elseif (in_array($schoolType, ['ss', 'vs']) && ($year < 1 || $year > 5)) {
                return response()->json(['message' => 'Pre strednú školu (SŠ) a vysokú školu (VŠ) musí byť ročník v rozsahu 1-5.'], 422);
            }
        }

        // Determine project type (use updated type if provided, otherwise existing)
        // This must be done before file handling to use correct type for storage paths
        $projectType = $request->has('type') ? $request->type : $project->type;
        
        // Type-specific validation
        $typeValidation = $this->getTypeSpecificValidation($projectType);
        $request->validate($typeValidation);

        // Handle file updates - delete old files if new ones uploaded
        $updateData = [];

        // Update basic fields - always update all provided fields
        $updateData['title'] = $validated['title'];
        $updateData['description'] = $validated['description'] ?? null;
        $updateData['type'] = $validated['type'];
        $updateData['school_type'] = $validated['school_type'];
        $updateData['subject'] = $validated['subject'];
        
        // Handle nullable fields - convert empty strings to null (but allow 0 for year_of_study)
        if (isset($validated['year_of_study']) && $validated['year_of_study'] !== '' && $validated['year_of_study'] !== null) {
            $updateData['year_of_study'] = $validated['year_of_study'];
        } else {
            $updateData['year_of_study'] = null;
        }
        
        if (isset($validated['release_date']) && $validated['release_date'] !== '' && $validated['release_date'] !== null) {
            $updateData['release_date'] = $validated['release_date'];
        } else {
            $updateData['release_date'] = null;
        }
        
        if (isset($validated['video_url']) && $validated['video_url'] !== '' && $validated['video_url'] !== null) {
            $updateData['video_url'] = $validated['video_url'];
        } else {
            $updateData['video_url'] = null;
        }
        
        if (isset($validated['academic_year_id']) && !empty($validated['academic_year_id'])) {
            $updateData['academic_year_id'] = $validated['academic_year_id'];
        }

        // Handle splash screen update
        if ($request->hasFile('splash_screen')) {
            // Delete old splash screen if exists
            if ($project->splash_screen_path) {
                Storage::disk('public')->delete($project->splash_screen_path);
            }
            $updateData['splash_screen_path'] = $request->file('splash_screen')->store("projects/{$projectType}/splash_screens", 'public');
        }

        // Handle video update
        if ($request->hasFile('video')) {
            // Delete old video if exists
            if ($project->video_path) {
                Storage::disk('public')->delete($project->video_path);
            }
            $updateData['video_path'] = $request->file('video')->store("projects/{$projectType}/videos", 'public');
            // Clear video_url if video file is uploaded
            $updateData['video_url'] = null;
        } elseif ($request->has('video_url')) {
            // If video_url is provided, clear video_path
            if ($project->video_path) {
                Storage::disk('public')->delete($project->video_path);
            }
            $updateData['video_path'] = null;
        }

        // Handle type-specific files
        $currentFiles = $project->files ?? [];
        $newFiles = $currentFiles; // Start with existing files

        foreach ($this->getFileFieldsForType($projectType) as $field => $config) {
            if ($request->hasFile($field)) {
                // Delete old file if exists
                if (isset($currentFiles[$field])) {
                    Storage::disk('public')->delete($currentFiles[$field]);
                }
                // Store new file
                $newFiles[$field] = $request->file($field)->store("projects/{$projectType}/{$config['folder']}", 'public');
            }
        }

        $updateData['files'] = $newFiles;

        // Handle metadata update
        $currentMetadata = $project->metadata ?? [];
        $newMetadata = array_merge($currentMetadata, $this->extractMetadata($request));
        $updateData['metadata'] = $newMetadata;

        // Ensure academic_year_id is set if not provided
        if (!isset($updateData['academic_year_id']) && !$project->academic_year_id) {
            $academicYearId = AcademicYear::orderByDesc('id')->value('id');
            if ($academicYearId) {
                $updateData['academic_year_id'] = $academicYearId;
            }
        }

        // Log update data for debugging
        \Log::info('Updating project', [
            'project_id' => $project->id,
            'update_data' => $updateData,
            'update_data_keys' => array_keys($updateData),
        ]);

        $project->update($updateData);
        $project->refresh();
        $project->load(['team.members', 'academicYear']);

        \Log::info('Project updated successfully', [
            'project_id' => $project->id,
            'title' => $project->title,
            'description' => $project->description,
        ]);

        return response()->json(['project' => $project, 'message' => 'Projekt bol úspešne aktualizovaný!'], 200);
    }

    private function getTypeSpecificValidation($type)
    {
        return match($type) {
            'game' => ['export' => 'nullable|file|mimes:zip|max:512000', 'source_code' => 'nullable|file|mimes:zip|max:204800', 'tech_stack' => 'nullable|string', 'github_url' => 'nullable|url'],
            'web_app' => ['live_url' => 'nullable|url', 'github_url' => 'nullable|url', 'tech_stack' => 'nullable|string', 'source_code' => 'nullable|file|mimes:zip|max:204800'],
            'mobile_app' => ['apk_file' => 'nullable|file|max:102400', 'ios_file' => 'nullable|file|max:102400', 'platform' => 'nullable|in:android,ios,both', 'source_code' => 'nullable|file|mimes:zip|max:204800', 'github_url' => 'nullable|url', 'tech_stack' => 'nullable|string'],
            'library' => ['package_name' => 'nullable|string', 'npm_url' => 'nullable|url', 'github_url' => 'nullable|url', 'documentation' => 'nullable|file|mimes:pdf,zip|max:51200', 'source_code' => 'nullable|file|mimes:zip|max:204800', 'tech_stack' => 'nullable|string'],
            'other' => ['live_url' => 'nullable|url', 'github_url' => 'nullable|url', 'tech_stack' => 'nullable|string', 'source_code' => 'nullable|file|mimes:zip|max:204800'],
            default => [],
        };
    }

    private function getFileFieldsForType($type)
    {
        return match($type) {
            'game' => ['export' => ['folder' => 'exports'], 'source_code' => ['folder' => 'source']],
            'web_app' => ['source_code' => ['folder' => 'source']],
            'mobile_app' => ['apk_file' => ['folder' => 'apk'], 'ios_file' => ['folder' => 'ios'], 'source_code' => ['folder' => 'source']],
            'library' => ['documentation' => ['folder' => 'docs'], 'source_code' => ['folder' => 'source']],
            default => [],
        };
    }

    private function extractMetadata(Request $request)
    {
        $metadata = [];
        
        // URL fields - sanitize and validate
        $urlFields = ['live_url', 'github_url', 'npm_url'];
        foreach ($urlFields as $field) {
            if ($request->has($field)) {
                $val = $request->input($field);
                if ($val !== null && $val !== '') {
                    // Sanitize URL - remove javascript: and other dangerous protocols
                    $val = filter_var($val, FILTER_SANITIZE_URL);
                    if (filter_var($val, FILTER_VALIDATE_URL) && preg_match('/^https?:\/\//', $val)) {
                        $metadata[$field] = $val;
                    }
                }
            }
        }
        
        // String fields - sanitize
        $stringFields = ['package_name', 'platform'];
        foreach ($stringFields as $field) {
            if ($request->has($field)) {
                $val = $request->input($field);
                if ($val !== null && $val !== '') {
                    // Remove HTML tags and trim
                    $metadata[$field] = htmlspecialchars(strip_tags(trim($val)), ENT_QUOTES, 'UTF-8');
                }
            }
        }
        
        if ($request->has('tech_stack')) {
            $raw = $request->tech_stack;
            if (is_string($raw)) {
                $items = array_map('trim', preg_split('/[,;]/', $raw));
                // Sanitize each item
                $metadata['tech_stack'] = array_map(function($v) {
                    return htmlspecialchars(strip_tags(trim($v)), ENT_QUOTES, 'UTF-8');
                }, array_filter($items));
            } elseif (is_array($raw)) {
                $metadata['tech_stack'] = array_map(function($v) {
                    return htmlspecialchars(strip_tags(trim((string)$v)), ENT_QUOTES, 'UTF-8');
                }, array_filter($raw));
            }
        }
        return $metadata;
    }
}

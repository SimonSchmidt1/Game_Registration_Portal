<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Project;
use App\Models\GameRating;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;
use App\Rules\VideoMaxResolution;
use App\Models\AcademicYear;

class ProjectController extends Controller
{
    public function index(Request $request)
    {
        $query = $this->buildProjectQuery($request, false);
        $perPage = min((int) $request->query('per_page', 20), 50);
        $projects = $query->orderBy('created_at', 'desc')->paginate($perPage);
        return response()->json($projects);
    }

    public function publicIndex(Request $request)
    {
        $query = $this->buildProjectQuery($request, true);
        $perPage = min((int) $request->query('per_page', 20), 50);
        $projects = $query->orderBy('created_at', 'desc')->paginate($perPage);
        return response()->json($projects);
    }

    public function topRated(Request $request)
    {
        $limit = (int) $request->query('limit', 12);
        $limit = max(1, min($limit, 30));

        $query = Project::with(['team.members', 'academicYear']);

        // Only include projects from active teams if status exists
        if (Schema::hasColumn('teams', 'status')) {
            $query->whereHas('team', function ($q) {
                $q->where('status', 'active');
            });
        }

        $projects = $query
            ->orderByDesc('rating')
            ->orderByDesc('rating_count')
            ->orderByDesc('created_at')
            ->limit($limit)
            ->get();

        return response()->json($projects);
    }

    public function publicShow($id)
    {
        $query = Project::with(['team.members', 'academicYear'])->where('id', $id);

        if (Schema::hasColumn('teams', 'status')) {
            $query->whereHas('team', function ($q) {
                $q->where('status', 'active');
            });
        }

        $project = $query->first();

        if (!$project) {
            return response()->json(['message' => 'Projekt nebol nájdený.'], 404);
        }

        return response()->json(['project' => $project]);
    }

    public function ratePublic(Request $request, $id)
    {
        $request->validate(['rating' => 'required|integer|min:1|max:5']);

        $fingerprint = sha1(($request->ip() ?? 'unknown') . '|' . ($request->userAgent() ?? '')); 
        $voteKey = "public_project_rating:{$id}:{$fingerprint}";
        if (Cache::has($voteKey)) {
            return response()->json(['message' => 'Tento projekt ste už hodnotili.'], 429);
        }

        $query = Project::where('id', $id);
        if (Schema::hasColumn('teams', 'status')) {
            $query->whereHas('team', function ($q) {
                $q->where('status', 'active');
            });
        }

        $project = $query->first();
        if (!$project) {
            return response()->json(['message' => 'Projekt nebol nájdený.'], 404);
        }

        return DB::transaction(function () use ($id, $request, $project) {
            GameRating::create(['project_id' => $id, 'rating' => $request->rating]);

            $avgRating = GameRating::where('project_id', $id)->avg('rating');
            $ratingCount = GameRating::where('project_id', $id)->count();
            $project->update(['rating' => round($avgRating, 2), 'rating_count' => $ratingCount]);

            $fingerprint = sha1(($request->ip() ?? 'unknown') . '|' . ($request->userAgent() ?? ''));
            $voteKey = "public_project_rating:{$id}:{$fingerprint}";
            Cache::put($voteKey, true, now()->addDays(30));

            return response()->json([
                'message' => 'Hodnotenie uložené',
                'rating' => $project->rating,
                'rating_count' => $project->rating_count
            ]);
        });
    }

    private function buildProjectQuery(Request $request, bool $onlyActiveTeams)
    {
        $query = Project::with(['team.members', 'academicYear']);

        if ($onlyActiveTeams && Schema::hasColumn('teams', 'status')) {
            $query->whereHas('team', function ($q) {
                $q->where('status', 'active');
            });
        }

        if ($request->has('type') && $request->type !== 'all') {
            $query->where('type', $request->type);
        }

        if ($request->has('search') && $request->search) {
            $query->where(function($q) use ($request) {
                $q->where('title', 'like', '%' . $request->search . '%')
                  ->orWhere('description', 'like', '%' . $request->search . '%');
            });
        }

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

        return $query;
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
            'predmet' => 'required|string|max:100',
            'release_date' => 'nullable|date',
            'academic_year_id' => 'nullable|exists:academic_years,id',
            'team_id' => 'required|exists:teams,id',
            'splash_screen' => 'nullable|image|max:8192',
            'video' => ['nullable', 'file', 'mimes:mp4,webm,mov', 'max:102400', new VideoMaxResolution(1920, 1080)],
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

        // Check if user is admin - admins can create projects for any team
        $isAdmin = $request->user()->role === 'admin';

        if ($isAdmin) {
            // Admin can create project for any team
            $team = Team::findOrFail($validated['team_id']);
        } else {
            // Regular user must be Scrum Master of the team
            $team = $request->user()->teams()
                ->wherePivot('role_in_team', 'scrum_master')
                ->where('teams.id', $validated['team_id'])
                ->first();

            if (!$team) {
                return response()->json(['message' => 'Unauthorized'], 403);
            }
        }

        // Check if team is active (only active teams can publish projects)
        // Admins can bypass this check
        // Only check if status column exists
        if (!$isAdmin && Schema::hasColumn('teams', 'status')) {
            $teamStatus = $team->status ?? 'active';
            if ($teamStatus !== 'active') {
                $statusMessages = [
                    'pending' => 'Váš tím čaká na schválenie administrátorom. Projekty môžete publikovať až po schválení tímu.',
                    'suspended' => 'Váš tím bol pozastavený. Nie je možné publikovať projekty.',
                ];
                return response()->json([
                    'message' => $statusMessages[$teamStatus] ?? 'Váš tím nie je aktívny. Nie je možné publikovať projekty.',
                    'team_status' => $teamStatus
                ], 403);
            }
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

                // Debug logging for splash screen upload
                if ($request->hasFile('splash_screen')) {
                    \Log::info('Splash screen file received', [
                        'original_name' => $request->file('splash_screen')->getClientOriginalName(),
                        'mime_type' => $request->file('splash_screen')->getMimeType(),
                        'size' => $request->file('splash_screen')->getSize()
                    ]);
                    $splashPath = $request->file('splash_screen')->store("projects/{$request->type}/splash_screens", 'public');
                } else {
                    \Log::warning('No splash screen file received during project upload');
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
                    'predmet' => $validated['predmet'],
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
        $teamId = (int) $request->query('team_id');
        if ($teamId <= 0) {
            return response()->json(['message' => 'team_id query parameter required'], 422);
        }

        $user = $request->user();
        $isAdmin = $user && $user->role === 'admin';
        $isMember = $user
            ? $user->teams()->where('teams.id', $teamId)->exists()
            : false;

        if (!$isAdmin && !$isMember) {
            return response()->json(['message' => 'Nemáte prístup k projektom tohto tímu.'], 403);
        }

        $projects = Project::where('team_id', $teamId)->with(['academicYear','team'])->orderBy('created_at','desc')->get();
        return response()->json(['projects' => $projects, 'count' => $projects->count()], 200);
    }

    public function update(Request $request, $id)
    {
        $project = Project::with('team.members')->findOrFail($id);
        $user = $request->user();

        // Check if user is admin - admins can update any project
        $isAdmin = $user->role === 'admin';

        if (!$isAdmin) {
            // Check if user is Scrum Master of the team that owns this project
            $isScrumMaster = $project->team->members()
                ->where('users.id', $user->id)
                ->where('team_user.role_in_team', 'scrum_master')
                ->exists();

            if (!$isScrumMaster) {
                return response()->json(['message' => 'Iba Scrum Master tímu môže upravovať projekt.'], 403);
            }
        }

        // Check if team is active (only active teams can edit projects)
        // Admins can bypass this check
        // Only check if status column exists
        if (!$isAdmin && Schema::hasColumn('teams', 'status')) {
            $team = $project->team;
            $teamStatus = $team->status ?? 'active';
            if ($teamStatus !== 'active') {
                $statusMessages = [
                    'pending' => 'Váš tím čaká na schválenie administrátorom. Projekty môžete upravovať až po schválení tímu.',
                    'suspended' => 'Váš tím bol pozastavený. Nie je možné upravovať projekty.',
                ];
                return response()->json([
                    'message' => $statusMessages[$teamStatus] ?? 'Váš tím nie je aktívny. Nie je možné upravovať projekty.',
                    'team_status' => $teamStatus
                ], 403);
            }
        }

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'type' => 'required|in:game,web_app,mobile_app,library,other',
            'school_type' => 'required|in:zs,ss,vs',
            'year_of_study' => 'nullable|integer|min:1|max:9',
            'subject' => 'required|string|max:255',
            'predmet' => 'required|string|max:100',
            'release_date' => 'nullable|date',
            'academic_year_id' => 'nullable|exists:academic_years,id',
            'splash_screen' => 'nullable|image|max:8192',
            'video' => ['nullable', 'file', 'mimes:mp4,webm,mov', 'max:102400', new VideoMaxResolution(1920, 1080)],
            'video_url' => 'nullable|url',
            'project_folder' => 'nullable|file|mimes:zip,rar|max:20480',
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
        $updateData['predmet'] = $validated['predmet'];
        
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
        // Universal file uploads for all project types
        $universalValidation = [
            'documentation' => 'nullable|file|mimes:pdf,docx,zip,rar|max:10240', // 10MB - PDF, DOCX, ZIP, or RAR
            'presentation' => 'nullable|file|mimes:pdf,ppt,pptx|max:15360', // 15MB
            'source_code' => 'nullable|file|mimes:zip,rar|max:204800', // 200MB
            'export' => 'nullable|file|mimes:zip,rar,exe,apk,ipa|max:512000', // 500MB
            'project_folder' => 'nullable|file|mimes:zip,rar|max:20480', // 20MB
            'export_type' => 'nullable|in:standalone,webgl,mobile,executable',
            'tech_stack' => 'nullable|string',
            'github_url' => 'nullable|url',
        ];

        // Type-specific additional validation
        $typeSpecific = match($type) {
            'game' => [],
            'web_app' => ['live_url' => 'nullable|url'],
            'mobile_app' => ['platform' => 'nullable|in:android,ios,both'],
            'library' => ['package_name' => 'nullable|string', 'npm_url' => 'nullable|url'],
            'other' => ['live_url' => 'nullable|url'],
            default => [],
        };

        return array_merge($universalValidation, $typeSpecific);
    }

    private function getFileFieldsForType($type)
    {
        // Universal file fields for all project types
        return [
            'documentation' => ['folder' => 'documentation'],
            'presentation' => ['folder' => 'presentations'],
            'source_code' => ['folder' => 'source'],
            'export' => ['folder' => 'exports'],
            'project_folder' => ['folder' => 'folders'],
        ];
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
        $stringFields = ['package_name', 'platform', 'export_type'];
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

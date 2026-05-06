<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Project;
use App\Models\Team;
use App\Models\GameRating;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Str;
use App\Rules\VideoMaxResolution;
use App\Models\AcademicYear;

class ProjectController extends Controller
{
    public function index(Request $request)
    {
        $query = $this->buildProjectQuery($request, false);
        $perPage = min((int) $request->query('per_page', 21), 50);
        $projects = $query->orderBy('created_at', 'desc')->paginate($perPage);
        return response()->json($projects);
    }

    public function publicIndex(Request $request)
    {
        $query = $this->buildProjectQuery($request, true);
        $perPage = min((int) $request->query('per_page', 21), 50);
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

        $guestId = (string) ($request->cookie('guest_rating_id') ?? '');
        if ($guestId === '') {
            $guestId = (string) Str::uuid();
        }
        $guestFingerprint = hash('sha256', $guestId);

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

        $alreadyRatedByGuest = GameRating::where('project_id', $id)
            ->whereNull('user_id')
            ->where('guest_fingerprint', $guestFingerprint)
            ->exists();
        if ($alreadyRatedByGuest) {
            return response()->json([
                'message' => 'Tento projekt ste už hodnotili.',
                'already_rated' => true,
                'rating' => $project->rating,
                'rating_count' => $project->rating_count,
            ], 200)
                ->cookie(Cookie::make('guest_rating_id', $guestId, 60 * 24 * 365 * 5, '/', null, false, true, false, 'Lax'));
        }

        return DB::transaction(function () use ($id, $request, $project, $guestId, $guestFingerprint) {
            try {
                GameRating::create([
                    'project_id' => $id,
                    'user_id' => null,
                    'guest_fingerprint' => $guestFingerprint,
                    'rating' => $request->rating,
                ]);
            } catch (QueryException $e) {
                // SQLSTATE 23000 => integrity constraint violation (duplicate unique key)
                if ((string) $e->getCode() === '23000') {
                    return response()->json([
                        'message' => 'Tento projekt ste už hodnotili.',
                        'already_rated' => true,
                        'rating' => $project->rating,
                        'rating_count' => $project->rating_count,
                    ], 200)
                        ->cookie(Cookie::make('guest_rating_id', $guestId, 60 * 24 * 365 * 5, '/', null, false, true, false, 'Lax'));
                }
                throw $e;
            }

            $avgRating = GameRating::where('project_id', $id)->avg('rating');
            $ratingCount = GameRating::where('project_id', $id)->count();
            $project->fill(['rating' => round($avgRating, 2), 'rating_count' => $ratingCount]);
            if (!$project->save()) {
                throw new \RuntimeException('Failed to persist project rating counters.');
            }

            return response()->json([
                'message' => 'Hodnotenie uložené',
                'rating' => $project->rating,
                'rating_count' => $project->rating_count
            ])->cookie(Cookie::make('guest_rating_id', $guestId, 60 * 24 * 365 * 5, '/', null, false, true, false, 'Lax'));
        });
    }

    private function buildProjectQuery(Request $request, bool $onlyActiveTeams)
    {
        $query = Project::with(['team.members', 'academicYear']);

        if ($request->boolean('my_projects')) {
            $user = $request->user();

            if (!$user) {
                // Keep consistent response shape for unauthenticated callers.
                $query->whereRaw('1 = 0');
                return $query;
            }

            $teamId = (int) $request->query('team_id');
            $teamIds = $user->teams()->pluck('teams.id');

            if ($teamId > 0) {
                if (!$teamIds->contains($teamId) && $user->role !== 'admin') {
                    $query->whereRaw('1 = 0');
                    return $query;
                }
                $query->where('team_id', $teamId);
            } elseif ($teamIds->isNotEmpty()) {
                $query->whereIn('team_id', $teamIds);
            } else {
                $query->whereRaw('1 = 0');
                return $query;
            }
        }

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
            'type' => 'required|in:game,web_app,mobile_app,library,other,webgl',
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

                // Extract WebGL build if provided, otherwise URL mode needs no local path
                if ($validated['type'] === 'webgl') {
                    if ($request->hasFile('webgl_build')) {
                        $webglPath = $this->extractWebGLBuild($request->file('webgl_build'), $project->id);
                        if ($webglPath) {
                            $meta = $project->metadata ?? [];
                            unset($meta['webgl_url']);
                            $meta['webgl_local_path'] = $webglPath;
                            $project->metadata = $meta;
                            $project->save();
                        }
                    } elseif (!empty($project->metadata['webgl_url'])) {
                        // URL mode — ensure no stale local path
                        $meta = $project->metadata ?? [];
                        unset($meta['webgl_local_path']);
                        $project->metadata = $meta;
                        $project->save();
                    }
                }

                return response()->json(['project' => $project, 'message' => 'Projekt bol úspešne vytvorený!'], 201);
            });
        } catch (\Exception $e) {
            \Log::error('Project creation failed', ['error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
            return response()->json(['message' => 'Chyba pri vyvváraní projektu: ' . $e->getMessage() . ' na riadku ' . $e->getLine()], 500);
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
            'type' => 'required|in:game,web_app,mobile_app,library,other,webgl',
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

        // Handle WebGL build upload on update
        if ($validated['type'] === 'webgl') {
            if ($request->hasFile('webgl_build')) {
                // New build uploaded — extract it and clear any URL
                $webglPath = $this->extractWebGLBuild($request->file('webgl_build'), $project->id);
                if ($webglPath) {
                    $newMetadata['webgl_local_path'] = $webglPath;
                    unset($newMetadata['webgl_url']);
                }
            } elseif (!empty($newMetadata['webgl_url'])) {
                // URL mode — clear any old local build path
                unset($newMetadata['webgl_local_path']);
                $this->deleteWebGLBuild($project->id);
            }
        }

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
            'webgl' => [
                'webgl_url'   => 'nullable|url',
                'webgl_build' => 'nullable|file|mimes:zip|max:102400', // 100MB zip
            ],
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

    /**
     * Extract a WebGL zip build into storage/webgl/{projectId}/ and return the public path.
     * Returns null on failure (logs the error).
     */
    private function extractWebGLBuild(\Illuminate\Http\UploadedFile $zip, int $projectId): ?string
    {
        $allowedExtensions = ['html','htm','js','mjs','wasm','data','json','css',
                              'png','jpg','jpeg','gif','webp','svg','ico','txt','unityweb',
                              'framework','loader','mem','symbols','br','gz',
                              'mp3','mp4','ogg','wav','webm','m4a',
                              'glb','gltf','bin','fbx','obj','mtl',
                              'ttf','otf','woff','woff2',
                              'atlas','xml','vert','frag'];
        $maxFiles   = 500;
        $maxBytes   = 200 * 1024 * 1024; // 200MB uncompressed

        $za = new \ZipArchive();
        if ($za->open($zip->getRealPath()) !== true) {
            \Log::error('WebGL: failed to open zip', ['project_id' => $projectId]);
            return null;
        }

        // Security + sanity checks before extracting anything
        if ($za->count() > $maxFiles) {
            $za->close();
            \Log::error('WebGL: too many files in zip', ['count' => $za->count()]);
            return null;
        }

        $hasIndex   = false;
        $totalBytes = 0;
        for ($i = 0; $i < $za->count(); $i++) {
            $stat = $za->statIndex($i);
            $name = $stat['name'];

            // Block path traversal
            if (str_contains($name, '..') || str_starts_with($name, '/')) {
                $za->close();
                \Log::error('WebGL: path traversal detected', ['name' => $name]);
                return null;
            }

            // Skip directories
            if (str_ends_with($name, '/')) continue;

            // Check extension
            $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
            if ($ext !== '' && !in_array($ext, $allowedExtensions, true)) {
                $za->close();
                \Log::error('WebGL: disallowed extension', ['name' => $name, 'ext' => $ext]);
                return null;
            }

            $totalBytes += $stat['size'];
            if ($totalBytes > $maxBytes) {
                $za->close();
                \Log::error('WebGL: uncompressed size exceeds limit');
                return null;
            }

            // Accept index.html at root or one level deep
            if (basename($name) === 'index.html') $hasIndex = true;
        }

        if (!$hasIndex) {
            $za->close();
            \Log::error('WebGL: no index.html found in zip', ['project_id' => $projectId]);
            return null;
        }

        // Delete any previous build
        Storage::disk('public')->deleteDirectory('webgl/' . $projectId);
        Storage::disk('public')->makeDirectory('webgl/' . $projectId);

        $destPath = Storage::disk('public')->path('webgl/' . $projectId);

          // Extract everything as-is no assumptions about folder structure
          $za->extractTo($destPath);
          $za->close();

          // Generate an .htaccess file in this directory to fix common 404 errors for Unity WebGL files (.wasm, .gz, .br) on Apache
          $htaccessContent = <<<HTACCESS
<IfModule mod_mime.c>
  AddType application/wasm .wasm
  AddType application/javascript .js
  AddType application/octet-stream .data
  AddEncoding gzip .gz
  AddEncoding brotli .br
</IfModule>

<IfModule mod_headers.c>
  # Fix for Unity WebGL throwing CSP 'eval' errors which leads to 404 asm.js fallback
  Header set Content-Security-Policy "default-src 'self' 'unsafe-eval' 'unsafe-inline' data: blob:; script-src * 'unsafe-eval' 'unsafe-inline' data: blob:; connect-src * 'unsafe-eval' 'unsafe-inline' data: blob:; style-src * 'unsafe-inline'; worker-src * blob:;"

<FilesMatch "\.wasm\.gz$">
  Header set Content-Encoding gzip
  ForceType application/wasm
</FilesMatch>
<FilesMatch "\.js\.gz$">
  Header set Content-Encoding gzip
  ForceType application/javascript
</FilesMatch>
<FilesMatch "\.data\.gz$">
  Header set Content-Encoding gzip
  ForceType application/octet-stream
</FilesMatch>
<FilesMatch "\.wasm\.br$">
  Header set Content-Encoding brotli
  ForceType application/wasm
</FilesMatch>
<FilesMatch "\.js\.br$">
  Header set Content-Encoding brotli
  ForceType application/javascript
</FilesMatch>
<FilesMatch "\.data\.br$">
  Header set Content-Encoding brotli
  ForceType application/octet-stream
</FilesMatch>
</IfModule>
HTACCESS;
          file_put_contents($destPath . DIRECTORY_SEPARATOR . '.htaccess', $htaccessContent);

        // Find the shallowest index.html anywhere in the extracted tree
        $indexPath = $this->findShallowIndex($destPath);
        if (!$indexPath) {
            \Log::error('WebGL: index.html not found after extraction', ['project_id' => $projectId]);
            $this->deleteWebGLBuild($projectId);
            return null;
        }

// Force rename to strictly lowercase "index.html" so the frontend URL matched perfectly on case-sensitive Linux servers
          $indexDir = dirname($indexPath);
          $baseName = basename($indexPath);
          if ($baseName !== 'index.html') {
              $newIndexPath = $indexDir . DIRECTORY_SEPARATOR . 'index.html';
              rename($indexPath, $newIndexPath);
              $indexPath = $newIndexPath;
          }

          // Store path relative to storage/app/public/
          $destPathNormalized = str_replace('\\', '/', Storage::disk('public')->path('webgl/' . $projectId));
          $indexDirNormalized = str_replace('\\', '/', $indexDir);

          $storedPath = rtrim('webgl/' . $projectId . ($indexDirNormalized !== rtrim($destPathNormalized, '/')
              ? '/' . ltrim(substr($indexDirNormalized, strlen(rtrim($destPathNormalized, '/'))), '/')
              : ''), '/');

        \Log::info('WebGL build extracted', ['project_id' => $projectId, 'index' => $indexPath, 'path' => $storedPath]);
        return $storedPath;
    }

    private function deleteWebGLBuild(int $projectId): void
    {
        Storage::disk('public')->deleteDirectory('webgl/' . $projectId);
    }

    /**
     * Recursively find the shallowest index.html under $dir.
     * Returns the absolute path to index.html, or null if not found.
     */
    private function findShallowIndex(string $dir): ?string
    {
        $found = [];
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($dir, \RecursiveDirectoryIterator::SKIP_DOTS)
        );
        foreach ($iterator as $file) {
            if (!$file->isFile()) continue;
            if (strtolower($file->getFilename()) === 'index.html') {
                $found[] = $file->getRealPath();
            }
        }
        if (empty($found)) return null;
        // Return the shallowest one (fewest path segments)
        usort($found, fn($a, $b) => substr_count($a, DIRECTORY_SEPARATOR) <=> substr_count($b, DIRECTORY_SEPARATOR));
        return $found[0];
    }

    private function deleteDirectory(string $path): void
    {
        if (!is_dir($path)) return;
        $files = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($path, \RecursiveDirectoryIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST
        );
        foreach ($files as $file) {
            $file->isDir() ? rmdir($file->getRealPath()) : unlink($file->getRealPath());
        }
        rmdir($path);
    }

    private function extractMetadata(Request $request)
    {
        $metadata = [];
        
        // URL fields - sanitize and validate
        $urlFields = ['live_url', 'github_url', 'npm_url', 'webgl_url'];
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

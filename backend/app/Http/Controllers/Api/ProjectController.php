<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Project;
use App\Models\GameRating;
use Illuminate\Support\Facades\Storage;
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

        if ($request->has('category') && $request->category !== 'all') {
            $query->where('category', $request->category);
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
            'category' => 'nullable|string|max:100',
            'release_date' => 'nullable|date',
            'academic_year_id' => 'nullable|exists:academic_years,id',
            'team_id' => 'required|exists:teams,id',
            'splash_screen' => 'nullable|image|max:10240',
            'video' => ['nullable', 'file', 'mimes:mp4,webm,mov', 'max:51200', new VideoMaxResolution(1920, 1080)],
            'video_url' => 'nullable|url',
        ]);

        $typeValidation = $this->getTypeSpecificValidation($request->type);
        $request->validate($typeValidation);

        $team = $request->user()->teams()
            ->wherePivot('role_in_team', 'scrum_master')
            ->where('teams.id', $validated['team_id'])
            ->first();

        if (!$team) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        // Allow multiple projects per team? If not, uncomment below block.
        // $existingProject = Project::where('team_id', $team->id)->first();
        // if ($existingProject) {
        //     return response()->json(['message' => 'Váš tím už má projekt.'], 400);
        // }

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
            'category' => $validated['category'] ?? null,
            'release_date' => $validated['release_date'] ?? null,
            'academic_year_id' => $academicYearId,
            'splash_screen_path' => $splashPath,
            'video_path' => $videoPath,
            'video_url' => $validated['video_url'] ?? null,
            'files' => $files,
            'metadata' => $metadata,
        ]);

        return response()->json(['project' => $project, 'message' => 'Projekt bol úspešne vytvorený!'], 201);
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

        $existingRating = GameRating::where('project_id', $id)->where('user_id', $user->id)->first();
        if ($existingRating) {
            return response()->json(['message' => 'Projekt už bol hodnotený týmto používateľom.'], 422);
        }
        GameRating::create(['project_id' => $id, 'user_id' => $user->id, 'rating' => $request->rating]);

        $avgRating = GameRating::where('project_id', $id)->avg('rating');
        $ratingCount = GameRating::where('project_id', $id)->count();
        $project->update(['rating' => round($avgRating, 2), 'rating_count' => $ratingCount]);

        return response()->json(['message' => 'Hodnotenie uložené', 'rating' => $project->rating, 'rating_count' => $project->rating_count]);
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
        // Common optional metadata across types
        foreach (['live_url','github_url','npm_url','package_name','platform'] as $field) {
            if ($request->has($field)) {
                $val = $request->input($field);
                if ($val !== null && $val !== '') $metadata[$field] = $val;
            }
        }
        if ($request->has('tech_stack')) {
            $raw = $request->tech_stack;
            if (is_string($raw)) {
                $metadata['tech_stack'] = array_map('trim', preg_split('/[,;]/', $raw));
            } elseif (is_array($raw)) {
                $metadata['tech_stack'] = array_map(fn($v)=>trim((string)$v), $raw);
            }
        }
        return $metadata;
    }
}

<?php

namespace App\Services;

use App\DTO\ProjectData;
use App\Enums\ProjectType;
use App\Models\Game; // For now games only
use App\Models\Team;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Facade-like service to abstract future multi-project handling.
 * Currently delegates to Game model/service logic (to be split later).
 */
class ProjectService
{
    public function __construct(private GameService $gameService) {}

    /**
     * Create a project (currently only type GAME supported).
     * Returns array with 'game' key for backward compatibility.
     */
    public function create(User $user, ProjectData $data): array
    {
        return match($data->type) {
            ProjectType::GAME => $this->createGameProject($user, $data),
            default => ['error' => 'unsupported_type', 'message' => 'Project type not yet implemented.'],
        };
    }

    private function createGameProject(User $user, ProjectData $data): array
    {
        // Map ProjectData -> existing GameService expected shape.
        $payload = [
            'team_id' => $data->teamId,
            'title' => $data->title,
            'description' => $data->description,
            'category' => $data->category,
            'release_date' => $data->releaseDate,
            // Attachments handled at controller layer (unchanged for now)
        ];
        return $this->gameService->createGame($user, $payload);
    }
}

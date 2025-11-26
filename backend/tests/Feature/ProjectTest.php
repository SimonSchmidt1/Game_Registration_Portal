<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\User;
use App\Models\Team;
use App\Models\Project;
use App\Models\AcademicYear;
use App\Models\GameRating;
use Illuminate\Support\Facades\Storage;

class ProjectTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
    }

    private function makeScrumMaster(): array
    {
        $user = User::factory()->create();
        $year = AcademicYear::factory()->create();
        $team = Team::factory()->create(['academic_year_id' => $year->id]);
        $team->members()->attach($user->id, ['role_in_team' => 'scrum_master']);
        return [$user,$team];
    }

    public function test_scrum_master_can_create_project(): void
    {
        [$user,$team] = $this->makeScrumMaster();
        $this->actingAs($user, 'sanctum');

        $resp = $this->postJson('/api/projects', [
            'title' => 'Test Web App',
            'description' => 'Desc',
            'type' => 'web_app',
            'category' => 'Akčná',
            'team_id' => $team->id,
            'live_url' => 'https://example.com',
            'github_url' => 'https://github.com/example/repo',
            'tech_stack' => 'Vue, Laravel; MySQL'
        ]);

        $resp->assertCreated();
        $resp->assertJsonStructure(['project' => ['id','title','metadata']]);
        $project = Project::first();
        $this->assertEquals('web_app', $project->type);
        $this->assertEquals(['Vue','Laravel','MySQL'], $project->metadata['tech_stack']);
    }

    public function test_non_scrum_master_cannot_create_project(): void
    {
        $user = User::factory()->create();
        $year = AcademicYear::factory()->create();
        $team = Team::factory()->create(['academic_year_id' => $year->id]);
        $team->members()->attach($user->id, ['role_in_team' => 'member']);
        $this->actingAs($user, 'sanctum');

        $resp = $this->postJson('/api/projects', [
            'title' => 'Blocked',
            'description' => 'Desc',
            'type' => 'game',
            'team_id' => $team->id,
        ]);
        $resp->assertStatus(403);
    }

    public function test_single_rating_enforced(): void
    {
        [$user,$team] = $this->makeScrumMaster();
        $this->actingAs($user,'sanctum');
        $project = Project::factory()->create(['team_id' => $team->id, 'type' => 'game']);

        $first = $this->postJson("/api/projects/{$project->id}/rate", ['rating' => 5]);
        $first->assertOk();
        $second = $this->postJson("/api/projects/{$project->id}/rate", ['rating' => 4]);
        $second->assertStatus(422);
        $this->assertEquals(1, GameRating::count());
    }

    public function test_type_filtering(): void
    {
        [$user,$team] = $this->makeScrumMaster();
        $this->actingAs($user,'sanctum');
        Project::factory()->create(['team_id' => $team->id, 'type' => 'game']);
        Project::factory()->create(['team_id' => $team->id, 'type' => 'library']);

        $all = $this->getJson('/api/projects');
        $all->assertOk();
        $this->assertCount(2, $all->json());

        $filtered = $this->getJson('/api/projects?type=library');
        $filtered->assertOk();
        $this->assertCount(1, $filtered->json());
        $this->assertEquals('library', $filtered->json()[0]['type']);
    }
}

# Backend Architecture Notes (Scaffolding for Multi-Project Support)

This file introduces additive (non-breaking) structures to ease future expansion beyond Games (e.g., Applications, Libraries).

## Goals
- Preserve current working behavior (no existing controller/service modified).
- Provide enums, DTOs, service abstraction, and API resources ready for gradual adoption.
- Enable consistent serialization & validation when new project types are added.

## New Elements
1. `App\Enums\TeamRole` – Central source for role strings (scrum_master, member) to prevent typos.
2. `App\Enums\ProjectType` – Enumerates future project categories (game, application, library, other).
3. `App\DTO\ProjectData` – Immutable DTO to pass structured project creation data to services.
4. `App\Services\ProjectService` – Facade-like service; currently delegates only to `GameService`. Future types map here.
5. `App\Http\Resources\TeamResource` – Standardized JSON shape for teams including member roles. Unused yet.
6. `App\Http\Resources\GameResource` – Standardized JSON shape for games; can replace raw model responses.
7. `App\Http\Requests\GameStoreRequest` – FormRequest for better validation separation. Not wired yet.

## Recommended Gradual Adoption
1. **Controller Refactor (Optional)**: Inject `ProjectService` where game creation currently happens; branch by `ProjectType`.
2. **Resource Usage**: Return `GameResource::collection($games)` instead of raw arrays for `/api/games`.
3. **FormRequest Integration**: Replace inline `validate()` calls in game controller with `GameStoreRequest` parameter.
4. **Future Types**: Add models/migrations for new project types and extend `ProjectService::create()` match cases.

## Why This Helps
- **Consistency**: DTO + Resources produce predictable shapes for frontend.
- **Safety**: Enums reduce stringly-typed logic.
- **Extensibility**: Adding a new project type touches isolated spots (enum + service + resource).
- **Validation Clarity**: FormRequest centralizes constraints & messages.

## Next Potential Steps (Deferred)
- Add `Project` polymorphic table or separate tables per type.
- Introduce Policies for granular access control per project type.
- Implement caching for frequently accessed resources (projects list).
- Add events/listeners for project lifecycle (Created, Updated).

This is scaffolding only. Existing endpoints behavior remains unchanged.

# Frontend Architecture Notes (Preparation for Multi-Project Support)

Additive scaffolding that does not alter current behavior. All new files are unused until explicitly imported.

## New Files
1. `src/services/apiClient.js` – Central Axios instance with token + error interception.
2. `src/features/projects/useProjects.js` – Composable to unify loading of future project types (currently just games).
3. `src/types/entities.d.ts` – Type hints for Teams, Games, Projects.

## Benefits
- Single place to adjust headers, auth, timeouts.
- Future endpoints (e.g. `/projects`) plug into `useProjects` easily.
- Type hints improve IDE/AI suggestions and reduce shape confusion.

## Suggested Next Steps (Deferred)
1. Introduce Pinia store for `team` and `auth` state to remove localStorage event wiring.
2. Migrate game-specific logic into `useGames.js` composable.
3. Replace fetch calls with `apiClient` progressively.
4. Split `HomeView.vue` into: `TeamSelector.vue`, `TeamMembersList.vue`, `GameList.vue` for clarity.
5. Add dynamic project form component that switches fields by `ProjectType`.

All changes are passive. No imports added yet to avoid any build impact.

# Project Editing Feature Documentation

## Overview
Scrum Masters can now edit projects they have created. This feature allows updating project information, replacing files, and modifying metadata while maintaining data integrity and proper authorization.

## Backend Implementation

### Endpoint
- **Method**: `PUT`
- **Route**: `/api/projects/{id}`
- **Authorization**: Requires authentication + Scrum Master of the project's team
- **Throttling**: Uses `throttle:projects` middleware

### Controller Method
**File**: `backend/app/Http/Controllers/Api/ProjectController.php`
**Method**: `update(Request $request, $id)`

#### Authorization Check
```php
// Verifies user is Scrum Master of the project's team
$isScrumMaster = $project->team->members()
    ->where('users.id', $user->id)
    ->where('team_user.role_in_team', 'scrum_master')
    ->exists();
```

#### Validation
- Uses `sometimes` validation rules (fields optional unless provided)
- Validates project type if changed
- Validates year_of_study range based on school_type
- Type-specific validation based on project type

#### File Handling
- **Splash Screen**: If new file uploaded, old file deleted and new one stored
- **Video**: If new file uploaded, old file deleted and new one stored. If video_url provided, video_path cleared
- **Type-specific Files**: Old files deleted when new ones uploaded, existing files preserved if not replaced
- **Storage Paths**: Uses correct project type for file storage (handles type changes)

#### Metadata Handling
- Merges new metadata with existing metadata
- Preserves existing metadata fields if not provided
- Updates tech_stack, URLs, and type-specific fields

### Security Features
1. **Authorization**: Only Scrum Master of project's team can edit
2. **File Safety**: Old files deleted before new ones stored (prevents orphaned files)
3. **Type Validation**: Ensures project type changes are valid
4. **Data Integrity**: Preserves ratings, views, and other read-only fields

## Frontend Implementation

### Views Modified

#### 1. ProjectView.vue
**Location**: `frontend/src/views/ProjectView.vue`

**Changes**:
- Added "Upraviť projekt" button (visible only to Scrum Master)
- Added `isCurrentUserScrumMaster` computed property
- Added `loadCurrentUser()` function to get current user ID
- Added `checkScrumMasterStatus()` to verify Scrum Master role
- Added `editProject()` function to navigate to edit page

**Button Visibility Logic**:
```javascript
// Button only shows if user is Scrum Master of project's team
v-if="isCurrentUserScrumMaster"
```

#### 2. AddProjectView.vue
**Location**: `frontend/src/views/AddProjectView.vue`

**Changes**:
- Modified to support both create and edit modes
- Added `isEditMode` ref to detect edit mode from route
- Added `projectId` ref to store project ID when editing
- Added `existingProject` ref to store loaded project data
- Added `loadProjectForEdit()` function to load and populate form
- Modified `submitForm()` to handle both POST (create) and PUT (update)
- Updated button text: "Uložiť zmeny" (edit) vs "Zverejniť projekt" (create)
- Updated page title: "Upraviť projekt" (edit) vs "Pridať nový projekt" (create)

**Form Population**:
- All fields pre-populated with existing project data
- Files shown as existing (with note that uploading new replaces old)
- Metadata fields loaded from project.metadata
- School type change triggers year options update

### Routing
**File**: `frontend/src/router/index.js`

**New Route**:
```javascript
{ 
  path: '/edit-project/:id', 
  name: 'EditProject', 
  component: AddProjectView, 
  meta: { requiresAuth: true } 
}
```

## User Flow

1. **View Project**: User views project detail page
2. **Authorization Check**: System checks if user is Scrum Master of project's team
3. **Edit Button**: "Upraviť projekt" button appears if authorized
4. **Navigate to Edit**: Clicking button navigates to `/edit-project/{id}`
5. **Load Data**: Form loads and populates with existing project data
6. **Make Changes**: User modifies fields, uploads new files, etc.
7. **Submit**: Form submits PUT request to `/api/projects/{id}`
8. **Backend Validation**: Server validates authorization and data
9. **Update**: Project updated, files replaced if new ones uploaded
10. **Redirect**: User redirected back to project detail page
11. **Success Message**: Toast notification confirms successful update

## File Update Behavior

### When New File Uploaded
- Old file deleted from storage
- New file stored in appropriate directory
- Database updated with new file path

### When File Not Changed
- Existing file preserved
- No database changes for that file
- File remains accessible

### Type-Specific Files
- Files stored in type-specific directories: `projects/{type}/{folder}/`
- If project type changes, new files use new type directory
- Old files remain in original location (orphaned but not deleted)

## Edge Cases Handled

1. **Type Change**: If project type changes, new files use correct type directory
2. **File Replacement**: Old files safely deleted before new ones stored
3. **Metadata Merge**: Existing metadata preserved, new fields added
4. **Authorization**: Non-Scrum Masters cannot access edit functionality
5. **Missing Data**: Form handles null/undefined values gracefully
6. **Network Errors**: Error handling with user-friendly messages

## Testing Checklist

- [ ] Scrum Master can see "Upraviť projekt" button on their project
- [ ] Non-Scrum Master cannot see edit button
- [ ] Edit form pre-populates with existing data
- [ ] Can update text fields (title, description, etc.)
- [ ] Can replace files (splash screen, video, type-specific files)
- [ ] Existing files preserved if not replaced
- [ ] Can update metadata (URLs, tech stack, etc.)
- [ ] Can change project type (files stored in correct directory)
- [ ] Authorization check works (403 if not Scrum Master)
- [ ] Success message appears after update
- [ ] Redirects to project detail page after update
- [ ] Old files deleted when new ones uploaded
- [ ] Validation errors display correctly

## API Request Example

```http
PUT /api/projects/1
Authorization: Bearer {token}
Content-Type: multipart/form-data

title: Updated Project Title
description: Updated description
school_type: vs
subject: Informatika
splash_screen: [file]
video: [file]
tech_stack: Vue, Laravel, MySQL
```

## API Response Example

```json
{
  "project": {
    "id": 1,
    "title": "Updated Project Title",
    "description": "Updated description",
    "type": "web_app",
    "school_type": "vs",
    "subject": "Informatika",
    "splash_screen_path": "projects/web_app/splash_screens/...",
    "video_path": "projects/web_app/videos/...",
    "metadata": {
      "tech_stack": ["Vue", "Laravel", "MySQL"]
    },
    ...
  },
  "message": "Projekt bol úspešne aktualizovaný!"
}
```

## Security Considerations

1. **Authorization**: Always verified on backend, not just frontend
2. **File Deletion**: Old files deleted safely (no orphaned files)
3. **Type Validation**: Project type changes validated
4. **Data Integrity**: Ratings, views, and other metrics preserved
5. **Input Validation**: All inputs validated before processing

## Future Enhancements

Potential improvements:
- Bulk file upload/replacement
- File preview before replacement
- Change history/versioning
- Rollback functionality
- Image cropping/resizing before upload

---

*Last Updated: [Current Date]*
*Feature Version: 1.0*


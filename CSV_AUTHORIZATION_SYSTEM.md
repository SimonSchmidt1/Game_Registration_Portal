# Authorized Students System (CSV Import)

**Status:** ✅ Implemented (Feature Flag: DISABLED by default)

## Overview

This system restricts user registration to only students who are pre-approved and listed in the `authorized_students` database table. Students can be imported via CSV upload through the admin panel.

## Feature Flag

**Default:** `REQUIRE_AUTHORIZED_STUDENTS=false` (DISABLED)

The system is fully implemented but **not active** until you enable it. This allows you to:
- Import student data in advance
- Test the CSV upload functionality
- Activate when ready with a single config change

## How to Enable

1. **Get CSV from school headquarters** with student data
2. **Import CSV** via Admin Panel → "Importovať študentov (CSV)" button
3. **Verify** import was successful (check results dialog)
4. **Enable feature** by adding to `backend/.env`:
   ```env
   REQUIRE_AUTHORIZED_STUDENTS=true
   ```
5. **Clear config cache**:
   ```bash
   cd backend
   php artisan config:clear
   ```
6. **Test registration** - only authorized students can now register

## CSV Format

### Required Columns:
- `student_id` - 7-digit UCM student ID
- `name` - Full name
- `email` - UCM email (format: 1234567@ucm.sk)
- `student_type` - Either `denny` or `externy`

### Example CSV:
```csv
student_id,name,email,student_type
1234567,Ján Novák,1234567@ucm.sk,denny
7654321,Mária Kováčová,7654321@ucm.sk,externy
9876543,Peter Svoboda,9876543@ucm.sk,denny
```

**Template:** See `backend/storage/app/authorized_students_template.csv`

## Database Structure

### Migration:
`2026_02_01_000000_create_authorized_students_table.php`

### Schema:
```
authorized_students:
- id (primary key)
- student_id (string, unique, 7 chars)
- name (string)
- email (string, unique)
- student_type (enum: denny, externy)
- is_active (boolean, default true)
- created_at, updated_at
```

## Features

### Admin Panel
- **CSV Upload** - Import students in bulk
- **Error Reporting** - Shows validation errors per row
- **Update Support** - Re-uploading updates existing records
- **Toggle Active Status** - Deactivate students without deleting

### Registration Validation
- Checks if email exists in `authorized_students` table
- Only active students can register
- Auto-fills name and student_type from authorized data
- **International teams (SPE) are exempt** - always bypass this check

### API Endpoints (Admin Only)
```
POST   /api/admin/authorized-students/import       - Upload CSV
GET    /api/admin/authorized-students             - List all
POST   /api/admin/authorized-students/{id}/toggle - Toggle active status
GET    /api/admin/config                          - Get feature flag status
```

## Implementation Files

### Backend:
- ✅ `app/Models/AuthorizedStudent.php` - Model with helper methods
- ✅ `database/migrations/2026_02_01_000000_create_authorized_students_table.php`
- ✅ `app/Http/Controllers/Api/AdminController.php` - Import logic
- ✅ `app/Http/Controllers/Api/AuthController.php` - Registration validation
- ✅ `routes/api.php` - New admin routes
- ✅ `config/app.php` - Feature flag config

### Frontend:
- ✅ `src/views/AdminView.vue` - CSV upload dialog and UI

### Config:
- ✅ `.env.example` - Added `REQUIRE_AUTHORIZED_STUDENTS` setting

## Validation Rules

### CSV Import:
- `student_id`: Required, 7 digits, unique
- `name`: Required, string
- `email`: Required, valid email format, unique
- `student_type`: Required, must be `denny` or `externy`

### Registration Check:
- Email must exist in `authorized_students` table
- `is_active` must be `true`
- **Exception:** International teams (invite code starting with "SPE") always skip this check

## Error Handling

### CSV Import Errors:
- Missing required columns
- Invalid student_id format (not 7 digits)
- Invalid email format
- Invalid student_type (not denny/externy)
- Duplicate emails (last import wins)

All errors are logged with line numbers for easy debugging.

### Registration Errors:
When feature is enabled and student not authorized:
```json
{
  "error": "Nie ste registrovaný v systéme UCM. Kontaktujte administrátora.",
  "requires_authorization": true
}
```
**HTTP Status:** 403 Forbidden

## Testing Checklist

### Before Enabling:
- [ ] Run migration: `php artisan migrate`
- [ ] Upload test CSV with sample students
- [ ] Verify import success (check statistics)
- [ ] Check `authorized_students` table has data
- [ ] Feature flag still shows disabled in admin panel

### After Enabling:
- [ ] Set `REQUIRE_AUTHORIZED_STUDENTS=true` in `.env`
- [ ] Clear config: `php artisan config:clear`
- [ ] Admin panel button no longer disabled
- [ ] Try registering with authorized email → Success
- [ ] Try registering with non-authorized email → Error 403
- [ ] International team registration still works (SPE code)

## Rollback Plan

If you need to disable the feature:

1. Set `REQUIRE_AUTHORIZED_STUDENTS=false` in `.env`
2. Run `php artisan config:clear`
3. System immediately returns to normal (all emails allowed)
4. Data in `authorized_students` table is preserved

## Future Enhancements

Potential improvements when needed:
- Bulk student activation/deactivation
- Export authorized students to CSV
- Automatic sync with school database (if direct access granted)
- Email notifications to students when added/removed
- Admin audit log for student list changes

## Notes

- **International teams are always exempt** - SPE invite codes bypass authorization
- **Existing users are not affected** - check only applies to new registrations
- **CSV can be re-uploaded** - Updates existing records, adds new ones
- **Safe to implement now** - Zero impact until feature flag enabled
- **Template CSV provided** in `backend/storage/app/authorized_students_template.csv`

---

**Last Updated:** February 1, 2026  
**Version:** 1.5.0 (Prepared, Disabled)  
**Status:** Ready for activation when CSV data received from school HQ

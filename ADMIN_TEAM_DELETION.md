# Admin Team Deletion - How It Works

**Date:** November 30, 2025

---

## How Team Deletion Works

### Soft Delete (Not Permanent)

The system uses **Soft Deletes** for teams, which means:

1. ✅ **The record stays in the database** - it's not permanently removed
2. ✅ **A `deleted_at` timestamp is set** - marks when it was deleted
3. ✅ **The team is hidden from normal queries** - won't appear in lists
4. ✅ **Can be recovered later** - if needed by admin

### Why Soft Delete?

- **Safety**: Prevents accidental permanent data loss
- **Recovery**: Can restore deleted teams if needed
- **Audit trail**: Can see what was deleted and when
- **Relationships**: Preserves referential integrity

---

## Verifying Deletion

### ✅ Correct Way to Check (Application)

1. **In Admin Panel**: Deleted teams should disappear from the list
2. **Via API**: `GET /api/admin/teams` won't return deleted teams
3. **In Code**: `Team::find($id)` returns `null` for deleted teams

### ❌ Incorrect Way to Check (Direct Database)

If you check the database directly with:
```sql
SELECT * FROM teams WHERE id = X;
```

**You WILL still see the record!** This is normal and expected.

### ✅ Correct Database Check

To see if a team is deleted, check for `deleted_at`:
```sql
SELECT id, name, deleted_at FROM teams WHERE id = X;
```

- `deleted_at = NULL` → Team is **active** (not deleted)
- `deleted_at = '2025-11-30 15:30:00'` → Team is **deleted** (soft deleted)

---

## How to Verify Deletion Worked

### Method 1: Check Admin Panel
1. Delete a team via admin panel
2. Refresh the teams list
3. The deleted team should **disappear** from the list

### Method 2: Check Laravel Logs
```bash
tail -f backend/storage/logs/laravel.log | grep "deleted team"
```

You should see:
```
[2025-11-30 15:30:00] local.INFO: Team soft deleted successfully {"team_id":5,"team_name":"Test Team","deleted_at":"2025-11-30 15:30:00"}
```

### Method 3: Check Database (Correct Way)
```sql
SELECT id, name, deleted_at 
FROM teams 
WHERE id = YOUR_TEAM_ID;
```

If `deleted_at` has a timestamp, deletion worked!

### Method 4: Test via API
```bash
curl -X GET http://127.0.0.1:8000/api/admin/teams \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Accept: application/json"
```

The deleted team should **not** appear in the response.

---

## Troubleshooting

### Team Still Appears in Admin Panel After Delete

**Possible Causes:**
1. Frontend not refreshing - try manual refresh (F5)
2. Browser cache - clear cache and try again
3. Delete failed silently - check Laravel logs

**Fix:**
1. Check browser console for errors
2. Check Laravel logs: `backend/storage/logs/laravel.log`
3. Try deleting again and watch the logs

### Team Still in Database After Delete

**This is NORMAL!** Soft delete keeps the record but sets `deleted_at`.

**To verify it's deleted:**
```sql
SELECT deleted_at FROM teams WHERE id = YOUR_TEAM_ID;
```

If `deleted_at` is NOT NULL, deletion worked correctly.

### Want Permanent Deletion?

If you need to permanently delete a team (not recommended):

```php
// In tinker or a command
$team = Team::withTrashed()->find($id);
$team->forceDelete(); // Permanent deletion
```

**Warning:** This cannot be undone!

---

## Code Implementation

### Backend (`AdminController::deleteTeam`)

```php
// Detaches all members
$team->members()->detach();

// Soft deletes the team (sets deleted_at)
$team->delete();

// Verifies deletion
if (!$team->trashed()) {
    throw new \Exception('Deletion failed');
}
```

### Frontend (`AdminView.vue`)

```javascript
// After successful delete
await loadData(); // Refreshes the teams list
```

The `loadData()` function calls `/api/admin/teams`, which automatically excludes soft-deleted teams because the `Team` model uses `SoftDeletes` trait.

---

## Summary

✅ **Deletion IS working** if:
- Team disappears from admin panel
- `deleted_at` column has a timestamp in database
- Team doesn't appear in API responses

❌ **Deletion is NOT working** if:
- Team still appears in admin panel after refresh
- `deleted_at` is still NULL after delete
- Error messages in logs or browser console

---

## Need Help?

If deletion isn't working:

1. **Check Laravel logs** for error messages
2. **Check browser console** for API errors
3. **Verify migration ran**: `php artisan migrate:status`
4. **Check `deleted_at` column exists**: 
   ```sql
   DESCRIBE teams;
   ```
   Should show `deleted_at` column


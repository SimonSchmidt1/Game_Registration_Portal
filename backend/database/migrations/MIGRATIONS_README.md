# Migrations Guide

This document explains the migration structure and which migrations are legacy.

## Migration Status

All migrations have been run. The database is up-to-date.

## Legacy Migrations (Can be ignored for new installations)

These migrations are kept for historical reference but the changes have been consolidated:

### Games Table Migrations (LEGACY - replaced by projects table)
- `2025_11_10_171626_create_games_table.php` - Created games table (LEGACY)
- `2025_11_20_173859_add_rating_and_views_to_games_table.php` - Added rating/views to games (LEGACY)
- `2025_11_21_021706_add_rating_count_to_games_table.php` - Added rating_count to games (LEGACY)

**Note**: All games were migrated to the projects table in `2025_11_23_155535_migrate_games_to_projects.php`. The games table can be dropped using `2025_11_28_172557_drop_games_table_if_exists.php`.

### Column Addition Migrations (Can be consolidated)
These migrations add columns to existing tables. For new installations, these could be combined into the initial table creation:

- `2025_11_09_234456_add_role_to_users_table.php` - Adds role column to users
- `2025_11_12_003325_add_team_id_to_users_table.php` - Adds team_id to users (legacy, now using team_user pivot)
- `2025_11_14_000001_add_scrum_master_id_to_teams_table.php` - Adds scrum_master_id to teams
- `2025_11_20_000001_add_avatar_path_to_users_table.php` - Adds avatar_path to users
- `2025_11_21_024214_add_email_verification_and_failed_logins_to_users_table.php` - Adds email verification fields

## Current Active Migrations

### Core Tables
- `0001_01_01_000000_create_users_table.php` - Users table (Laravel default)
- `2025_11_10_171604_create_academic_years_table.php` - Academic years
- `2025_11_10_171618_create_teams_table.php` - Teams
- `2025_11_10_171622_create_team_user_table.php` - Team-user pivot table
- `2025_11_23_154309_create_projects_table.php` - Projects table (replaces games)
- `2025_11_21_021633_create_game_ratings_table.php` - Ratings (works with projects)
- `2025_11_25_171337_create_password_reset_tokens_table.php` - Password reset tokens

### Data Migration
- `2025_11_23_155535_migrate_games_to_projects.php` - Migrated all games to projects table

### Schema Updates
- `2025_11_23_160414_add_project_id_to_game_ratings_table.php` - Added project_id to ratings
- `2025_11_23_161457_add_unique_project_user_index_to_game_ratings.php` - Added unique index
- `2025_11_24_134910_drop_game_id_from_game_ratings_table.php` - Removed game_id from ratings
- `2025_11_25_153720_add_performance_indexes_to_tables.php` - Performance indexes
- `2025_11_26_023739_add_soft_deletes_to_users_teams_games_tables.php` - Soft deletes (updated to handle games table safely)
- `2025_11_26_023841_add_admin_indexes_to_tables.php` - Admin indexes (updated to handle games table safely)
- `2025_11_28_165758_update_projects_categorization_system.php` - New categorization system
- `2025_11_28_172557_drop_games_table_if_exists.php` - Drops legacy games table

## For New Installations

If setting up a fresh database, you can:
1. Run all migrations in order (they're all safe)
2. The games table will be created and then dropped automatically
3. All data will be in the projects table

## Consolidation Notes

For future reference, if creating a fresh installation from scratch, you could consolidate:
- All user table column additions into the initial users table creation
- All team table column additions into the initial teams table creation
- Skip games table creation entirely (use projects from the start)

However, **do not delete existing migrations** - they're part of the migration history and may be needed for rollbacks or other environments.


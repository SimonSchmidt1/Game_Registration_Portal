# Admin Permissions Design - Future Implementation

## Overview
This document outlines all admin permissions that should be implemented to allow administrators to manage the Game Registration Portal without requiring code changes.

---

## 1. Team Management

### 1.1 Team Creation Control ⭐ (Priority - User Requested)
- **Allow/Reject Team Creation**
  - Toggle global setting: "Allow new team creation" (on/off)
  - When disabled: Hide "Vytvoriť Tím" button, show message explaining why
  - When enabled: Normal team creation flow
  - Reason: Control system growth, manage capacity, freeze registrations during maintenance

### 1.2 Team Moderation
- **View All Teams**
  - List all teams with filters (academic year, member count, creation date)
  - Search by team name, invite code, Scrum Master name
  - Sort by various criteria

- **Edit Team Details**
  - Change team name
  - Change invite code (regenerate)
  - Change academic year assignment
  - Transfer Scrum Master role to another member

- **Delete/Archive Teams**
  - Soft delete teams (recoverable)
  - Permanent delete (with confirmation)
  - Bulk delete operations
  - Archive inactive teams

- **Team Member Management**
  - View all members of any team
  - Remove members from teams
  - Add members to teams (by email)
  - Change member occupations
  - Change member roles (promote to Scrum Master, demote to member)

- **Team Limits**
  - Set maximum team size (currently hardcoded to 10)
  - Set minimum team size
  - Set maximum teams per user
  - Set maximum teams per academic year

---

## 2. User Management

### 2.1 User Account Control
- **View All Users**
  - List all users with filters (role, student type, verification status, registration date)
  - Search by name, email, student ID
  - Export user list (CSV/Excel)

- **Edit User Details**
  - Change user name
  - Change email (with validation)
  - Change student type (daily/external)
  - Change role (user/admin)
  - Reset password (force password reset on next login)
  - Unlock locked accounts (reset failed login attempts)

- **User Verification**
  - Manually verify email (bypass email verification)
  - Resend verification email
  - Revoke verification (force re-verification)

- **Account Actions**
  - Suspend/Unsuspend accounts (prevent login)
  - Delete accounts (soft delete with recovery)
  - Permanent delete (with confirmation)
  - Merge duplicate accounts

- **Bulk Operations**
  - Bulk verify users
  - Bulk suspend users
  - Bulk delete users
  - Bulk export user data

---

## 3. Project/Game Management

### 3.1 Project Moderation
- **View All Projects**
  - List all projects with filters (type, school type, academic year, team, status)
  - Search by title, description, team name
  - Sort by rating, views, creation date

- **Project Status Control**
  - Approve/Reject projects (before public visibility)
  - Set project status: Draft, Pending Review, Approved, Rejected, Archived
  - Bulk approve/reject operations

- **Edit Project Details**
  - Edit any project (title, description, metadata)
  - Change project type
  - Update categorization (school type, year, subject)
  - Modify files (replace splash screen, video, source code)
  - Update links (GitHub, live URL, etc.)

- **Project Actions**
  - Delete projects (soft delete with recovery)
  - Permanent delete (with file cleanup)
  - Archive old projects
  - Feature projects (highlight on homepage)
  - Pin projects to top of listings

- **Content Moderation**
  - Flag inappropriate content
  - Review flagged projects
  - Edit/Remove inappropriate descriptions
  - Censor sensitive information

---

## 4. Academic Year Management

### 4.1 Academic Year Control
- **Create/Edit Academic Years**
  - Create new academic years
  - Edit existing academic year names
  - Set start/end dates
  - Set as active/inactive
  - Delete academic years (with team/project reassignment)

- **Academic Year Assignment**
  - Assign teams to different academic years
  - Bulk reassign projects to academic years
  - Set default academic year for new registrations

---

## 5. System Configuration

### 5.1 Registration & Access Control
- **Registration Settings**
  - Enable/Disable new user registration
  - Enable/Disable team creation (⭐ User Requested)
  - Enable/Disable project submission
  - Set registration periods (start/end dates)
  - Require admin approval for new accounts

- **Login & Security**
  - Configure failed login attempt limits (currently 5)
  - Configure temporary password expiration (currently 15 minutes)
  - Configure session token expiration (currently 2 hours for users, 24 hours for admin)
  - Enable/Disable password reset functionality
  - Configure email verification requirements

- **Email Settings**
  - Configure email templates (verification, password reset, temporary password)
  - Test email sending
  - View email logs
  - Configure email rate limiting

### 5.2 Content & Display Settings
- **Homepage Configuration**
  - Set featured projects
  - Configure project display limits
  - Set default filters
  - Configure sorting defaults

- **Project Display Rules**
  - Set minimum rating for display
  - Set minimum views for display
  - Configure project visibility rules
  - Set project expiration dates (auto-archive old projects)

### 5.3 File Management
- **Storage Limits**
  - Set maximum file sizes (currently: splash 5MB, video 50MB, source 500MB)
  - Set maximum total storage per team
  - Configure file retention policies
  - Clean up orphaned files

- **File Moderation**
  - Review uploaded files
  - Scan for malicious content
  - Delete inappropriate files
  - Regenerate thumbnails

---

## 6. Ratings & Reviews Management

### 6.1 Rating Control
- **View All Ratings**
  - List all ratings with filters (project, user, date, value)
  - Search by user or project

- **Rating Moderation**
  - Delete suspicious ratings
  - Reset project ratings (recalculate average)
  - Ban users from rating
  - Set minimum rating threshold

- **Rating Statistics**
  - View rating distribution
  - Identify projects with unusual rating patterns
  - Export rating data

---

## 7. Analytics & Reporting

### 7.1 System Statistics
- **User Statistics**
  - Total users, active users, verified users
  - Registration trends (daily/weekly/monthly)
  - User activity metrics
  - Student type distribution

- **Team Statistics**
  - Total teams, active teams
  - Average team size
  - Teams per academic year
  - Team creation trends

- **Project Statistics**
  - Total projects, by type, by school type
  - Average rating, total views
  - Most popular projects
  - Project submission trends

- **System Health**
  - Database size, file storage usage
  - API request counts
  - Error rates
  - Performance metrics

### 7.2 Reports
- **Generate Reports**
  - User activity reports
  - Team participation reports
  - Project submission reports
  - Export data (CSV, Excel, PDF)

- **Scheduled Reports**
  - Configure automatic report generation
  - Email reports to admins
  - Archive historical reports

---

## 8. Notifications & Communications

### 8.1 System Notifications
- **Send Announcements**
  - Broadcast messages to all users
  - Send targeted messages (by role, team, academic year)
  - Schedule announcements
  - View notification history

### 8.2 Email Management
- **Email Templates**
  - Edit email templates (verification, password reset, etc.)
  - Preview email templates
  - Test email delivery
  - Configure email variables

---

## 9. Logs & Audit Trail

### 9.1 Activity Logs
- **View System Logs**
  - User login/logout logs
  - Team creation/join/leave logs
  - Project creation/edit/delete logs
  - Admin action logs
  - Error logs

- **Audit Trail**
  - Track all admin actions
  - View change history for users, teams, projects
  - Export audit logs
  - Search logs by user, action, date

---

## 10. Backup & Maintenance

### 10.1 Data Management
- **Backup Operations**
  - Create manual backups
  - Configure automatic backups
  - Restore from backup
  - View backup history

- **Database Maintenance**
  - Optimize database tables
  - Clean up old data
  - Rebuild indexes
  - Run database health checks

### 10.2 System Maintenance
- **Maintenance Mode**
  - Enable/Disable maintenance mode
  - Set maintenance message
  - Configure maintenance access (admin only or specific IPs)

- **Cache Management**
  - Clear application cache
  - Clear config cache
  - Clear route cache
  - Clear view cache

---

## 11. Advanced Permissions (Future)

### 11.1 Role-Based Admin Access
- **Admin Roles**
  - Super Admin (full access)
  - Moderator (content moderation only)
  - Support Admin (user management only)
  - Analytics Admin (reports and statistics only)

### 11.2 API Management
- **API Keys**
  - Generate API keys for external integrations
  - Revoke API keys
  - View API usage statistics
  - Configure API rate limits

---

## Implementation Priority

### Phase 1 (Critical - Immediate)
1. ⭐ **Team Creation Control** (User Requested)
2. User Account Management (view, edit, suspend)
3. Project Moderation (approve/reject, edit, delete)
4. System Configuration (registration, login settings)

### Phase 2 (Important - Short Term)
5. Team Management (view, edit, delete teams)
6. Academic Year Management
7. Content Moderation Tools
8. Basic Analytics Dashboard

### Phase 3 (Useful - Medium Term)
9. Advanced User Management (bulk operations)
10. File Management & Storage
11. Rating Management
12. Notification System

### Phase 4 (Nice to Have - Long Term)
13. Advanced Analytics & Reporting
14. Audit Trail & Logging
15. Backup & Maintenance Tools
16. Role-Based Admin Access

---

## Technical Considerations

### Database Changes Needed
- Add `settings` table for system configuration
- Add `admin_actions` table for audit trail
- Add `project_status` enum/column
- Add `team_creation_enabled` boolean to settings
- Add `suspended_at` timestamp to users table
- Add `featured` boolean to projects table

### Frontend Requirements
- Admin dashboard/panel
- Admin navigation menu
- Permission checks on all admin routes
- Confirmation dialogs for destructive actions
- Bulk action interfaces
- Data tables with sorting/filtering

### Backend Requirements
- Admin middleware (already exists)
- Settings service/controller
- Audit logging service
- Permission checking helpers
- Bulk operation endpoints
- Export functionality

---

## Notes

- All admin actions should be logged for audit purposes
- Destructive actions (delete, permanent delete) should require confirmation
- Bulk operations should have progress indicators
- All admin interfaces should have permission checks
- Consider rate limiting on admin endpoints
- Admin actions should be reversible where possible (soft deletes)

---

**Last Updated**: Based on system analysis and user requirements
**Status**: Design Document - Ready for Implementation


# Complete Test Plan - Game Registration Portal

## Table of Contents
1. [Authentication Tests](#authentication-tests)
2. [User Management Tests](#user-management-tests)
3. [Team Management Tests](#team-management-tests)
4. [Project Management Tests](#project-management-tests)
5. [Email Functionality Tests](#email-functionality-tests)
6. [Security & Edge Cases](#security--edge-cases)
7. [UI/UX Tests](#uiux-tests)
8. [Integration Tests](#integration-tests)

---

## Authentication Tests

### 1. User Registration

#### Test 1.1: Successful Registration
- **Steps**:
  1. Navigate to `/register`
  2. Fill in valid UCM email (format: `1234567@ucm.sk`)
  3. Enter password (min 8 characters)
  4. Enter matching password confirmation
  5. Enter name
  6. Select student type (denny/externy)
  7. Submit form
- **Expected**: 
  - Success message displayed
  - Redirected to email verification page
  - Verification email sent to MailHog
  - User created in database with `email_verified_at = null`

#### Test 1.2: Registration with Invalid Email Format
- **Steps**:
  1. Try to register with email not matching `[0-9]{7}@ucm\.sk`
  2. Examples: `test@ucm.sk`, `123456@ucm.sk`, `12345678@ucm.sk`, `user@gmail.com`
- **Expected**: 
  - Validation error displayed
  - Form not submitted
  - No user created

#### Test 1.3: Registration with Duplicate Email
- **Steps**:
  1. Register with email `1234567@ucm.sk`
  2. Try to register again with same email
- **Expected**: 
  - Error message: "Email already exists"
  - Registration fails

#### Test 1.4: Registration with Weak Password
- **Steps**:
  1. Try to register with password less than 8 characters
- **Expected**: 
  - Validation error
  - Password strength requirement message

#### Test 1.5: Registration with Mismatched Passwords
- **Steps**:
  1. Enter password: `password123`
  2. Enter password confirmation: `password456`
- **Expected**: 
  - Validation error
  - Passwords must match message

---

### 2. Email Verification

#### Test 2.1: Successful Email Verification
- **Prerequisites**: User registered but not verified
- **Steps**:
  1. Check MailHog for verification email
  2. Click verification link or copy token
  3. Navigate to `/verify-email` and submit token
- **Expected**: 
  - Email verified successfully
  - `email_verified_at` set in database
  - Redirected to login page
  - Success message displayed

#### Test 2.2: Verification with Invalid Token
- **Steps**:
  1. Try to verify with random/invalid token
- **Expected**: 
  - Error message: "Invalid verification token"
  - Email remains unverified

#### Test 2.3: Verification with Expired Token
- **Steps**:
  1. Wait for token to expire (if expiration is implemented)
  2. Try to verify
- **Expected**: 
  - Error message about expired token
  - Option to resend verification email

#### Test 2.4: Resend Verification Email
- **Steps**:
  1. As unverified user, request verification email again
- **Expected**: 
  - New verification email sent to MailHog
  - Old token invalidated (if applicable)

---

### 3. User Login

#### Test 3.1: Successful Login
- **Prerequisites**: User registered and verified
- **Steps**:
  1. Navigate to `/login`
  2. Enter valid email and password
  3. Submit
- **Expected**: 
  - Success message
  - Token stored in localStorage
  - Redirected to home page (`/`)
  - User data loaded
  - Navbar shows user info

#### Test 3.2: Login with Wrong Password (Attempts 1-4)
- **Steps**:
  1. Enter correct email, wrong password
  2. Submit
  3. Repeat 4 times
- **Expected**: 
  - Error message: "Nesprávne heslo"
  - Shows remaining attempts: "Zostávajúce pokusy: X"
  - Counter increments: 1, 2, 3, 4
  - No temporary password sent yet

#### Test 3.3: Login with Wrong Password (5th Attempt)
- **Prerequisites**: 4 failed attempts already
- **Steps**:
  1. Enter wrong password 5th time
- **Expected**: 
  - Status 429 response
  - Temporary password email sent to MailHog
  - Frontend switches to temporary password form
  - Toast: "Dočasné heslo odoslané"
  - Counter = 5

#### Test 3.4: Login with Wrong Password (6+ Attempts)
- **Prerequisites**: 5+ failed attempts
- **Steps**:
  1. Continue entering wrong password
- **Expected**: 
  - New temporary password sent each time
  - Previous temporary passwords invalidated
  - Counter continues incrementing: 6, 7, 8...
  - Can still attempt login

#### Test 3.5: Login with Temporary Password
- **Prerequisites**: Temporary password received (5+ failed attempts)
- **Steps**:
  1. Check MailHog for temporary password (format: `XXXX-XXXX-XXXX`)
  2. Enter email and temporary password
  3. Submit
- **Expected**: 
  - Login successful
  - Token stored
  - Redirected to home
  - `failed_login_attempts` reset to 0
  - Toast: "Odporúčame zmeniť heslo v nastaveniach"

#### Test 3.6: Login with Invalid Temporary Password
- **Steps**:
  1. Enter valid email, invalid temporary password format
- **Expected**: 
  - Error: "Neplatné dočasné heslo alebo email"
  - Login fails

#### Test 3.7: Login with Expired Temporary Password
- **Steps**:
  1. Wait 15+ minutes after temporary password sent
  2. Try to login with expired temporary password
- **Expected**: 
  - Error: "Temporary password expired"
  - Must request new temporary password (by failing login again)

#### Test 3.8: Login with Unverified Email
- **Prerequisites**: User registered but not verified
- **Steps**:
  1. Try to login
- **Expected**: 
  - Status 403
  - Error: "Účet nie je overený"
  - Redirected to verification page
  - Verification email resent

#### Test 3.9: Login with Non-Existent Email
- **Steps**:
  1. Enter email that doesn't exist in database
- **Expected**: 
  - Error: "Nesprávny email alebo heslo"
  - No user-specific error (security best practice)
  - Counter not incremented (no user found)

#### Test 3.10: Counter Reset After Session Expiration
- **Prerequisites**: User has failed attempts, but last login was 2+ hours ago
- **Steps**:
  1. Check user has tokens older than 2 hours
  2. Try to login with wrong password
- **Expected**: 
  - Counter resets to 0 before incrementing
  - Fresh 5 attempts available
  - Log shows: "Resetting failed login attempts due to session expiration"

#### Test 3.11: Counter Reset After Successful Login
- **Prerequisites**: User has 3 failed attempts
- **Steps**:
  1. Login successfully with correct password
- **Expected**: 
  - `failed_login_attempts` reset to 0
  - Login successful

---

### 4. Password Reset

#### Test 4.1: Request Password Reset
- **Steps**:
  1. Navigate to `/forgot-password`
  2. Enter valid registered email
  3. Submit
- **Expected**: 
  - Success message (even if email doesn't exist - security)
  - Password reset email sent to MailHog
  - Token created in `password_reset_tokens` table
  - Old reset tokens invalidated

#### Test 4.2: Password Reset Email Content
- **Prerequisites**: Password reset requested
- **Steps**:
  1. Check MailHog for reset email
- **Expected**: 
  - Subject: "Reset hesla - Game Registration Portal"
  - Contains reset link with token
  - Link valid for 1 hour
  - Clear instructions

#### Test 4.3: Reset Password with Valid Token
- **Prerequisites**: Password reset email received
- **Steps**:
  1. Click reset link or navigate to `/reset-password?token=XXX`
  2. Enter new password
  3. Confirm new password
  4. Submit
- **Expected**: 
  - Password changed successfully
  - Token marked as used
  - Can login with new password
  - Cannot reuse same token

#### Test 4.4: Reset Password with Invalid Token
- **Steps**:
  1. Try to reset with random/invalid token
- **Expected**: 
  - Error: "Invalid or expired token"
  - Password not changed

#### Test 4.5: Reset Password with Expired Token
- **Steps**:
  1. Wait 1+ hour after token creation
  2. Try to use token
- **Expected**: 
  - Error: "Token expired"
  - Must request new reset link

#### Test 4.6: Reset Password with Used Token
- **Prerequisites**: Token already used once
- **Steps**:
  1. Try to use same token again
- **Expected**: 
  - Error: "Token already used"
  - Must request new reset link

#### Test 4.7: Request Multiple Password Resets
- **Steps**:
  1. Request password reset
  2. Request again before using first token
- **Expected**: 
  - First token invalidated
  - New token created
  - Only latest email works

---

### 5. Logout

#### Test 5.1: Successful Logout
- **Prerequisites**: User logged in
- **Steps**:
  1. Click logout button
- **Expected**: 
  - Token revoked
  - Token removed from localStorage
  - Redirected to login page
  - User data cleared

#### Test 5.2: Logout with Invalid Token
- **Steps**:
  1. Manually remove token from localStorage
  2. Try to logout
- **Expected**: 
  - Handled gracefully
  - User redirected/logged out

---

## User Management Tests

### 6. Profile Management

#### Test 6.1: View Current User Profile
- **Prerequisites**: User logged in
- **Steps**:
  1. Navigate to profile page (if exists) or check `/api/user`
- **Expected**: 
  - User data displayed: name, email, student_type, avatar
  - Email is read-only

#### Test 6.2: Update Profile Name
- **Steps**:
  1. Navigate to profile settings
  2. Change name
  3. Save
- **Expected**: 
  - Name updated in database
  - Success message
  - UI reflects new name immediately

#### Test 6.3: Update Profile with Invalid Data
- **Steps**:
  1. Try to set empty name
- **Expected**: 
  - Validation error
  - Changes not saved

#### Test 6.4: Upload Avatar
- **Steps**:
  1. Navigate to profile
  2. Select image file (JPG, PNG)
  3. Upload
- **Expected**: 
  - Avatar uploaded successfully
  - Image stored in storage
  - Avatar displayed in UI
  - Avatar URL accessible

#### Test 6.5: Upload Invalid Avatar File
- **Steps**:
  1. Try to upload non-image file (PDF, TXT)
  2. Try to upload file > max size
- **Expected**: 
  - Validation error
  - Upload rejected
  - Clear error message

#### Test 6.6: Change Password
- **Prerequisites**: User logged in
- **Steps**:
  1. Navigate to password change
  2. Enter current password
  3. Enter new password
  4. Confirm new password
  5. Submit
- **Expected**: 
  - Password changed
  - Must login again with new password
  - Old password no longer works

#### Test 6.7: Change Password with Wrong Current Password
- **Steps**:
  1. Enter incorrect current password
  2. Enter new password
- **Expected**: 
  - Error: "Current password is incorrect"
  - Password not changed

#### Test 6.8: Change Password with Weak New Password
- **Steps**:
  1. Enter valid current password
  2. Enter new password < 8 characters
- **Expected**: 
  - Validation error
  - Password not changed

---

## Team Management Tests

### 7. Team Creation

#### Test 7.1: Create Team (Scrum Master)
- **Prerequisites**: User logged in, not in team
- **Steps**:
  1. Navigate to home page
  2. Click "Vytvoriť Tím"
  3. Enter team name
  4. Select academic year
  5. Select occupation (Programátor, Grafik 2D, etc.)
  6. Submit
- **Expected**: 
  - Team created successfully
  - User becomes Scrum Master
  - Invite code generated (6 characters, uppercase)
  - Success dialog with invite code
  - Team appears in "Moje Tímy"
  - User occupation saved in pivot table

#### Test 7.2: Create Team with Duplicate Name
- **Prerequisites**: Team with name "Team Alpha" exists
- **Steps**:
  1. Try to create team with name "Team Alpha"
- **Expected**: 
  - Validation error: "Team name already exists"
  - Team not created

#### Test 7.3: Create Team Without Required Fields
- **Steps**:
  1. Try to create team without name
  2. Try without academic year
  3. Try without occupation
- **Expected**: 
  - Validation errors for each missing field
  - Team not created

#### Test 7.4: Create Multiple Teams
- **Steps**:
  1. Create first team
  2. Create second team
- **Expected**: 
  - Both teams created
  - User is Scrum Master of both
  - Can switch between teams

---

### 8. Team Joining

#### Test 8.1: Join Team with Valid Code
- **Prerequisites**: Team exists with invite code
- **Steps**:
  1. Click "Pripojiť sa k tímu"
  2. Enter 6-character invite code
  3. Select occupation
  4. Submit
- **Expected**: 
  - Successfully joined team
  - User added as member (not Scrum Master)
  - Occupation saved in pivot
  - Team appears in "Moje Tímy"
  - Member count incremented

#### Test 8.2: Join Team with Invalid Code
- **Steps**:
  1. Enter invalid/non-existent invite code
- **Expected**: 
  - Error: "Tím s týmto kódom nebol nájdený"
  - Not joined

#### Test 8.3: Join Team When Already Member
- **Prerequisites**: User already in team
- **Steps**:
  1. Try to join same team again
- **Expected**: 
  - Error: "Už ste členom tohto tímu"
  - Not added again

#### Test 8.4: Join Full Team
- **Prerequisites**: Team has 10 members (max capacity)
- **Steps**:
  1. Try to join team
- **Expected**: 
  - Error: "Tím je plný. Maximálny počet členov je 10."
  - Not joined

#### Test 8.5: Join Team Without Occupation
- **Steps**:
  1. Enter valid invite code
  2. Don't select occupation
  3. Submit
- **Expected**: 
  - Error: "Povinné je zadať povolanie"
  - Not joined

#### Test 8.6: Join Team with Invalid Occupation
- **Steps**:
  1. Try to submit invalid occupation value
- **Expected**: 
  - Validation error
  - Not joined

---

### 9. Team Viewing

#### Test 9.1: View Team Details
- **Prerequisites**: User in team
- **Steps**:
  1. Navigate to `/team/{id}` or click team name
- **Expected**: 
  - Team name displayed
  - Academic year shown
  - Member count (X/10)
  - All members listed with:
    - Name
    - Email
    - Occupation badge
    - "S" badge for Scrum Master
  - Invite code displayed
  - Team projects listed

#### Test 9.2: View Team as Scrum Master
- **Prerequisites**: User is Scrum Master
- **Steps**:
  1. View team details
- **Expected**: 
  - "S" badge visible next to user's name
  - Can see all members
  - Can remove members (buttons visible)

#### Test 9.3: View Team as Member
- **Prerequisites**: User is regular member
- **Steps**:
  1. View team details
- **Expected**: 
  - No "S" badge
  - Can see all members
  - Cannot remove members
  - Can leave team (button visible)

#### Test 9.4: View Team Members with Occupations
- **Steps**:
  1. View team with multiple members
- **Expected**: 
  - Each member shows occupation badge:
    - Programátor
    - Grafik 2D
    - Grafik 3D
    - Tester
    - Animátor
  - Occupations displayed in member list

#### Test 9.5: Click Member to View Details
- **Steps**:
  1. Click on member card in team view
- **Expected**: 
  - Dialog opens showing:
    - Member name
    - Email
    - Student type
    - Occupation
    - Role (S if Scrum Master)
  - Avatar displayed if available

---

### 10. Team Member Management

#### Test 10.1: Remove Member (Scrum Master)
- **Prerequisites**: User is Scrum Master, team has members
- **Steps**:
  1. Open "Moje Tímy" dialog
  2. Click "Odstrániť" next to member
  3. Confirm
- **Expected**: 
  - Member removed from team
  - Member count decremented
  - Success message
  - Team list updated

#### Test 10.2: Remove Member (Non-Scrum Master)
- **Prerequisites**: User is regular member
- **Steps**:
  1. Try to remove another member
- **Expected**: 
  - Remove button not visible
  - Cannot remove (403 if API called directly)

#### Test 10.3: Remove Scrum Master
- **Prerequisites**: User is Scrum Master
- **Steps**:
  1. Try to remove self or another Scrum Master
- **Expected**: 
  - Error: "Scrum Mastera nie je možné odstrániť"
  - Member not removed

#### Test 10.4: Leave Team (Member)
- **Prerequisites**: User is regular member
- **Steps**:
  1. Open "Moje Tímy"
  2. Click "Opustiť" on own team
  3. Confirm
- **Expected**: 
  - User removed from team
  - Team no longer in "Moje Tímy"
  - Success message

#### Test 10.5: Leave Team (Scrum Master)
- **Prerequisites**: User is Scrum Master
- **Steps**:
  1. Try to leave team
- **Expected**: 
  - Error: "Scrum Master nemôže opustiť tím"
  - Cannot leave
  - Must transfer role first (if implemented)

---

### 11. Team Selection & Active Team

#### Test 11.1: Select Active Team
- **Prerequisites**: User in multiple teams
- **Steps**:
  1. Use team dropdown in home page
  2. Select different team
- **Expected**: 
  - Selected team becomes active
  - Stored in localStorage
  - Team info displayed in "Aktívny Tím" bar
  - Shows:
    - Team name
    - Academic year
    - Member count
    - "S" badge if Scrum Master
    - User's occupation in this team

#### Test 11.2: Active Team Persistence
- **Steps**:
  1. Select team
  2. Navigate to different page
  3. Return to home
- **Expected**: 
  - Same team still selected
  - Active team persisted across navigation

#### Test 11.3: Active Team for Project Creation
- **Prerequisites**: User is Scrum Master of active team
- **Steps**:
  1. Navigate to "Pridať projekt"
- **Expected**: 
  - Active team used for project creation
  - Can create project

---

## Project Management Tests

### 12. Project Creation

#### Test 12.1: Create Game Project (Scrum Master)
- **Prerequisites**: User is Scrum Master, has active team
- **Steps**:
  1. Navigate to `/add-project`
  2. Select project type: "Hra"
  3. Enter title
  4. Select school type (ZŠ/SŠ/VŠ)
  5. Select subject
  6. Enter description
  7. Upload video (or YouTube URL)
  8. Upload splash screen
  9. Upload export file (.exe, .apk, .zip)
  10. Enter GitHub URL (optional)
  11. Enter tech stack
  12. Submit
- **Expected**: 
  - Project created successfully
  - Assigned to active team
  - All files uploaded
  - Project appears in team's project list
  - Success message

#### Test 12.2: Create Web App Project
- **Steps**:
  1. Select type: "Web App"
  2. Fill required fields
  3. Enter live URL
  4. Enter GitHub URL
  5. Enter tech stack
  6. Upload source code (.zip)
- **Expected**: 
  - Project created with web app specific fields
  - Live URL and GitHub URL saved

#### Test 12.3: Create Mobile App Project
- **Steps**:
  1. Select type: "Mobile App"
  2. Select platform (android/ios/both)
  3. Upload APK file
  4. Upload iOS build (if applicable)
- **Expected**: 
  - Project created with mobile app fields
  - Platform saved
  - Files uploaded correctly

#### Test 12.4: Create Library Project
- **Steps**:
  1. Select type: "Knižnica"
  2. Enter package name
  3. Enter NPM URL
  4. Upload documentation
- **Expected**: 
  - Project created with library-specific fields
  - Package name and NPM URL saved

#### Test 12.5: Create Project Without Required Fields
- **Steps**:
  1. Try to create project without:
     - Title
     - School type
     - Subject
     - Description
- **Expected**: 
  - Validation errors for each missing field
  - Project not created

#### Test 12.6: Create Project as Non-Scrum Master
- **Prerequisites**: User is regular member
- **Steps**:
  1. Try to navigate to `/add-project`
- **Expected**: 
  - Error: "Iba Scrum Master môže pridať projekt"
  - Cannot create project
  - Form not accessible

#### Test 12.7: Create Project Without Team
- **Prerequisites**: User not in any team
- **Steps**:
  1. Try to create project
- **Expected**: 
  - Error: "Musíte byť členom tímu"
  - Cannot create project

#### Test 12.8: Create Project with YouTube Video
- **Steps**:
  1. Select "YouTube link" option
  2. Enter YouTube URL
  3. Submit
- **Expected**: 
  - YouTube URL saved
  - Video plays from YouTube on project page

#### Test 12.9: Create Project with File Upload Video
- **Steps**:
  1. Select "Nahrať súbor"
  2. Upload video file (max 50MB)
  3. Submit
- **Expected**: 
  - Video file uploaded
  - Video plays from storage on project page

#### Test 12.10: Create Project with Large Files
- **Steps**:
  1. Try to upload file > max size limit
- **Expected**: 
  - Validation error
  - Upload rejected
  - Clear error message about file size

---

### 13. Project Viewing & Filtering

#### Test 13.1: View All Projects
- **Prerequisites**: User logged in, projects exist
- **Steps**:
  1. Navigate to home page (`/`)
- **Expected**: 
  - All projects displayed in grid
  - Each project card shows:
    - Title
    - Splash screen image (or placeholder)
    - Type badge
    - School type badge
    - Year of study (if applicable)
    - Subject badge
    - Team name
    - Academic year
    - Rating (with star)
    - View count
    - Description preview

#### Test 13.2: Filter Projects by Type
- **Steps**:
  1. Select project type from dropdown (Hra, Web App, etc.)
- **Expected**: 
  - Only projects of selected type shown
  - Filter persists
  - Count updates

#### Test 13.3: Filter Projects by School Type
- **Steps**:
  1. Select school type (ZŠ, SŠ, VŠ)
- **Expected**: 
  - Only projects for selected school type shown
  - Filter works correctly

#### Test 13.4: Filter Projects by Year of Study
- **Steps**:
  1. Select year (1-9 for ZŠ, 1-5 for SŠ/VŠ)
- **Expected**: 
  - Only projects for selected year shown
  - Filter works correctly

#### Test 13.5: Filter Projects by Subject
- **Steps**:
  1. Select subject from dropdown
- **Expected**: 
  - Only projects for selected subject shown
  - Filter works correctly

#### Test 13.6: Filter Projects by Academic Year
- **Steps**:
  1. Click on academic year badge in project card
- **Expected**: 
  - Filtered to show only projects from that academic year
  - Filter applied

#### Test 13.7: Search Projects by Title
- **Steps**:
  1. Enter search term in search box
- **Expected**: 
  - Projects matching search term shown
  - Search is case-insensitive
  - Partial matches work

#### Test 13.8: Combine Multiple Filters
- **Steps**:
  1. Apply multiple filters simultaneously:
     - Type: "Hra"
     - School type: "VŠ"
     - Subject: "Informatika"
- **Expected**: 
  - Only projects matching ALL filters shown
  - Filters work together correctly

#### Test 13.9: Reset Filters
- **Prerequisites**: Filters applied
- **Steps**:
  1. Click "Resetovať filtre"
- **Expected**: 
  - All filters cleared
  - All projects shown again
  - Search cleared

#### Test 13.10: View My Team's Projects
- **Prerequisites**: User in team with projects
- **Steps**:
  1. Click "Moje Projekty" button
- **Expected**: 
  - Only projects from active team shown
  - Success message with count
  - Can switch back to "Všetky Projekty"

#### Test 13.11: View Project Detail
- **Steps**:
  1. Click on project card or "Zobraziť detail"
- **Expected**: 
  - Navigate to `/project/{id}`
  - Full project details shown:
    - Title
    - Type
    - Description (full)
    - Video (plays)
    - Splash screen
    - Team members
    - Rating (interactive)
    - View count
    - Download links (if available)
    - All metadata

#### Test 13.12: View Count Increment
- **Steps**:
  1. View project detail page
- **Expected**: 
  - View count incremented by 1
  - Count updated in database
  - Multiple views from same user increment count

---

### 14. Project Rating

#### Test 14.1: Rate Project (First Time)
- **Prerequisites**: User logged in, project exists
- **Steps**:
  1. Navigate to project detail
  2. Click on star (1-5)
- **Expected**: 
  - Rating submitted successfully
  - Average rating updated
  - Rating count incremented
  - Success message
  - Stars reflect average rating
  - User cannot rate again

#### Test 14.2: Rate Project (Already Rated)
- **Prerequisites**: User already rated this project
- **Steps**:
  1. Try to click on star again
- **Expected**: 
  - Error: "Hru môžeš hodnotiť iba raz"
  - Rating not changed
  - Warning toast

#### Test 14.3: View User Rating
- **Prerequisites**: User rated project
- **Steps**:
  1. Navigate to project detail
  2. Check rating display
- **Expected**: 
  - Shows user's rating
  - Shows average rating
  - Shows total rating count

#### Test 14.4: Multiple Users Rate Same Project
- **Prerequisites**: Multiple users, one project
- **Steps**:
  1. User A rates 5 stars
  2. User B rates 3 stars
  3. User C rates 4 stars
- **Expected**: 
  - Average = (5+3+4)/3 = 4.0
  - Rating count = 3
  - Each user can only rate once

---

## Email Functionality Tests

### 15. Email Delivery

#### Test 15.1: Verification Email Sent
- **Prerequisites**: User registers
- **Steps**:
  1. Check MailHog at `http://localhost:8025`
- **Expected**: 
  - Verification email appears in MailHog
  - Email contains verification link
  - Email is HTML formatted
  - Subject: "Overenie e-mailu"

#### Test 15.2: Password Reset Email Sent
- **Prerequisites**: Password reset requested
- **Steps**:
  1. Check MailHog
- **Expected**: 
  - Password reset email appears immediately
  - Email contains reset link with token
  - Email is HTML formatted
  - Subject: "Reset hesla - Game Registration Portal"
  - Link valid for 1 hour

#### Test 15.3: Temporary Password Email Sent
- **Prerequisites**: 5 failed login attempts
- **Steps**:
  1. Check MailHog
- **Expected**: 
  - Temporary password email appears immediately
  - Email contains temporary password (format: `XXXX-XXXX-XXXX`)
  - Email is HTML formatted
  - Subject: "Dočasné heslo - Game Registration Portal"
  - Password valid for 15 minutes
  - Clear instructions included

#### Test 15.4: Email Format (HTML vs Plaintext)
- **Steps**:
  1. Check emails in MailHog
  2. View HTML version
- **Expected**: 
  - Emails render as HTML
  - Proper formatting
  - Links clickable
  - Styled content

#### Test 15.5: Email Not Queued
- **Steps**:
  1. Request password reset
  2. Check MailHog immediately
- **Expected**: 
  - Email appears instantly (not queued)
  - No queue worker needed
  - Synchronous sending

---

## Security & Edge Cases

### 16. Authentication Security

#### Test 16.1: SQL Injection Attempt
- **Steps**:
  1. Try to login with email: `' OR '1'='1`
- **Expected**: 
  - Query sanitized
  - Login fails
  - No SQL injection possible

#### Test 16.2: XSS Attempt
- **Steps**:
  1. Try to register with name: `<script>alert('XSS')</script>`
- **Expected**: 
  - Input sanitized
  - Script not executed
  - Safe display

#### Test 16.3: CSRF Protection
- **Steps**:
  1. Try to submit form without CSRF token
- **Expected**: 
  - Request rejected
  - 419 error
  - CSRF protection active

#### Test 16.4: Token Expiration
- **Prerequisites**: User logged in
- **Steps**:
  1. Wait 2 hours (token expiration time)
  2. Try to make API request
- **Expected**: 
  - Token expired
  - 401 Unauthorized
  - Must login again

#### Test 16.5: Token Revocation on Logout
- **Steps**:
  1. Login
  2. Logout
  3. Try to use same token
- **Expected**: 
  - Token revoked
  - 401 Unauthorized
  - Cannot use revoked token

#### Test 16.6: Rate Limiting
- **Steps**:
  1. Make many rapid requests to login endpoint
- **Expected**: 
  - Rate limiting kicks in
  - 429 Too Many Requests
  - Throttling active

---

### 17. Authorization Tests

#### Test 17.1: Access Protected Route Without Token
- **Steps**:
  1. Try to access `/api/user` without token
- **Expected**: 
  - 401 Unauthorized
  - Must login first

#### Test 17.2: Access Admin Route as Regular User
- **Steps**:
  1. Try to access `/api/admin/dashboard` as regular user
- **Expected**: 
  - 403 Forbidden
  - Admin access denied

#### Test 17.3: Access Other User's Data
- **Steps**:
  1. Try to access another user's profile
- **Expected**: 
  - Only own data accessible
  - Cannot view other users' private data

#### Test 17.4: Modify Other User's Project
- **Prerequisites**: User A creates project
- **Steps**:
  1. User B tries to modify User A's project
- **Expected**: 
  - 403 Forbidden
  - Only team Scrum Master can modify

---

### 18. Data Validation

#### Test 18.1: Invalid File Types
- **Steps**:
  1. Try to upload non-allowed file types
- **Expected**: 
  - Validation error
  - Upload rejected
  - Clear error message

#### Test 18.2: File Size Limits
- **Steps**:
  1. Try to upload file exceeding size limit
- **Expected**: 
  - Validation error
  - Upload rejected
  - Size limit message

#### Test 18.3: Invalid URL Formats
- **Steps**:
  1. Try to enter invalid URLs (GitHub, YouTube, Live URL)
- **Expected**: 
  - Validation error (if URL validation implemented)
  - Or accepted if validation is lenient

#### Test 18.4: Empty Required Fields
- **Steps**:
  1. Try to submit forms with empty required fields
- **Expected**: 
  - Validation errors
  - Form not submitted
  - Fields highlighted

---

## UI/UX Tests

### 19. Navigation

#### Test 19.1: Navigation Between Pages
- **Steps**:
  1. Navigate: Home → Project Detail → Team View → Home
- **Expected**: 
  - Smooth navigation
  - No broken links
  - Back button works
  - URLs update correctly

#### Test 19.2: Responsive Design
- **Steps**:
  1. Test on different screen sizes:
     - Desktop (1920x1080)
     - Tablet (768x1024)
     - Mobile (375x667)
- **Expected**: 
  - Layout adapts
  - Elements readable
  - Buttons accessible
  - No horizontal scroll

#### Test 19.3: Loading States
- **Steps**:
  1. Perform actions that take time (API calls)
- **Expected**: 
  - Loading spinners shown
  - Buttons disabled during loading
  - Clear feedback

#### Test 19.4: Error Messages
- **Steps**:
  1. Trigger various errors
- **Expected**: 
  - Error messages clear and helpful
  - Toast notifications appear
  - Errors don't break UI

---

### 20. User Experience

#### Test 20.1: Toast Notifications
- **Steps**:
  1. Perform various actions (success, error, warning)
- **Expected**: 
  - Toasts appear appropriately
  - Auto-dismiss after timeout
  - Can manually dismiss
  - Don't stack excessively

#### Test 20.2: Form Validation Feedback
- **Steps**:
  1. Fill forms with invalid data
- **Expected**: 
  - Real-time or on-submit validation
  - Fields highlighted
  - Error messages near fields
  - Clear indication of issues

#### Test 20.3: Confirmation Dialogs
- **Steps**:
  1. Perform destructive actions (remove member, leave team)
- **Expected**: 
  - Confirmation dialog appears
  - Can cancel
  - Action only proceeds on confirm

#### Test 20.4: Copy to Clipboard
- **Steps**:
  1. Click "Kopírovať" on invite code
- **Expected**: 
  - Code copied to clipboard
  - Success message
  - Can paste code

---

## Integration Tests

### 21. End-to-End Scenarios

#### Test 21.1: Complete User Journey
- **Steps**:
  1. Register new user
  2. Verify email
  3. Login
  4. Create team
  5. Add occupation
  6. Create project
  7. View project
  8. Rate project
  9. Update profile
  10. Logout
- **Expected**: 
  - All steps complete successfully
  - Data persists
  - No errors

#### Test 21.2: Team Collaboration Flow
- **Steps**:
  1. User A creates team
  2. User B joins team with code
  3. User A (Scrum Master) creates project
  4. User B views project
  5. User B rates project
  6. User A views team members
- **Expected**: 
  - All interactions work
  - Data shared correctly
  - Permissions enforced

#### Test 21.3: Password Recovery Flow
- **Steps**:
  1. User forgets password
  2. Request reset
  3. Receive email
  4. Click reset link
  5. Enter new password
  6. Login with new password
- **Expected**: 
  - Complete flow works
  - Old password invalid
  - New password works

#### Test 21.4: Temporary Password Flow
- **Steps**:
  1. User fails login 5 times
  2. Receive temporary password email
  3. Login with temporary password
  4. Change password in profile
  5. Login with new password
- **Expected**: 
  - Temporary password works
  - Can change password
  - New password works
  - Failed attempts reset

---

## Test Execution Checklist

### Pre-Test Setup
- [ ] Backend server running (`php artisan serve`)
- [ ] Frontend dev server running (`npm run dev`)
- [ ] MailHog running and accessible at `http://localhost:8025`
- [ ] Database migrated and seeded (if needed)
- [ ] Clear browser cache and localStorage
- [ ] Test user accounts created (or use registration)

### Test Environment
- [ ] `.env` configured correctly
- [ ] MailHog SMTP settings: `MAIL_HOST=127.0.0.1`, `MAIL_PORT=1025`
- [ ] Frontend `VITE_API_URL` points to backend
- [ ] Browser console open for debugging

### Test Execution
- [ ] Execute tests in order (some depend on previous)
- [ ] Document any failures
- [ ] Take screenshots of issues
- [ ] Check browser console for errors
- [ ] Check Laravel logs for backend errors
- [ ] Verify MailHog receives emails

### Post-Test
- [ ] Review all test results
- [ ] Document bugs found
- [ ] Verify fixes for critical issues
- [ ] Re-test failed scenarios after fixes

---

## Test Data Requirements

### Test Users
- User 1: Verified, Scrum Master, in team
- User 2: Verified, regular member
- User 3: Unverified
- User 4: With failed login attempts

### Test Teams
- Team 1: Full team (10 members)
- Team 2: Team with projects
- Team 3: Empty team

### Test Projects
- Project 1: Game type
- Project 2: Web App type
- Project 3: Mobile App type
- Project 4: Library type
- Project 5: With ratings
- Project 6: With high view count

---

## Notes

- **Test Frequency**: Run full test suite before each release
- **Critical Tests**: Authentication, email delivery, team management
- **Browser Testing**: Test in Chrome, Firefox, Edge
- **Mobile Testing**: Test on actual devices or responsive mode
- **Performance**: Monitor API response times during tests
- **Security**: Pay special attention to authorization tests

---

## Bug Reporting Template

When reporting bugs found during testing:

```
**Test Case**: [Test ID and name]
**Steps to Reproduce**:
1. 
2. 
3. 

**Expected Result**: 

**Actual Result**: 

**Screenshots**: [if applicable]

**Browser/OS**: 

**Console Errors**: 

**Backend Logs**: 
```

---

*Last Updated: [Current Date]*
*Test Plan Version: 1.0*


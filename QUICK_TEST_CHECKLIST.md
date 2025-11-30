# Quick Test Checklist - Game Registration Portal

## 🔴 Critical Path Tests (Must Pass)

### Authentication
- [ ] Register new user → Email verification sent
- [ ] Verify email → Can login
- [ ] Login with correct credentials → Success
- [ ] Login wrong password 5 times → Temporary password sent
- [ ] Login with temporary password → Success
- [ ] Request password reset → Reset email sent
- [ ] Reset password with token → Can login with new password

### Team Management
- [ ] Create team → Team created, invite code shown
- [ ] Join team with code → Member added
- [ ] View team → All members shown with occupations
- [ ] Remove member (Scrum Master) → Member removed
- [ ] Leave team (Member) → User removed from team

### Project Management
- [ ] Create project (Scrum Master) → Project created
- [ ] View all projects → Projects displayed
- [ ] Filter projects → Filters work
- [ ] View project detail → Full details shown
- [ ] Rate project → Rating saved (once per user)

### Email Delivery
- [ ] Verification email → Appears in MailHog
- [ ] Password reset email → Appears in MailHog
- [ ] Temporary password email → Appears in MailHog

---

## 🟡 Important Tests

### User Profile
- [ ] Update name → Name changed
- [ ] Upload avatar → Avatar displayed
- [ ] Change password → Can login with new password

### Team Features
- [ ] Select active team → Team selected
- [ ] View member details → Dialog shows info
- [ ] Copy invite code → Code copied

### Project Features
- [ ] Search projects → Results filtered
- [ ] View my team's projects → Only team projects shown
- [ ] Project view count → Increments on view

---

## 🟢 Edge Cases

### Security
- [ ] Access protected route without token → 401
- [ ] Try to create project as member → 403
- [ ] Try to remove Scrum Master → Error

### Validation
- [ ] Register with invalid email → Error
- [ ] Create team without required fields → Validation errors
- [ ] Upload invalid file type → Rejected

### Limits
- [ ] Join full team (10 members) → Error
- [ ] Rate project twice → Error (already rated)

---

## 📋 Pre-Test Setup Checklist

- [ ] Backend running: `php artisan serve`
- [ ] Frontend running: `npm run dev`
- [ ] MailHog running: `http://localhost:8025`
- [ ] Database migrated
- [ ] Browser console open
- [ ] MailHog UI open

---

## 🐛 Common Issues to Check

- [ ] Emails not appearing in MailHog → Check `ShouldQueue` removed
- [ ] Counter stuck at 4 → Check session expiration logic
- [ ] Temporary password not working → Check token expiration (15 min)
- [ ] Projects not showing → Check filters, team selection
- [ ] Occupations not displaying → Check pivot data included

---

## ⚡ Quick Smoke Test (5 minutes)

1. Register → Verify → Login
2. Create team → Join team (second user)
3. Create project → View project
4. Rate project → Check rating
5. Request password reset → Check MailHog

**If all pass → System is functional!**

---

*Use full `COMPLETE_TEST_PLAN.md` for comprehensive testing*


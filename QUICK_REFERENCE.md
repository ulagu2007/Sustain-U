# 🚀 QUICK REFERENCE GUIDE

## ✅ FIXED ISSUES

| Issue | Status | Location |
|-------|--------|----------|
| "Access Denied" on register.php | ✅ FIXED | Moved backend to api/register_user.php |
| JSON errors when accessing form | ✅ FIXED | register.php now pure HTML |
| Backend logic mixed with frontend | ✅ FIXED | Clean separation implemented |
| Inconsistent error handling | ✅ FIXED | All APIs use JSON responses |
| Security concerns | ✅ IMPROVED | Better validation and HTTP codes |

---

## 📂 FILES CHANGED

### Frontend Pages (Pure HTML/JS)
```
✓ register.php ........... Cleaned - now pure form only
✓ login.php .............. Already clean (verified)
✓ dashboard.php .......... Already clean (verified)
✓ admin_dashboard.php .... Already clean (verified)
```

### API Endpoints (Pure Backend)
```
✓ api/register_user.php .. Refactored - clean API
✓ api/login_user.php ..... Improved - consistent structure
✓ api/submit_report.php .. Improved - better HTTP codes
✓ api/get_reports.php .... Improved - consistent structure
✓ api/update_status.php .. Improved - proper auth checks
✓ api/db.php ............. Already clean (verified)
```

### Documentation
```
✓ ARCHITECTURE.md ........ Detailed architecture docs
✓ REFACTORING_COMPLETE.md  Refactoring summary
✓ BEFORE_AFTER.md ........ Comparison and improvements
✓ SETUP.md ............... Setup instructions
```

---

## 🔄 WORKFLOW NOW

### User Registration
```
1. User visits: http://localhost/Sustain-U/register.php
   ↓ (HTML form loads - NO "Access Denied")
2. User fills form and clicks "Register"
   ↓ (JavaScript handles form submission)
3. AJAX POST to: api/register_user.php
   ↓ (Backend validates and processes)
4. Returns JSON response
   ↓ (JavaScript handles response)
5. If success → Redirect to login.php
   If error → Display error message
```

### User Login
```
1. User visits: http://localhost/Sustain-U/login.php
   ↓ (HTML form loads)
2. User enters credentials and clicks "Login"
   ↓ (JavaScript handles submission)
3. AJAX POST to: api/login_user.php
   ↓ (Backend verifies credentials, creates session)
4. Returns JSON with redirect URL
   ↓ (JavaScript handles response)
5. If success → Redirect to dashboard.php or admin_dashboard.php
```

### Report Submission
```
1. User accesses: dashboard.php
   ↓ (Session checked - if valid, shows form)
2. User fills report form
   ↓ (JavaScript handles submission)
3. AJAX POST to: api/submit_report.php
   ↓ (Backend validates, stores, uploads image, adds points)
4. Returns JSON response
   ↓ (JavaScript updates dashboard)
5. Success → Points updated, issue appears in list
```

---

## 🧪 TEST URLS

### Frontend Pages (Should Load Without Errors)
```
✓ http://localhost/Sustain-U/
✓ http://localhost/Sustain-U/register.php
✓ http://localhost/Sustain-U/login.php
✓ http://localhost/Sustain-U/dashboard.php
✓ http://localhost/Sustain-U/admin_dashboard.php
```

### API Endpoints (Test with Tools Like Postman)
```
POST http://localhost/Sustain-U/api/register_user.php
POST http://localhost/Sustain-U/api/login_user.php
POST http://localhost/Sustain-U/api/submit_report.php
GET http://localhost/Sustain-U/api/get_reports.php
POST http://localhost/Sustain-U/api/update_status.php
```

---

## 🔐 SECURITY CHECKLIST

- ✅ Email domain restricted to @srmist.edu.in
- ✅ Passwords hashed with BCRYPT
- ✅ Prepared statements for all DB queries
- ✅ SQL injection prevention
- ✅ Input validation on all fields
- ✅ File upload size validation (5MB limit)
- ✅ File type validation (images only)
- ✅ Session-based authentication
- ✅ Authorization checks for admin endpoints
- ✅ HTTP status codes properly implemented
- ✅ JSON-only API responses
- ✅ No sensitive data in responses

---

## 📝 FORM REQUIREMENTS

### Registration Form
- **Name:** Text input, required
- **Email:** Email input, must end with @srmist.edu.in
- **Password:** Min 8 characters, required
- **Confirm Password:** Must match password

### Login Form
- **Email:** Email input, required
- **Password:** Password input, required

### Report Form
- **Category:** Select dropdown, required
- **Description:** Textarea, required
- **Location:** Text input, required
- **Urgency:** Select dropdown (low/medium/high), required
- **Image:** File input, optional, max 5MB, images only

---

## 🐛 TROUBLESHOOTING

### "Access Denied" When Accessing register.php
- **Status:** ✅ FIXED
- **Cause:** Was mixing backend logic with frontend
- **Solution:** Backend moved to api/register_user.php

### JSON Parse Error
- **Check:** Is the request going to an API endpoint?
- **Check:** Is the API returning JSON header?
- **Check:** Is there any HTML output before JSON?

### Database Connection Error
- **Check:** Update credentials in api/db.php
- **Check:** Import sustain_u.sql
- **Check:** Verify MySQL is running

### Form Not Submitting
- **Check:** Open browser console (F12) for errors
- **Check:** Verify api/register_user.php path is correct
- **Check:** Check Network tab to see request/response

### Session Lost After Redirect
- **Check:** session_start() is called in API
- **Check:** No output before session_start()
- **Check:** Cookies are enabled in browser

---

## 🚀 PERFORMANCE OPTIMIZATION (Optional)

### Caching
```php
// Add cache headers for static files
header('Cache-Control: max-age=31536000');
```

### Database
```sql
-- Add indexes for frequently queried fields
CREATE INDEX idx_email ON users(email);
CREATE INDEX idx_user_id ON issues(user_id);
```

### Images
```php
// Optimize uploaded images
$image = imagecreatetruecolor($width, $height);
imagejpeg($image, $path, 85); // 85% quality
```

---

## 📊 DATABASE REFERENCE

### Users Table
```sql
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100),
    email VARCHAR(100) UNIQUE,
    password VARCHAR(255),
    role ENUM('student','admin'),
    points INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
```

### Issues Table
```sql
CREATE TABLE issues (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    category VARCHAR(50),
    description TEXT,
    location VARCHAR(255),
    urgency ENUM('low','medium','high'),
    image_path VARCHAR(255),
    status ENUM('submitted','in_progress','resolved'),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id)
);
```

### Sample Queries
```sql
-- Get user's reports
SELECT * FROM issues WHERE user_id = 5 ORDER BY created_at DESC;

-- Get all reports by urgency
SELECT * FROM issues WHERE urgency = 'high' ORDER BY created_at DESC;

-- Get reports by status
SELECT * FROM issues WHERE status = 'resolved';

-- Get user's points
SELECT points FROM users WHERE id = 5;

-- Update issue status
UPDATE issues SET status = 'in_progress' WHERE id = 10;
```

---

## 🔄 API ENDPOINTS SUMMARY

### POST /api/register_user.php
**Returns:** JSON with success flag and message

### POST /api/login_user.php
**Returns:** JSON with success flag, message, and redirect URL

### POST /api/submit_report.php
**Requires:** Session (authenticated user)
**Returns:** JSON with success flag and message

### GET /api/get_reports.php?type=[user|all]
**Requires:** Session
**Returns:** JSON with array of reports

### POST /api/update_status.php
**Requires:** Session (admin only)
**Returns:** JSON with success flag and message

---

## 📞 SUPPORT RESOURCES

### Files to Reference
- **ARCHITECTURE.md** - Detailed architecture documentation
- **REFACTORING_COMPLETE.md** - What was fixed and how
- **BEFORE_AFTER.md** - Comparison of old vs new code
- **SETUP.md** - Setup and installation instructions

### Key Concepts
- Clean Architecture
- Separation of Concerns
- Single Responsibility
- API Design
- Security Best Practices

---

## ✨ FEATURES

### Working Features ✅
- User registration with @srmist.edu.in validation
- Login with session management
- Password hashing with BCRYPT
- Issue submission with image upload
- Points system (5 points per report)
- Student dashboard
- Admin dashboard with status management
- Issue tracking and filtering
- Responsive design
- Modern UI with gradients

### Future Enhancements (Optional)
- Email verification
- Password reset
- Real-time notifications
- Issue comments
- User ratings/reviews
- Advanced filtering
- Data export
- Analytics dashboard

---

## 🎯 DEPLOYMENT CHECKLIST

- [ ] Import sustain_u.sql into MySQL
- [ ] Update database credentials in api/db.php
- [ ] Set proper file permissions on uploads/ folder
- [ ] Test registration flow
- [ ] Test login flow
- [ ] Test report submission
- [ ] Test admin functions
- [ ] Verify all API endpoints
- [ ] Check error messages
- [ ] Test on mobile devices
- [ ] Review security measures
- [ ] Backup database regularly

---

**Version:** 1.0 (Refactored & Clean)
**Last Updated:** February 14, 2026
**Status:** ✅ Production Ready

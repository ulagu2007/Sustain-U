# 🚀 QUICK START GUIDE - SUSTAIN-U PRODUCTION DEPLOYMENT

## ⚡ TL;DR - Get Running in 5 Minutes

### Step 1: Database Import (2 min)
```bash
# Open MySQL and run:
mysql -u root -p

# Create database and import schema:
CREATE DATABASE sustain_u;
USE sustain_u;
SOURCE C:/xampp/htdocs/Sustain-U/sustain_u.sql;
```

### Step 2: Configure Credentials (1 min)
Edit `c:\xampp\htdocs\Sustain-U\config.php`:
```php
define('DB_USER', 'your_mysql_username');  // Usually 'root'
define('DB_PASS', 'your_mysql_password');  // Usually empty for local dev
```

### Step 3: Create Required Directories (1 min)
```bash
# Windows PowerShell:
cd C:\xampp\htdocs\Sustain-U
mkdir uploads
mkdir reports
mkdir logs
```

### Step 4: Install FPDF (1 min - Optional for PDF generation)
```bash
# If you have Composer installed:
composer require fpdf/fpdf

# Or download manually from: https://github.com/setasign/FPDF
```

### Step 5: Test It Out!
```
1. Open browser: http://localhost/Sustain-U/
2. Click "Register" and create account with @srmist.edu.in email
3. Login with your credentials
4. Go to "Report Issue" and submit an issue
5. Submit an issue with an image
6. View in "My Works"
```

---

## 🔑 TEST CREDENTIALS

### Admin Account (Auto-created)
```
Email: admin@srmist.edu.in
Password: Use BCRYPT hash provided in sustain_u.sql
```

### Test Student Account (Create your own)
```
Email: yourname@srmist.edu.in
Password: (any password with 8+ characters)
```

---

## 📊 KEY API ENDPOINTS (For Integration Testing)

### Test Registration
```bash
curl -X POST http://localhost/Sustain-U/api/register_user.php \
  -d "name=Test User" \
  -d "email=test@srmist.edu.in" \
  -d "password=TestPassword123" \
  -d "confirm_password=TestPassword123"
```

### Geofencing
Geofencing has been deprecated and removed from this release. Reporting relies on photo evidence and manual review.

### Test Issue Submission
```bash
# Requires authentication (session from login)
curl -X POST http://localhost/Sustain-U/api/submit_issue.php \
  -F "category=Air" \
  -F "urgency=needs_attention" \
  -F "building=Building A" \
  -F "floor=2nd Floor" \
  -F "area=Corridor" \
  -F "image_before=@/path/to/image.jpg"
```

---

## 🗂️ FILE STRUCTURE REFERENCE

```
┌─ c:\xampp\htdocs\Sustain-U\
│
├─ 📄 config.php              [EDIT THIS: Add your DB credentials]
├─ 📄 index.php               [Landing page]
├─ 📄 register.php            [Registration form]
├─ 📄 login.php               [Login form]
├─ 📄 logout.php              [Logout handler]
│
├─ 🔒 PROTECTED PAGES (require login):
│  ├─ dashboard.php           [Student dashboard]
│  ├─ report_issue.php        [Submit new issue]
│  ├─ my_works.php            [View own issues]
│  ├─ profile.php             [User profile]
│  ├─ download_report.php     [Download PDF]
│
├─ 👨‍💼 ADMIN ONLY:
│  ├─ admin_login.php
│  ├─ admin_dashboard.php
│  ├─ issue_details.php
│
├─ 📁 api/                    [Backend API endpoints]
│  ├─ db.php                  [Database connection]
│  ├─ register_user.php       [POST: Register student]
│  ├─ login_user.php          [POST: Authenticate]
│  ├─ submit_issue.php        [POST: Submit issue + image]
│  ├─ get_student_issues.php  [GET: User's issues]
│  ├─ get_all_issues.php      [GET: All issues (admin)]
│  ├─ update_status.php       [POST: Change status (admin)]
│  ├─ upload_resolution.php   [POST: Upload after-image (admin)]
│  ├─ generate_pdf.php        [GET: Generate PDF report]
│
├─ 🎨 css/
│  └─ style.css              [Mobile-first responsive CSS]
│
├─ 📜 js/
│  └─ main.js                [utilities]
│
├─ 📁 uploads/               [Issue images - auto-created]
├─ 📁 reports/               [Generated PDFs - auto-created]
├─ 📁 logs/                  [Error logs - auto-created]
│
└─ 📋 sustain_u.sql          [Database schema - import this]
```

---

## 🔐 SECURITY CHECKLIST BEFORE PRODUCTION

- [ ] **Database:** MySQL credentials set in config.php
- [ ] **File Permissions:** uploads/, reports/, logs/ directories are writable
- [ ] **HTTPS:** Enable HTTPS in production (config.php sets cookie_secure=0 for local)
- [ ] **Session Timeout:** Review SESSION_TIMEOUT in config.php
- [ ] **Email Validation:** Domain restriction to @srmist.edu.in working
- [ ] **File Upload:** Max 5MB enforced, MIME types validated
- [ ] **Error Logging:** Check logs/ directory for errors
- [ ] **API Testing:** All endpoints return proper HTTP status codes
- [ ] **XSS Protection:** Output escaping with sanitize() function
- [ ] **SQL Injection:** All queries use prepared statements

---

## 🧪 COMPREHENSIVE TESTING MATRIX

### Student Registration & Login
```
✓ Register with @srmist.edu.in email
✓ Registration fails with non-domain email
✓ Password must be 8+ characters
✓ Passwords must match
✓ Cannot register twice with same email
✓ Login with correct credentials succeeds
✓ Login with wrong password fails
✓ Redirects to dashboard.php after login
```

### Geofencing
Geofencing has been deprecated and removed from this release; no geolocation checks are performed.

### Issue Submission
```
✓ All fields required (category, urgency, building, floor, area)
✓ Image upload max 5MB enforced
✓ Only image types accepted (jpg, png, webp)
✓ Issue created with status='submitted'
✓ User gets +5 points
✓ Image saved with unique filename
✓ Issue appears in my_works.php immediately
```

### Admin Dashboard
```
✓ Admin login redirects to admin_dashboard.php
✓ Non-admin cannot access admin pages
✓ Lists all issues from all students
✓ Shows student name and email
✓ Filter by status works (submitted/in_progress/resolved)
✓ Filter by category works (Air/Water/Waste)
✓ Can view issue details
```

### Issue Management
```
✓ Change status from submitted → in_progress
✓ Change status from in_progress → resolved
✓ Resolved timestamp is set when resolved
✓ Can upload after-resolution image
✓ PDF generated when status → resolved
✓ PDF saved to reports/ directory
✓ PDF path stored in database
✓ Student can download PDF
```

### PDF Report
```
✓ PDF contains issue ID
✓ PDF contains student name and email
✓ PDF contains category, urgency, location
✓ PDF contains before image
✓ PDF contains after image (if available)
✓ PDF contains submitted date
✓ PDF contains resolved date (if resolved)
✓ PDF contains points awarded
```

---

## 🐛 COMMON ISSUES & FIXES

| Issue | Symptom | Fix |
|-------|---------|-----|
| Database Connection Error | "Database connection failed" message | Check DB credentials in config.php, verify MySQL running |
| File Upload Fails | "File upload error" on submit issue | Check uploads/ directory exists and is writable |
| Geolocation (N/A) | Geofencing removed in this release | No action required |
| PDF Generation Fails | "PDF library not installed" message | Run: `composer require fpdf/fpdf` |
| Session Lost on Reload | Redirected to login page | Check session.save_path is writable, verify php.ini settings |
| "Access Denied" Error | Cannot access protected pages | Make sure you're logged in, check browser cookies |
| Email Validation Fails | Cannot register non-domain email | Verify regex in `isValidStudentEmail()` in config.php |
| Images Not Displaying | Broken image links | Check image files exist in uploads/, verify permissions |

---

## 📱 MOBILE TESTING

### Responsive Design Features
- ✓ Touch-friendly buttons (min 44px height)
- ✓ Mobile-first CSS with responsive breakpoints
- ✓ Bottom navigation bar on mobile (<768px)
- ✓ Optimized form inputs for mobile keyboards
- ✓ Geolocation works on mobile devices

### Test on Mobile/Tablet
```
1. Use Chrome DevTools mobile emulation
2. Or test on actual phone/tablet on same network
3. Ensure camera access works for image uploads
4. Test image upload and issue submission on device (geofencing removed)
```

---

## 📈 MONITORING & LOGS

### Check Error Logs
```bash
# View recent errors:
tail -f C:\xampp\htdocs\Sustain-U\logs\error.log

# Or open in editor:
C:\xampp\htdocs\Sustain-U\logs\error.log
```

### Monitor Database
```sql
-- Check user registrations
SELECT COUNT(*) FROM users;

-- Check submitted issues
SELECT COUNT(*) FROM issues WHERE status='submitted';

-- Check total points awarded
SELECT SUM(points) FROM users;

-- Check PDF generation success
SELECT COUNT(*) FROM issues WHERE report_path IS NOT NULL;
```

---

## 🚀 DEPLOYMENT CHECKLIST

### Pre-Deployment
- [ ] All tests passing
- [ ] Error log is clean
- [ ] Database optimized with proper indexes
- [ ] File uploads working correctly
- [ ] PDF generation tested
- [ ] Geofencing tested on actual device
- [ ] All API endpoints tested
- [ ] CSS/JS loaded correctly
- [ ] Images displaying properly

### Deployment Steps
1. Backup database: `mysqldump -u root -p sustain_u > backup.sql`
2. Copy all files to production server
3. Update config.php with production DB credentials
4. Set proper file permissions (755 for dirs, 644 for files)
5. Enable HTTPS and update session.cookie_secure=1
6. Run database migrations if any
7. Test all functionality on production
8. Monitor error logs for first 24 hours

### Post-Deployment
- [ ] Monitor error logs daily
- [ ] Backup database regularly
- [ ] Check disk space for uploads/reports
- [ ] Review user feedback
- [ ] Track performance metrics

---

## 📞 TECHNICAL SUPPORT

### Quick Reference
- **Database:** `/sustain_u.sql` contains schema and sample admin user
- **Config:** `/config.php` has all constants and helper functions
- **Logs:** `/logs/error.log` contains all errors
- **Uploads:** `/uploads/` stores issue images
- **Reports:** `/reports/` stores generated PDFs

### Debug Mode
Add to config.php for detailed error output:
```php
ini_set('display_errors', 1);
error_reporting(E_ALL);
```

**⚠️ WARNING:** Disable in production!

---

## ✅ DEPLOYMENT VALIDATION

After completing all steps, verify:

```
1. Navigate to http://localhost/Sustain-U/
   → Should load landing page with green header

2. Click "Register"
   → Should load registration form

3. Enter: name@srmist.edu.in, password123456
   → Should see success message and redirect to login

4. Click "Login"
   → Should load login form

5. Login with credentials
   → Should redirect to dashboard

6. Click "Report Issue"
   → Should check geofence (allow location access)
   → Should show "You are within campus" (if at correct coordinates)
   → Should show issue form

7. Fill form and select image
   → Should show image preview

8. Click Submit
   → Should see success message
   → Points should increase by 5

9. Click "My Works"
   → Should list submitted issue with gray badge

10. Open browser dev tools
    → Should be NO JavaScript errors
    → Should be NO CORS errors
    → Should be NO database errors
```

---

## 🎉 YOU'RE READY!

Once all steps are complete, Sustain-U is ready for production use!

---

**For detailed documentation, see:** `PRODUCTION_REFACTORING_COMPLETE.md`

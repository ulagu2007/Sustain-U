# SUSTAIN-U ARCHITECTURE DOCUMENTATION

## 🏗️ Clean Architecture Implementation

### Separation of Concerns

#### **Frontend Layer (Pure HTML/JS)**
- `index.php` - Landing page
- `login.php` - Login form
- `register.php` - Registration form
- `dashboard.php` - Student dashboard
- `admin_dashboard.php` - Admin panel
- `logout.php` - Session termination

**Key Features:**
- Contains ONLY HTML, CSS, and inline JavaScript
- NO backend logic or database queries
- NO PHP backend code
- Forms submit via AJAX/Fetch to API endpoints
- Handles UI rendering and user interactions

#### **API Layer (Backend Logic)**
Location: `api/`

- `db.php` - Database connection (shared across all APIs)
- `register_user.php` - Registration logic
- `login_user.php` - Authentication logic
- `submit_report.php` - Issue submission
- `get_reports.php` - Fetch reports
- `update_status.php` - Admin status management

**Key Features:**
- Accept only their designated HTTP method (POST/GET)
- Require proper authentication/authorization
- Perform all business logic and database operations
- Return JSON responses
- Use prepared statements for security
- Proper HTTP status codes

---

## 📋 Request/Response Flow

### Registration Flow
```
register.php (form) 
    ↓
    → api/register_user.php (backend)
    ↓
    ← Returns JSON response
    ↓
login.php (redirect)
```

### Login Flow
```
login.php (form)
    ↓
    → api/login_user.php (backend)
    ↓
    ← Returns JSON + session creation
    ↓
dashboard.php (redirect)
```

### Report Submission Flow
```
dashboard.php (form)
    ↓
    → api/submit_report.php (backend)
    ↓
    ← Returns JSON + points awarded
    ↓
dashboard.php (display)
```

---

## 🔐 Security Measures

### 1. **API Access Control**
- Only POST/GET requests allowed (HTTP 405 for others)
- Authentication checks before processing
- Authorization checks for admin endpoints

### 2. **Authentication**
- Session-based authentication
- Password hashing with `password_hash()` (BCRYPT)
- Password verification with `password_verify()`

### 3. **Database Security**
- Prepared statements to prevent SQL injection
- Parameterized queries for all database operations
- Input validation before database queries

### 4. **File Upload Security**
- 5MB file size limit
- Image-only validation (MIME type checking)
- Unique filename generation
- Restricted directory permissions

### 5. **Authorization**
- Email domain restriction (@srmist.edu.in)
- Role-based access control (student/admin)
- Session validation on protected pages

---

## 📁 File Structure

```
Sustain-U/
├── api/
│   ├── db.php ........................ Database connection
│   ├── register_user.php ............ User registration endpoint
│   ├── login_user.php .............. Login endpoint
│   ├── submit_report.php ........... Report submission endpoint
│   ├── get_reports.php ............. Fetch reports endpoint
│   └── update_status.php ........... Status update endpoint
│
├── css/
│   └── style.css ................... Main stylesheet
│
├── js/
│   └── main.js ..................... Utility functions
│
├── uploads/ ......................... User-uploaded files
│
├── index.php ....................... Landing page
├── login.php ....................... Login page
├── register.php .................... Registration page
├── dashboard.php ................... Student dashboard
├── admin_dashboard.php ............. Admin panel
├── logout.php ...................... Logout endpoint
│
└── sustain_u.sql ................... Database schema
```

---

## 🚀 API Endpoints

### POST `/api/register_user.php`
**Input:**
- `name` (string, required)
- `email` (string, required, must be @srmist.edu.in)
- `password` (string, required, min 8 chars)
- `confirm_password` (string, required)

**Response:**
```json
{
    "success": true/false,
    "message": "User-friendly message"
}
```

---

### POST `/api/login_user.php`
**Input:**
- `email` (string, required)
- `password` (string, required)

**Response:**
```json
{
    "success": true/false,
    "message": "Status message",
    "redirect": "dashboard.php" or "admin_dashboard.php"
}
```

**Side Effect:** Creates session if credentials valid

---

### POST `/api/submit_report.php`
**Auth Required:** ✓ (Session user_id)

**Input:**
- `category` (string, required)
- `description` (text, required)
- `location` (string, required)
- `urgency` (enum: low/medium/high)
- `image` (file, optional, max 5MB)

**Response:**
```json
{
    "success": true/false,
    "message": "Status message"
}
```

**Side Effect:** Adds 5 points to user's account

---

### GET `/api/get_reports.php?type=[user|all]`
**Auth Required:** ✓ (Session user_id)

**Parameters:**
- `type` (string) - "user" for own reports, "all" for admin view

**Response:**
```json
{
    "success": true/false,
    "data": [
        {
            "id": 1,
            "user_id": 2,
            "category": "water_waste",
            "description": "...",
            "location": "...",
            "urgency": "high",
            "image_path": "uploads/...",
            "status": "submitted",
            "created_at": "2026-02-14 10:30:00"
        }
    ],
    "message": ""
}
```

---

### POST `/api/update_status.php`
**Auth Required:** ✓ (Admin role only)

**Input:**
- `issue_id` (int, required)
- `action` (string: "update" or "delete")
- `status` (string: "submitted", "in_progress", "resolved") - required if action is "update"

**Response:**
```json
{
    "success": true/false,
    "message": "Status message"
}
```

---

## 🔄 Data Flow Example

### Complete User Registration to Submission

1. **User visits register.php**
   - Browser renders HTML form
   - No database queries yet

2. **User submits form**
   - JavaScript captures form data
   - Sends AJAX POST to `api/register_user.php`

3. **Backend validates and stores**
   - Checks email format and domain
   - Validates password
   - Checks for duplicates
   - Hashes password
   - Inserts into database
   - Returns JSON response

4. **Frontend handles response**
   - If success: redirect to login.php
   - If error: display error message

5. **User logs in**
   - Submits to `api/login_user.php`
   - Backend verifies credentials
   - Creates session
   - Returns redirect URL

6. **User accesses dashboard.php**
   - PHP checks session exists
   - Renders dashboard (no direct DB queries)
   - JavaScript fetches reports via `api/get_reports.php`

7. **User submits report**
   - Form submits to `api/submit_report.php`
   - Backend: validates, stores, uploads image, adds points
   - Returns success response
   - JavaScript refreshes report list

---

## ✅ Checklist for New API Endpoints

When creating new API endpoints, ensure:

- [ ] Located in `api/` directory
- [ ] Requires appropriate HTTP method (POST/GET/PUT/DELETE)
- [ ] Returns HTTP status codes (200, 400, 401, 403, 404, 405)
- [ ] Sets `Content-Type: application/json` header
- [ ] Requires authentication where necessary
- [ ] Requires authorization for admin operations
- [ ] Uses prepared statements for all DB queries
- [ ] Validates all input
- [ ] Returns JSON response with success/message
- [ ] No HTML output
- [ ] No direct browser access handling needed (only method check)
- [ ] Proper error handling

---

## 🔧 Configuration

### Database Connection
Edit `api/db.php`:
```php
$host = 'localhost';
$db_user = 'root';
$db_pass = '';
$database = 'sustain_u';
```

### Allowed Email Domain
Edit validation in `api/register_user.php`:
```php
if (!preg_match('/@srmist\.edu\.in$/', $email)) {
```

### Image Upload Limits
Edit in `api/submit_report.php`:
```php
$max_size = 5 * 1024 * 1024; // 5MB
$allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
```

---

## 📊 Database Schema

### users table
```
id (INT, AUTO_INCREMENT, PRIMARY KEY)
name (VARCHAR 100)
email (VARCHAR 100, UNIQUE)
password (VARCHAR 255)
role (ENUM: 'student', 'admin')
points (INT, DEFAULT 0)
created_at (TIMESTAMP, DEFAULT CURRENT_TIMESTAMP)
```

### issues table
```
id (INT, AUTO_INCREMENT, PRIMARY KEY)
user_id (INT, FOREIGN KEY → users.id)
category (VARCHAR 50)
description (TEXT)
location (VARCHAR 255)
urgency (ENUM: 'low', 'medium', 'high')
image_path (VARCHAR 255)
status (ENUM: 'submitted', 'in_progress', 'resolved')
created_at (TIMESTAMP, DEFAULT CURRENT_TIMESTAMP)
```

---

## 🧪 Testing

### Register New User
```
POST /api/register_user.php
name=John Doe
email=john@srmist.edu.in
password=SecurePass123
confirm_password=SecurePass123
```

### Login
```
POST /api/login_user.php
email=john@srmist.edu.in
password=SecurePass123
```

### Submit Report
```
POST /api/submit_report.php
(with valid session)
category=water_waste
description=Leaking tap near cafeteria
location=Cafeteria Building
urgency=high
image=(file)
```

### Get Reports
```
GET /api/get_reports.php?type=user
(with valid session)
```

### Update Status (Admin)
```
POST /api/update_status.php
(with admin session)
issue_id=1
action=update
status=in_progress
```

---

## 🔍 Troubleshooting

### "Access Denied" Error
**Cause:** Direct browser access to API file
**Solution:** APIs are designed for AJAX only. Access through frontend pages.

### JSON Parse Error
**Cause:** Frontend receiving HTML instead of JSON
**Solution:** Ensure API endpoint is returning correct header and format

### Database Connection Failed
**Cause:** Wrong credentials or database doesn't exist
**Solution:** Check `api/db.php` and import `sustain_u.sql`

### Session Lost on Redirect
**Cause:** Session not properly stored
**Solution:** Ensure `session_start()` is called before any output

---

**Last Updated:** February 14, 2026

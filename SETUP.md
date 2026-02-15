## SUSTAIN-U Setup Instructions

### 1. Database Setup
- Open phpMyAdmin (http://localhost/phpmyadmin)
- Import `sustain_u.sql` file
- Or run the SQL commands manually

### 2. Database Configuration
- Open `api/db.php`
- Update credentials if needed:
  - host: localhost
  - db_user: root
  - db_pass: (empty)
  - database: sustain_u

### 3. File Permissions
- Ensure `uploads/` folder has write permissions
- Set folder permissions to 755

### 4. Project Access
- http://localhost/Sustain-U/

### 5. Test Credentials
- Admin Email: admin@srmist.edu.in
- Admin Password: admin123

### 6. Features Implemented
✅ Student Registration (only @srmist.edu.in)
✅ Student/Admin Login
✅ Issue Submission with Image Upload
✅ Points System (+5 points per report)
✅ Dashboard with Issue Tracking
✅ Admin Panel with Status Management
✅ Responsive Design
✅ Security (Prepared Statements, Password Hashing, Session Management)

### 7. File Structure
```
Sustain-U/
├── api/
│   ├── db.php
│   ├── register_user.php
│   ├── login_user.php
│   ├── submit_report.php
│   ├── get_reports.php
│   └── update_status.php
├── css/
│   └── style.css
├── js/
│   └── main.js
├── uploads/
├── index.php
├── login.php
├── register.php
├── dashboard.php
├── admin_dashboard.php
├── logout.php
└── sustain_u.sql
```

<?php
/**
 * SUSTAIN-U - Complete Profile Page
 * Mandatory for first-time login
 */
require_once 'config.php';
ini_set('display_errors', 1);
error_reporting(E_ALL);
require_once 'api/db.php';

// Ensure user is logged in
if (!isLoggedIn()) {
    header('Location: login.php');
    exit;
}

// Ensure user is a student (Admin doesn't need this flow usually, or maybe they do? user said "Students")
if (!isStudent()) {
    header('Location: admin_dashboard.php');
    exit;
}

$userId = $_SESSION['user_id'];
$error = '';
$success = '';

// Fetch current user data to pre-fill
$stmt = $conn->prepare("SELECT name, email, register_number, degree, department, section, phone, other_details FROM users WHERE id = ?");
if (!$stmt) {
    logError("Profile Fetch Prepare Failed", ['error' => $conn->error, 'user_id' => $userId]);
    die("Database Prepare Error: " . $conn->error . " | Please contact admin.");
}
$stmt->bind_param("i", $userId);
$stmt->execute();
$result = $stmt->get_result();
if (!$result) {
    logError("Profile Fetch Execution Failed", ['error' => $stmt->error, 'user_id' => $userId]);
    die("Database Execution Error: " . $stmt->error . " | Please contact admin.");
}
$user = $result->fetch_assoc();
$stmt->close();

if (!$user) {
    // Should not happen if logged in, but defensive check
    logError("Profile Fetch Failed - User not found in DB", ['user_id' => $userId]);
    $_SESSION['login_error'] = "Session error. Please login again.";
    header('Location: logout.php');
    exit;
}

// Ensure all required keys exist to avoid undefined index notices
$user['name'] = $user['name'] ?? '';
$user['email'] = $user['email'] ?? '';
$user['register_number'] = $user['register_number'] ?? '';
$user['degree'] = $user['degree'] ?? '';
$user['department'] = $user['department'] ?? '';
$user['section'] = $user['section'] ?? '';
$user['phone'] = $user['phone'] ?? '';
$user['other_details'] = $user['other_details'] ?? '';

// Handle Form Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $register_number = trim($_POST['register_number'] ?? '');
    $degree = trim($_POST['degree'] ?? '');
    $department = trim($_POST['department'] ?? '');
    $section = trim($_POST['section'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $other_details = trim($_POST['other_details'] ?? '');

    // Basic Validation
    $isValid = false;
    if ($degree === 'Others') {
        if (!empty($name) && !empty($register_number) && !empty($phone) && !empty($other_details)) {
            $isValid = true;
            $department = 'Other';
            $section = 'Other';
        } else {
            $error = "Name, ID, Phone, and Details are mandatory.";
        }
    } else {
        if (!empty($name) && !empty($register_number) && !empty($degree) && !empty($department) && !empty($section) && !empty($phone)) {
            $isValid = true;
        } else {
            $error = "All fields are mandatory.";
        }
    }

    if ($isValid) {
        if (!preg_match('/^[0-9]{10}$/', $phone)) {
            $error = "Phone number must be exactly 10 digits (numbers only).";
        }
        else {
            // Update Database
            $updateStmt = $conn->prepare("UPDATE users SET name = ?, register_number = ?, degree = ?, department = ?, section = ?, phone = ?, other_details = ? WHERE id = ?");
            if ($updateStmt) {
                $updateStmt->bind_param("sssssssi", $name, $register_number, $degree, $department, $section, $phone, $other_details, $userId);
                if ($updateStmt->execute()) {
                    // Update Session Data if needed
                    $_SESSION['user_name'] = $name;
                    $_SESSION['profile_complete'] = true;

                    // Redirect to Dashboard
                    header('Location: index.php');
                    exit;
                }
                else {
                    logError("Profile Update Execution Failed", ['error' => $updateStmt->error, 'user_id' => $userId]);
                    $error = "Failed to update profile. Please try again.";
                }
                $updateStmt->close();
            }
            else {
                logError("Profile Update Prepare Failed", ['error' => $conn->error, 'user_id' => $userId]);
                $error = "Database error during profile update.";
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Complete Profile - Sustain-U</title>
    <link rel="stylesheet" href="css/style.css">
    <style>
        .profile-card {
            max-width: 600px;
            margin: 3rem auto;
            padding: 2rem;
        }
    </style>
</head>
<body>
    <?php include __DIR__ . '/inc/header.php'; ?>

    <main class="container">
        <div class="card profile-card">
            <h2 class="text-center" style="color: var(--primary-color);">Complete Your Profile</h2>
            <p class="text-center text-muted" style="margin-bottom: 2rem;">Please provide the following details to continue. This is a one-time process.</p>

            <?php if ($error): ?>
                <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
            <?php
endif; ?>

            <form method="POST" action="">
                <!-- Full Name -->
                <div class="form-group">
                    <label for="name">Full Name <span style="color:red">*</span></label>
                    <input type="text" id="name" name="name" value="<?php echo htmlspecialchars($user['name'] ?? ''); ?>" required>
                </div>

                <!-- Email ID (Read-only) -->
                <div class="form-group">
                    <label for="email">Email ID <span style="color:red">*</span></label>
                    <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($user['email'] ?? ''); ?>" readonly style="background-color: #eee; cursor: not-allowed;">
                    <small class="text-muted">Email cannot be changed.</small>
                </div>

                <!-- Registration Number -->
                <div class="form-group">
                    <label for="register_number">Registration Number <span style="color:red">*</span></label>
                    <input type="text" id="register_number" name="register_number" value="<?php echo htmlspecialchars($user['register_number'] ?? ''); ?>" required placeholder="e.g. RA2111003010001">
                </div>

                <!-- Degree -->
                <div class="form-group">
                    <label for="degree">Degree <span style="color:red">*</span></label>
                    <select id="degree" name="degree" required onchange="updateDepartments()">
                        <option value="">Select Degree</option>
                    </select>
                </div>

                <!-- Department -->
                <div id="dept_container" class="form-group">
                    <label for="department">Department / Specialization <span style="color:red">*</span></label>
                    <select id="department" name="department">
                        <option value="">Select Department</option>
                    </select>
                </div>

                <!-- Other Details (Conditional) -->
                <div id="other_details_container" class="form-group" style="display: none;">
                    <label for="other_details">Other Details (Faculty ID, Department, Designation) <span style="color:red">*</span></label>
                    <textarea id="other_details" name="other_details" rows="3" placeholder="Enter Faculty ID, Department, and Designation details here..."><?php echo htmlspecialchars($user['other_details'] ?? ''); ?></textarea>
                </div>

                <script>
                const degreeData = {
                    "B.Tech": ["Computer Science & Engineering", "CSE – Artificial Intelligence & Machine Learning", "CSE – Data Science", "CSE – Big Data Analytics", "CSE – Cloud Computing", "CSE – Cyber Security", "CSE – Internet of Things", "CSE – Gaming Technology", "Computer Science & Business Systems", "Artificial Intelligence", "Information Technology", "Electronics & Communication Engineering", "ECE – Data Science", "Electronics Engineering (VLSI Design)", "Electrical & Electronics Engineering", "Mechanical Engineering", "Mechanical – AI & ML", "Mechanical – Robotics & Automation", "Mechatronics Engineering", "Mechatronics – Robotics", "Mechatronics – IoT Systems", "Automobile Engineering", "Aerospace Engineering", "Civil Engineering", "Chemical Engineering", "Nanotechnology", "Biomedical Engineering", "Biotechnology", "Biotechnology – Regenerative Medicine", "Genetic Engineering", "Bioinformatics", "Food Process Engineering"],
                    "B.Sc": ["Physics", "Chemistry", "Mathematics", "Computer Science", "Biotechnology", "Microbiology", "Biochemistry", "Visual Communication", "Psychology", "Environmental Science", "Hospitality / Hotel Management", "Neuroscience Technology", "Nursing", "Allied Health Sciences", "Occupational Therapy"],
                    "BA": ["English", "Economics", "Journalism / Mass Communication"],
                    "B.Com": ["Commerce", "Accounting & Finance"],
                    "BCA": ["Computer Applications"],
                    "MBBS": ["Medicine"],
                    "BDS": ["Dentistry"],
                    "BPT": ["Physiotherapy"],
                    "BBA": ["Business Administration"],
                    "MBA": ["Finance", "Marketing", "HR", "Business Analytics", "Healthcare Management"],
                    "BA LLB": ["Law"],
                    "BBA LLB": ["Law"],
                    "LLB": ["Law"],
                    "LLM": ["Law"],
                    "B.Arch": ["Architecture"],
                    "M.Arch": ["Architecture"],
                    "B.Des": ["Interior Design"],
                    "M.Tech": ["All Engineering Specializations"],
                    "M.Sc": ["Physics", "Chemistry", "Math", "Biotech", "Microbiology"],
                    "MCA": ["Computer Applications"],
                    "M.Com": ["Commerce"],
                    "MA": ["English", "Economics"],
                    "MD/MS": ["Medical Specializations"],
                    "MDS": ["Dentistry"],
                    "MPH": ["Public Health"],
                    "PhD": ["All disciplines"],
                    "Others": []
                };

                const degreeSelect = document.getElementById('degree');
                const deptSelect = document.getElementById('department');
                
                // Track initial values from PHP
                const initialDegree = "<?php echo $user['degree'] ?? ''; ?>";
                const initialDept = "<?php echo $user['department'] ?? ''; ?>";

                function initDropdowns() {
                    // Populate Degrees
                    for (const degree in degreeData) {
                        const opt = document.createElement('option');
                        opt.value = degree;
                        opt.textContent = degree;
                        if (degree === initialDegree) opt.selected = true;
                        degreeSelect.appendChild(opt);
                    }
                    
                    if (initialDegree) {
                        updateDepartments(initialDept);
                    }
                }

                function updateDepartments(selectedDept = '') {
                    const selectedDegree = degreeSelect.value;
                    const deptContainer = document.getElementById('dept_container');
                    const sectionContainer = document.getElementById('section_container');
                    const otherContainer = document.getElementById('other_details_container');
                    const regLabel = document.querySelector('label[for="register_number"]');

                    if (selectedDegree === 'Others') {
                        deptContainer.style.display = 'none';
                        sectionContainer.style.display = 'none';
                        otherContainer.style.display = 'block';
                        regLabel.innerHTML = 'Faculty / Staff ID <span style="color:red">*</span>';
                        document.getElementById('department').required = false;
                        document.getElementById('section').required = false;
                        document.getElementById('other_details').required = true;
                    } else {
                        deptContainer.style.display = 'block';
                        sectionContainer.style.display = 'block';
                        otherContainer.style.display = 'none';
                        regLabel.innerHTML = 'Registration Number <span style="color:red">*</span>';
                        document.getElementById('department').required = true;
                        document.getElementById('section').required = true;
                        document.getElementById('other_details').required = false;
                        
                        deptSelect.innerHTML = '<option value="">Select Department</option>';
                        if (selectedDegree && degreeData[selectedDegree]) {
                            degreeData[selectedDegree].forEach(dept => {
                                const opt = document.createElement('option');
                                opt.value = dept;
                                opt.textContent = dept;
                                if (dept === selectedDept) opt.selected = true;
                                deptSelect.appendChild(opt);
                            });
                        }
                    }
                }

                document.addEventListener('DOMContentLoaded', initDropdowns);
                </script>

                <!-- Section -->
                <div id="section_container" class="form-group">
                    <label for="section">Section <span style="color:red">*</span></label>
                    <input type="text" id="section" name="section" value="<?php echo htmlspecialchars($user['section'] ?? ''); ?>" placeholder="e.g. A, B, C or Particular Group">
                </div>

                <div class="form-group">
                    <label for="phone">Phone Number <span style="color:red">*</span></label>
                    <input type="tel" id="phone" name="phone"
                           value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>"
                           required
                           placeholder="e.g. 9876543210"
                           pattern="[0-9]{10}"
                           maxlength="10"
                           inputmode="numeric"
                           title="Enter exactly 10 digits — numbers only"
                           oninput="this.value=this.value.replace(/[^0-9]/g,''); validatePhone(this);"
                           onkeypress="return event.charCode >= 48 && event.charCode <= 57;">
                    <small id="phone-hint" style="color:#e53935; display:none;">Please enter exactly 10 digits (numbers only).</small>
                </div>
                <script>
                function validatePhone(input) {
                    const hint = document.getElementById('phone-hint');
                    if (input.value.length > 0 && !/^[0-9]{10}$/.test(input.value)) {
                        hint.style.display = 'block';
                        input.style.borderColor = '#e53935';
                    } else {
                        hint.style.display = 'none';
                        input.style.borderColor = '';
                    }
                }
                document.querySelector('form').addEventListener('submit', function(e) {
                    const phone = document.getElementById('phone');
                    if (!/^[0-9]{10}$/.test(phone.value)) {
                        e.preventDefault();
                        document.getElementById('phone-hint').style.display = 'block';
                        phone.style.borderColor = '#e53935';
                        phone.focus();
                    }
                });
                </script>

                <div style="margin-top: 2rem;">
                    <button type="submit" class="btn btn-primary btn-block">Save & Continue</button>
                </div>
            </form>
        </div>
    </main>
</body>
</html>

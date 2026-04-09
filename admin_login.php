<?php
/**
 * SUSTAIN-U - Admin Login Page
 * Login form for administrators
 */
require_once 'config.php';

// Server-side POST handler (fallback) so admin login works even if fetch/cookie issues occur
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($email) || empty($password)) {
        $_SESSION['login_error'] = 'Email and password are required';
        header('Location: admin_login.php');
        exit;
    }

    // Removal of Hardcoded Admins: Relying solely on database authentication.
    // Logic continues below to check the users table for admin role.

    // Try DB-backed admin account
    require_once __DIR__ . '/api/db.php';
    $stmt = $conn->prepare("SELECT id, name, email, password, role FROM users WHERE email = ?");
    if ($stmt) {
        $stmt->bind_param('s', $email);
        $stmt->execute();
        $user = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if ($user && password_verify($password, $user['password'])) {
            if (($user['role'] ?? '') === 'admin') {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_name'] = $user['name'];
                $_SESSION['user_email'] = $user['email'];
                $_SESSION['user_role'] = 'admin';
                $_SESSION['login_time'] = time();
                session_regenerate_id(true);
                header('Location: admin_dashboard.php');
                exit;
            }
            $_SESSION['login_error'] = 'Account is not an administrator';
            header('Location: admin_login.php');
            exit;
        }
    }

    $_SESSION['login_error'] = 'Invalid email or password';
    header('Location: admin_login.php');
    exit;
}

// Redirect if already logged in as admin
if (isAdmin()) {
    header('Location: admin_dashboard.php');
    exit;
}

$flashError = $_SESSION['login_error'] ?? null;
unset($_SESSION['login_error']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login - Sustain-U</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body class="login-page">
    <?php include __DIR__ . '/inc/header.php'; ?>

    <main class="container" style="max-width: 500px; margin: 3rem auto;">
        <div class="card">
            <div class="card-header">
                <h2>Administrator Login</h2>
                <p style="margin: 0.5rem 0 0; color: #666;">Restricted access for administrators only</p>
            </div>

            <div class="card-body">
            <div id="errorMessage" class="alert alert-danger <?= empty($flashError) ? 'hidden' : '' ?>" style="margin-bottom: 1.5rem;"><?= !empty($flashError) ? sanitize($flashError) : '' ?></div> 

                <form id="adminLoginForm" method="POST">
                    <div class="form-group">
                        <label for="email">Admin Email</label>
                        <input type="email" id="email" name="email" required placeholder="Enter Admin Email" autofocus>
                        <small class="error-message" id="emailError"></small>
                    </div>

                    <div class="form-group">
                        <label for="password">Password</label>
                        <input type="password" id="password" name="password" required placeholder="Enter your password">
                        <small class="error-message" id="passwordError"></small>
                    </div>

                    <button type="submit" class="btn btn-primary btn-block" style="margin-top: 1.5rem;">Login as Admin</button>
                </form>

                <hr style="margin: 1.5rem 0; border: none; border-top: 1px solid var(--border-color);">

                <p style="text-align: center; font-size: 0.9rem; color: #666;">
                    <strong>Student?</strong> <a href="login.php">Login as Student</a>
                </p>


            </div>
        </div>
    </main>

    <script src="js/main.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const form = document.getElementById('adminLoginForm');
            const errorMessage = document.getElementById('errorMessage');

            if (!form) {
                console.error('Admin login form not found!');
                return;
            }

            // Named handler so we can remove it if we need to fallback to a normal POST
            async function handleAdminLogin(e) {
                e.preventDefault();

                errorMessage.classList.add('hidden');
                document.getElementById('emailError').textContent = '';
                document.getElementById('passwordError').textContent = '';

                const email = document.getElementById('email').value.trim();
                const password = document.getElementById('password').value;

                if (!validateEmail(email)) {
                    document.getElementById('emailError').textContent = 'Please enter a valid email';
                    return;
                }

                if (!password) {
                    document.getElementById('passwordError').textContent = 'Password is required';
                    return;
                }

                const button = form.querySelector('button[type="submit"]');
                button.disabled = true;
                button.textContent = 'Verifying...';

                try {
                    const response = await fetch('api/login_user.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                        },
                        credentials: 'include', // ensure cookies are always sent/accepted
                        body: JSON.stringify({ email, password, is_admin: true })
                    });

                    const data = await response.json();

                    if (data && data.success) {
                        window.location.href = data.redirect || 'admin_dashboard.php';
                        return;
                    }

                    errorMessage.textContent = data.message || 'Login failed. Invalid credentials.';
                    errorMessage.classList.remove('hidden');
                    button.disabled = false;
                    button.textContent = 'Login as Admin';
                } catch (err) {
                    // Network/fetch failed — fallback to normal form POST to let server handle (useful when fetch/cookie blocked)
                    console.warn('Fetch failed, falling back to regular POST', err);
                    form.removeEventListener('submit', handleAdminLogin);
                    form.submit();
                }
            }

            form.addEventListener('submit', handleAdminLogin);
        });
    </script>
</body>
</html>

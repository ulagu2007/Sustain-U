<?php
/**
 * SUSTAIN-U - Admin Login Page
 * Login form for administrators
 */
require_once 'config.php';

// Redirect if already logged in as admin
if (isAdmin()) {
    header('Location: /Sustain-U/admin_dashboard.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login - Sustain-U</title>
    <link rel="stylesheet" href="/Sustain-U/css/style.css">
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
                <div id="errorMessage" class="alert alert-danger hidden" style="margin-bottom: 1.5rem;"></div>

                <form id="adminLoginForm" method="POST">
                    <div class="form-group">
                        <label for="email">Admin Email</label>
                        <input type="email" id="email" name="email" required placeholder="admin@srmist.edu.in" autofocus>
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
                    <strong>Student?</strong> <a href="/Sustain-U/login.php">Login as Student</a>
                </p>
            </div>
        </div>
    </main>

    <script src="/Sustain-U/js/main.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const form = document.getElementById('adminLoginForm');
            const errorMessage = document.getElementById('errorMessage');

            if (!form) {
                console.error('Admin login form not found!');
                return;
            }

            form.addEventListener('submit', async (e) => {
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

                try {
                    const button = form.querySelector('button[type="submit"]');
                    button.disabled = true;
                    button.textContent = 'Verifying...';

                    const response = await fetch('/Sustain-U/api/login_user.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                        },
                        body: JSON.stringify({
                            email: email,
                            password: password,
                            is_admin: true
                        })
                    });

                    const data = await response.json();

                    if (data.success) {
                        window.location.href = '/Sustain-U/admin_dashboard.php';
                    } else {
                        errorMessage.textContent = data.message || 'Login failed. Invalid credentials.';
                        errorMessage.classList.remove('hidden');
                        button.disabled = false;
                        button.textContent = 'Login as Admin';
                    }
                } catch (error) {
                    console.error('Error:', error);
                    errorMessage.textContent = 'An error occurred. Please try again later.';
                    errorMessage.classList.remove('hidden');
                    form.querySelector('button[type="submit"]').disabled = false;
                    form.querySelector('button[type="submit"]').textContent = 'Login as Admin';
                }
            });
        });
    </script>
</body>
</html>

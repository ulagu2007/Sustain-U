<?php
/**
 * SUSTAIN-U - Student Login (structured)
 * - improved markup, accessibility and client behaviour
 */
require_once 'config.php';

// Redirect if already logged in
if (isLoggedIn()) {
    header('Location: /Sustain-U/my_works.php');
    exit;
}

// Optional server-side flash (not used by API by default)
$flashError = $_SESSION['login_error'] ?? null;
unset($_SESSION['login_error']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Login - Sustain-U</title>
    <link rel="stylesheet" href="/Sustain-U/css/style.css">
</head>
<body class="login-page">
    <?php include __DIR__ . '/inc/header.php'; ?>

    <main class="container auth-wrapper" id="main-content">
        <div class="card" role="region" aria-labelledby="loginHeading">
            <div class="card-header">
                <h2 id="loginHeading">Student Login</h2>
                <p class="text-muted mt-1">Access your Sustain-U dashboard</p>
            </div>

            <div class="card-body">
                <?php if ($flashError): ?>
                    <div class="alert alert-danger" role="alert"><?= sanitize($flashError) ?></div>
                <?php endif; ?>

                <div id="errorMessage" class="alert alert-danger hidden" role="alert" aria-live="assertive" aria-atomic="true"></div>

                <form id="loginForm" method="POST" class="login-form" autocomplete="on">
                    <div class="form-group">
                        <label for="email">Student Email</label>
                        <input type="email" id="email" name="email" required placeholder="student@srmist.edu.in" autocomplete="email" aria-describedby="emailError" autofocus>
                        <small class="error-message" id="emailError" aria-live="polite"></small>
                    </div>

                    <div class="form-group">
                        <label for="password">Password</label>
                        <input type="password" id="password" name="password" required placeholder="Enter your password" autocomplete="current-password" aria-describedby="passwordError">
                        <small class="error-message" id="passwordError" aria-live="polite"></small>
                    </div>

                    <div class="form-group form-actions">
                        <label for="rememberMe" style="display:flex;align-items:center;gap:0.5rem;">
                            <input type="checkbox" id="rememberMe" name="remember_me">
                            <span>Remember me</span>
                        </label>
                        <a href="#forgot" class="text-muted" style="font-size:0.9rem;">Forgot password?</a>
                    </div>

                    <button type="submit" class="btn btn-primary btn-block mt-3" id="submitBtn">Login</button>
                </form>

                <p class="text-center mt-3">
                    Don't have an account? <a href="/Sustain-U/register.php" class="text-primary" style="font-weight:600;">Register here</a>
                </p>

                <hr class="divider">

                <p class="text-center text-muted">
                    <strong>Admin?</strong> <a href="/Sustain-U/admin_login.php">Login as Admin</a>
                </p>
            </div>
        </div>
    </main>

    <noscript>
        <div class="container auth-wrapper">
            <div class="card">
                <p class="text-muted">JavaScript is required for the best experience. You can still try to login, but responses will be raw JSON.</p>
            </div>
        </div>
    </noscript>

    <script src="/Sustain-U/js/main.js"></script>
    <script>
    (function () {
        'use strict';

        const form = document.getElementById('loginForm');
        const errorMessage = document.getElementById('errorMessage');
        const submitBtn = document.getElementById('submitBtn');

        function showError(text, focusId) {
            errorMessage.textContent = text;
            errorMessage.classList.remove('hidden');
            if (focusId) document.getElementById(focusId)?.focus();
        }

        if (!form) return;

        form.addEventListener('submit', async (e) => {
            e.preventDefault();

            // clear previous
            errorMessage.classList.add('hidden');
            document.getElementById('emailError').textContent = '';
            document.getElementById('passwordError').textContent = '';

            const email = document.getElementById('email').value.trim();
            const password = document.getElementById('password').value;

            if (!validateEmail(email)) {
                document.getElementById('emailError').textContent = 'Please enter a valid email';
                return document.getElementById('email').focus();
            }

            if (!password) {
                document.getElementById('passwordError').textContent = 'Password is required';
                return document.getElementById('password').focus();
            }

            submitBtn.disabled = true;
            submitBtn.textContent = 'Logging in...';

            try {
                const res = await fetch('/Sustain-U/api/login_user.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    credentials: 'same-origin',
                    body: JSON.stringify({ email, password })
                });

                const data = await res.json();
                if (data?.success) {
                    window.location.href = data.redirect || '/Sustain-U/my_works.php';
                    return;
                }

                showError(data?.message || 'Login failed. Please try again.', 'email');
            } catch (err) {
                console.error(err);
                showError('An unexpected error occurred. Please try again later.');
            } finally {
                submitBtn.disabled = false;
                submitBtn.textContent = 'Login';
            }
        });
    })();
    </script>
</body>
</html>

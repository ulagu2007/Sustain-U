<?php
/**
 * SUSTAIN-U - Student Login Page
 * Login form for students
 * Restore Final (No Escaping)
 */
require_once __DIR__ . '/config.php';

// Check if config loaded
if (!defined('SUSTAIN_U_LOADED')) {
    die("Configuration error: Config not loaded.");
}

// Server-side POST handler
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($email) || empty($password)) {
        $_SESSION['login_error'] = 'Email and password are required';
        header('Location: login.php');
        exit;
    }

    require_once __DIR__ . '/api/db.php';
    
    if (!isset($conn)) {
        die("Database connection error");
    }

    $stmt = $conn->prepare("SELECT id, name, email, password, role FROM users WHERE email = ?");
    if ($stmt) {
        $stmt->bind_param('s', $email);
        $stmt->execute();
        $user = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if ($user && password_verify($password, $user['password'])) {
            // REJECT ADMINISTRATORS: Admin login only allowed on admin_login.php
            if (($user['role'] ?? '') === 'admin') {
                $_SESSION['login_error'] = 'Invalid email or password';
                header('Location: login.php');
                exit;
            }

            // Success
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_name'] = $user['name'];
            $_SESSION['user_email'] = $user['email'];
            $_SESSION['user_role'] = $user['role'];
            $_SESSION['login_time'] = time();
            session_regenerate_id(true);
            
             // Check profile completion
            $profileComplete = check_profile_completion($conn, $user['id']);
            $_SESSION['profile_complete'] = $profileComplete;

            header('Location: ' . ($profileComplete ? 'index.php' : 'complete_profile.php'));
            exit;
        }
    }

    $_SESSION['login_error'] = 'Invalid email or password';
    header('Location: login.php');
    exit;
}

// Redirect if already logged in and not admin
if (isLoggedIn() && isStudent()) {
    $target = ($_SESSION['profile_complete'] ?? false) ? 'index.php' : 'complete_profile.php';
    header("Location: $target");
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
    <title>Login - Sustain-U</title>
    <link rel="stylesheet" href="css/style.css">
    
    <!-- DOM Logic for 2FA Login -->
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const passwordFormContainer = document.getElementById('passwordFormContainer');
            const otpFormContainer = document.getElementById('otpFormContainer');
            const btnSubmitLogin = document.getElementById('btnSubmitLogin');
            const btnVerifyOtp = document.getElementById('btnVerifyOtp');
            const errorMessage = document.getElementById('errorMessage');
            const successMessage = document.getElementById('successMessage');
            let verifiedEmail = '';

            function showMessage(msg, isError) {
                errorMessage.classList.add('hidden');
                successMessage.classList.add('hidden');
                if (isError) {
                    errorMessage.textContent = msg;
                    errorMessage.classList.remove('hidden');
                } else {
                    successMessage.textContent = msg;
                    successMessage.classList.remove('hidden');
                }
            }

            // --- Forgot Password Flow ---
            const linkForgotPassword = document.getElementById('linkForgotPassword');
            const forgotRequestContainer = document.getElementById('forgotRequestContainer');
            const forgotVerifyContainer = document.getElementById('forgotVerifyContainer');
            const forgotResetContainer = document.getElementById('forgotResetContainer');
            const btnSendResetOtp = document.getElementById('btnSendResetOtp');
            const btnVerifyResetOtp = document.getElementById('btnVerifyResetOtp');
            const btnResetPassword = document.getElementById('btnResetPassword');
            const linksBackToLogin = document.querySelectorAll('.linkBackToLogin');

            if (linkForgotPassword) {
                linkForgotPassword.addEventListener('click', () => {
                    passwordFormContainer.classList.add('hidden');
                    otpFormContainer.classList.add('hidden');
                    forgotRequestContainer.classList.remove('hidden');
                    forgotVerifyContainer.classList.add('hidden');
                    forgotResetContainer.classList.add('hidden');
                    errorMessage.classList.add('hidden');
                    successMessage.classList.add('hidden');
                });
            }

            linksBackToLogin.forEach(link => {
                link.addEventListener('click', () => {
                    passwordFormContainer.classList.remove('hidden');
                    forgotRequestContainer.classList.add('hidden');
                    forgotVerifyContainer.classList.add('hidden');
                    forgotResetContainer.classList.add('hidden');
                    otpFormContainer.classList.add('hidden');
                });
            });

            if (btnSendResetOtp) {
                btnSendResetOtp.addEventListener('click', async () => {
                    const email = document.getElementById('forgotEmail').value.trim();
                    if (!email) {
                        showMessage('Email is required.', true);
                        return;
                    }

                    btnSendResetOtp.disabled = true;
                    btnSendResetOtp.textContent = 'Sending OTP...';

                    try {
                        const res = await fetch('api/forgot_password.php', {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/json' },
                            body: JSON.stringify({ email })
                        });
                        const data = await res.json();

                        if (data.success) {
                            showMessage(data.message, false);
                            forgotRequestContainer.classList.add('hidden');
                            forgotVerifyContainer.classList.remove('hidden');
                            verifiedEmail = email; // Store for the next step
                        } else {
                            showMessage(data.message, true);
                        }
                    } catch (err) {
                        showMessage('Error sending OTP. Try again.', true);
                    } finally {
                        btnSendResetOtp.disabled = false;
                        btnSendResetOtp.textContent = 'Send Reset OTP';
                    }
                });
            }

            let verifiedOtp = '';
            if (btnVerifyResetOtp) {
                btnVerifyResetOtp.addEventListener('click', async () => {
                    const otp = document.getElementById('forgotVerifyOtpCode').value.trim();
                    if (!otp) {
                        showMessage('OTP is required.', true);
                        return;
                    }

                    btnVerifyResetOtp.disabled = true;
                    btnVerifyResetOtp.textContent = 'Verifying...';

                    try {
                        const res = await fetch('api/verify_reset_otp.php', {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/json' },
                            body: JSON.stringify({ email: verifiedEmail, otp })
                        });
                        const data = await res.json();

                        if (data.success) {
                            showMessage(data.message, false);
                            verifiedOtp = otp;
                            forgotVerifyContainer.classList.add('hidden');
                            forgotResetContainer.classList.remove('hidden');
                        } else {
                            showMessage(data.message, true);
                        }
                    } catch (err) {
                        showMessage('Error verifying OTP. Try again.', true);
                    } finally {
                        btnVerifyResetOtp.disabled = false;
                        btnVerifyResetOtp.textContent = 'Verify OTP';
                    }
                });
            }

            if (btnResetPassword) {
                btnResetPassword.addEventListener('click', async () => {
                    const new_password = document.getElementById('newPassword').value;
                    const confirm_password = document.getElementById('confirmNewPassword').value;

                    if (!new_password || !confirm_password) {
                        showMessage('Both password fields are required.', true);
                        return;
                    }

                    if (new_password !== confirm_password) {
                        showMessage('Passwords do not match.', true);
                        return;
                    }

                    if (new_password.length < 4) {
                        showMessage('Password must be at least 4 characters.', true);
                        return;
                    }

                    btnResetPassword.disabled = true;
                    btnResetPassword.textContent = 'Resetting...';

                    try {
                        const res = await fetch('api/reset_password.php', {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/json' },
                            body: JSON.stringify({ 
                                email: verifiedEmail, 
                                otp: verifiedOtp, 
                                new_password, 
                                confirm_password 
                            })
                        });
                        const data = await res.json();

                        if (data.success) {
                            showMessage(data.message, false);
                            setTimeout(() => { window.location.reload(); }, 3000);
                        } else {
                            showMessage(data.message, true);
                        }
                    } catch (err) {
                        showMessage('Error resetting password. Try again.', true);
                    } finally {
                        btnResetPassword.disabled = false;
                        btnResetPassword.textContent = 'Reset Password';
                    }
                });
            }

            if (btnSubmitLogin) {
                btnSubmitLogin.addEventListener('click', async () => {
                    const email = document.getElementById('email').value.trim();
                    const password = document.getElementById('password').value;

                    if (!email || !password) {
                        showMessage('Email and password are required.', true);
                        return;
                    }

                    btnSubmitLogin.disabled = true;
                    btnSubmitLogin.textContent = 'Verifying & Sending OTP...';
                    errorMessage.classList.add('hidden');
                    successMessage.classList.add('hidden');

                    try {
                        const res = await fetch('api/login_user.php', {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/json' },
                            body: JSON.stringify({ email, password })
                        });
                        const data = await res.json();

                        if (!res.ok || !data.success) {
                            showMessage(data.message || 'Login failed.', true);
                            btnSubmitLogin.disabled = false;
                            btnSubmitLogin.textContent = 'Login';
                        } else if (data.require_otp) {
                            showMessage(data.message, false);
                            verifiedEmail = email;
                            passwordFormContainer.classList.add('hidden');
                            otpFormContainer.classList.remove('hidden');
                            document.getElementById('otpCode').focus();
                        } else if (data.redirect) {
                            window.location.href = data.redirect;
                        }
                    } catch (err) {
                        showMessage('Network error. Please try again.', true);
                        btnSubmitLogin.disabled = false;
                        btnSubmitLogin.textContent = 'Login';
                    }
                });
            }

            if (btnVerifyOtp) {
                btnVerifyOtp.addEventListener('click', async () => {
                    const otp = document.getElementById('otpCode').value.trim();

                    if (!otp) {
                        showMessage('OTP is required.', true);
                        return;
                    }

                    btnVerifyOtp.disabled = true;
                    btnVerifyOtp.textContent = 'Verifying...';
                    errorMessage.classList.add('hidden');
                    successMessage.classList.add('hidden');

                    try {
                        const res = await fetch('api/verify_otp.php', {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/json' },
                            body: JSON.stringify({ email: verifiedEmail, otp })
                        });
                        const data = await res.json();

                        if (!res.ok || !data.success) {
                            showMessage(data.message || 'Verification failed.', true);
                            btnVerifyOtp.disabled = false;
                            btnVerifyOtp.textContent = 'Verify & Login';
                        } else {
                            showMessage(data.message, false);
                            setTimeout(() => {
                                if (data.redirect) {
                                    // Use window.top.location.href to escape any frame-based wrappers
                                    // and ensure full-page navigation.
                                    window.top.location.href = data.redirect;
                                } else {
                                    window.top.location.reload();
                                }
                            }, 500);
                        }
                    } catch (err) {
                        showMessage('Network error. Please try again.', true);
                        btnVerifyOtp.disabled = false;
                        btnVerifyOtp.textContent = 'Verify & Login';
                    }
                });
            }
            
            // Allow Enter key
            const inputsPass = document.querySelectorAll('#passwordFormContainer input');
            inputsPass.forEach(input => {
                input.addEventListener('keypress', function(e) {
                    if (e.key === 'Enter') {
                        e.preventDefault();
                        btnSubmitLogin.click();
                    }
                });
            });

            const inputsOtp = document.querySelectorAll('#otpFormContainer input');
            inputsOtp.forEach(input => {
                input.addEventListener('keypress', function(e) {
                    if (e.key === 'Enter') {
                        e.preventDefault();
                        btnVerifyOtp.click();
                    }
                });
            });
        });
    </script>
</head>
<body class="login-page">
    <?php include __DIR__ . '/inc/header.php'; ?>

    <main class="container" style="max-width: 500px; margin: 3rem auto;">
        <div class="card">
            <div class="card-header">
                <h2>Student Login</h2>
                <p style="margin: 0.5rem 0 0; color: #666;">Welcome back! Please login to your account.</p>
            </div>

            <div class="card-body">
                <div id="errorMessage" class="alert alert-danger <?= empty($flashError) ? 'hidden' : '' ?>" style="margin-bottom: 1.5rem;"><?= !empty($flashError) ? sanitize($flashError) : '' ?></div>
                <div id="successMessage" class="alert alert-success hidden" style="margin-bottom: 1.5rem;"></div>

                <div id="passwordFormContainer">
                    <form onsubmit="return false;">
                        <div class="form-group">
                            <label for="email">Email Address</label>
                            <input type="email" id="email" required placeholder="username@srmist.edu.in" autofocus>
                        </div>
    
                        <div class="form-group">
                            <label for="password">Password</label>
                            <input type="password" id="password" required placeholder="Enter your password">
                        </div>
    
                         <div style="margin-bottom: 1.5rem; text-align: right;">
                            <a href="javascript:void(0)" id="linkForgotPassword" style="font-size: 0.9rem; color: var(--primary-color) !important;">Forgot Password?</a>
                        </div>
    
                        <button type="button" id="btnSubmitLogin" class="btn btn-primary btn-block" style="border-radius: 8px;">Login</button>
                    </form>
                </div>

                <!-- Forgot Password: Step 1 (Request OTP) -->
                <div id="forgotRequestContainer" class="hidden">
                    <form onsubmit="return false;">
                        <div class="card-header" style="padding: 0; margin-bottom: 1.5rem; background: none; border: none;">
                            <h3 style="color: #fff;">Reset Password</h3>
                        </div>
                        <div class="form-group">
                            <input type="email" id="forgotEmail" placeholder="username@srmist.edu.in" required>
                        </div>
                        <button type="button" id="btnSendResetOtp" class="btn btn-primary btn-block" style="border-radius: 8px; margin-bottom: 1rem;">Send Reset OTP</button>
                        <div style="text-align: center;">
                            <a href="javascript:void(0)" class="linkBackToLogin" style="font-size: 0.9rem; color: #ccc !important;">Back to Login</a>
                        </div>
                    </form>
                </div>

                <!-- Forgot Password: Step 2 (Verify OTP) -->
                <div id="forgotVerifyContainer" class="hidden">
                    <form onsubmit="return false;">
                        <div class="card-header" style="padding: 0; margin-bottom: 1.5rem; background: none; border: none;">
                            <h3 style="color: #fff;">Verify OTP</h3>
                            <p style="color: #ccc; font-size: 0.9rem;">Enter the 6-digit OTP sent to your email.</p>
                        </div>
                        <div class="form-group">
                            <input type="text" id="forgotVerifyOtpCode" placeholder="Enter OTP" required maxlength="6" style="letter-spacing: 0.3rem; text-align: center;">
                        </div>
                        <button type="button" id="btnVerifyResetOtp" class="btn btn-primary btn-block" style="border-radius: 8px; margin-bottom: 1rem;">Verify OTP</button>
                        <div style="text-align: center;">
                            <a href="javascript:void(0)" class="linkBackToLogin" style="font-size: 0.9rem; color: #ccc !important;">Back to Login</a>
                        </div>
                    </form>
                </div>

                <!-- Forgot Password: Step 3 (New Password) -->
                <div id="forgotResetContainer" class="hidden">
                    <form onsubmit="return false;">
                        <div class="card-header" style="padding: 0; margin-bottom: 1.5rem; background: none; border: none;">
                            <h3 style="color: #fff;">Set New Password</h3>
                            <p style="color: #ccc; font-size: 0.9rem;">Enter your new password and confirm it.</p>
                        </div>
                        <div class="form-group">
                            <label for="newPassword">New Password</label>
                            <input type="password" id="newPassword" placeholder="Minimum 4 characters" required>
                        </div>
                        <div class="form-group">
                            <label for="confirmNewPassword">Confirm New Password</label>
                            <input type="password" id="confirmNewPassword" placeholder="Confirm new password" required>
                        </div>
                        <button type="button" id="btnResetPassword" class="btn btn-primary btn-block" style="border-radius: 8px; margin-bottom: 1rem;">Reset Password</button>
                        <div style="text-align: center;">
                            <a href="javascript:void(0)" class="linkBackToLogin" style="font-size: 0.9rem; color: #ccc !important;">Back to Login</a>
                        </div>
                    </form>
                </div>

                <div id="otpFormContainer" class="hidden">
                    <form onsubmit="return false;">
                        <div class="form-group" style="margin-bottom: 1.5rem;">
                            <label for="otpCode">One-Time Password (OTP)</label>
                            <input type="text" id="otpCode" placeholder="Enter 6-digit OTP" required maxlength="6" pattern="\d{6}" style="letter-spacing: 0.5rem; text-align: center; font-size: 1.25rem;">
                            <small class="text-muted" style="display:block; margin-top:0.75rem; font-size:0.85rem; text-align:center;">An OTP has been sent to your email. Expires in 5 minutes.</small>
                        </div>

                        <button type="button" id="btnVerifyOtp" class="btn btn-primary btn-block" style="border-radius:8px;">Verify & Login</button>
                    </form>
                </div>

                <hr style="margin: 1.5rem 0; border: none; border-top: 1px solid var(--border-color);">

                <div style="text-align: center;">
                    <p style="margin-bottom: 0.5rem;">Don't have an account?</p>
                    <a href="register.php" class="btn btn-secondary btn-sm" style="color: #ffffff !important;">Create New Account</a>
                </div>

                <div style="margin-top: 1.5rem; text-align: center; font-size: 0.9rem;">
                    <a href="admin_login.php" class="text-muted">Admin Login</a>
                </div>
            </div>
        </div>
    </main>

    <script src="js/main.js"></script>
</body>
</html>

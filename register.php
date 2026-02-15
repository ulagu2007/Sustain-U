<?php
/**
 * SUSTAIN-U - Student Registration Page
 * Registration form that submits to api/register_user.php
 */
require_once 'config.php';

// Redirect if already logged in
if (isLoggedIn()) {
    header('Location: /Sustain-U/my_works.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - Sustain-U</title>
    <link rel="stylesheet" href="/Sustain-U/css/style.css">
</head>
<body class="login-page">
    <?php include __DIR__ . '/inc/header.php'; ?>

    <main class="container" style="max-width: 500px; margin: 3rem auto;">
        <div class="card">
            <div class="card-header">
                <h2>Create Your Account</h2>
                <p style="margin: 0.5rem 0 0; color: #666;">Join Sustain-U and start making an impact</p>
            </div>

            <div class="card-body">
                <div id="successMessage" class="alert alert-success hidden" style="margin-bottom: 1.5rem;">
                    Registration successful! <a href="/Sustain-U/login.php">Login here</a>
                </div>

                <div id="errorMessage" class="alert alert-danger hidden" style="margin-bottom: 1.5rem;"></div>

                <form id="registerForm" method="POST">
                    <div class="form-group">
                        <label for="fullName">Full Name</label>
                        <input type="text" id="fullName" name="full_name" required placeholder="John Doe">
                        <small class="error-message" id="fullNameError"></small>
                    </div>

                    <div class="form-group">
                        <label for="email">Student Email</label>
                        <input type="email" id="email" name="email" required placeholder="student@srmist.edu.in">
                        <small style="color: #666;">Must be a valid SRMIST email (@srmist.edu.in)</small>
                        <small class="error-message" id="emailError"></small>
                    </div>

                    <div class="form-group">
                        <label for="password">Password</label>
                        <input type="password" id="password" name="password" required placeholder="Minimum 8 characters">
                        <small style="color: #666;">At least 8 characters with uppercase, lowercase, and numbers</small>
                        <small class="error-message" id="passwordError"></small>
                    </div>

                    <div class="form-group">
                        <label for="confirmPassword">Confirm Password</label>
                        <input type="password" id="confirmPassword" name="confirm_password" required placeholder="Confirm password">
                        <small class="error-message" id="confirmPasswordError"></small>
                    </div>

                    <div class="form-group">
                        <label for="department">Department/Course</label>
                        <input type="text" id="department" name="department" placeholder="e.g., Computer Science" required>
                        <small class="error-message" id="departmentError"></small>
                    </div>

                    <div class="form-group">
                        <label for="phone">Phone Number (Optional)</label>
                        <input type="tel" id="phone" name="phone" placeholder="9876543210">
                    </div>

                    <div class="form-group">
                        <label style="display: flex; align-items: center; gap: 0.5rem;">
                            <input type="checkbox" id="agreeTerms" name="agree_terms" required>
                            <span>I agree to the <a href="#" style="color: var(--primary-color);">Terms and Conditions</a></span>
                        </label>
                        <small class="error-message" id="termsError"></small>
                    </div>

                    <button type="submit" class="btn btn-primary btn-block" style="margin-top: 1.5rem;">Create Account</button>
                </form>

                <p style="text-align: center; margin-top: 1.5rem;">
                    Already have an account? <a href="/Sustain-U/login.php" style="font-weight: 600;">Login here</a>
                </p>
            </div>
        </div>
    </main>

    <script src="/Sustain-U/js/main.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const form = document.getElementById('registerForm');
            const errorMessage = document.getElementById('errorMessage');
            const successMessage = document.getElementById('successMessage');

            if (!form) {
                console.error('Register form not found!');
                return;
            }

            form.addEventListener('submit', async (e) => {
                e.preventDefault();

                // Clear previous messages
                errorMessage.classList.add('hidden');
                successMessage.classList.add('hidden');

                // Validate inputs
                const fullName = document.getElementById('fullName').value.trim();
                const email = document.getElementById('email').value.trim();
                const password = document.getElementById('password').value;
                const confirmPassword = document.getElementById('confirmPassword').value;
                const department = document.getElementById('department').value.trim();
                const agreeTerms = document.getElementById('agreeTerms').checked;

                // Clear error messages
                document.querySelectorAll('.error-message').forEach(el => el.textContent = '');

                let hasErrors = false;

                if (!fullName || fullName.length < 3) {
                    document.getElementById('fullNameError').textContent = 'Full name is required (minimum 3 characters)';
                    hasErrors = true;
                }

                if (!validateEmail(email)) {
                    document.getElementById('emailError').textContent = 'Please enter a valid email address';
                    hasErrors = true;
                }

                if (!email.endsWith('@srmist.edu.in')) {
                    document.getElementById('emailError').textContent = 'Must use your SRMIST student email';
                    hasErrors = true;
                }

                if (!validatePassword(password)) {
                    document.getElementById('passwordError').textContent = 'Password must be at least 8 characters with uppercase, lowercase, and numbers';
                    hasErrors = true;
                }

                if (password !== confirmPassword) {
                    document.getElementById('confirmPasswordError').textContent = 'Passwords do not match';
                    hasErrors = true;
                }

                if (!department) {
                    document.getElementById('departmentError').textContent = 'Department/Course is required';
                    hasErrors = true;
                }

                if (!agreeTerms) {
                    document.getElementById('termsError').textContent = 'You must agree to the terms and conditions';
                    hasErrors = true;
                }

                if (hasErrors) return;

                try {
                    const button = form.querySelector('button[type="submit"]');
                    button.disabled = true;
                    button.textContent = 'Creating Account...';

                    const response = await fetch('/Sustain-U/api/register_user.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                        },
                        body: JSON.stringify({
                            full_name: fullName,
                            email: email,
                            password: password,
                            department: department,
                            phone: document.getElementById('phone').value.trim()
                        })
                    });

                    const data = await response.json();

                    if (data.success) {
                        form.reset();
                        successMessage.classList.remove('hidden');
                        setTimeout(() => {
                            window.location.href = '/Sustain-U/login.php';
                        }, 2000);
                    } else {
                        errorMessage.textContent = data.message || 'Registration failed. Please try again.';
                        errorMessage.classList.remove('hidden');
                    }

                    button.disabled = false;
                    button.textContent = 'Create Account';
                } catch (error) {
                    console.error('Error:', error);
                    errorMessage.textContent = 'An error occurred. Please try again later.';
                    errorMessage.classList.remove('hidden');
                    form.querySelector('button[type="submit"]').disabled = false;
                    form.querySelector('button[type="submit"]').textContent = 'Create Account';
                }
            });
        });
    </script>
</body>
</html>

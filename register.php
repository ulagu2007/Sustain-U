<?php
/**
 * SUSTAIN-U - Student Registration Page
 * Registration form that submits to api/register_user.php
 */
require_once 'config.php';

// Redirect if already logged in
if (isLoggedIn()) {
    header('Location: my_works.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sustain-U Student Registration</title>
    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        @font-face {
            font-family: 'Canva Sans';
            src: local('Canva Sans'), local('Arial');
        }

        :root {
            --primary: #1a73e8;
            --surface: #ffffff;
            --background: #f8f9fa;
            --text-main: #202124;
            --text-secondary: #5f6368;
            --border: #dadce0;
            --radius: 12px;
            --shadow: 0 1px 3px rgba(60,64,67,0.12), 0 1px 2px rgba(60,64,67,0.24);
        }

        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Canva Sans', 'Inter', sans-serif; }
        
        body {
            /* Background Image with Overlay */
            background: linear-gradient(rgba(255, 255, 255, 0.85), rgba(255, 255, 255, 0.95)), 
                        url('assets/bg-new.jpg') no-repeat center center fixed;
            background-size: cover;
            color: var(--text-main);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        /* AppBar */
        .app-bar {
            background: rgba(255, 255, 255, 0.9);
            backdrop-filter: blur(10px);
            padding: 1rem 2rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
            position: sticky;
            top: 0;
            z-index: 100;
            box-shadow: 0 1px 3px rgba(0,0,0,0.08);
            border-bottom: 1px solid rgba(0,0,0,0.05);
        }

        .logo {display: flex; align-items: center; gap: 0.75rem; font-size: 1.5rem; font-weight: 700; color: #2c3e50;}
        .logo img { height: 40px; width: auto; }

        .form-container {
            flex: 1;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 2rem 0;
        }

        .login-card {
            background: rgba(255, 255, 255, 0.95);
            padding: 2.5rem;
            border-radius: 16px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.1);
            width: 100%;
            max-width: 500px;
            text-align: left;
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255,255,255,0.5);
            margin: 0 1rem;
        }

        .form-group { margin-bottom: 1.25rem; }
        
        label { display: block; margin-bottom: 0.5rem; font-weight: 500; font-size: 0.9rem; }
        
        input {
            width: 100%; padding: 0.75rem; border: 1px solid #dadce0; border-radius: 8px; font-size: 1rem;
            transition: border-color 0.2s;
        }
        input:focus { outline: none; border-color: var(--primary); box-shadow: 0 0 0 2px rgba(26,115,232,0.2); }

        .btn-primary {
            width: 100%; padding: 0.85rem; background: var(--primary); color: white; border: none;
            border-radius: 8px; font-weight: 600; cursor: pointer; transition: background 0.2s;
            margin-top: 1rem;
        }
        .btn-primary:hover { background: #1558b0; }

        .text-center { text-align: center; }
        .text-muted { color: var(--text-secondary); }
        .text-primary { color: var(--primary); text-decoration: none; }
        
        .alert { 
            padding: 0.75rem 1rem; margin-bottom: 1rem; border-radius: 8px; font-size: 0.9rem; 
            border: 1px solid transparent;
        }
        .alert-danger { background: #fce8e6; color: #c5221f; }
        .alert-success { background: #e6f4ea; color: #137333; }
        .hidden { display: none; }

        .error-message { color: #d93025; font-size: 0.85rem; display: block; margin-top: 0.25rem; }
    </style>
</head>
<body>

    <nav class="app-bar">
        <div class="logo">
            <img src="assets/logo.jpeg" alt="Sustain-U Logo">
        </div>
        <div>
            <a href="login.php" style="text-decoration: none; color: var(--primary); font-weight: 500;">Login</a>
        </div>
    </nav>

    <div class="form-container">
        <div class="login-card">
            <div style="text-align: center; margin-bottom: 2rem;">
                <h2 style="font-weight: 800; color: #202124; margin-bottom: 0.5rem;">Create account</h2>
                <p class="text-muted">Join Sustain-U to report campus issues</p>
            </div>

            <div id="successMessage" class="alert alert-success hidden">
                Registration successful! <a href="login.php" style="color: inherit; font-weight: bold;">Login now</a>
            </div>

            <div id="errorMessage" class="alert alert-danger hidden"></div>

            <form id="registerForm" method="POST">
                <div class="form-group">
                    <label for="fullName">Full Name</label>
                    <input type="text" id="fullName" name="full_name" required placeholder="John Doe">
                    <small class="error-message" id="fullNameError"></small>
                </div>

                <div class="form-group">
                    <label for="email">Student Email</label>
                    <input type="email" id="email" name="email" required placeholder="student@srmist.edu.in">
                    <small style="color: #666; font-size: 0.8rem; margin-top: 0.25rem;">Must be a valid SRMIST email (@srmist.edu.in)</small>
                    <small class="error-message" id="emailError"></small>
                </div>

                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" required placeholder="Minimum 6 characters">
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
                    <input type="tel" id="phone" name="phone"
                           placeholder="9876543210"
                           pattern="[0-9]{10}"
                           maxlength="10"
                           inputmode="numeric"
                           title="Enter exactly 10 digits — numbers only"
                           oninput="this.value=this.value.replace(/[^0-9]/g,''); validateRegPhone(this);"
                           onkeypress="return event.charCode >= 48 && event.charCode <= 57;">
                    <small id="reg-phone-hint" style="color:#d93025; font-size:0.85rem; display:none;">Phone must be exactly 10 digits (numbers only).</small>
                </div>



                <button type="submit" class="btn-primary">Create Account</button>
            </form>

            <p class="text-center text-muted" style="margin-top: 2rem; font-size: 0.9rem;">
                Already have an account? <a href="login.php" class="text-primary" style="font-weight: 600;">Log in</a>
            </p>
        </div>
    </div>

    <script src="js/main.js"></script>
    <script>
        function validateRegPhone(input) {
            const hint = document.getElementById('reg-phone-hint');
            if (input.value.length > 0 && !/^[0-9]{10}$/.test(input.value)) {
                hint.style.display = 'block';
                input.style.borderColor = '#d93025';
            } else {
                hint.style.display = 'none';
                input.style.borderColor = '';
            }
        }

        document.addEventListener('DOMContentLoaded', () => {
            const form = document.getElementById('registerForm');
            const errorMessage = document.getElementById('errorMessage');
            const successMessage = document.getElementById('successMessage');

            if (!form) { return; }

            form.addEventListener('submit', async (e) => {
                e.preventDefault();

                // Clear previous messages
                errorMessage.classList.add('hidden');
                successMessage.classList.add('hidden');
                document.querySelectorAll('.error-message').forEach(el => el.textContent = '');

                // Validate inputs
                const fullName = document.getElementById('fullName').value.trim();
                const email = document.getElementById('email').value.trim();
                // Sustain-U: Trim passwords to avoid accidental whitespace issues (common on mobile)
                const password = document.getElementById('password').value.trim();
                const confirmPassword = document.getElementById('confirmPassword').value.trim();
                const department = document.getElementById('department').value.trim();


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
                    document.getElementById('passwordError').textContent = 'Password must be at least 6 characters';
                    hasErrors = true;
                }

                if (password !== confirmPassword) {
                    document.getElementById('confirmPasswordError').textContent = 'Passwords do not match';
                    hasErrors = true;
                }

                if (!department) {
                    document.getElementById('departmentError').textContent = 'Department is required';
                    hasErrors = true;
                }



                // Phone validation (optional but must be 10 digits if provided)
                const phoneVal = document.getElementById('phone').value.trim();
                if (phoneVal && !/^[0-9]{10}$/.test(phoneVal)) {
                    document.getElementById('reg-phone-hint').style.display = 'block';
                    document.getElementById('phone').style.borderColor = '#d93025';
                    hasErrors = true;
                }

                if (hasErrors) return;

                try {
                    const button = form.querySelector('button[type="submit"]');
                    button.disabled = true;
                    button.textContent = 'Creating Account...';

                    const response = await fetch('api/register_user.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({
                            full_name: fullName,
                            email: email,
                            password: password,
                            confirm_password: confirmPassword,
                            department: department,
                            phone: document.getElementById('phone').value.trim()
                        })
                    });

                    const data = await response.json();

                    if (data.success) {
                        form.reset();
                        successMessage.classList.remove('hidden');
                        setTimeout(() => { window.location.href = 'login.php'; }, 2000);
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

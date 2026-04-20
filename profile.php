<?php
/**
 * SUSTAIN-U - User Profile Page
 * Shows user information, points, and profile settings
 */
require_once 'config.php';
requireLogin();

if (isAdmin()) {
    header('Location: admin_dashboard.php');
    exit;
}

// redirect first-time users to complete profile
if (empty($_SESSION['profile_complete'])) {
    header('Location: complete_profile.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profile - Sustain-U</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body class="app">
    <?php include __DIR__ . '/inc/header.php'; ?>

    <main class="container" style="margin: 2rem auto; max-width: 600px;">
        <!-- Profile Header -->
        <div class="card" style="background: linear-gradient(135deg, var(--primary-color) 0%, #1d6b47 100%); color: white;">
            <div style="text-align: center; padding: 2rem;">
                <div style="width: 80px; height: 80px; margin: 0 auto 1rem; border-radius: 50%; background: rgba(255, 255, 255, 0.3); display: flex; align-items: center; justify-content: center; font-size: 2.5rem;">
                    ðŸ‘¤
                </div>
                <h2 id="userName" style="margin: 0 0 0.5rem; color: white;">Loading...</h2>
                <p id="userEmail" style="margin: 0; opacity: 0.9;">Loading...</p>
            </div>
        </div>

        <!-- Stats Cards -->
        <div class="grid grid-3" style="margin: 2rem 0;">
            <div class="card" style="text-align: center;">
                <div style="font-size: 1.5rem;">⭐</div>
                <div id="totalPoints" style="font-size: 2rem; color: var(--primary-color); font-weight: bold; margin: 0.5rem 0;">0</div>
                <small>Total Points</small>
            </div>

            <div class="card" style="text-align: center;">
                <div style="font-size: 1.5rem;">ðŸ“</div>
                <div id="totalIssues" style="font-size: 2rem; color: var(--primary-color); font-weight: bold; margin: 0.5rem 0;">0</div>
                <small>Issues Reported</small>
            </div>

            <div class="card" style="text-align: center;">
                <div style="font-size: 1.5rem;">✓</div>
                <div id="resolvedIssues" style="font-size: 2rem; color: var(--success-color); font-weight: bold; margin: 0.5rem 0;">0</div>
                <small>Resolved</small>
            </div>
        </div>

        <!-- Profile Information -->
        <div class="card">
            <div class="card-header">
                <h3>Profile Information</h3>
            </div>

            <div class="card-body">
                <div id="profileInfo" style="display: none;">
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin-bottom: 1.5rem;">
                         <div>
                            <label style="color: var(--primary-color); font-weight: 600;">Full Name</label>
                            <p id="displayName" style="margin: 0.5rem 0 0;">--</p>
                        </div>
                        <div>
                            <label style="color: var(--primary-color); font-weight: 600;">Registration Number</label>
                            <p id="displayRegNo" style="margin: 0.5rem 0 0;">--</p>
                        </div>
                    </div>

                    <div style="margin-bottom: 1.5rem;">
                        <label style="color: var(--primary-color); font-weight: 600;">Email</label>
                        <p id="displayEmail" style="margin: 0.5rem 0 0;">--</p>
                    </div>

                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin-bottom: 1.5rem;">
                         <div>
                            <label style="color: var(--primary-color); font-weight: 600;">Department</label>
                            <p id="displayDept" style="margin: 0.5rem 0 0;">--</p>
                        </div>
                        <div>
                            <label style="color: var(--primary-color); font-weight: 600;">Section</label>
                            <p id="displaySection" style="margin: 0.5rem 0 0;">--</p>
                        </div>
                    </div>

                    <div style="margin-bottom: 1.5rem;">
                        <label style="color: var(--primary-color); font-weight: 600;">Degree</label>
                        <p id="displayDegree" style="margin: 0.5rem 0 0;">--</p>
                    </div>

                    <div style="margin-bottom: 1.5rem;">
                        <label style="color: var(--primary-color); font-weight: 600;">Phone</label>
                        <p id="displayPhone" style="margin: 0.5rem 0 0;">--</p>
                    </div>

                    <div style="margin-bottom: 1.5rem;">
                        <label style="color: var(--primary-color); font-weight: 600;">Member Since</label>
                        <p id="displayJoined" style="margin: 0.5rem 0 0;">--</p>
                    </div>
                </div>

                <div id="loadingProfile" class="loading">
                    <div class="spinner"></div>
                    <p>Loading profile...</p>
                </div>
            </div>
        </div>

        <!-- Achievements Section -->
        <div class="card">
            <div class="card-header">
                <h3>Achievements</h3>
            </div>

            <div class="card-body">
                <div id="achievementsList" style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;"></div>
            </div>
        </div>


    </main>



    <script src="js/main.js"></script>
    <script>
        // Load profile on page load
        loadProfile();

        async function loadProfile() {
            try {
                const response = await fetch('api/get_user_profile.php', { credentials: 'same-origin' });
                const data = await response.json();

                if (!data.success) {
                    document.getElementById('loadingProfile').innerHTML = 
                        `<div class="alert alert-danger">Error: ${data.message || 'Failed to load profile'}</div>`;
                    return;
                }

                const user = data.data;

                // Update header
                document.getElementById('userName').textContent = sanitize(user.full_name);
                document.getElementById('userEmail').textContent = sanitize(user.email);

                // Update stats
                document.getElementById('totalPoints').textContent = user.points || 0;
                document.getElementById('totalIssues').textContent = user.total_issues || 0;
                document.getElementById('resolvedIssues').textContent = user.resolved_issues || 0;

                // Update profile information
                document.getElementById('displayName').textContent = sanitize(user.full_name);
                document.getElementById('displayEmail').textContent = sanitize(user.email);
                document.getElementById('displayDept').textContent = sanitize(user.department || 'N/A');
                document.getElementById('displaySection').textContent = sanitize(user.section || 'N/A');
                document.getElementById('displayPhone').textContent = sanitize(user.phone || 'Not provided');
                document.getElementById('displayRegNo').textContent = sanitize(user.register_number || 'N/A');
                document.getElementById('displayDegree').textContent = sanitize(user.degree || 'N/A');
                document.getElementById('displayJoined').textContent = new Date(user.created_at).toLocaleDateString();

                // Hide loading and show profile
                document.getElementById('loadingProfile').style.display = 'none';
                document.getElementById('profileInfo').style.display = 'block';

                // Load achievements
                loadAchievements(user);
            } catch (error) {
                console.error('Error:', error);
                document.getElementById('loadingProfile').innerHTML = 
                    `<div class="alert alert-danger">Error loading profile. Please try again.</div>`;
            }
        }

        function loadAchievements(user) {
            const achievements = [];

            if (user.total_issues >= 1) achievements.push({ icon: 'ðŸ“', name: 'Reporter', desc: '1 issue' });
            if (user.total_issues >= 5) achievements.push({ icon: 'ðŸŒŸ', name: 'Active Contributor', desc: '5 issues' });
            if (user.resolved_issues >= 1) achievements.push({ icon: '✓', name: 'Problem Solver', desc: '1 resolved' });
            if (user.points >= 50) achievements.push({ icon: 'ðŸ†', name: 'Point Collector', desc: '50 points' });
            if (user.points >= 100) achievements.push({ icon: 'ðŸ‘‘', name: 'Sustain-U Champion', desc: '100 points' });

            const list = document.getElementById('achievementsList');
            if (achievements.length === 0) {
                list.innerHTML = '<p style="grid-column: 1/-1;">Keep reporting issues to unlock achievements!</p>';
            } else {
                list.innerHTML = achievements.map(a => `
                    <div style="text-align: center; padding: 1rem; background: var(--light-color); border-radius: var(--radius);">
                        <div style="font-size: 2rem; margin-bottom: 0.5rem;">${a.icon}</div>
                        <strong>${a.name}</strong>
                        <small style="display: block; color: #666; margin-top: 0.25rem;">${a.desc}</small>
                    </div>
                `).join('');
            }
        }

        function togglePasswordForm() {
            const form = document.getElementById('passwordForm');
            form.style.display = form.style.display === 'none' ? 'block' : 'none';
        }

        // Password change handler (only if form exists on page)
        const passwordForm = document.getElementById('passwordForm');
        if (passwordForm) {
            passwordForm.addEventListener('submit', async (e) => {
                e.preventDefault();

                const currentPassword = document.getElementById('currentPassword').value;
                const newPassword = document.getElementById('newPassword').value;
                const confirmPassword = document.getElementById('confirmNewPassword').value;
                const messageDiv = document.getElementById('passwordMessage');

                if (newPassword !== confirmPassword) {
                    messageDiv.textContent = 'New passwords do not match';
                    messageDiv.classList.remove('hidden');
                    return;
                }

                if (newPassword.length < 6) {
                    messageDiv.textContent = 'New password must be at least 6 characters';
                    messageDiv.classList.remove('hidden');
                    return;
                }

                try {
                    const response = await fetch('api/update_profile.php', {
                        method: 'POST',
                        credentials: 'same-origin',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({
                            current_password: currentPassword,
                            new_password: newPassword
                        })
                    });

                    const data = await response.json();

                    if (data.success) {
                        messageDiv.className = 'alert alert-success';
                        messageDiv.textContent = 'Password updated successfully';
                        passwordForm.reset();
                        setTimeout(() => togglePasswordForm(), 2000);
                    } else {
                        messageDiv.className = 'alert alert-danger';
                        messageDiv.textContent = data.message || 'Failed to update password';
                    }
                    messageDiv.classList.remove('hidden');
                } catch (error) {
                    console.error('Error:', error);
                    messageDiv.className = 'alert alert-danger';
                    messageDiv.textContent = 'Error updating password';
                    messageDiv.classList.remove('hidden');
                }
            });
        }

        function sanitize(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }
    </script>
    <?php include __DIR__ . '/inc/footer.php'; ?>
</body>
</html>

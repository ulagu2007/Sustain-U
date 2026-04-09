<?php
/**
 * SUSTAIN-U - Landing Page (Cleaned)
 * Matches exact UI design from screenshots
 */
require_once 'config.php';
require_once 'api/db.php';

// --- Automatic Redirection for Logged-in Users ---
if (isLoggedIn()) {
    if (isAdmin()) {
        header('Location: admin_dashboard.php');
        exit;
    } else {
        // Redir student to profile if not complete
        if (!check_profile_completion($conn, $_SESSION['user_id'])) {
            header('Location: complete_profile.php');
            exit;
        }
    }
    // Otherwise, students stay on index.php (which is their dashboard)
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sustain-U - Report Campus Issues & Earn Points</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body class="app">
    <?php include __DIR__ . '/inc/header.php'; ?>

    <!-- Hero Section -->
    <section class="hero">
        <div class="container">
            <div style="background-color: white; padding: 2rem; border-radius: 15px; display: inline-block; text-align: center;">
                <?php if (isLoggedIn() && isset($_SESSION['user_name'])): ?>
                    <h1 style="color: #1a73e8; margin-bottom: 0.5rem;">Welcome ! <?php echo htmlspecialchars($_SESSION['user_name']); ?></h1>
                <?php
else: ?>
                    <h1 style="margin-bottom: 0.5rem; display: flex; align-items: center; flex-wrap: wrap; justify-content: center; gap: 10px;">
                        <span style="font-weight: 720; color: #1a73e8; letter-spacing: -0.5px;">Make Campus</span>
                        <img src="assets/sustainable-logo.png" alt="Sustainable" style="height: 62px; width: auto;">
                    </h1>
                <?php
endif; ?>
                <p style="color: #1a73e8; font-size: 1.1rem; font-weight: 500; margin: 0;">Report environmental issues around the campus , collaborate with our community, and make it sustainable!</p>
            </div>
            <br>
            <?php if (!isLoggedIn()): ?>
                <a href="login.php" class="btn btn-primary btn-lg">Login</a>
            <?php elseif (isAdmin()): ?>
                <a href="admin_dashboard.php" class="btn btn-primary btn-lg">Admin Dashboard</a>
            <?php else: ?>
                <a href="report_issue.php" class="btn btn-secondary btn-lg">Report an Issue</a>
            <?php endif; ?>
        </div>
    </section>

    <!-- Main Content -->
    <main class="container" style="padding: 3rem 2rem;">
        
        <!-- Report a New Issue Card -->
        <div class="card" style="margin-bottom: 2rem; padding: 2.5rem; text-align: center; border-radius: 15px;">
            <div style="font-size: 3rem; margin-bottom: 1rem;">📸</div>
            <h3 style="font-size: 1.5rem; margin-bottom: 0.5rem;">Report a New Issue</h3>
            <p style="color: #666; margin-bottom: 1.5rem;">Upload an image and select the category</p>
            <?php if (isAdmin()): ?>
                <a href="admin_dashboard.php" class="btn btn-primary">Go to Admin Panel</a>
            <?php elseif (isLoggedIn()): ?>
                <a href="report_issue.php" class="btn btn-primary">+ Start Reporting</a>
            <?php else: ?>
                <a href="register.php" class="btn btn-primary">+ Start Reporting</a>
            <?php endif; ?>
        </div>

        <!-- Track Your Issues Card -->
        <div class="card" style="margin-bottom: 3rem; padding: 2.5rem; text-align: center; border-radius: 15px;">
            <div style="font-size: 3rem; margin-bottom: 1rem;">⏱️</div>
            <h3 style="font-size: 1.5rem; margin-bottom: 0.5rem;">Track Your Issues</h3>
            <p style="color: #666; margin-bottom: 1.5rem;">View status and resolution progress</p>
            <?php if (isLoggedIn()): ?>
                <a href="my_works.php" class="btn btn-primary">View My Issues</a>
            <?php
elseif (!isLoggedIn()): ?>
                <a href="login.php" class="btn btn-primary">View My Issues</a>
            <?php
endif; ?>
        </div>

        <!-- How It Works Section -->
        <section class="card" style="margin-top: 4rem; padding: 2rem;">
            <div style="text-align:center; max-width:900px; margin: 0 auto 1.5rem;">
                <h2 style="margin-bottom: 0.5rem;">How It Works</h2>
                <p style="color: #666; margin-bottom: 0; font-size: 0.98rem;">Simple 5-step process to report campus issues</p>
            </div>

            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(160px, 1fr)); gap: 2rem; margin-top: 1rem; align-items: start; color: var(--text-color);">
                <div style="text-align: center;">
                    <div style="width: 70px; height: 70px; background: var(--primary-color); border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 1rem; font-size: 1.5rem; color: white; font-weight: bold;">1</div>
                    <h4 style="margin-bottom: 0.5rem;">Upload Image</h4>
                    <p style="font-size: 0.95rem; color: #666; margin: 0;">Take or upload a photo</p>
                </div>

                <div style="text-align: center;">
                    <div style="width: 70px; height: 70px; background: var(--primary-color); border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 1rem; font-size: 1.5rem; color: white; font-weight: bold;">2</div>
                    <h4 style="margin-bottom: 0.5rem;">Select Category</h4>
                    <p style="font-size: 0.95rem; color: #666; margin: 0;">Air, Water, or Waste</p>
                </div>

                <div style="text-align: center;">
                    <div style="width: 70px; height: 70px; background: var(--primary-color); border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 1rem; font-size: 1.5rem; color: white; font-weight: bold;">3</div>
                    <h4 style="margin-bottom: 0.5rem;">Add Location</h4>
                    <p style="font-size: 0.95rem; color: #666; margin: 0;">Building &amp; floor details</p>
                </div>

                <div style="text-align: center;">
                    <div style="width: 70px; height: 70px; background: var(--primary-color); border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 1rem; font-size: 1.5rem; color: white; font-weight: bold;">4</div>
                    <h4 style="margin-bottom: 0.5rem;">Set Urgency</h4>
                    <p style="font-size: 0.95rem; color: #666; margin: 0;">Can Wait to Emergency</p>
                </div>

                <div style="text-align: center;">
                    <div style="width: 70px; height: 70px; background: var(--primary-color); border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 1rem; font-size: 1.5rem; color: white; font-weight: bold;">5</div>
                    <h4 style="margin-bottom: 0.5rem;">Submit</h4>
                    <p style="font-size: 0.95rem; color: #666; margin: 0;">Issue Submitted!</p>
                </div>
            </div>
        </section>

    </main>



    <?php include __DIR__ . '/inc/footer.php'; ?>
</body>
</html>
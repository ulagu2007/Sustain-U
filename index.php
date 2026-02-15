<?php
/**
 * SUSTAIN-U - Landing Page (Cleaned)
 * Matches exact UI design from screenshots
 */
require_once 'config.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sustain-U - Report Campus Issues & Earn Points</title>
    <link rel="stylesheet" href="/Sustain-U/css/style.css">
</head>
<body class="app">
    <?php include __DIR__ . '/inc/header.php'; ?>

    <!-- Hero Section -->
    <section class="hero">
        <div class="container">
            <h1>Make Campus Sustainable</h1>
            <p>Report environmental issues, collaborate with your community, and earn rewards!</p>
            <br>
            <?php if (!isLoggedIn()): ?>
                <a href="/Sustain-U/register.php" class="btn btn-secondary btn-lg">Get Started</a>
                <a href="/Sustain-U/login.php" class="btn btn-primary btn-lg" style="margin-left: 1rem;">Login</a>
            <?php else: ?>
                <a href="/Sustain-U/report_issue.php" class="btn btn-secondary btn-lg">Report an Issue</a>
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
            <?php if (isLoggedIn() && !isAdmin()): ?>
                <a href="/Sustain-U/report_issue.php" class="btn btn-primary">+ Start Reporting</a>
            <?php elseif (!isLoggedIn()): ?>
                <a href="/Sustain-U/register.php" class="btn btn-primary">+ Start Reporting</a>
            <?php endif; ?>
        </div>

        <!-- Track Your Issues Card -->
        <div class="card" style="margin-bottom: 3rem; padding: 2.5rem; text-align: center; border-radius: 15px;">
            <div style="font-size: 3rem; margin-bottom: 1rem;">⏱️</div>
            <h3 style="font-size: 1.5rem; margin-bottom: 0.5rem;">Track Your Issues</h3>
            <p style="color: #666; margin-bottom: 1.5rem;">View status and resolution progress</p>
            <?php if (isLoggedIn() && !isAdmin()): ?>
                <a href="/Sustain-U/my_works.php" class="btn btn-primary">View My Issues</a>
            <?php elseif (!isLoggedIn()): ?>
                <a href="/Sustain-U/login.php" class="btn btn-primary">View My Issues</a>
            <?php endif; ?>
        </div>

        <!-- How It Works Section -->
        <section style="margin-top: 4rem;">
            <h2 style="text-align: center; margin-bottom: 1rem;">How It Works</h2>
            <p style="text-align: center; color: #666; margin-bottom: 3rem; font-size: 0.95rem;">Simple 5-step process to report campus issues</p>
            
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(140px, 1fr)); gap: 2rem; margin-top: 2rem;">
                <div style="text-align: center;">
                    <div style="width: 70px; height: 70px; background: var(--primary-color); border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 1rem; font-size: 1.5rem; color: white; font-weight: bold;">1</div>
                    <h4 style="margin-bottom: 0.5rem;">Upload Image</h4>
                    <p style="font-size: 0.85rem; color: #666; margin: 0;">Take or upload a photo</p>
                </div>

                <div style="text-align: center;">
                    <div style="width: 70px; height: 70px; background: var(--primary-color); border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 1rem; font-size: 1.5rem; color: white; font-weight: bold;">2</div>
                    <h4 style="margin-bottom: 0.5rem;">Select Category</h4>
                    <p style="font-size: 0.85rem; color: #666; margin: 0;">Air, Water, or Waste</p>
                </div>

                <div style="text-align: center;">
                    <div style="width: 70px; height: 70px; background: var(--primary-color); border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 1rem; font-size: 1.5rem; color: white; font-weight: bold;">3</div>
                    <h4 style="margin-bottom: 0.5rem;">Add Location</h4>
                    <p style="font-size: 0.85rem; color: #666; margin: 0;">Building & floor details</p>
                </div>

                <div style="text-align: center;">
                    <div style="width: 70px; height: 70px; background: var(--primary-color); border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 1rem; font-size: 1.5rem; color: white; font-weight: bold;">4</div>
                    <h4 style="margin-bottom: 0.5rem;">Set Urgency</h4>
                    <p style="font-size: 0.85rem; color: #666; margin: 0;">Can Wait to Emergency</p>
                </div>

                <div style="text-align: center;">
                    <div style="width: 70px; height: 70px; background: var(--primary-color); border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 1rem; font-size: 1.5rem; color: white; font-weight: bold;">5</div>
                    <h4 style="margin-bottom: 0.5rem;">Submit</h4>
                    <p style="font-size: 0.85rem; color: #666; margin: 0;">Earn points!</p>
                </div>
            </div>
        </section>

    </main>

</body>
</html>
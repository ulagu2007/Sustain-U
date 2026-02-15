<?php
/**
 * SUSTAIN-U - Landing Page
 * Features showcase and entry point for users
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

    <!-- Features Section -->
    <main class="container" style="padding: 3rem 0;">
        <h2 style="text-align: center; margin-bottom: 3rem;">Why Choose Sustain-U?</h2>
        
        <div class="grid grid-3">
            <div class="card feature">
                <div class="feature-icon">�</div>
                <h3>Photo Verified</h3>
                <p>Attach clear photos—evidence ensures reports are accurate and actionable.</p>
            </div>

            <div class="card feature">
                <div class="feature-icon">🏆</div>
                <h3>Earn Points</h3>
                <p>Get rewarded with points for every issue you report. Collect and redeem amazing prizes!</p>
            </div>

            <div class="card feature">
                <div class="feature-icon">🤝</div>
                <h3>Community Driven</h3>
                <p>Work together with students and administrators to solve campus environmental challenges.</p>
            </div>

            <div class="card feature">
                <div class="feature-icon">💚</div>
                <h3>Environmental Impact</h3>
                <p>Track your contribution to a greener, more sustainable campus environment.</p>
            </div>

            <div class="card feature">
                <div class="feature-icon">📊</div>
                <h3>Real-time Updates</h3>
                <p>Get instant notifications on the status of issues you've reported and their resolutions.</p>
            </div>

            <div class="card feature">
                <div class="feature-icon">📱</div>
                <h3>Mobile Friendly</h3>
                <p>Access Sustain-U from any device. Designed for seamless mobile experience.</p>
            </div>
        </div>




    </main>

    <!-- Footer -->
    <footer>
        <div class="container">
            <p>&copy; 2026 Sustain-U. Making Campus Greener Together.</p>
            <p style="margin-top: 1rem; font-size: 0.9rem;">
                <a href="#about">About</a> • 
                <a href="#contact">Contact</a> • 
                <a href="#privacy">Privacy Policy</a>
            </p>
        </div>
    </footer>
                    <h3>Track Progress</h3>
                    <p>Monitor the status of your reports from submission to resolution</p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon">👥</div>
                    <h3>Community Impact</h3>
                    <p>Help make SRM University a greener, cleaner campus</p>
                </div>
            </div>
        </section>

        <footer class="footer">
            <p>&copy; 2026 Sustain-U. Making a difference together.</p>
        </footer>
    </div>

    <script src="js/main.js"></script>
</body>
</html> 

<?php
// Shared minimal header for Sustain-U
?>
<meta charset="UTF-8">
<header class="site-header">
  <div class="container header-content-min">
    <a href="index.php" class="logo-link" style="display: flex; align-items: center; gap: 15px;">
      <img src="assets/bg-srmlogo.jpg.png" alt="SRM Logo" class="srm-logo" style="height: 45px; width: auto;">
      <img src="assets/loogo.jpg" alt="Sustain-U" class="site-logo">
    </a>
    <div class="header-center-logo">
      <img src="assets/logo.jpeg" alt="Sustain-U" class="center-banner">
    </div>
    <?php if (isLoggedIn()): ?>
    <div class="hamburger-menu-container">
        <button class="hamburger-btn" onclick="toggleMenu()">
            <span></span>
            <span></span>
            <span></span>
        </button>
        <nav class="nav-menu" id="navMenu">
            <?php if (isAdmin()): ?>
                <a href="admin_dashboard.php">Dashboard</a>
                <a href="auditing.php">Auditing</a>
                <a href="profile.php">Profile</a>
                <a href="logout.php" style="color: red;">Logout</a>
            <?php else: ?>
                <a href="index.php">Dashboard</a>
                <a href="my_works.php">My Issues</a>
                <a href="report_issue.php">Report Issue</a>
                <a href="profile.php">Profile</a>
                <a href="logout.php" style="color: red;">Logout</a>
            <?php endif; ?>
        </nav>
    </div>
    <script>
    function toggleMenu() {
        const btn = document.querySelector('.hamburger-btn');
        const nav = document.getElementById('navMenu');
        if (btn && nav) {
            btn.classList.toggle('active');
            nav.classList.toggle('active');
        }
    }
    document.addEventListener('click', (e) => {
        const nav = document.getElementById('navMenu');
        const btn = document.querySelector('.hamburger-btn');
        if (btn && nav && nav.classList.contains('active')) {
            if (!nav.contains(e.target) && !btn.contains(e.target)) {
                nav.classList.remove('active');
                btn.classList.remove('active');
            }
        }
    });
    </script>
    <?php endif; ?>
  </div>
</header>
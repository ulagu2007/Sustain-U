<?php
// Shared minimal header for Campus Care / Sustain-U
?>
<header class="site-header">
  <div class="container header-content-min">
    <a href="/Sustain-U/index.php" class="logo-link">
      <img src="/Sustain-U/assets/logo.jpeg" alt="Sustain-U" class="site-logo">
    </a>
    <nav class="minimal-nav" aria-label="Main navigation">
      <?php if (isLoggedIn()): ?>
        <?php if (isAdmin()): ?>
          <a href="/Sustain-U/admin_dashboard.php">Dashboard</a>
        <?php else: ?>
          <a href="/Sustain-U/my_works.php">My Issues</a>
          <a href="/Sustain-U/report_issue.php">Report</a>
        <?php endif; ?>
        <a href="/Sustain-U/logout.php">Logout</a>
      <?php else: ?>
        <a href="/Sustain-U/login.php">Student</a>
        <a href="/Sustain-U/admin_login.php">Admin</a>
      <?php endif; ?>
    </nav>
  </div>
</header>

<?php if (basename($_SERVER['PHP_SELF']) === 'index.php' || (function_exists('isAdmin') && isAdmin())): ?>
<footer class="site-footer">
    <div class="footer-content">
            <p style="color: white; font-weight: bold; font-size: 1.1rem; text-transform: uppercase;">Created for SRMIST by</p>
        <div class="footer-details">
            <div class="student-info left">
                <p><strong>Ulaganathan P</strong></p>
                <p>RA2411003010265</p>
            </div>
            <div class="student-info right">
                <p><strong>Vishaal Thennarasu</strong></p>
                <p>RA2411003010284</p>
            </div>
        </div>
        <div style="margin-top: 1.5rem; color: rgba(255,255,255,0.7); font-size: 0.85rem;">
            &copy; <?php echo date('Y'); ?> Sustain-U SRMIST. All Rights Reserved.
        </div>
    </div>
</footer>
<?php endif; ?>

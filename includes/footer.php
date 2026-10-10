<?php
// ============================================================
// University Alumni Network - Global Footer Component
// ============================================================
$baseUrl = isset($baseUrl) ? $baseUrl : get_base_url();
?>
    <!-- Cinematic Deep Navy Footer -->
    <footer class="footer">
        <div class="container">
            <div class="footer-top">
                <div>
                    <div class="footer-brand-title">
                        <span class="logo-badge">AN</span>
                        <span>ALUMNI <span class="logo-gold">NETWORK</span></span>
                    </div>
                    <p class="footer-tagline">
                        Connecting graduates. Creating opportunities. Building the future of our prestigious university community.
                    </p>
                    <div style="font-size: 0.85rem; color: var(--text-secondary);">
                        University Alumni Association &bull; Open to All Graduates &amp; Faculties
                    </div>
                </div>

                <div>
                    <div class="footer-col-title">Navigation</div>
                    <ul class="footer-links">
                        <li><a href="<?php echo $baseUrl; ?>index.php">Home</a></li>
                        <li><a href="<?php echo $baseUrl; ?>directory.php">Alumni Directory</a></li>
                        <li><a href="<?php echo $baseUrl; ?>events.php">Upcoming Events</a></li>
                        <li><a href="<?php echo $baseUrl; ?>jobs.php">Career Opportunities</a></li>
                        <li><a href="<?php echo $baseUrl; ?>about.php">About Us</a></li>
                    </ul>
                </div>

                <div>
                    <div class="footer-col-title">Alumni Portal</div>
                    <ul class="footer-links">
                        <li><a href="<?php echo $baseUrl; ?>auth/login.php">Member Login</a></li>
                        <li><a href="<?php echo $baseUrl; ?>auth/register.php">Join the Network</a></li>
                        <li><a href="<?php echo $baseUrl; ?>events.php">Submit an Event</a></li>
                        <li><a href="<?php echo $baseUrl; ?>jobs.php">Post a Vacancy</a></li>
                        <li><a href="<?php echo $baseUrl; ?>config/setup.php">Database Setup</a></li>
                    </ul>
                </div>

                <div>
                    <div class="footer-col-title">Connect</div>
                    <ul class="footer-links">
                        <li><a href="mailto:alumni@university.edu">alumni@university.edu</a></li>
                        <li><a href="tel:+94112345678">+94 (11) 234-5678</a></li>
                        <li><span style="color: var(--text-secondary); font-size: 0.9rem;">University Campus Quad, Colombo</span></li>
                    </ul>
                </div>
            </div>

            <div class="footer-bottom">
                <div>
                    &copy; 2026 <span class="footer-gold-link">Alumni Network</span>. All rights reserved.
                </div>
                <div>
                    Crafted with cinematic university pride &bull; Pure HTML, CSS, JS &amp; PHP
                </div>
            </div>
        </div>
    </footer>

    <!-- Vanilla JavaScript -->
    <script src="<?php echo $baseUrl; ?>assets/js/main.js?v=4.2"></script>
</body>
</html>

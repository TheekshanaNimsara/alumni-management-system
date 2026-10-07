<?php
// ============================================================
// University Alumni Network - About Us
// ============================================================
$pageTitle = 'About Us';
$currentPage = 'about';
require_once __DIR__ . '/includes/header.php';
?>

<!-- Cinematic Header -->
<section style="background: var(--primary-color); color: var(--text-light); padding: 4.5rem 0 3.5rem; border-bottom: 2px solid var(--accent-color);">
    <div class="container text-center" style="text-align: center;">
        <span class="section-eyebrow">Heritage &bull; Excellence &bull; Community</span>
        <h1 class="section-title" style="color: var(--text-light); margin-bottom: 0.8rem;">About Our Alumni Network</h1>
        <p style="color: rgba(248, 244, 234, 0.75); max-width: 650px; margin: 0 auto; font-size: 1.05rem;">
            Rooted in academic excellence and forward-looking leadership, our network links generations of university graduates across the world.
        </p>
    </div>
</section>

<!-- Mission & Vision Section (Dark Maroon Rhythm) -->
<section class="section section-dark reveal">
    <div class="container">
        <div class="grid grid-2" style="align-items: center;">
            <div>
                <span class="section-eyebrow">Our Guiding Philosophy</span>
                <h2 class="section-title" style="margin-bottom: 1.5rem;">Connecting Past, Present &amp; Future</h2>
                <div class="gold-divider-left"></div>
                <p style="margin-bottom: 1.2rem; font-size: 1.05rem;">
                    The University Alumni Network was founded to preserve the camaraderie, intellectual curiosity, and shared pride nurtured during undergraduate and postgraduate years.
                </p>
                <p style="color: rgba(248, 244, 234, 0.75); font-size: 0.95rem;">
                    Today, it stands as an international bridge uniting scholars, industry leaders, founders, and undergraduates to create high-impact opportunities across all academic disciplines.
                </p>
            </div>
            <div class="card card-dark" style="padding: 2.5rem; border-left: 4px solid var(--accent-color);">
                <h3 style="color: var(--accent-color); font-size: 1.4rem; margin-bottom: 0.8rem;">Our Pillars of Impact</h3>
                <ul style="list-style: none; color: rgba(248, 244, 234, 0.75);">
                    <li style="margin-bottom: 1rem;">
                        <strong style="color: var(--text-light);">&#9656; Professional Mentorship:</strong> Guiding undergraduates through career pathways and research directions.
                    </li>
                    <li style="margin-bottom: 1rem;">
                        <strong style="color: var(--text-light);">&#9656; Industry Partnerships:</strong> Facilitating direct recruitment into top national and multinational organizations.
                    </li>
                    <li style="margin-bottom: 1rem;">
                        <strong style="color: var(--text-light);">&#9656; Research Innovation:</strong> Sponsoring faculty lab incubators, symposiums, and startup initiatives.
                    </li>
                    <li>
                        <strong style="color: var(--text-light);">&#9656; Lifelong Fellowship:</strong> Celebrating batch milestones, reunions, and campus heritage.
                    </li>
                </ul>
            </div>
        </div>
    </div>
</section>

<!-- Leadership & Governance (Light Section) -->
<section class="section section-light reveal">
    <div class="container">
        <div class="section-header">
            <span class="section-eyebrow">Governance &amp; Leadership</span>
            <h2 class="section-title">Alumni Association Board</h2>
            <div class="gold-divider"></div>
            <p style="color: var(--text-muted); max-width: 600px; margin: 0 auto;">
                Dedicated alumni and faculty patrons guiding the association’s global programs and strategic vision.
            </p>
        </div>

        <div class="grid grid-3">
            <div class="card" style="padding: 2.2rem; text-align: center;">
                <div class="alumni-avatar-wrapper">
                    <img src="assets/images/default-avatar.svg" alt="Prof. K. Perera" class="alumni-avatar">
                </div>
                <h3 style="color: var(--primary-color); font-size: 1.25rem;">Prof. K. Perera</h3>
                <p style="font-size: 0.88rem; color: var(--text-muted);">Dean &bull; University Academic Council</p>
            </div>

            <div class="card" style="padding: 2.2rem; text-align: center;">
                <div class="alumni-avatar-wrapper">
                    <img src="assets/images/default-avatar.svg" alt="Eng. Samantha Silva" class="alumni-avatar">
                </div>
                <h3 style="color: var(--primary-color); font-size: 1.25rem;">Eng. Samantha Silva</h3>
                <div style="color: var(--accent-color); font-weight: 700; font-size: 0.88rem; margin-bottom: 0.5rem;">President, Alumni Association</div>
                <p style="font-size: 0.88rem; color: var(--text-muted);">Class of 2012 &bull; Chief Technology Officer, NexaCloud</p>
            </div>

            <div class="card" style="padding: 2.2rem; text-align: center;">
                <div class="alumni-avatar-wrapper">
                    <img src="assets/images/default-avatar.svg" alt="Dr. Aruni Fernando" class="alumni-avatar">
                </div>
                <h3 style="color: var(--primary-color); font-size: 1.25rem;">Dr. Aruni Fernando</h3>
                <div style="color: var(--accent-color); font-weight: 700; font-size: 0.88rem; margin-bottom: 0.5rem;">Secretary &amp; Global Relations</div>
                <p style="font-size: 0.88rem; color: var(--text-muted);">Class of 2015 &bull; Senior Research Fellow, University AI Lab</p>
            </div>
        </div>
    </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>

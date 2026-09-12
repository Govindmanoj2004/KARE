<?php

/**
 * index.php (project root)
 * -----------------------------------------------------------------------
 * Guest/landing page (README §6 "Not built" -> Guest/landing page; to-do
 * #15). The entry point for a first-time visitor who doesn't already
 * know to go straight to auth/login or auth/signup. Introduces the
 * product and links into both existing auth flows -- no new backend,
 * this is a pure static-content page.
 *
 * If someone is already logged in, send them straight to their
 * dashboard instead of showing them a pitch for a product they're
 * already using.
 * -----------------------------------------------------------------------
 */
session_start();

if (isset($_SESSION['user_id'], $_SESSION['role'])) {
    $home = match ($_SESSION['role']) {
        'doctor' => 'pages/doctor/home.php',
        'admin'  => 'pages/admin/home.php',
        default  => 'pages/user/home.php',
    };
    header('Location: ' . $home);
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Kare — medication reminders for patients, and a direct line to their doctor.">
    <title>Kare — Never miss a dose</title>

    <link rel="stylesheet" href="shared/tokens.css">
    <link rel="stylesheet" href="guest.css">

    <link rel="stylesheet" href="https://unpkg.com/@phosphor-icons/web@2.1.1/src/regular/style.css">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
</head>

<body class="mr-guest">

    <header class="mr-guest-topbar">
        <div class="mr-guest-topbar-inner">
            <a href="index.php" class="mr-guest-brand">
                <svg viewBox="0 0 2700 567" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                    <g clip-path="url(#guest-heart-clip)">
                        <text x="313" y="450" text-anchor="middle" font-weight="600" font-size="480" fill="#226699">&#10084;</text>
                        <text x="283" y="420" text-anchor="middle" font-weight="600" font-size="450" fill="#55ACEE">&#10084;</text>
                    </g>
                    <defs>
                        <clipPath id="guest-heart-clip">
                            <rect width="566" height="566" fill="white" />
                        </clipPath>
                    </defs>
                    <text x="650" y="385" font-weight="600" font-size="300" fill="#143045">we.kare.com</text>
                </svg>
            </a>
            <a href="auth/login/index.php" class="mr-guest-topbar-login">Log in</a>
        </div>
    </header>

    <main>
        <section class="mr-guest-hero">
            <div class="mr-guest-hero-copy" data-mr-fade>
                <span class="mr-guest-eyebrow">Medication reminders, made simple</span>
                <h1>Never miss a dose. Stay connected with your doctor.</h1>
                <p>
                    Kare helps patients keep track of their medicines and doses, and gives
                    doctors a direct line to the people they're treating — connection
                    requests, secure messaging, and prescription updates, all in one place.
                </p>
                <div class="mr-guest-cta-row">
                    <a href="auth/signup/index.php?role=patient" class="mr-guest-btn mr-guest-btn-primary">
                        <i class="ph ph-user"></i> Sign up as a Patient
                    </a>
                    <a href="auth/signup/index.php?role=doctor" class="mr-guest-btn mr-guest-btn-secondary">
                        <i class="ph ph-stethoscope"></i> Sign up as a Doctor
                    </a>
                </div>
                <p class="mr-guest-login-hint">
                    Already have an account? <a href="auth/login/index.php">Log in</a>
                </p>
            </div>

            <div class="mr-guest-hero-art" aria-hidden="true">
                <div class="mr-guest-hero-card">
                    <div class="mr-guest-hero-card-row">
                        <span class="mr-guest-hero-dot is-taken"></span>
                        <span>Metformin 500mg &middot; 8:00 AM</span>
                        <span class="mr-guest-hero-tag is-taken">Taken</span>
                    </div>
                    <div class="mr-guest-hero-card-row">
                        <span class="mr-guest-hero-dot is-upcoming"></span>
                        <span>Atorvastatin 10mg &middot; 9:00 PM</span>
                        <span class="mr-guest-hero-tag is-upcoming">Upcoming</span>
                    </div>
                    <div class="mr-guest-hero-card-row">
                        <span class="mr-guest-hero-dot is-missed"></span>
                        <span>Vitamin D &middot; 1:00 PM</span>
                        <span class="mr-guest-hero-tag is-missed">Missed</span>
                    </div>
                </div>
                <div class="ocean">
                    <div class="wave"></div>
                    <div class="wave"></div>
                </div>
            </div>
        </section>

        <section class="mr-guest-features">
            <h2 data-mr-fade>Everything a patient and their doctor need</h2>
            <div class="mr-guest-feature-grid">
                <div class="mr-guest-feature-card" data-mr-fade>
                    <span class="mr-guest-feature-icon"><i class="ph ph-bell-ringing"></i></span>
                    <h3>Medicine reminders</h3>
                    <p>Add medicines and reminder times; mark doses taken, snooze, or track what's missed.</p>
                </div>
                <div class="mr-guest-feature-card" data-mr-fade>
                    <span class="mr-guest-feature-icon"><i class="ph ph-heartbeat"></i></span>
                    <h3>Connect with a doctor</h3>
                    <p>Send a connection request to a verified doctor and message them directly once accepted.</p>
                </div>
                <div class="mr-guest-feature-card" data-mr-fade>
                    <span class="mr-guest-feature-icon"><i class="ph ph-file-text"></i></span>
                    <h3>Prescriptions, kept current</h3>
                    <p>Upload and store prescriptions, and request an update from your doctor when you need one.</p>
                </div>
                <div class="mr-guest-feature-card" data-mr-fade>
                    <span class="mr-guest-feature-icon"><i class="ph ph-chart-line-up"></i></span>
                    <h3>See your progress</h3>
                    <p>A 30-day adherence rate, per-medicine breakdown, and a calendar of your dose history.</p>
                </div>
            </div>
        </section>

        <section class="mr-guest-final-cta">
            <div class="mr-guest-final-cta-card" data-mr-fade>
                <h2>Ready to get started?</h2>
                <p>It takes less than a minute to create an account.</p>
                <div class="mr-guest-cta-row">
                    <a href="auth/signup/index.php?role=patient" class="mr-guest-btn mr-guest-btn-primary">Sign up as a Patient</a>
                    <a href="auth/signup/index.php?role=doctor" class="mr-guest-btn mr-guest-btn-secondary">Sign up as a Doctor</a>
                </div>
            </div>
        </section>
    </main>

    <footer class="mr-guest-footer">
        <span>&copy; <?= date('Y') ?> Kare</span>
        <a href="auth/login/index.php">Log in</a>
    </footer>

    <script src="guest.js"></script>
</body>

</html>

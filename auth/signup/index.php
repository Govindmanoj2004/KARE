<?php
session_start();

// Pull old input set by controller.php on failed validation, then clear it
$old = $_SESSION['signup_old'] ?? [];
unset($_SESSION['signup_old']);

function old(string $key, array $old): string
{
  return htmlspecialchars($old[$key] ?? '', ENT_QUOTES, 'UTF-8');
}

// Landing-page CTAs deep-link here as signup/index.php?role=doctor so the
// role toggle opens on the right tab. A failed-submit's old() value still
// wins over this (see the value="" below).
$mrGetRole = $_GET['role'] ?? '';
$mrDefaultRole = in_array($mrGetRole, ['patient', 'doctor'], true) ? $mrGetRole : 'patient';
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Sign Up</title>
  <link rel="preconnect" href="https://fonts.googleapis.com" />
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
  <link
    href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&display=swap"
    rel="stylesheet" />
  <link rel="stylesheet" href="style.css" />
  <link rel="stylesheet" href="../../shared/toast/toast.css" />
  <link rel="stylesheet" href="https://unpkg.com/@phosphor-icons/web@2.1.1/src/regular/style.css" />
</head>

<body>
  <section class="page signup-9">
    <div class="signup-9-card">
      <div class="signup-9-hero">
        <div class="ocean">
          <div class="wave"></div>
          <div class="wave"></div>
        </div>
      </div>
      <form class="signup-9-form" id="signup-9-form" action="controller.php" method="POST" novalidate>
        <svg class="signup-9-logo" viewBox="0 0 2700 567" xmlns="http://www.w3.org/2000/svg">
          <g clip-path="url(#signup9-heart-clip)">
            <text x="313" y="450" text-anchor="middle" font-weight="600" font-size="480" fill="#226699">&#10084;</text>
            <text x="283" y="420" text-anchor="middle" font-weight="600" font-size="450" fill="#55ACEE">&#10084;</text>
          </g>
          <defs>
            <clipPath id="signup9-heart-clip">
              <rect width="566" height="566" fill="white" />
            </clipPath>
          </defs>
          <text x="650" y="385" font-weight="600" font-size="300" fill="#143045">we.kare.com</text>
        </svg>
        <h3>Create your account</h3>

        <div class="signup-9-role-toggle" role="tablist" aria-label="Account type">
          <button type="button" class="signup-9-role-btn<?= $mrDefaultRole === 'patient' && old('role', $old) === '' ? ' is-active' : '' ?>" data-mr-role-btn="patient" role="tab" aria-selected="<?= $mrDefaultRole === 'patient' ? 'true' : 'false' ?>">
            Patient
          </button>
          <button type="button" class="signup-9-role-btn<?= $mrDefaultRole === 'doctor' && old('role', $old) === '' ? ' is-active' : '' ?>" data-mr-role-btn="doctor" role="tab" aria-selected="<?= $mrDefaultRole === 'doctor' ? 'true' : 'false' ?>">
            Doctor
          </button>
        </div>
        <input type="hidden" id="role" name="role" value="<?= old('role', $old) ?: $mrDefaultRole ?>" />

        <!-- <div class="signup-9-socials">
          <button type="button" class="signup-9-social-btn" id="google-btn">
            <img src="../../assets/svg/google.svg" alt="Google" />
            <p><span class="signup-9-extra-text">Sign up with</span> Google</p>
          </button>
          <button type="button" class="signup-9-social-btn" id="facebook-btn">
            <img src="../../assets/svg/facebook.svg" alt="Facebook" />
            <p><span class="signup-9-extra-text">Sign up with</span> Facebook</p>
          </button>
        </div>
        <span class="signup-9-or"></span> -->

        <input type="text" placeholder="Full Name" id="name" name="name" value="<?= old('name', $old) ?>" required />
        <input type="email" placeholder="Email" id="email" name="email" value="<?= old('email', $old) ?>" required />
        <input type="tel" placeholder="Phone (e.g. +91XXXXXXXXXX)" id="phone" name="phone" value="<?= old('phone', $old) ?>" required />
        <input type="text" placeholder="Specialty (e.g. Cardiologist)" id="specialty" name="specialty" value="<?= old('specialty', $old) ?>" data-mr-specialty-field style="display: none;" />

        <input type="password" placeholder="Password" id="password" name="password" required minlength="8" />
        <input type="password" placeholder="Confirm Password" id="confirm_password" name="confirm_password" required minlength="8" />

        <div class="signup-9-terms">
          <input type="checkbox" id="terms" name="terms" <?= isset($old['terms']) ? 'checked' : '' ?> />
          <label for="terms">I accept the <a href="#">Terms and Conditions</a></label>
        </div>
        <button type="submit">Sign Up</button>
        <p class="signup-9-login-link">Already have an account? <a href="../login/index.php">Log in</a></p>
      </form>
    </div>
  </section>

  <?php include __DIR__ . '/../../shared/toast/toast.php'; ?>
  <script src="../../shared/toast/toast.js"></script>
  <script src="script.js"></script>
</body>

</html>
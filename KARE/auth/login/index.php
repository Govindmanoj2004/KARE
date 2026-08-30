<?php
session_start();

// Pull old input set by controller.php on failed validation, then clear it
$old = $_SESSION['login_old'] ?? [];
unset($_SESSION['login_old']);

function old(string $key, array $old): string
{
  return htmlspecialchars($old[$key] ?? '', ENT_QUOTES, 'UTF-8');
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Login</title>
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
  <section class="page login-9">
    <div class="login-9-card">
      <div class="login-9-hero">
        <div class="ocean">
          <div class="wave"></div>
          <div class="wave"></div>
        </div>
      </div>
      <form class="login-9-form" id="login-9-form" action="controller.php" method="POST" novalidate>
        <svg class="login-9-logo" viewBox="0 0 2700 567" xmlns="http://www.w3.org/2000/svg">
          <g clip-path="url(#login9-heart-clip)">
            <text x="313" y="450" text-anchor="middle" font-weight="600" font-size="480" fill="#226699">&#10084;</text>
            <text x="283" y="420" text-anchor="middle" font-weight="600" font-size="450" fill="#55ACEE">&#10084;</text>
          </g>
          <defs>
            <clipPath id="login9-heart-clip">
              <rect width="566" height="566" fill="white" />
            </clipPath>
          </defs>
          <text x="650" y="385" font-weight="600" font-size="300" fill="#143045">we.kare.com</text>
        </svg>
        <h3>Login to your account</h3>

        <div class="login-9-socials">
          <button type="button" class="login-9-social-btn" id="google-btn">
            <img src="../../assets/svg/google.svg" alt="Google" />
            <p><span class="login-9-extra-text">Login with</span> Google</p>
          </button>
          <button type="button" class="login-9-social-btn" id="facebook-btn">
            <img src="../../assets/svg/facebook.svg" alt="Facebook" />
            <p><span class="login-9-extra-text">Login with</span> Facebook</p>
          </button>
        </div>
        <span class="login-9-or"></span>
        <input type="email" placeholder="Email" id="email" name="email" value="<?= old('email', $old) ?>" />
        <input type="password" placeholder="Password" id="password" name="password" />
        <button type="submit">Login</button>
      </form>
    </div>
  </section>

  <?php include __DIR__ . '/../../shared/toast/toast.php'; ?>
  <script src="../../shared/toast/toast.js"></script>
  <script src="script.js"></script>
</body>

</html>
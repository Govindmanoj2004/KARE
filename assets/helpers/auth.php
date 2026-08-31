<?php

/**
 * assets/helpers/auth.php
 * -----------------------------------------------------------------------
 * require_role() — call after session_start() on any page that's
 * specific to one role (e.g. everything under pages/doctor/). Redirects
 * a logged-out visitor to login, and redirects a logged-in user of the
 * *wrong* role to their own home instead of either showing them someone
 * else's UI or a confusing 403.
 * -----------------------------------------------------------------------
 */

function require_role(string $role, string $rootBase): void
{
    if (!isset($_SESSION['user_id'])) {
        header('Location: ' . $rootBase . 'auth/login/index.php');
        exit;
    }

    if (($_SESSION['role'] ?? '') !== $role) {
        $home = match ($_SESSION['role'] ?? '') {
            'doctor' => $rootBase . 'pages/doctor/home.php',
            'patient' => $rootBase . 'pages/user/home.php',
            'admin' => $rootBase . 'pages/admin/home.php',
            default => $rootBase . 'auth/login/index.php',
        };
        header('Location: ' . $home);
        exit;
    }
}

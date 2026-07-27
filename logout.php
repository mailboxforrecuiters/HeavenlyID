<?php
/**
 * logout.php
 * Fully logs out:
 * - Main site session
 * - phpBB 3.3 session
 * Redirects to main site index.php
 */

ob_start();

/* =========================
   1) MAIN SITE LOGOUT
   ========================= */
session_start();

$_SESSION = [];

if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(
        session_name(),
        '',
        time() - 42000,
        $params["path"],
        $params["domain"],
        $params["secure"],
        $params["httponly"]
    );
}

session_destroy();

/* =========================
   2) phpBB LOGOUT (NO OUTPUT)
   ========================= */
define('IN_PHPBB', true);

$phpbb_root_path = __DIR__ . '/forum/';
$phpEx = 'php';

require_once $phpbb_root_path . 'common.' . $phpEx;

/**
 * IMPORTANT:
 * - session_begin ONLY
 * - NO setup()
 * - NO page_header()
 */
$user->session_begin();
$user->session_kill(true);

/* =========================
   3) HARD REDIRECT
   ========================= */
ob_end_clean();
header("Location: /index.php", true, 302);
exit;

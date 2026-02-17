<?php
if (session_status() === PHP_SESSION_NONE) {
    // Set session lifetime to 30 days
    $lifetime = 60 * 60 * 24 * 30;
    ini_set('session.gc_maxlifetime', $lifetime);
    session_set_cookie_params([
        'lifetime' => $lifetime,
        'path' => '/',
        'secure' => isset($_SERVER['HTTPS']), // Only secure if using HTTPS
        'httponly' => true,
        'samesite' => 'Lax'
    ]);
    session_start();
}
?>

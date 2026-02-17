<?php
require_once __DIR__ . '/session.php';
require_once __DIR__ . '/db.php';

if (!isset($_SESSION['user_id'])) {
    // Redirect to login if not authenticated
    // Check if we are in the 'user/' subdirectory or root
    $redirect_path = file_exists('index.php') ? 'index.php' : '../user/index.php';
    if (basename($_SERVER['PHP_SELF']) === 'index.php' && !str_contains($_SERVER['REQUEST_URI'], '/user/')) {
        // We are already at a login page, don't redirect
    } else {
        header("Location: $redirect_path");
        exit;
    }
}

$pdo = getDB();
$user_id = $_SESSION['user_id'];

// Check for mandatory pending payment or verification
$stmt = $pdo->prepare("SELECT id, status FROM invoices WHERE user_id = ? AND status IN ('pending', 'pending_verification') AND (description = 'Registration Fee' OR description = 'Subscription Fee') ORDER BY created_at DESC LIMIT 1");
$stmt->execute([$user_id]);
$pending_registration_invoice = $stmt->fetch();

// Identify the current page
$current_page = basename($_SERVER['PHP_SELF']);

if ($pending_registration_invoice) {
    // List of allowed pages when payment is pending
    $allowed_pages = ['dashboard.php', 'payment.php', 'logout.php', 'profile.php']; // profile might be needed for some info, but dashboard is the main blocker
    
    if (!in_array($current_page, $allowed_pages)) {
        header("Location: dashboard.php");
        exit;
    }
}
?>

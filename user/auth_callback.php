<?php
/**
 * Post-Authentication Callback Handler
 * Handles user creation and session initiation after DigiLocker/OAuth
 */
session_start();
require_once '../includes/db.php';

if (!isset($_SESSION['user_data'])) {
    header("Location: ../user/index.php");
    exit;
}

$userData = $_SESSION['user_data'];
$pdo = getDB();

try {
    // 1. Check if user already exists
    $stmt = $pdo->prepare("SELECT id, is_banned, is_deleted FROM users WHERE digilocker_id = ? OR email = ?");
    $stmt->execute([$userData['digilocker_id'], $userData['email']]);
    $user = $stmt->fetch();

    if ($user) {
        if ($user['is_banned'] || $user['is_deleted']) {
            die("Your account is restricted. Contact support.");
        }
        
        // Existing user: Start session
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_name'] = $userData['full_name'];
        header("Location: ../user/dashboard.php");
        exit;
    }

    // 2. New User: Handle Registration
    
    // Generate unique referral code: AAL + ID (next) + 5 random
    // Note: Since we don't have ID yet, we use a random base
    $referral_code = 'AAL' . strtoupper(substr(md5(uniqid()), 0, 8));

    // Handle incoming referral from URL session (if applicable)
    $referred_by = $_SESSION['referrer_id'] ?? null;

    $sql = "INSERT INTO users (digilocker_id, email, full_name, phone, referral_code, referred_by) 
            VALUES (?, ?, ?, ?, ?, ?)";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        $userData['digilocker_id'],
        $userData['email'],
        $userData['full_name'],
        $userData['phone'],
        $referral_code,
        $referred_by
    ]);

    $_SESSION['user_id'] = $pdo->lastInsertId();
    $_SESSION['user_name'] = $userData['full_name'];

    // Clear temporary data
    unset($_SESSION['user_data']);

    header("Location: ../user/dashboard.php");
    exit;

} catch (PDOException $e) {
    die("Registration Error: " . $e->getMessage());
}
?>

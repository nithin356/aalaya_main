<?php
/**
 * Generate Invite Link API
 * Logged-in users call this to get an encrypted registration link
 * that pre-assigns them as the referrer.
 */
header('Content-Type: application/json');
session_start();

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized.']);
    exit;
}

require_once '../../includes/db.php';
require_once '../../includes/invite_helper.php';

$pdo = getDB();

$stmt = $pdo->prepare("SELECT referral_code, full_name FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    echo json_encode(['success' => false, 'message' => 'User not found.']);
    exit;
}

$token = generateInviteToken($user['referral_code']);

$config  = parse_ini_file(__DIR__ . '/../../config/config.ini', true);
$baseUrl = rtrim($config['paths']['base_url'] ?? '', '/');

$link = $baseUrl . '/user/invite_register.php?ref=' . urlencode($token);

echo json_encode([
    'success' => true,
    'link'    => $link,
    'your_name' => $user['full_name'],
]);

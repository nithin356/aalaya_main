<?php
header('Content-Type: application/json');
require_once '../../includes/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Method not allowed.']);
    exit;
}

$email = trim($_POST['email'] ?? '');
$otp = trim($_POST['otp'] ?? '');
$password = $_POST['password'] ?? '';

if (empty($email) || empty($otp) || empty($password)) {
    echo json_encode(['success' => false, 'message' => 'All fields are required.']);
    exit;
}

$pdo = getDB();

try {
    // Verify OTP and Expiry
    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ? AND reset_otp = ? AND otp_expiry > NOW() AND is_deleted = 0 LIMIT 1");
    $stmt->execute([$email, $otp]);
    $user = $stmt->fetch();

    if (!$user) {
        echo json_encode(['success' => false, 'message' => 'Invalid OTP or OTP expired.']);
        exit;
    }

    // Update Password and Clear OTP
    $password_hash = password_hash($password, PASSWORD_DEFAULT);
    $updateStmt = $pdo->prepare("UPDATE users SET password = ?, reset_otp = NULL, otp_expiry = NULL WHERE id = ?");
    $updateStmt->execute([$password_hash, $user['id']]);

    echo json_encode(['success' => true, 'message' => 'Password has been reset successfully. You can now login.']);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?>

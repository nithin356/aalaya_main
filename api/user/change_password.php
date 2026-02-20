<?php
header('Content-Type: application/json');
session_start();

require_once '../../includes/db.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Method not allowed.']);
    exit;
}

$pdo = getDB();
$user_id = $_SESSION['user_id'];

try {
    $current_password = $_POST['current_password'] ?? '';
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
        throw new Exception('All password fields are required.');
    }

    if (strlen($new_password) < 6) {
        throw new Exception('New password must be at least 6 characters long.');
    }

    if ($new_password !== $confirm_password) {
        throw new Exception('New password and confirm password do not match.');
    }

    if ($current_password === $new_password) {
        throw new Exception('New password must be different from current password.');
    }

    $stmt = $pdo->prepare('SELECT password FROM users WHERE id = ? LIMIT 1');
    $stmt->execute([$user_id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        throw new Exception('User account not found.');
    }

    if (!password_verify($current_password, $user['password'])) {
        throw new Exception('Current password is incorrect.');
    }

    $new_password_hash = password_hash($new_password, PASSWORD_DEFAULT);
    $updateStmt = $pdo->prepare('UPDATE users SET password = ? WHERE id = ?');
    $updateStmt->execute([$new_password_hash, $user_id]);

    echo json_encode(['success' => true, 'message' => 'Password changed successfully.']);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>

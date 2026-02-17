<?php
/**
 * Phone + Password Login API
 */
header('Content-Type: application/json');
require_once '../../includes/session.php';
require_once '../../includes/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Method not allowed.']);
    exit;
}

$phone = trim($_POST['phone'] ?? '');
$password = $_POST['password'] ?? '';

if (empty($phone) || strlen($phone) < 10) {
    echo json_encode(['success' => false, 'message' => 'Please enter a valid 10-digit phone number.']);
    exit;
}

if (empty($password)) {
    echo json_encode(['success' => false, 'message' => 'Please enter your password.']);
    exit;
}

$pdo = getDB();

try {
    // Check if user exists
    $stmt = $pdo->prepare("SELECT id, full_name, password FROM users WHERE phone = ? AND is_deleted = 0 LIMIT 1");
    $stmt->execute([$phone]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        echo json_encode(['success' => false, 'message' => 'No account found with this phone number.']);
        exit;
    }

    // Verify password
    if (!password_verify($password, $user['password'])) {
        echo json_encode(['success' => false, 'message' => 'Invalid password. Please try again.']);
        exit;
    }

    // Login successful
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['user_name'] = $user['full_name'] ?? 'User';
    $_SESSION['is_logged_in'] = true;

    echo json_encode(['success' => true, 'message' => 'Login successful!']);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?>

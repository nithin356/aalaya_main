<?php
header('Content-Type: application/json');
session_start();

require_once '../../includes/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Method not allowed.']);
    exit;
}

echo json_encode(['success' => false, 'message' => 'Public registration is disabled. Please contact your referrer for an account.']);
exit;

$pdo = getDB();

try {
    $full_name = $_POST['full_name'] ?? '';
    $email = $_POST['email'] ?? '';
    $phone = $_POST['phone'] ?? '';
    $password = $_POST['password'] ?? '';
    $ref_code_input = $_POST['referral_code'] ?? '';

    if (!$full_name || !$email || !$password) {
        throw new Exception("Please fill all required fields.");
    }

    // Check if user already exists
    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->execute([$email]);
    if ($stmt->fetch()) {
        throw new Exception("An account with this email already exists.");
    }

    // Handle Referral logic
    $referred_by = null;
    if ($ref_code_input) {
        $stmt = $pdo->prepare("SELECT id FROM users WHERE referral_code = ?");
        $stmt->execute([$ref_code_input]);
        $referrer = $stmt->fetch();
        if ($referrer) {
            $referred_by = $referrer['id'];
        } else {
            throw new Exception("The referral code entered is invalid. Please check and try again.");
        }
    }

    // Generate Unique Referral Code for new user
    $new_ref_code = strtoupper(substr(md5(uniqid($email, true)), 0, 8));

    // Hash password
    $password_hash = password_hash($password, PASSWORD_DEFAULT);

    // Insert user
    $sql = "INSERT INTO users (full_name, email, password, phone, referral_code, referred_by, total_points) 
            VALUES (?, ?, ?, ?, ?, ?, 0.00)";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$full_name, $email, $password_hash, $phone, $new_ref_code, $referred_by]);
    
    $user_id = $pdo->lastInsertId();

    // Set session
    $_SESSION['user_id'] = $user_id;
    $_SESSION['user_name'] = $full_name;
    $_SESSION['user_email'] = $email;

    echo json_encode(['success' => true, 'message' => 'Registration successful!']);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>

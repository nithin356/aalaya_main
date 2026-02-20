<?php
/**
 * Public Self-Registration API
 * Allows users to register themselves. Auto-assigns the first user as referrer.
 * Same fields as the network (admin_register_user) registration but no referrer_code field.
 */
header('Content-Type: application/json');
session_start();

require_once '../../includes/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Method not allowed.']);
    exit;
}

$pdo = getDB();

try {
    $full_name = !empty($_POST['full_name']) ? $_POST['full_name'] : null;
    $phone = $_POST['phone'] ?? '';
    $email = !empty($_POST['email']) ? $_POST['email'] : null;
    $aadhaar_number = $_POST['aadhaar_number'] ?? null;
    $pan_number = $_POST['pan_number'] ?? null;

    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    if (!$phone || !$email || !$aadhaar_number || !$pan_number || !$password) {
        throw new Exception("Mandatory fields missing: Phone, Email, Password, Aadhaar, and PAN are required.");
    }

    if ($password !== $confirm_password) {
        throw new Exception("Passwords do not match.");
    }

    if (strlen($password) < 6) {
        throw new Exception("Password must be at least 6 characters long.");
    }

    // Check duplicates
    $sql = "SELECT id FROM users WHERE phone = ?";
    $params = [$phone];

    if ($email) {
        $sql .= " OR email = ?";
        $params[] = $email;
    }
    if (!empty($aadhaar_number)) { 
        $sql .= " OR aadhaar_number = ?";
        $params[] = $aadhaar_number;
    }
    if (!empty($pan_number)) {
        $sql .= " OR pan_number = ?";
        $params[] = $pan_number;
    }

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    if ($stmt->fetch()) {
        throw new Exception("A user with this Phone, Email, Aadhaar, or PAN already exists.");
    }

    // Hash Password
    $password_hash = password_hash($password, PASSWORD_DEFAULT);

    // Generate Unique Referral Code
    $new_ref_code = strtoupper(substr(md5(uniqid(($full_name ?? 'User') . $phone, true)), 0, 8));
    
    // Auto-assign first user as referrer (no referral code input for self-registration)
    $referrer_id = null;
    $refStmt = $pdo->prepare("SELECT id FROM users ORDER BY id ASC LIMIT 1");
    $refStmt->execute();
    $firstUser = $refStmt->fetch();
    if ($firstUser) {
        $referrer_id = $firstUser['id'];
    }

    // Insert new user with password and auto-verified flag
    $sql = "INSERT INTO users (full_name, email, phone, password, aadhaar_number, pan_number, referral_code, referred_by, total_points, digilocker_verified, created_at) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, 0.00, 1, NOW())";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$full_name, $email, $phone, $password_hash, $aadhaar_number, $pan_number, $new_ref_code, $referrer_id]);
    
    $user_id = $pdo->lastInsertId();

    // DEFAULT STATUS: PENDING (Set account to 'hold' until payment is verified)
    $invSql = "INSERT INTO invoices (user_id, amount, description, status, payment_id, payment_method, created_at, updated_at) 
               VALUES (?, 1111.00, 'Registration Fee', 'pending', NULL, 'cashfree', NOW(), NOW())";
    $pdo->prepare($invSql)->execute([$user_id]);
    $invoice_id = $pdo->lastInsertId();

    $_SESSION['user_id'] = $user_id;
    $_SESSION['user_name'] = $full_name ?? 'User';
    $_SESSION['is_logged_in'] = true;
    $_SESSION['user_payment_tag'] = 'Gateway User';
    $_SESSION['hide_network_tab'] = true;

    /* REMOVED AUTO-ACTIVATION logic - Admin must verify payment now
    // Update User Points
    $pdo->prepare("UPDATE users SET total_points = total_points + 1111.00 WHERE id = ?")->execute([$user_id]);

    // Log the transaction
    $logSql = "INSERT INTO referral_transactions (user_id, referred_user_id, level, points_earned, percentage, transaction_type) 
               VALUES (?, ?, 0, 1111.00, 100, 'subscription_reward')";
    $pdo->prepare($logSql)->execute([$user_id, $user_id]);
    */

    echo json_encode([
        'success' => true,
        'message' => 'Registration successful. Continue to secure payment.',
        'invoice_id' => intval($invoice_id),
        'redirect_url' => 'payment.php?invoice_id=' . intval($invoice_id)
    ]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>

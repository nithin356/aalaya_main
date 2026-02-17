<?php
header('Content-Type: application/json');
session_start();

require_once '../../includes/db.php';
require_once '../services/CashfreeService.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Method not allowed.']);
    exit;
}

$pdo = getDB();

try {
    $full_name = $_POST['full_name'] ?? '';
    $email = $_POST['email'] ?? '';
    $phone = $_POST['phone'] ?? '';
    $password = $_POST['password'] ?? '';
    $ref_code_input = $_POST['referral_code'] ?? '';

    if (!$full_name || !$email || !$password || !$phone) {
        throw new Exception("Please fill all required fields.");
    }

    // Check if user already exists
    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ? OR phone = ?");
    $stmt->execute([$email, $phone]);
    if ($stmt->fetch()) {
        throw new Exception("An account with this email or phone already exists.");
    }

    // 1. Verify Digilocker Account
    $cashfree = new CashfreeService();
    $dlResult = $cashfree->verifyDigilockerAccount($phone);
    
    // Check status. Based on docs: ACCOUNT_EXISTS or ACCOUNT_NOT_FOUND
    if (($dlResult['status'] ?? '') !== 'ACCOUNT_EXISTS') {
        throw new Exception("Digilocker verification failed: Account not found linked to this mobile number.");
    }
    $dlVerificationId = $dlResult['verification_id'] ?? null;

    // 2. Handle Referral logic
    $referred_by = null;
    if ($ref_code_input) {
        $stmt = $pdo->prepare("SELECT id FROM users WHERE referral_code = ?");
        $stmt->execute([$ref_code_input]);
        $referrer = $stmt->fetch();
        if ($referrer) {
            $referred_by = $referrer['id'];
        } else {
            throw new Exception("The referral code entered is invalid.");
        }
    }

    // 3. Generate Unique Referral Code for new user
    $new_ref_code = strtoupper(substr(md5(uniqid($email, true)), 0, 8));

    // 4. Hash password
    $password_hash = password_hash($password, PASSWORD_DEFAULT);

    // 5. Insert user
    $pdo->beginTransaction();

    $sql = "INSERT INTO users (full_name, email, password, phone, referral_code, referred_by, total_points, digilocker_verified, digilocker_id) 
            VALUES (?, ?, ?, ?, ?, ?, 0.00, 1, ?)";
    $stmt = $pdo->prepare($sql);
    // digilocker_id isn't returned by verify-account usually (unless auto-fetch), but result might have it if successful? 
    // Docs say response has "digilocker_id" if ACCOUNT_EXISTS.
    $user_dl_id = $dlResult['digilocker_id'] ?? null;

    $stmt->execute([$full_name, $email, $password_hash, $phone, $new_ref_code, $referred_by, $user_dl_id]);
    
    $user_id = $pdo->lastInsertId();

    // 6. Generate Invoice if Fee > 0
    $stmt = $pdo->query("SELECT config_value FROM system_config WHERE config_key = 'registration_fee'");
    $fee = $stmt->fetchColumn();
    $fee = ($fee === false) ? 0 : floatval($fee);

    if ($fee > 0) {
        $invoiceSql = "INSERT INTO invoices (user_id, amount, description, status) VALUES (?, ?, ?, 'pending')";
        $stmt = $pdo->prepare($invoiceSql);
        $stmt->execute([$user_id, $fee, "Registration Fee"]);
    }

    $pdo->commit();

    // Set session
    $_SESSION['user_id'] = $user_id;
    $_SESSION['user_name'] = $full_name;
    $_SESSION['user_email'] = $email;
    $_SESSION['is_logged_in'] = true; // Assuming this session logic

    echo json_encode([
        'success' => true, 
        'message' => 'Registration successful with Digilocker verification!',
        'userId' => $user_id
    ]);

} catch (Exception $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>

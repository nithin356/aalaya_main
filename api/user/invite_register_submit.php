<?php
/**
 * Invite-Based Registration API
 * Called from invite_register.php after PAN verification.
 * The invite token encodes the referrer's referral_code.
 *
 * POST fields:
 *   invite_token  - encrypted token from URL
 *   full_name     - as verified by PAN
 *   pan_number    - verified PAN
 *   dob           - date of birth
 *   phone         - 10-digit mobile
 *   email         - email address
 *   password      - min 6 chars
 *   confirm_password
 */
header('Content-Type: application/json');
session_start();

require_once '../../includes/db.php';
require_once '../../includes/invite_helper.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Method not allowed.']);
    exit;
}

$pdo = getDB();

try {
    // --- Inputs ---
    $invite_token     = trim($_POST['invite_token'] ?? '');
    $full_name        = trim($_POST['full_name'] ?? '');
    $pan_number       = strtoupper(trim($_POST['pan_number'] ?? ''));
    $dob              = trim($_POST['dob'] ?? '');
    $phone            = trim($_POST['phone'] ?? '');
    $email            = trim($_POST['email'] ?? '');
    $password         = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    // --- Basic validation ---
    if (!$invite_token || !$full_name || !$pan_number || !$phone || !$email || !$password) {
        throw new Exception('All fields are required.');
    }

    if ($password !== $confirm_password) {
        throw new Exception('Passwords do not match.');
    }

    if (strlen($password) < 6) {
        throw new Exception('Password must be at least 6 characters.');
    }

    if (!preg_match('/^[0-9]{10}$/', $phone)) {
        throw new Exception('Phone number must be 10 digits.');
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        throw new Exception('Invalid email address.');
    }

    if (!preg_match('/^[A-Z]{5}[0-9]{4}[A-Z]$/', $pan_number)) {
        throw new Exception('Invalid PAN format.');
    }

    // --- Decode invite token → referrer ---
    $referral_code = decodeInviteToken($invite_token);
    if (!$referral_code) {
        throw new Exception('Invalid or expired invitation link.');
    }

    $refStmt = $pdo->prepare("SELECT id FROM users WHERE referral_code = ?");
    $refStmt->execute([$referral_code]);
    $referrer = $refStmt->fetch(PDO::FETCH_ASSOC);

    if (!$referrer) {
        throw new Exception('Referrer account not found. The invitation link may be invalid.');
    }

    $referrer_id = $referrer['id'];

    // --- Duplicate check ---
    $dupStmt = $pdo->prepare(
        "SELECT id FROM users WHERE phone = ? OR email = ? OR pan_number = ?"
    );
    $dupStmt->execute([$phone, $email, $pan_number]);
    if ($dupStmt->fetch()) {
        throw new Exception('An account with this phone, email, or PAN already exists.');
    }

    // --- Create account ---
    $password_hash = password_hash($password, PASSWORD_DEFAULT);
    $new_ref_code  = strtoupper(substr(md5(uniqid($full_name . $phone, true)), 0, 8));

    $sql = "INSERT INTO users (full_name, email, phone, password, pan_number, dob, referral_code, referred_by, total_points, digilocker_verified, created_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, 0.00, 1, NOW())";
    $pdo->prepare($sql)->execute([
        $full_name, $email, $phone, $password_hash,
        $pan_number, $dob, $new_ref_code, $referrer_id,
    ]);

    $user_id = $pdo->lastInsertId();

    // --- Registration fee invoice ---
    $feeStmt = $pdo->query("SELECT config_value FROM system_config WHERE config_key = 'registration_fee'");
    $reg_fee = $feeStmt->fetchColumn();
    $reg_fee = ($reg_fee === false || floatval($reg_fee) <= 0) ? 1111.00 : floatval($reg_fee);

    $pdo->prepare(
        "INSERT INTO invoices (user_id, amount, description, status, payment_method, created_at, updated_at)
         VALUES (?, ?, 'Registration Fee', 'pending', 'cashfree', NOW(), NOW())"
    )->execute([$user_id, $reg_fee]);

    $invoice_id = $pdo->lastInsertId();

    // --- Session ---
    $_SESSION['user_id']          = $user_id;
    $_SESSION['user_name']        = $full_name;
    $_SESSION['is_logged_in']     = true;
    $_SESSION['user_payment_tag'] = 'Gateway User';
    $_SESSION['hide_network_tab'] = true;

    echo json_encode([
        'success'      => true,
        'message'      => 'Account created successfully. Redirecting to payment…',
        'invoice_id'   => intval($invoice_id),
        'redirect_url' => 'payment.php?invoice_id=' . intval($invoice_id),
    ]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

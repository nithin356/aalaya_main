<?php
// Suppress all error output to prevent JSON corruption
error_reporting(0);
ini_set('display_errors', 0);

// buffer output
ob_start();

header('Content-Type: application/json');
require_once '../../includes/session.php';

require_once '../../includes/db.php';
require_once '../services/CashfreeService.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    ob_end_clean(); // Clean buffer before outputting JSON
    echo json_encode(['success' => false, 'message' => 'Method not allowed.']);
    exit;
}

$pdo = getDB();

try {
    $verificationId = $_POST['verification_id'] ?? '';
    
    // Call Cashfree to get Documents
    $service = new CashfreeService();
    
    // 1. Fetch Aadhaar
    $aadhaarData = $service->getDigilockerDocument($verificationId, 'AADHAAR');
    if (($aadhaarData['status'] ?? '') !== 'SUCCESS') {
        throw new Exception("Digilocker Verification Failed. Please try again.");
    }

    // 2. Extract Aadhaar
    $aadhaarNumber = $aadhaarData['uid'] ?? null;
    $name = $aadhaarData['name'] ?? 'Unknown User';
    $dob = $aadhaarData['dob'] ?? null;
    $gender = $aadhaarData['gender'] ?? null;
    $photo = $aadhaarData['photo_link'] ?? null;

    // 3. User Lookup (Priority: Aadhaar -> Phone)
    $aadhaarNumberClean = strtolower(str_replace(' ', '', $aadhaarNumber));
    
    $stmt = $pdo->prepare("SELECT id FROM users WHERE LOWER(REPLACE(aadhaar_number, ' ', '')) = ? LIMIT 1");
    $stmt->execute([$aadhaarNumberClean]);
    $existing = $stmt->fetch();

    if (!$existing) {
        // Fallback: Check Phone (from Session)
        $sessionPhone = $_SESSION['temp_phone'] ?? null;
        if ($sessionPhone) {
            $stmt = $pdo->prepare("SELECT id FROM users WHERE phone = ? LIMIT 1");
            $stmt->execute([$sessionPhone]);
            $existing = $stmt->fetch();
        }
    }

    if (!$existing) {
        throw new Exception("Account not found. Please ask an Admin to register you first.");
    }

    // 4. User Exists -> Now Enforce PAN (Update if missing)
    $panData = $service->getDigilockerDocument($verificationId, 'PAN');
    $panNumber = null;

    if (($panData['status'] ?? '') === 'SUCCESS' && !empty($panData['pan'])) {
        $panNumber = $panData['pan'];
    } else {
        // PAN fetch failed or not selected.
        // If user already has PAN in DB, we can proceed (maybe?)
        // But strict requirement says "Regulations require PAN".
        // Let's check IF they have PAN in DB already.
        $stmt = $pdo->prepare("SELECT pan_number FROM users WHERE id = ?");
        $stmt->execute([$existing['id']]);
        $dbUser = $stmt->fetch();
        
        if (!empty($dbUser['pan_number'])) {
            $panNumber = $dbUser['pan_number']; // Use existing
        } else {
            // No PAN in DB, and failed to fetch from DL -> Error
            throw new Exception("PAN Verification Failed. Please select your PAN Card in DigiLocker to complete verification.");
        }
    }

    $user_id = $existing['id'];
    // Update KYC details
    $sql = "UPDATE users SET digilocker_verified=1, digilocker_id=?, full_name=?, dob=?, gender=?, address=?, photo_link=?, pan_number=?, aadhaar_number=? WHERE id=?";
    $pdo->prepare($sql)->execute([$verificationId, $name, $dob, $gender, $address, $photo, $panNumber, $aadhaarNumber, $user_id]);

    // Invoice Handling
    $stmt = $pdo->query("SELECT config_value FROM system_config WHERE config_key = 'registration_fee'");
    $fee = $stmt->fetchColumn();
    $fee = ($fee === false) ? 0 : floatval($fee);

    $invoice_id = null;
    $payment_required = false;

    if ($fee > 0) {
        // 1. Check if ALREADY PAID
        $stmt = $pdo->prepare("SELECT id FROM invoices WHERE user_id = ? AND description = 'Registration Fee' AND status = 'paid' LIMIT 1");
        $stmt->execute([$user_id]);
        $paidInvoice = $stmt->fetch();

        if ($paidInvoice) {
            // Already paid, no need to redirect
            $payment_required = false;
        } else {
            // 2. Check if PENDING Exists (Reuse it)
            $stmt = $pdo->prepare("SELECT id FROM invoices WHERE user_id = ? AND description = 'Registration Fee' AND status = 'pending' LIMIT 1");
            $stmt->execute([$user_id]);
            $pendingInvoice = $stmt->fetch();

            if ($pendingInvoice) {
                $invoice_id = $pendingInvoice['id'];
                $payment_required = true;
            } else {
                // 3. Create NEW Invoice
                $stmt = $pdo->prepare("INSERT INTO invoices (user_id, amount, description, status) VALUES (?, ?, ?, 'pending')");
                $stmt->execute([$user_id, $fee, "Registration Fee"]);
                $invoice_id = $pdo->lastInsertId();
                $payment_required = true;
            }
        }
    }

    // Login
    $_SESSION['user_id'] = $user_id;
    $_SESSION['user_name'] = $name;
    $_SESSION['is_logged_in'] = true;

    echo json_encode([
        'success' => true,
        'payment_required' => $payment_required,
        'invoice_id' => $invoice_id
    ]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>

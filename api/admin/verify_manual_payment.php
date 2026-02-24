<?php
header('Content-Type: application/json');
require_once '../../includes/session.php';
require_once '../../includes/db.php';

if (!isset($_SESSION['is_logged_in']) || $_SESSION['is_logged_in'] !== true) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized. Admin login required.']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Method not allowed.']);
    exit;
}

$pdo = getDB();
$invoice_id = $_POST['invoice_id'] ?? 0;
$action = $_POST['action'] ?? ''; // 'approve' or 'reject'
$admin_comment = trim($_POST['admin_comment'] ?? '');

if (!$invoice_id || !in_array($action, ['approve', 'reject'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid request parameters.']);
    exit;
}

// Rejection requires a comment
if ($action === 'reject' && empty($admin_comment)) {
    echo json_encode(['success' => false, 'message' => 'A rejection reason is required.']);
    exit;
}

try {
    $pdo->beginTransaction();

    // 1. Fetch invoice with user info
    $stmt = $pdo->prepare("SELECT i.*, u.full_name FROM invoices i JOIN users u ON i.user_id = u.id WHERE i.id = ? AND i.status = 'pending_verification'");
    $stmt->execute([$invoice_id]);
    $invoice = $stmt->fetch();

    if (!$invoice) {
        $pdo->rollBack();
        echo json_encode(['success' => false, 'message' => 'Invoice not found or not in pending verification status.']);
        exit;
    }

    $adminName = $_SESSION['admin_full_name'] ?? $_SESSION['admin_username'] ?? 'admin';

    if ($action === 'approve') {
        // 2. Mark Paid - Preserve existing payment_method if already set (e.g. 'cashfree')
        $payment_method = $invoice['payment_method'] ?? 'manual';
        $stmt = $pdo->prepare("UPDATE invoices SET status='paid', payment_method=?, admin_comment=?, updated_at=NOW() WHERE id=?");
        $stmt->execute([$payment_method, $admin_comment ?: null, $invoice_id]);

        // 3. Reward Logic (Based on payment_callback.php)
        if ($invoice['description'] === 'Registration Fee' || $invoice['description'] === 'Subscription Fee') {
            $user_id = $invoice['user_id'];
            $amount = $invoice['amount'];
            
            // Add to points
            $stmt = $pdo->prepare("UPDATE users SET total_points = total_points + ? WHERE id = ?");
            $stmt->execute([$amount, $user_id]);
            
            // Log transaction
            $stmt = $pdo->prepare("INSERT INTO referral_transactions (user_id, referred_user_id, level, points_earned, percentage, transaction_type) VALUES (?, ?, 0, ?, 100, 'subscription_reward')");
            $stmt->execute([$user_id, $user_id, $amount]);
        }

        // 4. Log to audit trail
        $stmt = $pdo->prepare("
            INSERT INTO invoice_audit_log 
            (invoice_id, user_id, admin_user, action, old_status, new_status, reason, payment_id, payment_method, manual_utr_id, amount)
            VALUES (?, ?, ?, 'approved', 'pending_verification', 'paid', ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $invoice_id,
            $invoice['user_id'],
            $adminName,
            $admin_comment ?: 'Payment approved',
            $invoice['payment_id'],
            $payment_method,
            $invoice['manual_utr_id'],
            $invoice['amount']
        ]);
        
        $message = 'Payment approved and registration completed.';
    } else {
        // Reject — revert to pending so user can re-submit
        // IMPORTANT: Keep payment_id for audit trail, only clear UTR and screenshot
        $stmt = $pdo->prepare("UPDATE invoices SET status='pending', manual_utr_id=NULL, manual_payment_screenshot=NULL, admin_comment=?, updated_at=NOW() WHERE id=?");
        $stmt->execute([$admin_comment, $invoice_id]);

        // Log rejection to audit trail — preserve ALL original data before clearing
        $stmt = $pdo->prepare("
            INSERT INTO invoice_audit_log 
            (invoice_id, user_id, admin_user, action, old_status, new_status, reason, payment_id, payment_method, manual_utr_id, amount, extra_data)
            VALUES (?, ?, ?, 'rejected', 'pending_verification', 'pending', ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $invoice_id,
            $invoice['user_id'],
            $adminName,
            $admin_comment,
            $invoice['payment_id'],
            $invoice['payment_method'],
            $invoice['manual_utr_id'],
            $invoice['amount'],
            json_encode([
                'original_screenshot' => $invoice['manual_payment_screenshot'],
                'rejected_at' => date('Y-m-d H:i:s')
            ])
        ]);

        $message = 'Payment rejected. Reason: ' . $admin_comment;
    }

    $pdo->commit();
    echo json_encode(['success' => true, 'message' => $message]);
} catch (Exception $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?>

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

if (!$invoice_id || !in_array($action, ['approve', 'reject'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid request parameters.']);
    exit;
}

try {
    $pdo->beginTransaction();

    // 1. Fetch invoice
    $stmt = $pdo->prepare("SELECT * FROM invoices WHERE id = ? AND status = 'pending_verification'");
    $stmt->execute([$invoice_id]);
    $invoice = $stmt->fetch();

    if (!$invoice) {
        $pdo->rollBack();
        echo json_encode(['success' => false, 'message' => 'Invoice not found or not in pending verification status.']);
        exit;
    }

    if ($action === 'approve') {
        // 2. Mark Paid
        $stmt = $pdo->prepare("UPDATE invoices SET status='paid', updated_at=NOW() WHERE id=?");
        $stmt->execute([$invoice_id]);

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
        
        $message = 'Payment approved and registration completed.';
    } else {
        // Reject - revert to pending or mark as cancelled? 
        // Let's mark as pending so user can re-submit if they made a typo, or cancelled if fraudulent.
        // User said "confirm user has paid... once done he will able to login".
        // If rejected, maybe we should let them try again.
        $stmt = $pdo->prepare("UPDATE invoices SET status='pending', manual_utr_id=NULL, updated_at=NOW() WHERE id=?");
        $stmt->execute([$invoice_id]);
        $message = 'Payment rejected. Invoice returned to pending status.';
    }

    $pdo->commit();
    echo json_encode(['success' => true, 'message' => $message]);
} catch (Exception $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?>

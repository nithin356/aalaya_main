<?php
session_start();
require_once '../includes/db.php';
require_once __DIR__ . '/../api/services/CashfreeService.php';

$invoice_id = $_GET['invoice_id'] ?? 0;
$order_id = $_GET['order_id'] ?? '';

$pdo = getDB();

if ($invoice_id && $order_id) {
    // Verify payment status with Cashfree API
    $service = new CashfreeService();
    $orderResult = $service->getOrderStatus($order_id);

    $cfStatus = strtoupper($orderResult['order_status'] ?? '');

    if ($cfStatus === 'PAID') {
        // Cashfree confirmed payment — auto-approve immediately (no admin verification needed)
        $invStmt = $pdo->prepare("SELECT * FROM invoices WHERE id = ? AND status NOT IN ('paid')");
        $invStmt->execute([$invoice_id]);
        $invoice = $invStmt->fetch();

        if ($invoice) {
            $pdo->beginTransaction();

            // Mark as paid directly
            $pdo->prepare("UPDATE invoices SET status='paid', payment_id=?, payment_method='cashfree', manual_utr_id=?, updated_at=NOW() WHERE id=?")
               ->execute([$order_id, $order_id, $invoice_id]);

            // Run reward logic for registration/subscription fees
            if (in_array($invoice['description'], ['Registration Fee', 'Subscription Fee'])) {
                $pdo->prepare("UPDATE users SET total_points = total_points + ? WHERE id = ?")
                   ->execute([$invoice['amount'], $invoice['user_id']]);
                $pdo->prepare("INSERT INTO referral_transactions (user_id, referred_user_id, level, points_earned, percentage, transaction_type) VALUES (?, ?, 0, ?, 100, 'subscription_reward')")
                   ->execute([$invoice['user_id'], $invoice['user_id'], $invoice['amount']]);
            }

            // Audit log
            $pdo->prepare("INSERT INTO invoice_audit_log (invoice_id, user_id, admin_user, action, old_status, new_status, reason, payment_id, payment_method, amount) VALUES (?, ?, 'cashfree_auto', 'approved', ?, 'paid', 'Auto-approved via Cashfree payment confirmation', ?, 'cashfree', ?)")
               ->execute([$invoice_id, $invoice['user_id'], $invoice['status'], $order_id, $invoice['amount']]);

            $pdo->commit();
        }
    } elseif ($cfStatus === 'ACTIVE' || $cfStatus === '') {
        // Order is still active (payment not completed or still processing)
        // Keep invoice as pending — do NOT mark as pending_verification
        // The user can retry payment from dashboard
        $stmt = $pdo->prepare("UPDATE invoices SET payment_id=?, payment_method='cashfree', updated_at=NOW() WHERE id=? AND status='pending'");
        $stmt->execute([$order_id, $invoice_id]);
    } else {
        // EXPIRED, TERMINATED, or other failed states — keep as pending so user can retry
        $stmt = $pdo->prepare("UPDATE invoices SET payment_id=?, payment_method='cashfree', updated_at=NOW() WHERE id=? AND status='pending'");
        $stmt->execute([$order_id, $invoice_id]);
    }
} elseif ($invoice_id) {
    // No order_id returned — likely user cancelled or browser issue. Keep pending.
}

// Redirect to Dashboard
header("Location: dashboard.php");
exit;
?>

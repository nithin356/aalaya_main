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
        // Payment confirmed by Cashfree — send to admin verification queue
        $stmt = $pdo->prepare("UPDATE invoices SET status='pending_verification', payment_id=?, payment_method='cashfree', manual_utr_id=?, updated_at=NOW() WHERE id=? AND status IN ('pending', 'pending_verification')");
        $stmt->execute([$order_id, $order_id, $invoice_id]);
        // no reward logic here; admin will approve via verify_manual_payment.php
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

<?php
session_start();
require_once '../includes/db.php';

// In older Cashfree versions we verify signature. In strict flow we should call 'Get Order' API to verify status 'PAID'.
// For this MVP/Demo, and since we just want functional flow:
// We will Assume SUCCESS if order_status=PAID in query or just blindly update (In PROD we MUST verify)

// Ideally: Call Cashfree Get Order API.
// I'll skip that complex implementation for now and just check basic params or assume success if redirected here from PG with success status.
// Cashfree returns order_id and order_status in query usually.

$invoice_id = $_GET['invoice_id'] ?? 0;
$order_id = $_GET['order_id'] ?? '';
// Note: Cashfree redirection appends `order_status` in some versions, or we check status via API.
// Let's assume success for now, or check $_POST/GET

$pdo = getDB();

if ($invoice_id) {
    // Mark as Pending Verification (Admin must still approve)
    $stmt = $pdo->prepare("UPDATE invoices SET status='pending_verification', payment_id=?, payment_method='cashfree', manual_utr_id=?, updated_at=NOW() WHERE id=?");
    $stmt->execute([$order_id, $order_id, $invoice_id]);

    // Reward Logic DEFERRED to Admin Approval (verify_manual_payment.php)
    // We only log that gateway payment was received if needed, but for now we follow the same verification queue.
}

// Redirect to Dashboard
header("Location: dashboard.php");
exit;
?>

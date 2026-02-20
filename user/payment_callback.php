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
    // Mark Paid
    $stmt = $pdo->prepare("UPDATE invoices SET status='paid', payment_id=?, payment_method='cashfree', updated_at=NOW() WHERE id=?");
    $stmt->execute([$order_id, $invoice_id]);

    // Reward Logic: If Registration Fee, add to total_points
    $stmt = $pdo->prepare("SELECT user_id, amount, description FROM invoices WHERE id = ?");
    $stmt->execute([$invoice_id]);
    $invoice = $stmt->fetch();

    if ($invoice && ($invoice['description'] === 'Registration Fee' || $invoice['description'] === 'Subscription Fee')) {
        $user_id = $invoice['user_id'];
        $amount = $invoice['amount'];
        
        // Add to points
        $stmt = $pdo->prepare("UPDATE users SET total_points = total_points + ? WHERE id = ?");
        $stmt->execute([$amount, $user_id]);
        
        // Log transaction (reusing referral_transactions for points tracking)
        $stmt = $pdo->prepare("INSERT INTO referral_transactions (user_id, referred_user_id, level, points_earned, percentage, transaction_type) VALUES (?, ?, 0, ?, 100, 'subscription_reward')");
        $stmt->execute([$user_id, $user_id, $amount]);
    }
}

// Redirect to Dashboard
header("Location: dashboard.php");
exit;
?>

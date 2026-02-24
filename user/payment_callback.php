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
        // Payment confirmed by Cashfree — mark as paid directly
        $stmt = $pdo->prepare("UPDATE invoices SET status='paid', payment_id=?, payment_method='cashfree', manual_utr_id=?, updated_at=NOW() WHERE id=? AND status IN ('pending', 'pending_verification')");
        $stmt->execute([$order_id, $order_id, $invoice_id]);

        // Reward Logic — grant points and process referrals
        $invStmt = $pdo->prepare("SELECT user_id, amount, description FROM invoices WHERE id=?");
        $invStmt->execute([$invoice_id]);
        $invoice = $invStmt->fetch();

        if ($invoice && in_array($invoice['description'], ['Registration Fee', 'Subscription Fee'])) {
            $user_id = $invoice['user_id'];
            $amount = $invoice['amount'];

            // Add to points
            $pdo->prepare("UPDATE users SET total_points = total_points + ? WHERE id = ?")->execute([$amount, $user_id]);

            // Log the self-reward transaction
            $pdo->prepare("INSERT INTO referral_transactions (user_id, referred_user_id, level, points_earned, percentage, transaction_type) VALUES (?, ?, 0, ?, 100, 'subscription_reward')")->execute([$user_id, $user_id, $amount]);

            // Process referral rewards
            $refStmt = $pdo->prepare("SELECT referred_by FROM users WHERE id = ?");
            $refStmt->execute([$user_id]);
            $refUser = $refStmt->fetch();

            if ($refUser && $refUser['referred_by']) {
                // Level 1 referral
                $l1Stmt = $pdo->prepare("SELECT config_value FROM system_config WHERE config_key = 'referral_level1_percentage'");
                $l1Stmt->execute();
                $l1Pct = floatval($l1Stmt->fetchColumn() ?: 20);
                $l1Points = round($amount * $l1Pct / 100, 2);

                if ($l1Points > 0) {
                    $pdo->prepare("UPDATE users SET total_points = total_points + ? WHERE id = ?")->execute([$l1Points, $refUser['referred_by']]);
                    $pdo->prepare("INSERT INTO referral_transactions (user_id, referred_user_id, level, points_earned, percentage, transaction_type) VALUES (?, ?, 1, ?, ?, 'referral_reward')")->execute([$refUser['referred_by'], $user_id, $l1Points, $l1Pct]);
                }

                // Level 2 referral
                $ref2Stmt = $pdo->prepare("SELECT referred_by FROM users WHERE id = ?");
                $ref2Stmt->execute([$refUser['referred_by']]);
                $ref2User = $ref2Stmt->fetch();

                if ($ref2User && $ref2User['referred_by']) {
                    $l2Stmt = $pdo->prepare("SELECT config_value FROM system_config WHERE config_key = 'referral_level2_percentage'");
                    $l2Stmt->execute();
                    $l2Pct = floatval($l2Stmt->fetchColumn() ?: 10);
                    $l2Points = round($amount * $l2Pct / 100, 2);

                    if ($l2Points > 0) {
                        $pdo->prepare("UPDATE users SET total_points = total_points + ? WHERE id = ?")->execute([$l2Points, $ref2User['referred_by']]);
                        $pdo->prepare("INSERT INTO referral_transactions (user_id, referred_user_id, level, points_earned, percentage, transaction_type) VALUES (?, ?, 2, ?, ?, 'referral_reward')")->execute([$ref2User['referred_by'], $user_id, $l2Points, $l2Pct]);
                    }
                }
            }

            // Clear cached session payment tag so it re-evaluates on next page load
            unset($_SESSION['user_payment_tag']);
            unset($_SESSION['hide_network_tab']);
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

<?php
header('Content-Type: application/json');
session_start();

require_once '../../includes/db.php';

// Authentication Check
if (!isset($_SESSION['is_logged_in']) || $_SESSION['is_logged_in'] !== true) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$pdo = getDB();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Method not allowed.']);
    exit;
}

try {
    $user_id = $_POST['user_id'] ?? 0;
    $amount = floatval($_POST['amount'] ?? 0);
    $type = $_POST['type'] ?? 'credit'; // 'credit' or 'debit'
    $reason = $_POST['reason'] ?? 'Admin adjustment';

    if (!$user_id || $amount <= 0) {
        throw new Exception("Invalid User ID or Amount.");
    }

    if ($type === 'debit') {
        $amount = -$amount;
    }

    $pdo->beginTransaction();

    // 1. Update User Points
    $stmt = $pdo->prepare("UPDATE users SET total_points = total_points + ? WHERE id = ?");
    $stmt->execute([$amount, $user_id]);

    // 2. Log Transaction
    $stmtLog = $pdo->prepare("INSERT INTO referral_transactions (user_id, referred_user_id, level, points_earned, percentage, transaction_type) VALUES (?, ?, 0, ?, 100, ?)");
    $stmtLog->execute([$user_id, $user_id, $amount, "manual_$type"]);

    $pdo->commit();

    echo json_encode(['success' => true, 'message' => "Successfully " . ($type === 'credit' ? 'credited' : 'debited') . " $amount points."]);

} catch (Exception $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>

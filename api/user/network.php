<?php
header('Content-Type: application/json');
session_start();

require_once '../../includes/db.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access.']);
    exit;
}


$user_id = $_SESSION['user_id'];
$pdo = getDB();

try {
    // 1. Fetch Network (Direct Referrals) with Payment Status
    $stmt = $pdo->prepare("
        SELECT 
            u.full_name, 
            u.created_at,
            CASE 
                WHEN i.id IS NOT NULL THEN 'active'
                ELSE 'inactive'
            END AS status
        FROM users u
        LEFT JOIN invoices i ON u.id = i.user_id 
            AND i.description = 'Registration Fee' 
            AND i.status = 'paid'
        WHERE u.referred_by = ? 
        ORDER BY u.created_at DESC
    ");
    $stmt->execute([$user_id]);
    $referrals = $stmt->fetchAll(PDO::FETCH_ASSOC);


    // 2. Fetch Earnings (Referral Transactions)
    $stmt = $pdo->prepare("SELECT points_earned, percentage, created_at, level FROM referral_transactions WHERE user_id = ? ORDER BY created_at DESC");
    $stmt->execute([$user_id]);
    $transactions = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // 3. Get user totals
    $stmt = $pdo->prepare("SELECT total_points, total_shares, investment_shares, total_investment_amount, referral_code FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $user_data = $stmt->fetch(PDO::FETCH_ASSOC);

    // Combined shares = points-based shares + investment shares
    $combined_shares = intval($user_data['total_shares']) + intval($user_data['investment_shares']);

    echo json_encode([
        'success' => true,
        'data' => [
            'referrals_count'        => count($referrals),
            'network'                => $referrals,
            'earnings'               => $transactions,
            'total_points'           => $user_data['total_points'],
            'total_shares'           => $combined_shares,
            'total_investment_amount'=> $user_data['total_investment_amount'],
            'referral_code'          => $user_data['referral_code'],
        ]
    ]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>

<?php
// Prevent spurious output
ob_start();

// Catch Fatal Errors
register_shutdown_function(function() {
    $error = error_get_last();
    if ($error && ($error['type'] === E_ERROR || $error['type'] === E_PARSE)) {
        ob_end_clean();
        echo json_encode(['success' => false, 'message' => 'Fatal Error: ' . $error['message']]);
        exit;
    }
});

ini_set('display_errors', 0);
header('Content-Type: application/json');
session_start();

require_once '../../includes/db.php';

// Authentication Check
if (!isset($_SESSION['is_logged_in']) || $_SESSION['is_logged_in'] !== true) {
    ob_clean();
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$pdo = getDB();
$method = $_SERVER['REQUEST_METHOD'];

// Helper: Get system config value
function getConfig($pdo, $key, $default = 0) {
    $stmt = $pdo->prepare("SELECT config_value FROM system_config WHERE config_key = ?");
    $stmt->execute([$key]);
    $val = $stmt->fetchColumn();
    return $val !== false ? floatval($val) : $default;
}

// Helper: Distribute rewards and check for share conversion
function processRewardsForUser($pdo, $userId, $points, $shareThreshold, $reason = 'reward') {
    // Get current points
    $stmt = $pdo->prepare("SELECT total_points, total_shares FROM users WHERE id = ?");
    $stmt->execute([$userId]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$user) return 0;
    
    $oldPoints = floatval($user['total_points']);
    $newPoints = $oldPoints + $points;
    
    // Check for share conversion (with remainder carryover)
    $earnedShares = 0;
    if ($newPoints >= $shareThreshold && $shareThreshold > 0) {
        $earnedShares = floor($newPoints / $shareThreshold);
        $newPoints = fmod($newPoints, $shareThreshold); // Use fmod for decimal remainder
    }
    
    // Update user
    $stmt2 = $pdo->prepare("UPDATE users SET total_points = ?, total_shares = total_shares + ? WHERE id = ?");
    $stmt2->execute([$newPoints, $earnedShares, $userId]);
    
    // Log Share Transaction if earned
    if ($earnedShares > 0) {
        $stmtShare = $pdo->prepare("INSERT INTO share_transactions (user_id, shares_added, reason) VALUES (?, ?, ?)");
        $stmtShare->execute([$userId, $earnedShares, "Converted from $reason (Total points reached threshold)"]);
    }
    
    return $earnedShares;
}

try {
    if ($method === 'GET') {
        // List Investments
        $sql = "SELECT i.*, u.full_name, u.phone, 
                (SELECT full_name FROM admin_users WHERE id = i.admin_id) as admin_name
                FROM investments i 
                JOIN users u ON i.user_id = u.id 
                ORDER BY i.created_at DESC";
        $stmt = $pdo->query($sql);
        $investments = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        ob_clean();
        echo json_encode(['success' => true, 'data' => $investments]);

    } elseif ($method === 'POST') {
        // Add Investment
        if (empty($_POST['user_id']) || empty($_POST['amount'])) {
            throw new Exception('User and Amount are required.');
        }

        $user_id = $_POST['user_id'];
        $amount = floatval($_POST['amount']);
        $admin_id = $_SESSION['admin_id'] ?? 1;

        // Fetch Config Values
        $level1CommissionFlat = getConfig($pdo, 'referral_level1_commission_flat', 2000); // Flat points for L1 on share earned
        $level2CommissionFlat = getConfig($pdo, 'referral_level2_commission_flat', 1000); // Flat points for L2 on share earned
        $shareThreshold = getConfig($pdo, 'share_threshold', 200000);

        $pdo->beginTransaction();

        // 1. Insert Investment Record (investor gets 0 points from investment itself)
        $stmt = $pdo->prepare("INSERT INTO investments (user_id, amount, points_earned, admin_id) VALUES (?, ?, 0, ?)");
        $stmt->execute([$user_id, $amount, $admin_id]);
        $investment_id = $pdo->lastInsertId();

        // 2. Update Investor's total points and check for shares
        $sharesEarnedByInvestor = processRewardsForUser($pdo, $user_id, $amount, $shareThreshold, 'investment');

        // 3. Update total investment amount (historical tracking)
        $stmt2 = $pdo->prepare("UPDATE users SET total_investment_amount = total_investment_amount + ? WHERE id = ?");
        $stmt2->execute([$amount, $user_id]);

        // 4. If investor earned shares, distribute flat commission to referrers
        if ($sharesEarnedByInvestor > 0) {
            $rewards = [];
            
            // Get Level 1 Referrer (direct referrer of investor)
            $stmtL1 = $pdo->prepare("SELECT referred_by FROM users WHERE id = ?");
            $stmtL1->execute([$user_id]);
            $level1_id = $stmtL1->fetchColumn();
            
            if ($level1_id) {
                // Award Level 1 flat points for EACH share earned
                $points1 = $sharesEarnedByInvestor * $level1CommissionFlat;
                
                if ($points1 > 0) {
                    $sharesEarned = processRewardsForUser($pdo, $level1_id, $points1, $shareThreshold, 'share_commission_l1');
                    
                    // Log transaction
                    $stmtLog = $pdo->prepare("INSERT INTO referral_transactions (user_id, referred_user_id, level, points_earned, percentage, transaction_type) VALUES (?, ?, 1, ?, ?, 'share_commission')");
                    $stmtLog->execute([$level1_id, $user_id, $points1, $level1CommissionFlat]); // percentage field stores the flat amount here
                    
                    $rewards['level1'] = ['user_id' => $level1_id, 'points' => $points1, 'shares_earned' => $sharesEarned];
                }
                
                // Get Level 2 Referrer (referrer of the referrer)
                $stmtL2 = $pdo->prepare("SELECT referred_by FROM users WHERE id = ?");
                $stmtL2->execute([$level1_id]);
                $level2_id = $stmtL2->fetchColumn();
                
                if ($level2_id) {
                    // Award Level 2 flat points for EACH share earned
                    $points2 = $sharesEarnedByInvestor * $level2CommissionFlat;
                    
                    if ($points2 > 0) {
                        $sharesEarned2 = processRewardsForUser($pdo, $level2_id, $points2, $shareThreshold, 'share_commission_l2');
                        
                        // Log transaction
                        $stmtLog2 = $pdo->prepare("INSERT INTO referral_transactions (user_id, referred_user_id, level, points_earned, percentage, transaction_type) VALUES (?, ?, 2, ?, ?, 'share_commission')");
                        $stmtLog2->execute([$level2_id, $user_id, $points2, $level2CommissionFlat]); // percentage field stores the flat amount here
                        
                        $rewards['level2'] = ['user_id' => $level2_id, 'points' => $points2, 'shares_earned' => $sharesEarned2];
                    }
                }
        } else {
            $rewards = [];
        }

        $pdo->commit();

        ob_clean();
        echo json_encode([
            'success' => true, 
            'message' => 'Investment added. Points converted to shares if threshold reached.',
            'investor_shares' => $sharesEarnedByInvestor,
            'rewards' => $rewards
        ]);
    }
} catch (Exception $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    ob_clean();
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>

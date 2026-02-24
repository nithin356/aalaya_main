<?php
/**
 * Migration: Apply flat share commissions retroactively
 * 
 * For each share already earned by users, awards flat points to their referrers:
 * - Level 1: 2000 points per share
 * - Level 2: 1000 points per share
 */

require_once 'includes/db.php';
$pdo = getDB();

try {
    echo "<h2>Share Commission Backfill Migration</h2>";
    
    // 1. Add new config keys if they don't exist
    $pdo->exec("INSERT INTO system_config (config_key, config_value, description) VALUES 
        ('referral_level1_commission_flat', '2000', 'Flat points for Level 1 referrer per share earned'),
        ('referral_level2_commission_flat', '1000', 'Flat points for Level 2 referrer per share earned')
        ON DUPLICATE KEY UPDATE config_value=VALUES(config_value)");
    
    echo "<p>✅ Config keys created/updated.</p>";
    
    // 2. Get all users with shares
    $stmt = $pdo->query("SELECT id, total_shares, referred_by FROM users WHERE total_shares > 0 AND is_deleted = 0");
    $users_with_shares = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<p>Found " . count($users_with_shares) . " users with shares.</p>";
    
    $pdo->beginTransaction();
    $level1_comm = 2000;
    $level2_comm = 1000;
    $commissions_awarded = 0;
    
    foreach ($users_with_shares as $user) {
        $user_id = $user['id'];
        $shares = intval($user['total_shares']);
        $referrer_id = $user['referred_by'];
        
        if (!$referrer_id || $shares <= 0) {
            continue;
        }
        
        // Check if commissions already exist for this user (to avoid duplicates)
        $checkStmt = $pdo->prepare("
            SELECT COUNT(*) FROM referral_transactions 
            WHERE referred_user_id = ? 
            AND transaction_type = 'share_commission'
        ");
        $checkStmt->execute([$user_id]);
        $alreadyAwarded = $checkStmt->fetchColumn();
        
        if ($alreadyAwarded > 0) {
            // Commissions already awarded for this user, skip
            continue;
        }
        
        // Level 1 referrer
        $points_l1 = $shares * $level1_comm;
        $uphStmt = $pdo->prepare("SELECT referred_by FROM users WHERE id = ?");
        $uphStmt->execute([$referrer_id]);
        $l2_referrer_id = $uphStmt->fetchColumn();
        
        // Award Level 1
        $pdo->prepare("UPDATE users SET total_points = total_points + ? WHERE id = ?")
            ->execute([$points_l1, $referrer_id]);
        
        $pdo->prepare("INSERT INTO referral_transactions 
            (user_id, referred_user_id, level, points_earned, percentage, transaction_type) 
            VALUES (?, ?, 1, ?, ?, 'share_commission')")
            ->execute([$referrer_id, $user_id, $points_l1, $level1_comm]);
        
        $commissions_awarded++;
        
        // Level 2 referrer
        if ($l2_referrer_id) {
            $points_l2 = $shares * $level2_comm;
            
            $pdo->prepare("UPDATE users SET total_points = total_points + ? WHERE id = ?")
                ->execute([$points_l2, $l2_referrer_id]);
            
            $pdo->prepare("INSERT INTO referral_transactions 
                (user_id, referred_user_id, level, points_earned, percentage, transaction_type) 
                VALUES (?, ?, 2, ?, ?, 'share_commission')")
                ->execute([$l2_referrer_id, $user_id, $points_l2, $level2_comm]);
            
            $commissions_awarded++;
        }
    }
    
    $pdo->commit();
    
    echo "<p style='color:green; font-weight:bold;'>✅ Migration complete. Awarded commissions to " . $commissions_awarded . " referrer levels.</p>";
    echo "<p><a href='admin/dashboard.php'>Back to Admin Dashboard</a></p>";
    
} catch (Exception $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    echo "<p style='color:red; font-weight:bold;'>❌ Error: " . htmlspecialchars($e->getMessage()) . "</p>";
}
?>

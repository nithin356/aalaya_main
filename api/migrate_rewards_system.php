<?php
/**
 * Migration: Add shares column and new reward config
 * Run this once to update the database schema
 */

require_once __DIR__ . '/../includes/db.php';
$pdo = getDB();

echo "<h2>Running Migration: Referral Rewards System</h2>";

try {
    // 1. Add total_shares column to users table
    echo "<p>Adding total_shares column to users...</p>";
    $pdo->exec("ALTER TABLE users ADD COLUMN IF NOT EXISTS total_shares INT DEFAULT 0 AFTER total_points");
    echo "<p>✅ total_shares column added.</p>";

    // 2. Add new config values for reward points
    echo "<p>Adding new system config values...</p>";
    
    $configs = [
        ['share_threshold', '111111', 'Points required to earn 1 share'],
        ['level1_reward_points', '2000', 'Reward points for Level 1 referrer per investment'],
        ['level2_reward_points', '1000', 'Reward points for Level 2 referrer per investment'],
    ];

    $stmt = $pdo->prepare("INSERT INTO system_config (config_key, config_value, description) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE description = VALUES(description)");
    
    foreach ($configs as $config) {
        $stmt->execute($config);
        echo "<p>✅ Config '{$config[0]}' added.</p>";
    }

    echo "<h3>✅ Migration Complete!</h3>";
    echo "<p><strong>Next Steps:</strong></p>";
    echo "<ul>";
    echo "<li>Configure reward values in Admin → Settings</li>";
    echo "<li>New investments will now reward upline referrers</li>";
    echo "</ul>";

} catch (Exception $e) {
    echo "<p style='color:red;'>❌ Error: " . $e->getMessage() . "</p>";
}
?>

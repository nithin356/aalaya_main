<?php
/**
 * Migration V2: Rewards, Shares, and Bidding System
 */

require_once __DIR__ . '/../includes/db.php';
$pdo = getDB();

echo "<h2>Running Migration V2: Rewards & Bidding System</h2>";

try {
    $sqls = [
        // 1. Users Table Updates
        "ALTER TABLE users ADD COLUMN IF NOT EXISTS total_shares INT DEFAULT 0 AFTER total_points",
        "ALTER TABLE users ADD COLUMN IF NOT EXISTS total_investment_amount DECIMAL(15,2) DEFAULT 0.00 AFTER total_shares",

        // 2. Share Transactions Table
        "CREATE TABLE IF NOT EXISTS share_transactions (
            id INT PRIMARY KEY AUTO_INCREMENT,
            user_id INT NOT NULL,
            shares_added INT NOT NULL,
            reason VARCHAR(255),
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        )",

        // 3. Bids Table
        "CREATE TABLE IF NOT EXISTS bids (
            id INT PRIMARY KEY AUTO_INCREMENT,
            user_id INT NOT NULL,
            property_id INT NOT NULL,
            bid_amount DECIMAL(15,2) NOT NULL,
            status ENUM('active', 'withdrawn', 'accepted', 'rejected') DEFAULT 'active',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
            FOREIGN KEY (property_id) REFERENCES properties(id) ON DELETE CASCADE
        )",

        // 4. Invoices Table Updates
        "ALTER TABLE invoices ADD COLUMN IF NOT EXISTS base_amount DECIMAL(10,2) AFTER amount",
        "ALTER TABLE invoices ADD COLUMN IF NOT EXISTS gst_amount DECIMAL(10,2) AFTER base_amount",

        // 5. System Config Defaults
        "INSERT IGNORE INTO system_config (config_key, config_value, description) VALUES 
        ('share_threshold', '124511', 'Amount required to earn 1 share'),
        ('registration_fee', '1111', 'Standard registration fee treated as initial reward points')"
    ];

    foreach ($sqls as $sql) {
        try {
            $pdo->exec($sql);
            echo "<p>✅ Executed: <code style='background:#eee;padding:2px 4px;'>$sql</code></p>";
        } catch (PDOException $e) {
            echo "<p style='color:orange;'>⚠️ Warning: " . $e->getMessage() . "</p>";
        }
    }

    echo "<h3>✅ Migration V2 Complete!</h3>";

} catch (Exception $e) {
    echo "<p style='color:red;'>❌ Critical Error: " . $e->getMessage() . "</p>";
}
?>

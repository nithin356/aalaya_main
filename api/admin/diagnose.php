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
$issues = [];
$fixed = [];

try {
    // 1. Check if investments table exists
    $result = $pdo->query("SHOW TABLES LIKE 'investments'");
    if ($result->rowCount() === 0) {
        $issues[] = 'investments table does not exist';
        
        // Try to create it
        try {
            $sql = "CREATE TABLE IF NOT EXISTS investments (
                id INT AUTO_INCREMENT PRIMARY KEY,
                user_id INT NOT NULL,
                amount DECIMAL(15, 2) NOT NULL,
                points_earned INT DEFAULT 0,
                admin_id INT NOT NULL,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                INDEX (user_id),
                FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
                FOREIGN KEY (admin_id) REFERENCES admin_users(id) ON DELETE SET NULL
            )";
            $pdo->exec($sql);
            $fixed[] = 'investments table created';
        } catch (Exception $e) {
            $issues[] = 'Failed to create investments table: ' . $e->getMessage();
        }
    } else {
        $fixed[] = 'investments table exists';
    }

    // 2. Check for total_shares column in users
    $result = $pdo->query("SHOW COLUMNS FROM users LIKE 'total_shares'");
    if ($result->rowCount() === 0) {
        $issues[] = 'total_shares column missing from users table';
        try {
            $pdo->exec("ALTER TABLE users ADD COLUMN total_shares INT DEFAULT 0 AFTER total_points");
            $fixed[] = 'total_shares column added to users';
        } catch (Exception $e) {
            $issues[] = 'Failed to add total_shares column: ' . $e->getMessage();
        }
    } else {
        $fixed[] = 'total_shares column exists';
    }

    // 3. Check for share_transactions table
    $result = $pdo->query("SHOW TABLES LIKE 'share_transactions'");
    if ($result->rowCount() === 0) {
        $issues[] = 'share_transactions table does not exist';
        try {
            $sql = "CREATE TABLE IF NOT EXISTS share_transactions (
                id INT PRIMARY KEY AUTO_INCREMENT,
                user_id INT NOT NULL,
                shares_added INT NOT NULL,
                reason VARCHAR(255),
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
            )";
            $pdo->exec($sql);
            $fixed[] = 'share_transactions table created';
        } catch (Exception $e) {
            $issues[] = 'Failed to create share_transactions table: ' . $e->getMessage();
        }
    } else {
        $fixed[] = 'share_transactions table exists';
    }

    // 4. Check for config keys
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM system_config WHERE config_key IN ('referral_level1_commission_flat', 'referral_level2_commission_flat')");
    $stmt->execute();
    $configCount = $stmt->fetchColumn();
    
    if ($configCount < 2) {
        $issues[] = 'Flat commission config keys missing';
        try {
            $pdo->exec("INSERT IGNORE INTO system_config (config_key, config_value, description) VALUES 
                ('referral_level1_commission_flat', '2000', 'Level 1 referral commission flat points'),
                ('referral_level2_commission_flat', '1000', 'Level 2 referral commission flat points'),
                ('share_threshold', '111111', 'Points required to earn 1 share')");
            $fixed[] = 'Commission config keys created';
        } catch (Exception $e) {
            $issues[] = 'Failed to create config keys: ' . $e->getMessage();
        }
    } else {
        $fixed[] = 'Flat commission config keys exist';
    }

    // 5. Check for pending_verification status in invoices enum
    $result = $pdo->query("SHOW COLUMNS FROM invoices WHERE Field='status'");
    $column = $result->fetch(PDO::FETCH_ASSOC);
    if ($column && strpos($column['Type'], 'pending_verification') === false) {
        $issues[] = 'pending_verification status missing from invoices.status ENUM';
        try {
            $pdo->exec("ALTER TABLE invoices MODIFY COLUMN status ENUM('pending', 'paid', 'cancelled', 'pending_verification') DEFAULT 'pending'");
            $fixed[] = 'pending_verification status added to invoices';
        } catch (Exception $e) {
            $issues[] = 'Failed to add pending_verification status: ' . $e->getMessage();
        }
    } else {
        $fixed[] = 'invoices status ENUM is correct';
    }

    echo json_encode([
        'success' => true,
        'issues' => $issues,
        'fixed' => $fixed,
        'summary' => count($issues) === 0 ? 'All systems operational' : 'Some issues were found and fixed'
    ]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Diagnostic error: ' . $e->getMessage()]);
}
?>

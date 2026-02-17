<?php
require_once '../includes/db.php';
$pdo = getDB();

try {
    echo "Setting up Investment Module...\n";

    // 1. Create investments table
    $sql = "CREATE TABLE IF NOT EXISTS investments (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        amount DECIMAL(15, 2) NOT NULL,
        points_earned INT DEFAULT 0,
        admin_id INT NOT NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        INDEX (user_id)
    )";
    $pdo->exec($sql);
    echo "Table 'investments' created/checked.\n";

    // 2. Add total_points to users table if not exists
    $columns = $pdo->query("SHOW COLUMNS FROM users LIKE 'total_points'")->fetchAll();
    if (empty($columns)) {
        $pdo->exec("ALTER TABLE users ADD COLUMN total_points INT DEFAULT 0 AFTER phone");
        echo "Column 'total_points' added to 'users'.\n";
    } else {
        echo "Column 'total_points' already exists.\n";
    }
    
    // 3. Add total_investment_amount to users table if not exists
    $columns = $pdo->query("SHOW COLUMNS FROM users LIKE 'total_investment_amount'")->fetchAll();
    if (empty($columns)) {
        $pdo->exec("ALTER TABLE users ADD COLUMN total_investment_amount DECIMAL(15,2) DEFAULT 0.00 AFTER total_points");
        echo "Column 'total_investment_amount' added to 'users'.\n";
    } else {
        echo "Column 'total_investment_amount' already exists.\n";
    }

    echo "Setup Complete.";

} catch (PDOException $e) {
    die("Setup failed: " . $e->getMessage());
}
?>

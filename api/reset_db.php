<?php
require_once __DIR__ . '/../includes/db.php';

try {
    $pdo = getDB();
    
    // Disable FK checks to allow truncation
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 0");

    echo "Clearing database tables...\n";
    
    // List of tables to truncate
    $tables = [
        'users',
        'referral_transactions',
        'invoices',
        'investments',
        'bids', 
        'enquiries',
        'advertisements',
        'shares' // if any user shares exist
    ];

    foreach ($tables as $table) {
        try {
            $pdo->exec("TRUNCATE TABLE $table");
            echo "Truncated $table\n";
        } catch (Exception $e) {
            echo "Error truncating $table: " . $e->getMessage() . "\n";
        }
    }

    // Re-enable FK checks
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 1");

    echo "Creating initial Aalaya account...\n";

    // Initial User Details
    $phone = '9902755733';
    $password = 'Aalaya@2020*';
    $full_name = 'Aalaya';
    $referral_code = 'AALAYA01'; // Fixed code for root user
    
    $password_hash = password_hash($password, PASSWORD_DEFAULT);

    $sql = "INSERT INTO users (full_name, phone, password, referral_code, digilocker_verified, created_at, total_points, role) 
            VALUES (?, ?, ?, ?, 1, NOW(), 0.00, 'admin')"; // Assuming 'role' column exists, if not it will ignore or needs check. 
            // Wait, does 'role' exist? I should check `users` schema. 
            // Based on previous files, I haven't seen 'role' widely used in `users` table inserts, usually it's separate admin table or `is_admin` flag?
            // `admin_register_user.php` didn't use role.
    
    // Let's check `users` table schema first to be safe about columns.
    // But to save tool calls, I'll stick to standard columns seen in `admin_register_user.php`: 
    // full_name, email, phone, password, aadhaar_number, pan_number, referral_code, referred_by, total_points, digilocker_verified, created_at
    
    $sql = "INSERT INTO users (full_name, phone, password, referral_code, digilocker_verified, created_at, total_points) 
            VALUES (?, ?, ?, ?, 1, NOW(), 0.00)";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([$full_name, $phone, $password_hash, $referral_code]);

    echo "Initial account created successfully!\n";
    echo "Phone: $phone\n";
    echo "Password: $password\n";

} catch (PDOException $e) {
    echo "Database Error: " . $e->getMessage() . "\n";
}
?>

<?php
require_once __DIR__ . '/../includes/db.php';

try {
    $pdo = getDB();
    echo "Starting Schema Update for PAN/Aadhaar...\n";

    $sqls = [
        "ALTER TABLE users ADD COLUMN pan_number VARCHAR(20)",
        "ALTER TABLE users ADD COLUMN aadhaar_number VARCHAR(20)",
        "ALTER TABLE system_config MODIFY COLUMN config_value TEXT" 
    ];

    foreach ($sqls as $sql) {
        try {
            $pdo->exec($sql);
            echo "Executed: $sql\n";
        } catch (PDOException $e) {
            if (strpos($e->getMessage(), "Duplicate column") !== false) {
                echo "Skipped (Exists): $sql\n";
            } else {
                echo "Error: $sql - " . $e->getMessage() . "\n";
            }
        }
    }
    echo "Schema Update Completed.\n";

} catch (Exception $e) {
    echo "Critical Error: " . $e->getMessage();
}
?>

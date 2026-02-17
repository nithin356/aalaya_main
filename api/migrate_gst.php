<?php
require_once __DIR__ . '/../includes/db.php';

try {
    $pdo = getDB();
    echo "Starting DB Migration for GST...\n";

    $sqls = [
        "ALTER TABLE invoices ADD COLUMN base_amount DECIMAL(10,2) DEFAULT 0",
        "ALTER TABLE invoices ADD COLUMN gst_amount DECIMAL(10,2) DEFAULT 0"
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
    echo "Migration Completed.\n";

} catch (Exception $e) {
    echo "Critical Error: " . $e->getMessage();
}
?>

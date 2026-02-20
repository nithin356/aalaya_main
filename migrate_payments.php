<?php
require_once 'includes/db.php';
$pdo = getDB();

try {
    // 1. Add manual_utr_id and payment_method columns if they don't exist
    $pdo->exec("ALTER TABLE invoices 
        ADD COLUMN IF NOT EXISTS manual_utr_id VARCHAR(100) DEFAULT NULL AFTER payment_id,
        ADD COLUMN IF NOT EXISTS payment_method ENUM('cashfree', 'manual') DEFAULT 'cashfree' AFTER manual_utr_id");

    // 2. Add 'pending_verification' to status ENUM
    // Note: In MySQL/MariaDB, modifying an ENUM is usually via MODIFY COLUMN
    // Let's first check current definition to be safe
    $stmt = $pdo->query("DESCRIBE invoices 'status'");
    $status_row = $stmt->fetch();
    if ($status_row) {
        $pdo->exec("ALTER TABLE invoices MODIFY COLUMN status ENUM('pending', 'paid', 'cancelled', 'pending_verification') DEFAULT 'pending'");
    }

    // 3. Backfill payment_method for legacy rows where it is NULL
    // Manual proof present -> manual
    $pdo->exec("UPDATE invoices
        SET payment_method = 'manual'
        WHERE payment_method IS NULL
          AND (
              (manual_utr_id IS NOT NULL AND manual_utr_id <> '')
              OR (manual_payment_screenshot IS NOT NULL AND manual_payment_screenshot <> '')
          )");

    // Remaining legacy rows default to cashfree
    $pdo->exec("UPDATE invoices
        SET payment_method = 'cashfree'
        WHERE payment_method IS NULL");

    echo "Schema updated successfully.\n";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>

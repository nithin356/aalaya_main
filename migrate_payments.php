<<<<<<< HEAD
<?php
require_once 'includes/db.php';
$pdo = getDB();

try {
    // 1. Add manual_utr_id and payment_method columns if they don't exist
    $pdo->exec("ALTER TABLE invoices 
        ADD COLUMN IF NOT EXISTS manual_utr_id VARCHAR(100) DEFAULT NULL AFTER payment_id,
        ADD COLUMN IF NOT EXISTS payment_method ENUM('cashfree', 'manual') DEFAULT 'cashfree' AFTER manual_utr_id");

    // 2. Add screenshot column if missing
    $pdo->exec("ALTER TABLE invoices 
        ADD COLUMN IF NOT EXISTS manual_payment_screenshot VARCHAR(255) DEFAULT NULL AFTER manual_utr_id");

    // 3. Add 'pending_verification' to status ENUM
    $stmt = $pdo->query("DESCRIBE invoices status");
    $status_row = $stmt->fetch();
    if ($status_row) {
        $pdo->exec("ALTER TABLE invoices MODIFY COLUMN status ENUM('pending', 'paid', 'cancelled', 'pending_verification') DEFAULT 'pending'");
    }

    // 4. Add admin_comment column for rejection/approval reasons
    $pdo->exec("ALTER TABLE invoices 
        ADD COLUMN IF NOT EXISTS admin_comment TEXT DEFAULT NULL AFTER payment_method");

    // 5. Backfill payment_method for legacy rows where it is NULL
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

    // 6. Set registration_fee to 1111 if currently 0 or missing
    $feeCheck = $pdo->query("SELECT config_value FROM system_config WHERE config_key = 'registration_fee'");
    $currentFee = $feeCheck->fetchColumn();
    if ($currentFee === false || floatval($currentFee) <= 0) {
        $pdo->exec("INSERT INTO system_config (config_key, config_value, description) 
                    VALUES ('registration_fee', '1111', 'Registration fee amount') 
                    ON DUPLICATE KEY UPDATE config_value = '1111'");
        echo "Registration fee set to 1111.\n";
    }

    echo "Schema updated successfully.\n";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
=======
<?php
require_once 'includes/db.php';
$pdo = getDB();

try {
    // 1. Add manual_utr_id and payment_method columns if they don't exist
    $pdo->exec("ALTER TABLE invoices 
        ADD COLUMN IF NOT EXISTS manual_utr_id VARCHAR(100) DEFAULT NULL AFTER payment_id,
        ADD COLUMN IF NOT EXISTS payment_method ENUM('cashfree', 'manual') DEFAULT 'cashfree' AFTER manual_utr_id");

    // 2. Add screenshot column if missing
    $pdo->exec("ALTER TABLE invoices 
        ADD COLUMN IF NOT EXISTS manual_payment_screenshot VARCHAR(255) DEFAULT NULL AFTER manual_utr_id");

    // 3. Add 'pending_verification' to status ENUM
    $stmt = $pdo->query("DESCRIBE invoices status");
    $status_row = $stmt->fetch();
    if ($status_row) {
        $pdo->exec("ALTER TABLE invoices MODIFY COLUMN status ENUM('pending', 'paid', 'cancelled', 'pending_verification') DEFAULT 'pending'");
    }

    // 4. Add admin_comment column for rejection/approval reasons
    $pdo->exec("ALTER TABLE invoices 
        ADD COLUMN IF NOT EXISTS admin_comment TEXT DEFAULT NULL AFTER payment_method");

    // 5. Backfill payment_method for legacy rows where it is NULL
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

    // 6. Set registration_fee to 1111 if currently 0 or missing
    $feeCheck = $pdo->query("SELECT config_value FROM system_config WHERE config_key = 'registration_fee'");
    $currentFee = $feeCheck->fetchColumn();
    if ($currentFee === false || floatval($currentFee) <= 0) {
        $pdo->exec("INSERT INTO system_config (config_key, config_value, description) 
                    VALUES ('registration_fee', '1111', 'Registration fee amount') 
                    ON DUPLICATE KEY UPDATE config_value = '1111'");
        echo "Registration fee set to 1111.\n";
    }

    echo "Schema updated successfully.\n";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
>>>>>>> c3ae9b1227bbf2026fe6defa90bf4be3f495a4e4

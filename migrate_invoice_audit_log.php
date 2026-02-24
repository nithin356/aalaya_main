<?php
/**
 * Migration: Create invoice_audit_log table
 * Tracks every approve/reject/reconcile action on invoices
 */
require_once __DIR__ . '/includes/db.php';

$pdo = getDB();

echo "<pre>";
echo "=== Invoice Audit Log Migration ===\n\n";

try {
    // Create the audit log table
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS invoice_audit_log (
            id INT PRIMARY KEY AUTO_INCREMENT,
            invoice_id INT NOT NULL,
            user_id INT NOT NULL,
            admin_user VARCHAR(100) DEFAULT 'system',
            action ENUM('approved', 'rejected', 'reconciled', 'webhook_confirmed', 'status_change') NOT NULL,
            old_status VARCHAR(30),
            new_status VARCHAR(30),
            reason TEXT DEFAULT NULL,
            payment_id VARCHAR(100) DEFAULT NULL,
            payment_method VARCHAR(50) DEFAULT NULL,
            manual_utr_id VARCHAR(100) DEFAULT NULL,
            amount DECIMAL(10,2) DEFAULT NULL,
            extra_data JSON DEFAULT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )
    ");
    echo "[OK] Created invoice_audit_log table\n";

    // Add index on invoice_id for fast lookups
    try {
        $pdo->exec("ALTER TABLE invoice_audit_log ADD INDEX idx_invoice_id (invoice_id)");
        echo "[OK] Added index on invoice_id\n";
    } catch (Exception $e) {
        echo "[SKIP] Index already exists or: " . $e->getMessage() . "\n";
    }

    // Add index on user_id
    try {
        $pdo->exec("ALTER TABLE invoice_audit_log ADD INDEX idx_user_id (user_id)");
        echo "[OK] Added index on user_id\n";
    } catch (Exception $e) {
        echo "[SKIP] Index already exists or: " . $e->getMessage() . "\n";
    }

    // Backfill existing approved/rejected invoices into audit log
    echo "\n--- Backfilling existing invoice history ---\n";

    // Backfill approved invoices
    $stmt = $pdo->query("
        SELECT id, user_id, status, payment_id, payment_method, manual_utr_id, amount, admin_comment, updated_at
        FROM invoices 
        WHERE status = 'paid' AND admin_comment IS NOT NULL
    ");
    $approved = $stmt->fetchAll();
    
    $insertStmt = $pdo->prepare("
        INSERT INTO invoice_audit_log (invoice_id, user_id, admin_user, action, old_status, new_status, reason, payment_id, payment_method, manual_utr_id, amount, created_at)
        VALUES (?, ?, 'admin (backfill)', 'approved', 'pending_verification', 'paid', ?, ?, ?, ?, ?, ?)
    ");

    $count = 0;
    foreach ($approved as $inv) {
        // Check if already backfilled
        $check = $pdo->prepare("SELECT COUNT(*) FROM invoice_audit_log WHERE invoice_id = ?");
        $check->execute([$inv['id']]);
        if ($check->fetchColumn() > 0) continue;
        
        $insertStmt->execute([
            $inv['id'], $inv['user_id'], $inv['admin_comment'],
            $inv['payment_id'], $inv['payment_method'], $inv['manual_utr_id'],
            $inv['amount'], $inv['updated_at']
        ]);
        $count++;
    }
    echo "[OK] Backfilled $count approved invoices\n";

    // Backfill rejected invoices (status=pending with admin_comment set)
    $stmt = $pdo->query("
        SELECT id, user_id, status, payment_id, payment_method, manual_utr_id, amount, admin_comment, updated_at
        FROM invoices 
        WHERE status = 'pending' AND admin_comment IS NOT NULL AND admin_comment != ''
    ");
    $rejected = $stmt->fetchAll();

    $insertRejectStmt = $pdo->prepare("
        INSERT INTO invoice_audit_log (invoice_id, user_id, admin_user, action, old_status, new_status, reason, payment_id, payment_method, manual_utr_id, amount, created_at)
        VALUES (?, ?, 'admin (backfill)', 'rejected', 'pending_verification', 'pending', ?, ?, ?, ?, ?, ?)
    ");

    $countR = 0;
    foreach ($rejected as $inv) {
        $check = $pdo->prepare("SELECT COUNT(*) FROM invoice_audit_log WHERE invoice_id = ?");
        $check->execute([$inv['id']]);
        if ($check->fetchColumn() > 0) continue;

        $insertRejectStmt->execute([
            $inv['id'], $inv['user_id'], $inv['admin_comment'],
            $inv['payment_id'], $inv['payment_method'], $inv['manual_utr_id'],
            $inv['amount'], $inv['updated_at']
        ]);
        $countR++;
    }
    echo "[OK] Backfilled $countR rejected invoices\n";

    echo "\n=== Migration Complete ===\n";

} catch (Exception $e) {
    echo "[ERROR] " . $e->getMessage() . "\n";
}

echo "</pre>";
?>

<?php
/**
 * Migration: Add missing columns to invoices table
 * Run once on the server, then delete this file.
 */
require_once __DIR__ . '/includes/db.php';
$pdo = getDB();

$migrations = [
    "ALTER TABLE invoices ADD COLUMN manual_utr_id VARCHAR(100) DEFAULT NULL"                  => "manual_utr_id",
    "ALTER TABLE invoices ADD COLUMN manual_payment_screenshot VARCHAR(255) DEFAULT NULL"      => "manual_payment_screenshot",
    "ALTER TABLE invoices ADD COLUMN payment_method ENUM('cashfree','manual') DEFAULT 'cashfree'" => "payment_method",
    "ALTER TABLE invoices ADD COLUMN admin_comment TEXT DEFAULT NULL"                          => "admin_comment",
    "ALTER TABLE invoices ADD COLUMN base_amount DECIMAL(10,2) DEFAULT NULL"                  => "base_amount",
    "ALTER TABLE invoices ADD COLUMN gst_amount DECIMAL(10,2) DEFAULT NULL"                   => "gst_amount",
];

// Get existing columns
$existing = [];
$cols = $pdo->query("SHOW COLUMNS FROM invoices")->fetchAll(PDO::FETCH_ASSOC);
foreach ($cols as $col) {
    $existing[] = $col['Field'];
}

echo "<h2>Invoice Table Migration</h2>";
echo "<p>Existing columns: " . implode(', ', $existing) . "</p>";

$ran = 0;
foreach ($migrations as $sql => $col) {
    if (in_array($col, $existing)) {
        echo "<p style='color:grey'>&#9989; <code>$col</code> already exists — skipped</p>";
        continue;
    }
    try {
        $pdo->exec($sql);
        echo "<p style='color:green'>&#10003; Added <code>$col</code></p>";
        $ran++;
    } catch (PDOException $e) {
        echo "<p style='color:red'>&#10007; Failed <code>$col</code>: " . htmlspecialchars($e->getMessage()) . "</p>";
    }
}

echo "<hr><p><strong>Done. $ran column(s) added.</strong></p>";
echo "<p style='color:red'><strong>Delete this file from the server now!</strong></p>";

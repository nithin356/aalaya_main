<?php
/**
 * One-time migration script — run at aalaya.info/urquery.php
 * Delete this file after running.
 */
require_once 'includes/db.php';

$pdo = getDB();
$results = [];

$queries = [
    'Add pan_verified column to users' =>
        "ALTER TABLE users ADD COLUMN pan_verified TINYINT(1) DEFAULT 0 AFTER pan_number",

    'Backfill pan_verified for existing verified users' =>
        "UPDATE users SET pan_verified = 1 WHERE pan_number IS NOT NULL AND pan_number != '' AND digilocker_verified = 1",

    'Make pan_number nullable in digi_pending_registrations' =>
        "ALTER TABLE digi_pending_registrations MODIFY COLUMN pan_number VARCHAR(20) DEFAULT NULL",
];

foreach ($queries as $label => $sql) {
    try {
        $pdo->exec($sql);
        $results[] = "OK — $label";
    } catch (PDOException $e) {
        // Column/table already exists is fine — treat as success
        if (str_contains($e->getMessage(), 'Duplicate column') || str_contains($e->getMessage(), 'already exists')) {
            $results[] = "SKIPPED (already done) — $label";
        } else {
            $results[] = "ERROR — $label: " . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html>
<head><title>DB Migration</title></head>
<body style="font-family:monospace; padding:40px; background:#1a1a2e; color:#e0e0e0;">
    <h2 style="color:#22c55e;">Migration Results</h2>
    <?php foreach ($results as $r): ?>
        <p><?= htmlspecialchars($r) ?></p>
    <?php endforeach; ?>
    <hr style="border-color:#333; margin-top:30px;">
    <p style="color:#e74c3c; font-weight:bold;">Delete this file (urquery.php) after running!</p>
</body>
</html>

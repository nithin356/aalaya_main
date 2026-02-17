<?php
require_once '../includes/db.php';
$pdo = getDB();

try {
    echo "Checking System Config Schema...\n";

    // 1. Remove duplicates (Keep the one with highest ID)
    // We can't easily do a DELETE JOIN in one go compatible with all SQL versions in simple PDO,
    // so let's do it in steps.
    
    // Find duplicates
    $sql = "SELECT config_key, COUNT(*) as c, MAX(id) as max_id FROM system_config GROUP BY config_key HAVING c > 1";
    $stmt = $pdo->query($sql);
    $duplicates = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (count($duplicates) > 0) {
        echo "Found duplicates for: ";
        foreach ($duplicates as $row) {
            echo $row['config_key'] . " ";
            // Delete all except max_id
            $del = $pdo->prepare("DELETE FROM system_config WHERE config_key = ? AND id != ?");
            $del->execute([$row['config_key'], $row['max_id']]);
        }
        echo "\nDuplicates removed.\n";
    } else {
        echo "No duplicates found.\n";
    }

    // 2. Add Unique Index
    // Check if index exists
    $indexExists = false;
    $indexes = $pdo->query("SHOW INDEX FROM system_config WHERE Key_name = 'config_key_unique'")->fetchAll();
    if (count($indexes) > 0) {
        $indexExists = true;
    }

    if (!$indexExists) {
        $pdo->exec("ALTER TABLE system_config ADD UNIQUE INDEX config_key_unique (config_key)");
        echo "Unique index 'config_key_unique' added.\n";
    } else {
        echo "Unique index already exists.\n";
    }

    echo "Schema fixation complete.\n";

} catch (PDOException $e) {
    die("Fix failed: " . $e->getMessage());
}
?>

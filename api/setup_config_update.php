<?php
require_once '../includes/db.php';
$pdo = getDB();

try {
    echo "Updating System Config...\n";

    // Check if investment_threshold exists
    $stmt = $pdo->prepare("SELECT config_key FROM system_config WHERE config_key = ?");
    $stmt->execute(['investment_threshold']);
    
    if (!$stmt->fetch()) {
        // Insert default value: 124511
        $insert = $pdo->prepare("INSERT INTO system_config (config_key, config_value, description) VALUES (?, ?, ?)");
        $insert->execute(['investment_threshold', '124511', 'Amount required for 1 loyalty point']);
        echo "Inserted 'investment_threshold' = 124511.\n";
    } else {
        echo "'investment_threshold' already exists.\n";
    }

} catch (PDOException $e) {
    die("Setup failed: " . $e->getMessage());
}
?>

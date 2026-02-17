<?php
require_once '../includes/db.php';
$pdo = getDB();

try {
    echo "Updating properties schema...\n";
    
    $queries = [
        "ALTER TABLE properties ADD COLUMN IF NOT EXISTS owner_name VARCHAR(255) AFTER title",
        "ALTER TABLE properties ADD COLUMN IF NOT EXISTS legal_opinion_path VARCHAR(255) AFTER image_path",
        "ALTER TABLE properties ADD COLUMN IF NOT EXISTS evaluation_path VARCHAR(255) AFTER legal_opinion_path"
    ];

    foreach ($queries as $query) {
        $pdo->exec($query);
        echo "Executed: $query\n";
    }

    echo "Schema update completed successfully.\n";

} catch (PDOException $e) {
    die("Update failed: " . $e->getMessage());
}
?>

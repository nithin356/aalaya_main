<?php
require_once '../includes/db.php';
$pdo = getDB();

try {
    echo "Cleaning up corrupted paths...\n";

    // 1. Fix Properties
    $stmt = $pdo->query("UPDATE properties SET image_path = NULL WHERE image_path LIKE '%/'");
    echo "Fixed properties: " . $stmt->rowCount() . "\n";

    // 2. Fix Advertisements
    $stmt = $pdo->query("UPDATE advertisements SET image_path = NULL WHERE image_path LIKE '%/'");
    echo "Fixed advertisements: " . $stmt->rowCount() . "\n";

    // 3. Fix Media
    $stmt = $pdo->query("DELETE FROM media WHERE file_path LIKE '%/'");
    echo "Deleted corrupted media rows: " . $stmt->rowCount() . "\n";

    echo "Cleanup Complete.";

} catch (PDOException $e) {
    die("Cleanup failed: " . $e->getMessage());
}
?>

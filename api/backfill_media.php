<?php
require_once '../includes/db.php';
$pdo = getDB();

try {
    echo "Migrating Property Images...\n";
    $stm = $pdo->query("SELECT id, image_path FROM properties WHERE image_path IS NOT NULL AND image_path != ''");
    while ($row = $stm->fetch()) {
        // Check if already exists in media
        $chk = $pdo->prepare("SELECT id FROM media WHERE entity_type='property' AND entity_id=? AND file_path=?");
        $chk->execute([$row['id'], $row['image_path']]);
        if (!$chk->fetch()) {
            $ins = $pdo->prepare("INSERT INTO media (entity_type, entity_id, file_path, file_type, is_primary) VALUES (?, ?, ?, 'image', 1)");
            $ins->execute(['property', $row['id'], $row['image_path']]);
            echo "Migrated Property #{$row['id']}\n";
        }
    }

    echo "Migrating Advertisement Images...\n";
    $stm = $pdo->query("SELECT id, image_path FROM advertisements WHERE image_path IS NOT NULL AND image_path != ''");
    while ($row = $stm->fetch()) {
        $chk = $pdo->prepare("SELECT id FROM media WHERE entity_type='advertisement' AND entity_id=? AND file_path=?");
        $chk->execute([$row['id'], $row['image_path']]);
        if (!$chk->fetch()) {
            $ins = $pdo->prepare("INSERT INTO media (entity_type, entity_id, file_path, file_type, is_primary) VALUES (?, ?, ?, 'image', 1)");
            $ins->execute(['advertisement', $row['id'], $row['image_path']]);
            echo "Migrated Ad #{$row['id']}\n";
        }
    }

    echo "Migration Complete.";

} catch (PDOException $e) {
    die("Migration failed: " . $e->getMessage());
}
?>

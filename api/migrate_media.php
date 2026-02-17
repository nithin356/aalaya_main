<?php
require_once '../includes/db.php';
$pdo = getDB();

try {
    $sql = "CREATE TABLE IF NOT EXISTS media (
        id INT PRIMARY KEY AUTO_INCREMENT,
        entity_type ENUM('property', 'advertisement') NOT NULL,
        entity_id INT NOT NULL,
        file_path VARCHAR(255) NOT NULL,
        file_type ENUM('image', 'video') NOT NULL,
        is_primary TINYINT(1) DEFAULT 0,
        sort_order INT DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX (entity_type, entity_id)
    )";
    
    $pdo->exec($sql);
    echo "Media table created successfully.\n";

} catch (PDOException $e) {
    die("Migration failed: " . $e->getMessage());
}
?>

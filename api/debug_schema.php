<?php
require_once '../includes/db.php';
$pdo = getDB();
$stmt = $pdo->query("DESCRIBE system_config");
foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $col) {
    echo $col['Field'] . "\n";
}
?>

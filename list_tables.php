<?php
require_once 'includes/db.php';
$pdo = getDB();
$stmt = $pdo->query("SHOW TABLES");
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
?>

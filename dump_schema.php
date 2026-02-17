<?php
require_once 'includes/db.php';
$pdo = getDB();

$tables = ['properties', 'advertisements'];

foreach ($tables as $table) {
    echo "TABLE: $table\n";
    $stmt = $pdo->query("DESCRIBE $table");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($columns as $col) {
        echo $col['Field'] . " | " . $col['Type'] . "\n";
    }
    echo "-------------------\n";
}
?>

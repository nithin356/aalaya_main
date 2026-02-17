<?php
require_once 'includes/db.php';
$pdo = getDB();

function checkTable($table) {
    global $pdo;
    echo "--- Table: $table ---\n";
    try {
        $stmt = $pdo->query("DESCRIBE $table");
        print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
    } catch (Exception $e) {
        echo "Error: " . $e->getMessage() . "\n";
    }
    echo "\n";
}

checkTable('invoices');
checkTable('users');
?>

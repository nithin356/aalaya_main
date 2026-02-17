<?php
require_once '../includes/db.php';
$pdo = getDB();

echo "--- Properties Columns ---\n";
$stmt = $pdo->query("SHOW COLUMNS FROM properties");
while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    echo $row['Field'] . " - " . $row['Type'] . "\n";
}
?>

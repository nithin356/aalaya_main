<?php
require_once __DIR__ . '/../includes/db.php';
$pdo = getDB();

$phone = '9483887537';
$stmt = $pdo->prepare("SELECT id, full_name, phone, aadhaar_number, pan_number, digilocker_verified FROM users WHERE phone = ?");
$stmt->execute([$phone]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

echo "User Data:\n";
print_r($user);
?>

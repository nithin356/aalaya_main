<?php
header('Content-Type: application/json');
session_start();

require_once '../../includes/db.php';

if (!isset($_SESSION['is_logged_in']) || $_SESSION['is_logged_in'] !== true) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$pdo = getDB();
$type = $_GET['type'] ?? 'all';

try {
    $sql = "SELECT i.*, u.full_name, u.email, u.phone 
            FROM invoices i 
            LEFT JOIN users u ON i.user_id = u.id ";

    if ($type === 'registration') {
        $sql .= "WHERE i.description LIKE '%Registration%' ";
    } elseif ($type === 'investment') {
        $sql .= "WHERE i.description LIKE '%Investment%' ";
    }

    $sql .= "ORDER BY i.created_at DESC";
    
    $stmt = $pdo->query($sql);
    $invoices = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode(['success' => true, 'data' => $invoices]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>

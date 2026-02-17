<?php
header('Content-Type: application/json');
require_once '../../includes/session.php';
require_once '../../includes/db.php';

if (!isset($_SESSION['is_logged_in']) || $_SESSION['is_logged_in'] !== true) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized.']);
    exit;
}

$pdo = getDB();

try {
    $sql = "SELECT i.*, u.full_name, u.phone 
            FROM invoices i 
            JOIN users u ON i.user_id = u.id 
            WHERE i.status = 'pending_verification' 
            ORDER BY i.updated_at ASC";
    $stmt = $pdo->query($sql);
    $data = $stmt->fetchAll();

    echo json_encode(['success' => true, 'data' => $data]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?>

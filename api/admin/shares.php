<?php
header('Content-Type: application/json');
require_once '../../includes/session.php'; // Use centralized session
require_once '../../includes/db.php';

// Auth Check
if (!isset($_SESSION['is_logged_in']) || $_SESSION['is_logged_in'] !== true) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$pdo = getDB();

try {
    $sql = "SELECT s.*, u.full_name, u.phone 
            FROM share_transactions s 
            JOIN users u ON s.user_id = u.id 
            ORDER BY s.created_at DESC";
            
    $stmt = $pdo->query($sql);
    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode(['success' => true, 'data' => $data]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
?>

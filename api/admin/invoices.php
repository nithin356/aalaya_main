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
    
    // Get statistics
    $statsSql = "SELECT 
            COUNT(*) as total_count,
            SUM(CASE WHEN status = 'paid' THEN 1 ELSE 0 END) as paid_count,
            SUM(CASE WHEN status IN ('pending', 'pending_verification') THEN 1 ELSE 0 END) as pending_count,
            SUM(CASE WHEN status = 'paid' THEN amount ELSE 0 END) as total_paid_amount
        FROM invoices WHERE 1=1 ";
    
    if ($type === 'registration') {
        $statsSql .= "AND description LIKE '%Registration%' ";
    } elseif ($type === 'investment') {
        $statsSql .= "AND description LIKE '%Investment%' ";
    }
    
    $statsStmt = $pdo->query($statsSql);
    $stats = $statsStmt->fetch(PDO::FETCH_ASSOC);
    
    echo json_encode(['success' => true, 'data' => $invoices, 'stats' => $stats]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>

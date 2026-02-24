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
    // Get pending verifications (sorted by most recent first)
    $sql = "SELECT i.*, u.full_name, u.phone 
            FROM invoices i 
            JOIN users u ON i.user_id = u.id 
            WHERE i.status = 'pending_verification' 
            ORDER BY i.updated_at DESC";
    $stmt = $pdo->query($sql);
    $data = $stmt->fetchAll();

    // Get statistics
    $statsSql = "SELECT 
        (SELECT COUNT(*) FROM invoices WHERE status = 'pending_verification' AND type = 'registration') as pending_registration,
        (SELECT COUNT(*) FROM invoices WHERE status = 'paid' AND type = 'registration') as approved_registration,
        (SELECT COUNT(*) FROM invoices WHERE status = 'pending' AND type = 'registration' AND manual_utr_id IS NOT NULL) as rejected_registration,
        (SELECT SUM(amount) FROM invoices WHERE status = 'pending_verification') as total_pending_amount,
        (SELECT COUNT(*) FROM invoices WHERE status = 'pending_verification') as total_pending
    ";
    $statsStmt = $pdo->query($statsSql);
    $stats = $statsStmt->fetch();

    echo json_encode(['success' => true, 'data' => $data, 'stats' => $stats]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?>

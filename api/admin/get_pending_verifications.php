<?php
header('Content-Type: application/json');
require_once '../../includes/session.php';
require_once '../../includes/db.php';

if (!isset($_SESSION['is_logged_in']) || $_SESSION['is_logged_in'] !== true) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized.']);
    exit;
}

$pdo = getDB();
$filter = $_GET['filter'] ?? 'pending_verification';

try {
    // Build the query based on filter
    $params = [];
    if ($filter === 'all') {
        $sql = "SELECT i.*, u.full_name, u.phone, u.email 
                FROM invoices i 
                JOIN users u ON i.user_id = u.id 
                ORDER BY i.updated_at DESC";
    } elseif ($filter === 'pending') {
        // Show invoices that were rejected (pending with admin_comment) or never submitted
        $sql = "SELECT i.*, u.full_name, u.phone, u.email 
                FROM invoices i 
                JOIN users u ON i.user_id = u.id 
                WHERE i.status = 'pending' 
                ORDER BY i.updated_at DESC";
    } elseif ($filter === 'paid') {
        $sql = "SELECT i.*, u.full_name, u.phone, u.email 
                FROM invoices i 
                JOIN users u ON i.user_id = u.id 
                WHERE i.status = 'paid' 
                ORDER BY i.updated_at DESC";
    } elseif ($filter === 'cashfree_pending') {
        // Invoices with a Cashfree payment_id but still pending — candidates for reconciliation
        $sql = "SELECT i.*, u.full_name, u.phone, u.email 
                FROM invoices i 
                JOIN users u ON i.user_id = u.id 
                WHERE i.status = 'pending' AND i.payment_method = 'cashfree' AND i.payment_id IS NOT NULL
                ORDER BY i.updated_at DESC";
    } else {
        // Default: pending_verification
        $sql = "SELECT i.*, u.full_name, u.phone, u.email
                FROM invoices i
                JOIN users u ON i.user_id = u.id
                WHERE i.status = 'pending_verification'
                ORDER BY i.updated_at DESC";
    }

    $stmt = $pdo->query($sql);
    $data = $stmt->fetchAll();

    // Get stats
    $statsStmt = $pdo->query("
        SELECT 
            COUNT(*) as total,
            SUM(CASE WHEN status = 'pending_verification' THEN 1 ELSE 0 END) as pending,
            SUM(CASE WHEN status = 'paid' THEN 1 ELSE 0 END) as approved,
            SUM(CASE WHEN status = 'pending' AND admin_comment IS NOT NULL AND admin_comment != '' THEN 1 ELSE 0 END) as rejected,
            SUM(CASE WHEN status = 'pending' AND payment_method = 'cashfree' AND payment_id IS NOT NULL THEN 1 ELSE 0 END) as cashfree_pending
        FROM invoices
    ");
    $stats = $statsStmt->fetch();

    echo json_encode([
        'success' => true, 
        'data' => $data,
        'stats' => [
            'total' => intval($stats['total']),
            'pending' => intval($stats['pending']),
            'approved' => intval($stats['approved']),
            'rejected' => intval($stats['rejected']),
            'cashfree_pending' => intval($stats['cashfree_pending'])
        ]
    ]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?>

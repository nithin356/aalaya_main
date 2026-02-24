<?php
/**
 * Admin API: Get invoice audit log
 * 
 * GET ?invoice_id=33  → logs for specific invoice
 * GET ?action=rejected → all rejection logs
 * GET (no params)      → all audit logs (latest first)
 */
header('Content-Type: application/json');
require_once '../../includes/session.php';
require_once '../../includes/db.php';

if (!isset($_SESSION['is_logged_in']) || $_SESSION['is_logged_in'] !== true) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized.']);
    exit;
}

$pdo = getDB();
$invoice_id = intval($_GET['invoice_id'] ?? 0);
$action_filter = $_GET['action'] ?? '';
$limit = intval($_GET['limit'] ?? 100);

try {
    $params = [];
    $conditions = [];

    if ($invoice_id) {
        $conditions[] = "l.invoice_id = ?";
        $params[] = $invoice_id;
    }

    if ($action_filter && in_array($action_filter, ['approved', 'rejected', 'reconciled', 'webhook_confirmed', 'status_change'])) {
        $conditions[] = "l.action = ?";
        $params[] = $action_filter;
    }

    $where = count($conditions) > 0 ? 'WHERE ' . implode(' AND ', $conditions) : '';

    $sql = "
        SELECT l.*, u.full_name as user_name, u.phone as user_phone
        FROM invoice_audit_log l
        LEFT JOIN users u ON l.user_id = u.id
        $where
        ORDER BY l.created_at DESC
        LIMIT $limit
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $data = $stmt->fetchAll();

    // Get summary stats
    $statsSql = "
        SELECT 
            COUNT(*) as total,
            SUM(CASE WHEN action = 'approved' THEN 1 ELSE 0 END) as approved_count,
            SUM(CASE WHEN action = 'rejected' THEN 1 ELSE 0 END) as rejected_count,
            SUM(CASE WHEN action = 'reconciled' THEN 1 ELSE 0 END) as reconciled_count,
            SUM(CASE WHEN action = 'webhook_confirmed' THEN 1 ELSE 0 END) as webhook_count
        FROM invoice_audit_log
    ";
    $statsStmt = $pdo->query($statsSql);
    $stats = $statsStmt->fetch();

    echo json_encode([
        'success' => true,
        'data' => $data,
        'stats' => [
            'total' => intval($stats['total']),
            'approved' => intval($stats['approved_count']),
            'rejected' => intval($stats['rejected_count']),
            'reconciled' => intval($stats['reconciled_count']),
            'webhook' => intval($stats['webhook_count'])
        ]
    ]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?>

<?php
header('Content-Type: application/json');
require_once '../../includes/session.php';
require_once '../../includes/db.php';

if (!isset($_SESSION['is_logged_in']) || $_SESSION['is_logged_in'] !== true) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$pdo = getDB();
$method = $_SERVER['REQUEST_METHOD'];

try {
    if ($method === 'GET') {
        // Get all users with their registration invoice status
        $sql = "SELECT 
                    u.id,
                    u.full_name,
                    u.email,
                    u.phone,
                    u.referral_code,
                    u.referred_by,
                    u.total_points,
                    u.total_shares,
                    u.total_investment_amount,
                    u.is_banned,
                    u.is_deleted,
                    u.created_at,
                    (SELECT ref.full_name FROM users ref WHERE ref.id = u.referred_by) as referred_by_name,
                    i.id as invoice_id,
                    i.amount as invoice_amount,
                    i.status as payment_status,
                    i.payment_method,
                    i.payment_id,
                    i.manual_utr_id,
                    i.created_at as invoice_date,
                    i.updated_at as payment_date
                FROM users u
                LEFT JOIN invoices i ON i.id = (
                    SELECT id FROM invoices 
                    WHERE user_id = u.id 
                    AND description IN ('Registration Fee', 'Subscription Fee')
                    ORDER BY id DESC LIMIT 1
                )
                WHERE u.is_deleted = 0
                ORDER BY u.created_at DESC";

        $stmt = $pdo->query($sql);
        $users = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Summarize counts
        $stats = [
            'total' => count($users),
            'paid' => 0,
            'pending' => 0,
            'pending_verification' => 0,
            'no_invoice' => 0,
            'banned' => 0
        ];

        foreach ($users as $u) {
            if ($u['is_banned']) $stats['banned']++;
            if ($u['payment_status'] === 'paid') $stats['paid']++;
            elseif ($u['payment_status'] === 'pending_verification') $stats['pending_verification']++;
            elseif ($u['payment_status'] === 'pending') $stats['pending']++;
            else $stats['no_invoice']++;
        }

        echo json_encode(['success' => true, 'data' => $users, 'stats' => $stats]);

    } elseif ($method === 'DELETE') {
        // Hard delete a user and all related data
        $id = intval($_GET['id'] ?? 0);
        if (!$id) {
            echo json_encode(['success' => false, 'message' => 'User ID is required.']);
            exit;
        }

        // Safety: Don't delete users who have paid
        $checkStmt = $pdo->prepare("
            SELECT COUNT(*) FROM invoices 
            WHERE user_id = ? AND status = 'paid' 
            AND description IN ('Registration Fee', 'Subscription Fee')
        ");
        $checkStmt->execute([$id]);
        $hasPaid = $checkStmt->fetchColumn();

        if ($hasPaid > 0) {
            echo json_encode(['success' => false, 'message' => 'Cannot delete a user who has paid. Use soft-delete (ban) instead.']);
            exit;
        }

        $pdo->beginTransaction();

        // Delete in order to respect foreign keys
        $pdo->prepare("DELETE FROM referral_transactions WHERE user_id = ? OR referred_user_id = ?")->execute([$id, $id]);
        $pdo->prepare("DELETE FROM share_transactions WHERE user_id = ?")->execute([$id]);
        $pdo->prepare("DELETE FROM investments WHERE user_id = ?")->execute([$id]);
        $pdo->prepare("DELETE FROM invoices WHERE user_id = ?")->execute([$id]);
        $pdo->prepare("DELETE FROM bids WHERE user_id = ?")->execute([$id]);
        $pdo->prepare("DELETE FROM enquiries WHERE user_id = ?")->execute([$id]);
        
        // Clear referred_by references from other users
        $pdo->prepare("UPDATE users SET referred_by = NULL WHERE referred_by = ?")->execute([$id]);
        
        // Delete the user
        $pdo->prepare("DELETE FROM users WHERE id = ?")->execute([$id]);

        $pdo->commit();

        echo json_encode(['success' => true, 'message' => "User #$id permanently deleted with all related data."]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Method not allowed.']);
    }
} catch (Exception $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
?>

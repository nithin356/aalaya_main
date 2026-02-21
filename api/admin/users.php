<?php
header('Content-Type: application/json');
require_once '../../includes/session.php';

require_once '../../includes/db.php';

// Authentication Check
if (!isset($_SESSION['is_logged_in']) || $_SESSION['is_logged_in'] !== true) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access.']);
    exit;
}

$method = $_SERVER['REQUEST_METHOD'];
$pdo = getDB();

try {
    switch ($method) {
        case 'GET':
            // List all users with their latest registration/subscription payment status
            $sql = "SELECT 
                        u.id, 
                        u.full_name, 
                        u.email, 
                        u.phone, 
                        u.referral_code, 
                        u.aadhaar_number, 
                        u.pan_number, 
                        u.total_points, 
                        u.total_shares, 
                        u.is_banned, 
                        u.created_at,
                        (SELECT status FROM invoices 
                         WHERE user_id = u.id 
                         AND description IN ('Registration Fee', 'Subscription Fee') 
                         ORDER BY id DESC LIMIT 1) as payment_status
                    FROM users u 
                    WHERE u.is_deleted = 0 
                    ORDER BY u.created_at DESC";
            
            $stmt = $pdo->query($sql);
            $users = $stmt->fetchAll();
            echo json_encode(['success' => true, 'data' => $users]);
            break;

        case 'PUT':
            // Handle actions like Ban/Unban
            $input = json_decode(file_get_contents('php://input'), true);
            $action = $input['action'] ?? '';
            $id = $input['id'] ?? 0;

            if ($action === 'toggle_ban' && $id) {
                $stmt = $pdo->prepare("UPDATE users SET is_banned = 1 - is_banned WHERE id = ?");
                $stmt->execute([$id]);
                echo json_encode(['success' => true, 'message' => 'User status updated.']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Invalid action or ID.']);
            }
            break;

        case 'DELETE':
            // Soft delete
            $id = $_GET['id'] ?? 0;
            if ($id) {
                $stmt = $pdo->prepare("UPDATE users SET is_deleted = 1 WHERE id = ?");
                $stmt->execute([$id]);
                echo json_encode(['success' => true, 'message' => 'User deleted successfully.']);
            } else {
                echo json_encode(['success' => false, 'message' => 'ID is required.']);
            }
            break;

        default:
            echo json_encode(['success' => false, 'message' => 'Method not allowed.']);
            break;
    }
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?>

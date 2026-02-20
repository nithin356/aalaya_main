<?php
header('Content-Type: application/json');
session_start();

require_once '../../includes/db.php';

if (!isset($_SESSION['is_logged_in']) || $_SESSION['is_logged_in'] !== true) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access.']);
    exit;
}

$method = $_SERVER['REQUEST_METHOD'];
$pdo = getDB();

try {
    switch ($method) {
        case 'GET':
            // Fetch all enquiries with user details
            $stmt = $pdo->query("
                SELECT e.*, u.full_name as user_name, u.email as user_email
                FROM enquiries e
                LEFT JOIN users u ON e.user_id = u.id
                ORDER BY e.created_at DESC
            ");
            $enquiries = $stmt->fetchAll();
            echo json_encode(['success' => true, 'data' => $enquiries]);
            break;

        case 'PUT':
            // Update enquiry status
            $input = json_decode(file_get_contents('php://input'), true);
            $id = $input['id'] ?? 0;
            $status = $input['status'] ?? '';

            if ($id && $status) {
                $stmt = $pdo->prepare("UPDATE enquiries SET status = ? WHERE id = ?");
                $stmt->execute([$status, $id]);
                echo json_encode(['success' => true, 'message' => 'Status updated.']);
            }
            break;

        default:
            echo json_encode(['success' => false, 'message' => 'Method not allowed.']);
            break;
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>

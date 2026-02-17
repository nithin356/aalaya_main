<?php
header('Content-Type: application/json');
session_start();

require_once '../../includes/db.php';

// Authentication Check
if (!isset($_SESSION['is_logged_in']) || $_SESSION['is_logged_in'] !== true) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$pdo = getDB();
$method = $_SERVER['REQUEST_METHOD'];

try {
    switch ($method) {
        case 'GET':
            // Fetch all bids with user and property info
            $stmt = $pdo->query("
                SELECT b.*, u.full_name as user_name, u.phone as user_phone, p.title as property_title, p.location as property_location
                FROM bids b
                JOIN users u ON b.user_id = u.id
                JOIN properties p ON b.property_id = p.id
                ORDER BY b.created_at DESC
            ");
            $bids = $stmt->fetchAll(PDO::FETCH_ASSOC);
            echo json_encode(['success' => true, 'data' => $bids]);
            break;

        case 'PUT':
            // Update bid status
            $input = json_decode(file_get_contents('php://input'), true);
            $bid_id = $input['bid_id'] ?? 0;
            $status = $input['status'] ?? '';

            if (!$bid_id || !in_array($status, ['active', 'accepted', 'rejected', 'withdrawn'])) {
                throw new Exception("Invalid bid ID or status.");
            }

            $stmt = $pdo->prepare("UPDATE bids SET status = ? WHERE id = ?");
            $stmt->execute([$status, $bid_id]);

            echo json_encode(['success' => true, 'message' => "Bid status updated to $status."]);
            break;

        default:
            echo json_encode(['success' => false, 'message' => 'Method not allowed.']);
            break;
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>

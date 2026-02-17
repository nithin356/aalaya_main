<?php
header('Content-Type: application/json');
require_once '../../includes/session.php';
require_once '../../includes/db.php';

// Auth Check
if (!isset($_SESSION['is_logged_in']) || $_SESSION['is_logged_in'] !== true) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$pdo = getDB();

try {
    $sql = "SELECT t.*, 
            u.full_name as user_name, u.referral_code as user_code,
            r.full_name as referred_user_name, r.referral_code as referred_user_code
            FROM referral_transactions t 
            JOIN users u ON t.user_id = u.id 
            LEFT JOIN users r ON t.referred_user_id = r.id AND t.user_id != t.referred_user_id
            ORDER BY t.created_at DESC";
            
    $stmt = $pdo->query($sql);
    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode(['success' => true, 'data' => $data]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
?>

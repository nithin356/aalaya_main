<?php
header('Content-Type: application/json');
session_start();

require_once '../../includes/db.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Login required to send enquiries.']);
    exit;
}

$method = $_SERVER['REQUEST_METHOD'];
$pdo = getDB();

if ($method === 'POST') {
    try {
        $user_id = $_SESSION['user_id'];
        $type = $_POST['type'] ?? 'property'; // 'property' or 'advertisement'
        $reference_id = $_POST['reference_id'] ?? 0;
        $subject = $_POST['subject'] ?? 'New Enquiry';
        $message = $_POST['message'] ?? 'User expressed interest.';

        if (!$reference_id) {
            echo json_encode(['success' => false, 'message' => 'Invalid reference ID.']);
            exit;
        }

        // Check for duplicate enquiry
        $check_stmt = $pdo->prepare("SELECT id FROM enquiries WHERE user_id = ? AND enquiry_type = ? AND reference_id = ? AND status != 'closed'");
        $check_stmt->execute([$user_id, $type, $reference_id]);
        if ($check_stmt->fetch()) {
            echo json_encode(['success' => false, 'message' => 'You have already sent an enquiry for this listing. Our team will contact you shortly.']);
            exit;
        }

        $sql = "INSERT INTO enquiries (user_id, enquiry_type, reference_id, subject, message, status) 
                VALUES (?, ?, ?, ?, ?, 'pending')";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$user_id, $type, $reference_id, $subject, $message]);

        echo json_encode(['success' => true, 'message' => 'Enquiry sent successfully!']);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Method not allowed.']);
}
?>

<?php
header('Content-Type: application/json');
require_once '../../includes/session.php';
require_once '../../includes/db.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized.']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Method not allowed.']);
    exit;
}

$pdo = getDB();
$invoice_id = $_POST['invoice_id'] ?? 0;
$utr_id = trim($_POST['utr_id'] ?? '');

if (!$invoice_id) {
    echo json_encode(['success' => false, 'message' => 'Invoice ID is required.']);
    exit;
}

try {
    // 1. Verify invoice belongs to user and is pending
    $stmt = $pdo->prepare("SELECT id FROM invoices WHERE id = ? AND user_id = ? AND status = 'pending'");
    $stmt->execute([$invoice_id, $_SESSION['user_id']]);
    $invoice = $stmt->fetch();

    if (!$invoice) {
        echo json_encode(['success' => false, 'message' => 'Invalid or already processed invoice.']);
        exit;
    }

    // 2. Handle File Upload (Optional)
    $screenshot_path = null;
    if (isset($_FILES['payment_screenshot']) && $_FILES['payment_screenshot']['error'] === UPLOAD_ERR_OK) {
        $upload_dir = '../../assets/uploads/payments/';
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }

        $file_ext = strtolower(pathinfo($_FILES['payment_screenshot']['name'], PATHINFO_EXTENSION));
        $allowed_exts = ['jpg', 'jpeg', 'png'];

        if (!in_array($file_ext, $allowed_exts)) {
            throw new Exception("Invalid file type. Only JPG and PNG are allowed.");
        }

        $file_name = 'pay_' . $invoice_id . '_' . time() . '.' . $file_ext;
        $target_file = $upload_dir . $file_name;

        if (move_uploaded_file($_FILES['payment_screenshot']['tmp_name'], $target_file)) {
            $screenshot_path = 'assets/uploads/payments/' . $file_name;
        } else {
            throw new Exception("Failed to save uploaded image.");
        }
    }

    // 3. Update invoice with UTR, Screenshot and change status
    $sql = "UPDATE invoices SET 
            manual_utr_id = ?, 
            manual_payment_screenshot = ?,
            payment_method = 'manual', 
            status = 'pending_verification',
            updated_at = NOW()
            WHERE id = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$utr_id, $screenshot_path, $invoice_id]);

    echo json_encode(['success' => true, 'message' => 'Payment details submitted for verification.']);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?>

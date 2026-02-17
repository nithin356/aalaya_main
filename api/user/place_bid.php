<?php
header('Content-Type: application/json');
session_start();

require_once '../../includes/db.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$pdo = getDB();
$user_id = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Method not allowed.']);
    exit;
}

try {
    $property_id = $_POST['property_id'] ?? 0;
    $bid_amount = floatval($_POST['bid_amount'] ?? 0);

    if (!$property_id || $bid_amount <= 0) {
        throw new Exception("Invalid Property or Bid Amount.");
    }

    // Optional: Get Property Details to check if it exists and price
    $stmt = $pdo->prepare("SELECT price FROM properties WHERE id = ?");
    $stmt->execute([$property_id]);
    $property = $stmt->fetch();
    
    if (!$property) {
        throw new Exception("Property not found.");
    }

    // Check if user already has an active bid
    $stmt = $pdo->prepare("SELECT id, bid_amount FROM bids WHERE user_id = ? AND property_id = ? AND status = 'active'");
    $stmt->execute([$user_id, $property_id]);
    $existingBid = $stmt->fetch();

    if ($existingBid) {
        if ($bid_amount <= $existingBid['bid_amount']) {
            throw new Exception("New bid must be higher than your previous bid of ₹" . number_format($existingBid['bid_amount'], 2));
        }
        // Update existing bid
        $stmt = $pdo->prepare("UPDATE bids SET bid_amount = ?, created_at = NOW() WHERE id = ?");
        $stmt->execute([$bid_amount, $existingBid['id']]);
        $message = "Your bid has been updated successfully!";
    } else {
        // Insert new bid
        $stmt = $pdo->prepare("INSERT INTO bids (user_id, property_id, bid_amount) VALUES (?, ?, ?)");
        $stmt->execute([$user_id, $property_id, $bid_amount]);
        $message = "Your bid has been placed successfully!";
    }

    echo json_encode(['success' => true, 'message' => $message]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>

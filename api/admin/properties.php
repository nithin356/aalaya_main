<?php
// Prevent any spurious output
ob_start();

// Catch Fatal Errors Early
register_shutdown_function(function() {
    $error = error_get_last();
    if ($error && ($error['type'] === E_ERROR || $error['type'] === E_PARSE || $error['type'] === E_CORE_ERROR)) {
        ob_end_clean(); // Discard HTML
        echo json_encode(['success' => false, 'message' => 'Fatal Error: ' . $error['message'] . ' in ' . $error['file'] . ':' . $error['line']]);
        exit;
    }
});

ini_set('display_errors', 0);
error_reporting(E_ALL);

header('Content-Type: application/json');
session_start();

require_once '../../includes/db.php';
require_once '../../includes/functions.php';

// Check if POST size exceeded limit
if (empty($_FILES) && empty($_POST) && isset($_SERVER['CONTENT_LENGTH']) && $_SERVER['CONTENT_LENGTH'] > 0) {
    ob_end_clean();
    echo json_encode(['success' => false, 'message' => 'Upload exceeds limits (post_max_size). Check PHP config.']);
    exit;
}

// Catch Fatal Errors


// Authentication Check
$method = $_SERVER['REQUEST_METHOD'];
if ($method === 'GET') {
    // Users and Admins can view
    if (!isset($_SESSION['user_id']) && (!isset($_SESSION['is_logged_in']) || $_SESSION['is_logged_in'] !== true)) {
        echo json_encode(['success' => false, 'message' => 'Login required.']);
        exit;
    }
} else {
    // Only admins can modify
    if (!isset($_SESSION['is_logged_in']) || $_SESSION['is_logged_in'] !== true) {
        echo json_encode(['success' => false, 'message' => 'Admin access required.']);
        exit;
    }
}


$method = $_SERVER['REQUEST_METHOD'];
$pdo = getDB();
$config = parse_ini_file('../../config/config.ini', true);

try {
    switch ($method) {
        case 'GET':
            $stmt = $pdo->query("SELECT * FROM properties ORDER BY created_at DESC");
            $properties = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Attach Media
            foreach ($properties as &$prop) {
                $mStmt = $pdo->prepare("SELECT * FROM media WHERE entity_type='property' AND entity_id=? ORDER BY is_primary DESC, sort_order ASC");
                $mStmt->execute([$prop['id']]);
                $prop['media'] = $mStmt->fetchAll(PDO::FETCH_ASSOC);

                // Attach Highest Bid
                $bStmt = $pdo->prepare("SELECT MAX(bid_amount) as highest_bid FROM bids WHERE property_id = ? AND status = 'active'");
                $bStmt->execute([$prop['id']]);
                $bidResult = $bStmt->fetch(PDO::FETCH_ASSOC);
                $prop['highest_bid'] = $bidResult['highest_bid'] ?? null;
            }
            unset($prop);

            echo json_encode(['success' => true, 'data' => $properties]);
            break;

        case 'POST':
            $admin_id = $_SESSION['admin_id'];
            $property_id = $_POST['property_id'] ?? null;
            
            // Basic Validation
            if (empty($_POST['title'])) {
                echo json_encode(['success' => false, 'message' => 'Title is required.']);
                exit;
            }

            $pdo->beginTransaction();
            try {
                if ($property_id) {
                    // Update existing
                    $sql = "UPDATE properties SET title = ?, owner_name = ?, description = ?, property_type = ?, price = ?, location = ?, area = ?, area_unit = ?, bedrooms = ?, bathrooms = ?, status = ? WHERE id = ?";
                    $stmt = $pdo->prepare($sql);
                    $stmt->execute([
                        $_POST['title'], $_POST['owner_name'] ?? '', $_POST['description'] ?? '', $_POST['property_type'] ?? 'residential', $_POST['price'], 
                        $_POST['location'] ?? '', $_POST['area'] ?? 0, $_POST['area_unit'] ?? 'sqft', 
                        $_POST['bedrooms'] ?? 0, $_POST['bathrooms'] ?? 0, 
                        $_POST['status'] ?? 'available', $property_id
                    ]);
                } else {
                    // Insert New
                    $sql = "INSERT INTO properties (title, owner_name, description, property_type, price, location, area, area_unit, bedrooms, bathrooms, status, created_by) 
                            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
                    $stmt = $pdo->prepare($sql);
                    $stmt->execute([
                        $_POST['title'], $_POST['owner_name'] ?? '', $_POST['description'] ?? '', $_POST['property_type'] ?? 'residential', $_POST['price'], 
                        $_POST['location'] ?? '', $_POST['area'] ?? 0, $_POST['area_unit'] ?? 'sqft', 
                        $_POST['bedrooms'] ?? 0, $_POST['bathrooms'] ?? 0, 
                        $_POST['status'] ?? 'available', $admin_id
                    ]);
                    $property_id = $pdo->lastInsertId();
                }

                // Handle Document Uploads (Legal & Evaluation)
                $doc_dir = '../../assets/uploads/documents/';
                if (!is_dir($doc_dir)) mkdir($doc_dir, 0777, true);

                $docs = ['legal_opinion', 'evaluation'];
                foreach ($docs as $doc_key) {
                    if (isset($_FILES[$doc_key]) && $_FILES[$doc_key]['error'] === UPLOAD_ERR_OK) {
                        $ext = pathinfo($_FILES[$doc_key]['name'], PATHINFO_EXTENSION);
                        $target_name = $doc_key . '_' . $property_id . '_' . time() . '.' . $ext;
                        $target_path = $doc_dir . $target_name;
                        
                        if (move_uploaded_file($_FILES[$doc_key]['tmp_name'], $target_path)) {
                            $final_path = 'assets/uploads/documents/' . $target_name;
                            $pdo->prepare("UPDATE properties SET {$doc_key}_path = ? WHERE id = ?")->execute([$final_path, $property_id]);
                        }
                    }
                }

                // Handle Property Media (Multi-File)
                $primary_image = null;
                $upload_dir = '../../assets/uploads/properties/';
                if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);

                if (isset($_FILES['property_images'])) {
                    $files = $_FILES['property_images'];
                    $count = is_array($files['name']) ? count($files['name']) : 0;

                    for ($i = 0; $i < $count; $i++) {
                        if ($files['error'][$i] === UPLOAD_ERR_OK) {
                            $ext = pathinfo($files['name'][$i], PATHINFO_EXTENSION);
                            $target_name = 'prop_' . $property_id . '_' . time() . '_' . $i . '.' . $ext;
                            $target_path = $upload_dir . $target_name;
                            
                            $mime = mime_content_type($files['tmp_name'][$i]);
                            $file_type = (strpos($mime, 'video') !== false) ? 'video' : 'image';

                            if ($file_type === 'image') {
                                $resized_name = resizeImage($files['tmp_name'][$i], $target_path, 800, 600);
                                if ($resized_name) {
                                    $final_path = 'assets/uploads/properties/' . $resized_name;
                                } else {
                                    continue;
                                }
                            } else {
                                if (move_uploaded_file($files['tmp_name'][$i], $target_path)) {
                                    $final_path = 'assets/uploads/properties/' . $target_name;
                                } else {
                                    continue;
                                }
                            }

                            // Insert into Media
                            $mSql = "INSERT INTO media (entity_type, entity_id, file_path, file_type, is_primary) VALUES ('property', ?, ?, ?, 0)";
                            $pdo->prepare($mSql)->execute([$property_id, $final_path, $file_type]);

                            if (!$primary_image) {
                                // Check if property already has a primary image
                                $checkPrime = $pdo->prepare("SELECT COUNT(*) FROM properties WHERE id=? AND image_path IS NOT NULL");
                                $checkPrime->execute([$property_id]);
                                if ($checkPrime->fetchColumn() == 0) {
                                    $primary_image = $final_path;
                                    $pdo->prepare("UPDATE media SET is_primary=1 WHERE entity_type='property' AND entity_id=? AND file_path=?")->execute([$property_id, $final_path]);
                                }
                            }
                        }
                    }
                }

                if ($primary_image) {
                    $pdo->prepare("UPDATE properties SET image_path = ? WHERE id = ?")->execute([$primary_image, $property_id]);
                }

                $pdo->commit();
                ob_clean();
                echo json_encode(['success' => true, 'message' => 'Property saved successfully.']);

            } catch (Exception $e) {
                if ($pdo->inTransaction()) $pdo->rollBack();
                ob_clean();
                echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
            }
            break;

        case 'DELETE':
            $id = $_GET['id'] ?? 0;
            if ($id) {
                // Delete Media Files logic should be here ideally (cleanup files)
                $pdo->prepare("DELETE FROM media WHERE entity_type='property' AND entity_id=?")->execute([$id]);
                
                $stmt = $pdo->prepare("DELETE FROM properties WHERE id = ?");
                $stmt->execute([$id]);
                echo json_encode(['success' => true, 'message' => 'Property deleted.']);
            }
            break;

        default:
            echo json_encode(['success' => false, 'message' => 'Method not allowed.']);
            break;
    }
} catch (PDOException $e) {
    if ($pdo && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?>

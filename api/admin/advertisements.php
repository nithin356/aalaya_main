<?php
// Prevent any spurious output
ob_start();

// Catch Fatal Errors Early
register_shutdown_function(function() {
    $error = error_get_last();
    if ($error && ($error['type'] === E_ERROR || $error['type'] === E_PARSE || $error['type'] === E_CORE_ERROR)) {
        ob_end_clean(); 
        echo json_encode(['success' => false, 'message' => 'Fatal Error: ' . $error['message'] . ' in ' . $error['file'] . ':' . $error['line']]);
        exit;
    }
});

ini_set('display_errors', 0);
error_reporting(E_ALL);

header('Content-Type: application/json');
require_once '../../includes/session.php';

require_once '../../includes/db.php';
require_once '../../includes/functions.php';

if (empty($_FILES) && empty($_POST) && isset($_SERVER['CONTENT_LENGTH']) && $_SERVER['CONTENT_LENGTH'] > 0) {
    ob_end_clean();
    echo json_encode(['success' => false, 'message' => 'Upload exceeds limits (post_max_size). Check PHP config.']);
    exit;
}

// Catch Fatal Errors


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

try {
    switch ($method) {
        case 'GET':
            $id = $_GET['id'] ?? null;
            if ($id) {
                $stmt = $pdo->prepare("SELECT * FROM advertisements WHERE id = ?");
                $stmt->execute([$id]);
                $ad = $stmt->fetch(PDO::FETCH_ASSOC);
                if (!$ad) {
                    echo json_encode(['success' => false, 'message' => 'Ad not found.']);
                    exit;
                }
                // Attach Media
                $mStmt = $pdo->prepare("SELECT * FROM media WHERE entity_type='advertisement' AND entity_id=? ORDER BY is_primary DESC");
                $mStmt->execute([$id]);
                $ad['media'] = $mStmt->fetchAll(PDO::FETCH_ASSOC);
                echo json_encode(['success' => true, 'data' => $ad]);
            } else {
                $stmt = $pdo->query("SELECT * FROM advertisements ORDER BY created_at DESC");
                $ads = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                // Attach Media
                foreach ($ads as &$ad) {
                    $mStmt = $pdo->prepare("SELECT * FROM media WHERE entity_type='advertisement' AND entity_id=? ORDER BY is_primary DESC");
                    $mStmt->execute([$ad['id']]);
                    $ad['media'] = $mStmt->fetchAll(PDO::FETCH_ASSOC);
                }
                unset($ad);
                echo json_encode(['success' => true, 'data' => $ads]);
            }
            break;

        case 'POST':
            $admin_id = $_SESSION['admin_id'];
            $ad_id = $_POST['id'] ?? null;

            if (empty($_POST['title'])) {
                echo json_encode(['success' => false, 'message' => 'Title is required.']);
                exit;
            }

            $pdo->beginTransaction();
            try {
                if ($ad_id) {
                    // 1. Update existing Ad
                    $sql = "UPDATE advertisements SET title = ?, company_name = ?, ad_type = ?, start_date = ?, end_date = ? WHERE id = ?";
                    $stmt = $pdo->prepare($sql);
                    $stmt->execute([
                        $_POST['title'], $_POST['company_name'] ?? '', $_POST['ad_type'] ?? 'standard', 
                        $_POST['start_date'] ?? null, $_POST['end_date'] ?? null, $ad_id
                    ]);
                } else {
                    // 1. Insert New Ad
                    $sql = "INSERT INTO advertisements (title, company_name, ad_type, start_date, end_date, created_by) 
                            VALUES (?, ?, ?, ?, ?, ?)";
                    $stmt = $pdo->prepare($sql);
                    $stmt->execute([
                        $_POST['title'], $_POST['company_name'] ?? '', $_POST['ad_type'] ?? 'standard', 
                        $_POST['start_date'] ?? null, $_POST['end_date'] ?? null, $admin_id
                    ]);
                    $ad_id = $pdo->lastInsertId();
                }

                // 2. Handle Multi-File Upload
                $primary_image = null;
                $upload_dir = '../../assets/uploads/advertisements/';
                if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);

                if (isset($_FILES['ad_images']) && count($_FILES['ad_images']['name']) > 0 && $_FILES['ad_images']['name'][0] !== '') {
                    // Optional: If new images uploaded, decide whether to clear old media or just append
                    // For now, let's just append or follow the logic of the specific app. 
                    // Usually, if new images are uploaded in an 'edit', users might expect old ones to be replaced or appended.
                    // Given the current logic, let's append but allow marking a new primary.
                    
                    $files = $_FILES['ad_images'];
                    $count = count($files['name']);

                    for ($i = 0; $i < $count; $i++) {
                        if ($files['error'][$i] === UPLOAD_ERR_OK) {
                            $ext = pathinfo($files['name'][$i], PATHINFO_EXTENSION);
                            $target_name = 'ad_' . $ad_id . '_' . time() . '_' . $i . '.' . $ext;
                            $target_path = $upload_dir . $target_name;
                            
                            $mime = mime_content_type($files['tmp_name'][$i]);
                            $file_type = (strpos($mime, 'video') !== false) ? 'video' : 'image';

                            if ($file_type === 'image') {
                                // Resize (1200x400 for banner)
                                $resized_name = resizeImage($files['tmp_name'][$i], $target_path, 1200, 400);
                                if ($resized_name) {
                                    $final_path = 'assets/uploads/advertisements/' . $resized_name;
                                } else {
                                    continue; 
                                }
                            } else {
                                if (move_uploaded_file($files['tmp_name'][$i], $target_path)) {
                                    $final_path = 'assets/uploads/advertisements/' . $target_name;
                                } else {
                                    continue;
                                }
                            }

                            // Insert into Media
                            $is_prime = ($i === 0 && !$ad_id) ? 1 : 0; // Only force primary on new ads if first file
                            $mSql = "INSERT INTO media (entity_type, entity_id, file_path, file_type, is_primary) VALUES ('advertisement', ?, ?, ?, ?)";
                            $pdo->prepare($mSql)->execute([$ad_id, $final_path, $file_type, $is_prime]);

                            if ($is_prime || ($i === 0 && $ad_id)) $primary_image = $final_path;
                        }
                    }
                }

                // 3. Update Primary Image if a new one was uploaded as first
                if ($primary_image) {
                    $pdo->prepare("UPDATE advertisements SET image_path = ? WHERE id = ?")->execute([$primary_image, $ad_id]);
                }

                $pdo->commit();
                ob_clean();
                echo json_encode(['success' => true, 'message' => $ad_id ? 'Advertisement updated.' : 'Advertisement created.']);

            } catch (Exception $e) {
                if ($pdo->inTransaction()) $pdo->rollBack();
                ob_clean();
                echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
            }
            break;

        case 'DELETE':
            $id = $_GET['id'] ?? 0;
            if ($id) {
                // Cleanup Media
                $pdo->prepare("DELETE FROM media WHERE entity_type='advertisement' AND entity_id=?")->execute([$id]);
                
                $stmt = $pdo->prepare("DELETE FROM advertisements WHERE id = ?");
                $stmt->execute([$id]);
                echo json_encode(['success' => true, 'message' => 'Ad deleted.']);
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

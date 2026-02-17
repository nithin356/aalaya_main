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
    if ($method === 'GET') {
        $stmt = $pdo->query("SELECT config_key, config_value FROM system_config");
        $config = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
        echo json_encode(['success' => true, 'data' => $config]);
    } 
    elseif ($method === 'POST') {
        $input = $_POST;
        $pdo->beginTransaction();
        
        $sql = "INSERT INTO system_config (config_key, config_value) VALUES (?, ?) 
                ON DUPLICATE KEY UPDATE config_value = VALUES(config_value)";
        $stmt = $pdo->prepare($sql);
        
        foreach ($input as $key => $value) {
            $stmt->execute([$key, $value]);
        }
        
        $pdo->commit();
        echo json_encode(['success' => true, 'message' => 'Configuration updated successfully!']);
    }
} catch (Exception $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>

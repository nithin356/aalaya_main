<?php
/**
 * Unified User Management API
 * Combines user list, audit stats, points adjustment, ban/unban, and hard delete
 */
header('Content-Type: application/json');
require_once '../../includes/session.php';
require_once '../../includes/db.php';

if (!isset($_SESSION['is_logged_in']) || $_SESSION['is_logged_in'] !== true) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$method = $_SERVER['REQUEST_METHOD'];
$pdo = getDB();

try {
    switch ($method) {
        case 'GET':
            handleGet($pdo);
            break;
        case 'POST':
            handlePost($pdo);
            break;
        case 'PUT':
            handlePut($pdo);
            break;
        case 'DELETE':
            handleDelete($pdo);
            break;
        default:
            echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

function handleGet($pdo) {
    $filter = $_GET['filter'] ?? 'all';

    // Base query with referrer info
    $sql = "SELECT 
                u.id,
                u.full_name,
                u.email,
                u.phone,
                u.aadhaar_number,
                u.pan_number,
                u.referral_code,
                u.referred_by,
                u.total_points,
                u.total_shares,
                u.is_banned,
                u.created_at,
                ref.full_name as referred_by_name,
                i.status as payment_status,
                i.payment_method
            FROM users u
            LEFT JOIN users ref ON u.referred_by = ref.id
            LEFT JOIN (
                SELECT user_id, status, payment_method 
                FROM invoices 
                WHERE description IN ('Registration Fee', 'Subscription Fee')
                ORDER BY id DESC
            ) i ON u.id = i.user_id
            WHERE u.is_deleted = 0";

    $params = [];

    // Apply filter
    switch ($filter) {
        case 'active':
            $sql .= " AND i.status = 'paid'";
            break;
        case 'pending_verification':
            $sql .= " AND i.status = 'pending_verification'";
            break;
        case 'pending':
            $sql .= " AND i.status = 'pending'";
            break;
        case 'no_invoice':
            $sql .= " AND i.status IS NULL";
            break;
        case 'banned':
            $sql .= " AND u.is_banned = 1";
            break;
    }

    $sql .= " ORDER BY u.created_at DESC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $data = $stmt->fetchAll();

    // Get stats
    $statsSql = "SELECT 
                    COUNT(*) as total,
                    SUM(CASE WHEN i.status = 'paid' THEN 1 ELSE 0 END) as active,
                    SUM(CASE WHEN i.status = 'pending_verification' THEN 1 ELSE 0 END) as pending_verification,
                    SUM(CASE WHEN i.status = 'pending' THEN 1 ELSE 0 END) as pending,
                    SUM(CASE WHEN i.status IS NULL THEN 1 ELSE 0 END) as no_invoice,
                    SUM(CASE WHEN u.is_banned = 1 THEN 1 ELSE 0 END) as banned
                FROM users u
                LEFT JOIN (
                    SELECT user_id, status 
                    FROM invoices 
                    WHERE description IN ('Registration Fee', 'Subscription Fee')
                    ORDER BY id DESC
                ) i ON u.id = i.user_id
                WHERE u.is_deleted = 0";
    
    $statsStmt = $pdo->query($statsSql);
    $stats = $statsStmt->fetch();

    echo json_encode([
        'success' => true,
        'data' => $data,
        'stats' => [
            'total' => intval($stats['total'] ?? 0),
            'active' => intval($stats['active'] ?? 0),
            'pending_verification' => intval($stats['pending_verification'] ?? 0),
            'pending' => intval($stats['pending'] ?? 0),
            'no_invoice' => intval($stats['no_invoice'] ?? 0),
            'banned' => intval($stats['banned'] ?? 0)
        ]
    ]);
}

function handlePost($pdo) {
    $input = json_decode(file_get_contents('php://input'), true);
    $action = $input['action'] ?? '';

    if ($action === 'adjust_points') {
        $user_id = intval($input['user_id'] ?? 0);
        $type = $input['type'] ?? '';
        $amount = intval($input['amount'] ?? 0);
        $reason = trim($input['reason'] ?? '');

        if (!in_array($type, ['credit', 'debit']) || !$amount || !$reason || !$user_id) {
            echo json_encode(['success' => false, 'message' => 'Invalid input']);
            exit;
        }

        $pdo->beginTransaction();

        // Update user points
        if ($type === 'credit') {
            $stmt = $pdo->prepare("UPDATE users SET total_points = total_points + ? WHERE id = ?");
            $stmt->execute([$amount, $user_id]);
            $transaction_type = 'manual_credit';
        } else {
            $stmt = $pdo->prepare("UPDATE users SET total_points = total_points - ? WHERE id = ? AND total_points >= ?");
            $stmt->execute([$amount, $user_id, $amount]);
            $transaction_type = 'manual_debit';
        }

        // Log transaction
        $stmt = $pdo->prepare("
            INSERT INTO referral_transactions (user_id, referred_user_id, level, points_earned, percentage, transaction_type)
            VALUES (?, ?, -1, ?, 100, ?)
        ");
        $stmt->execute([$user_id, $user_id, $amount, $transaction_type]);

        $pdo->commit();

        echo json_encode(['success' => true, 'message' => "Points {$type}ed successfully"]);
    } elseif ($action === 'send_to_payment') {
        $user_id = intval($input['user_id'] ?? 0);
        if (!$user_id) {
            echo json_encode(['success' => false, 'message' => 'User ID required']);
            exit;
        }

        // Check user exists
        $stmt = $pdo->prepare("SELECT id, full_name FROM users WHERE id = ? AND is_deleted = 0");
        $stmt->execute([$user_id]);
        $user = $stmt->fetch();
        if (!$user) {
            echo json_encode(['success' => false, 'message' => 'User not found']);
            exit;
        }

        // Get registration fee from system_config
        $feeStmt = $pdo->query("SELECT config_value FROM system_config WHERE config_key = 'registration_fee'");
        $reg_fee = $feeStmt->fetchColumn();
        $reg_fee = ($reg_fee === false || floatval($reg_fee) <= 0) ? 1111.00 : floatval($reg_fee);

        $was_paid = false;

        // Check if user has ANY existing registration invoice (including paid)
        $stmt = $pdo->prepare("SELECT id, status, payment_method FROM invoices WHERE user_id = ? AND description IN ('Registration Fee', 'Subscription Fee') ORDER BY id DESC LIMIT 1");
        $stmt->execute([$user_id]);
        $existingInvoice = $stmt->fetch();

        if ($existingInvoice) {
            $was_paid = ($existingInvoice['status'] === 'paid');
            // Reset existing invoice to pending — clear old payment data
            $stmt = $pdo->prepare("UPDATE invoices SET status = 'pending', payment_id = NULL, payment_method = 'cashfree', admin_comment = NULL, screenshot_path = NULL, utr_number = NULL, amount = ?, updated_at = NOW() WHERE id = ?");
            $stmt->execute([$reg_fee, $existingInvoice['id']]);
            $invoice_id = $existingInvoice['id'];

            // If was previously paid (manual approval), also reset user points that were awarded
            if ($was_paid) {
                // Deduct the registration fee points that were credited
                $pdo->prepare("UPDATE users SET total_points = GREATEST(total_points - ?, 0) WHERE id = ?")->execute([$reg_fee, $user_id]);
            }
        } else {
            // Create new pending invoice
            $stmt = $pdo->prepare("INSERT INTO invoices (user_id, amount, description, status, payment_method, created_at, updated_at) VALUES (?, ?, 'Registration Fee', 'pending', 'cashfree', NOW(), NOW())");
            $stmt->execute([$user_id, $reg_fee]);
            $invoice_id = $pdo->lastInsertId();
        }

        $extra = $was_paid ? ' (Previous manual approval has been revoked.)' : '';

        echo json_encode([
            'success' => true,
            'message' => "User '{$user['full_name']}' has been sent to payment stage (Invoice #{$invoice_id}, ₹" . number_format($reg_fee, 2) . ").{$extra} They will see the payment gateway on next login."
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Invalid action']);
    }
}

function handlePut($pdo) {
    $input = json_decode(file_get_contents('php://input'), true);
    $action = $input['action'] ?? '';
    $user_id = intval($input['user_id'] ?? 0);

    if (!$user_id) {
        echo json_encode(['success' => false, 'message' => 'User ID required']);
        exit;
    }

    if ($action === 'toggle_ban') {
        $stmt = $pdo->prepare("UPDATE users SET is_banned = 1 - is_banned WHERE id = ?");
        $stmt->execute([$user_id]);

        $stmt = $pdo->prepare("SELECT is_banned FROM users WHERE id = ?");
        $stmt->execute([$user_id]);
        $isBanned = $stmt->fetch()['is_banned'];

        echo json_encode([
            'success' => true,
            'message' => $isBanned ? 'User banned successfully' : 'User unbanned successfully'
        ]);
    } elseif ($action === 'change_password') {
        $new_password = $input['new_password'] ?? '';
        if (strlen($new_password) < 6) {
            echo json_encode(['success' => false, 'message' => 'Password must be at least 6 characters.']);
            exit;
        }
        $hash = password_hash($new_password, PASSWORD_DEFAULT);
        $pdo->prepare("UPDATE users SET password = ? WHERE id = ?")->execute([$hash, $user_id]);
        echo json_encode(['success' => true, 'message' => 'Password updated successfully.']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Invalid action']);
    }
}

function handleDelete($pdo) {
    $input = json_decode(file_get_contents('php://input'), true);
    $user_id = intval($input['user_id'] ?? 0);

    if (!$user_id) {
        echo json_encode(['success' => false, 'message' => 'User ID required']);
        exit;
    }

    // Soft delete — preserve all data, just mark user as deleted and ban to prevent login
    $pdo->prepare("UPDATE users SET is_deleted = 1, is_banned = 1 WHERE id = ?")
        ->execute([$user_id]);

    echo json_encode(['success' => true, 'message' => 'User deleted. All data has been preserved.']);
}
?>

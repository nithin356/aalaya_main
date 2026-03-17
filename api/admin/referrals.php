<?php
require_once __DIR__ . '/../../includes/session.php';
require_once __DIR__ . '/../../includes/db.php';

if (!isset($_SESSION['is_logged_in']) || $_SESSION['is_logged_in'] !== true) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

header('Content-Type: application/json');
$pdo = getDB();
$method = $_SERVER['REQUEST_METHOD'];

// ── GET ──────────────────────────────────────────────────────────────────────
if ($method === 'GET') {
    $action = $_GET['action'] ?? 'list';

    // Search users for the "change referrer" picker
    if ($action === 'search_users') {
        $q = trim($_GET['q'] ?? '');
        if (strlen($q) < 2) {
            echo json_encode(['success' => true, 'data' => []]);
            exit;
        }
        $like = '%' . $q . '%';
        $stmt = $pdo->prepare("
            SELECT id, full_name, phone, referral_code
            FROM users
            WHERE is_deleted = 0
              AND (full_name LIKE ? OR phone LIKE ? OR referral_code LIKE ? OR email LIKE ?)
            ORDER BY full_name
            LIMIT 20
        ");
        $stmt->execute([$like, $like, $like, $like]);
        echo json_encode(['success' => true, 'data' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
        exit;
    }

    // Referrals per user (main list)
    // For each user: who referred them, how many they referred, points earned from referrals
    $filter = $_GET['filter'] ?? 'all';
    $search = trim($_GET['search'] ?? '');

    $where = ['u.is_deleted = 0'];
    $params = [];

    if ($filter === 'has_referrals') {
        $where[] = 'referral_count > 0';
    } elseif ($filter === 'no_referrals') {
        $where[] = 'referral_count = 0';
    } elseif ($filter === 'has_referrer') {
        $where[] = 'u.referred_by IS NOT NULL';
    } elseif ($filter === 'no_referrer') {
        $where[] = 'u.referred_by IS NULL';
    }

    if ($search !== '') {
        $like = '%' . $search . '%';
        $where[] = '(u.full_name LIKE ? OR u.phone LIKE ? OR u.referral_code LIKE ? OR u.email LIKE ?)';
        array_push($params, $like, $like, $like, $like);
    }

    $whereStr = implode(' AND ', $where);

    // Sub-query inline for referral_count and points_earned to avoid HAVING issues with aliases
    $sql = "
        SELECT
            u.id,
            u.full_name,
            u.email,
            u.phone,
            u.referral_code,
            u.referred_by,
            u.total_points,
            u.created_at,
            ref.full_name AS referrer_name,
            ref.phone     AS referrer_phone,
            ref.referral_code AS referrer_code,
            COALESCE(rc.cnt, 0)   AS referral_count,
            COALESCE(pe.pts, 0)   AS points_earned_from_referrals
        FROM users u
        LEFT JOIN users ref ON ref.id = u.referred_by AND ref.is_deleted = 0
        LEFT JOIN (
            SELECT referred_by, COUNT(*) AS cnt
            FROM users
            WHERE is_deleted = 0 AND referred_by IS NOT NULL
            GROUP BY referred_by
        ) rc ON rc.referred_by = u.id
        LEFT JOIN (
            SELECT user_id, SUM(points_earned) AS pts
            FROM referral_transactions
            WHERE transaction_type IN ('subscription_reward','share_commission','investment_reward')
            GROUP BY user_id
        ) pe ON pe.user_id = u.id
    ";

    // Rebuild WHERE after joins (filter on computed cols needs outer query)
    if ($filter === 'has_referrals' || $filter === 'no_referrals') {
        $innerWhere = array_filter($where, fn($w) => $w !== 'referral_count > 0' && $w !== 'referral_count = 0');
        $innerWhere = array_values($innerWhere);
        $innerWhereStr = implode(' AND ', $innerWhere);

        $sql = "SELECT * FROM ($sql WHERE $innerWhereStr) t WHERE " . ($filter === 'has_referrals' ? 'referral_count > 0' : 'referral_count = 0');
    } else {
        $sql .= " WHERE $whereStr";
    }

    $sql .= " ORDER BY referral_count DESC, u.created_at DESC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Stats
    $statsStmt = $pdo->query("
        SELECT
            COUNT(*) AS total_users,
            SUM(CASE WHEN referred_by IS NOT NULL THEN 1 ELSE 0 END) AS total_referred,
            SUM(CASE WHEN referred_by IS NULL THEN 1 ELSE 0 END)     AS direct_signups
        FROM users WHERE is_deleted = 0
    ");
    $stats = $statsStmt->fetch(PDO::FETCH_ASSOC);

    $totalReferralsStmt = $pdo->query("SELECT COUNT(*) FROM users WHERE is_deleted = 0 AND referred_by IS NOT NULL");
    $stats['total_referral_links'] = (int)$totalReferralsStmt->fetchColumn();

    echo json_encode(['success' => true, 'data' => $users, 'stats' => $stats]);
    exit;
}

// ── POST ─────────────────────────────────────────────────────────────────────
if ($method === 'POST') {
    $body = json_decode(file_get_contents('php://input'), true);
    $action = $body['action'] ?? '';

    if ($action === 'change_referrer') {
        $userId       = (int)($body['user_id'] ?? 0);
        $newReferrerId = isset($body['new_referrer_id']) && $body['new_referrer_id'] !== '' && $body['new_referrer_id'] !== null
                         ? (int)$body['new_referrer_id']
                         : null;

        if (!$userId) {
            echo json_encode(['success' => false, 'message' => 'Invalid user ID']);
            exit;
        }

        // Fetch current user
        $userStmt = $pdo->prepare("SELECT id, full_name, referred_by FROM users WHERE id = ? AND is_deleted = 0");
        $userStmt->execute([$userId]);
        $user = $userStmt->fetch(PDO::FETCH_ASSOC);
        if (!$user) {
            echo json_encode(['success' => false, 'message' => 'User not found']);
            exit;
        }

        if ($newReferrerId !== null) {
            // Can't refer yourself
            if ($newReferrerId === $userId) {
                echo json_encode(['success' => false, 'message' => 'A user cannot be their own referrer']);
                exit;
            }

            // Check new referrer exists
            $refStmt = $pdo->prepare("SELECT id, full_name FROM users WHERE id = ? AND is_deleted = 0");
            $refStmt->execute([$newReferrerId]);
            $newRef = $refStmt->fetch(PDO::FETCH_ASSOC);
            if (!$newRef) {
                echo json_encode(['success' => false, 'message' => 'New referrer not found']);
                exit;
            }

            // Prevent circular chain: walk up new referrer's chain; if we hit $userId, it's circular
            $visited = [];
            $checkId = $newReferrerId;
            while ($checkId !== null) {
                if ($checkId === $userId) {
                    echo json_encode(['success' => false, 'message' => 'Cannot set referrer: this would create a circular referral chain']);
                    exit;
                }
                if (isset($visited[$checkId])) break; // safety
                $visited[$checkId] = true;
                $chainStmt = $pdo->prepare("SELECT referred_by FROM users WHERE id = ?");
                $chainStmt->execute([$checkId]);
                $row = $chainStmt->fetch(PDO::FETCH_ASSOC);
                $checkId = $row ? ($row['referred_by'] ? (int)$row['referred_by'] : null) : null;
            }
        }

        // Update
        $updateStmt = $pdo->prepare("UPDATE users SET referred_by = ?, updated_at = NOW() WHERE id = ?");
        $updateStmt->execute([$newReferrerId, $userId]);

        $newRefName = $newReferrerId !== null ? ($newRef['full_name'] ?? 'Unknown') : 'None (Direct)';
        $msg = "Referrer for {$user['full_name']} changed to {$newRefName}";

        echo json_encode(['success' => true, 'message' => $msg]);
        exit;
    }

    echo json_encode(['success' => false, 'message' => 'Unknown action']);
    exit;
}

http_response_code(405);
echo json_encode(['success' => false, 'message' => 'Method not allowed']);

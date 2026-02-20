<?php
header('Content-Type: application/json');
session_start();

require_once '../../includes/db.php';

// Authentication Check
if (!isset($_SESSION['is_logged_in']) || $_SESSION['is_logged_in'] !== true) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access.']);
    exit;
}

try {
    $pdo = getDB();
    
    // 1. Total Users (Not deleted)
    $stmtUsers = $pdo->query("SELECT COUNT(*) as count FROM users WHERE is_deleted = 0");
    $totalUsers = $stmtUsers->fetch()['count'];

    // 2. Active Properties
    $stmtProperties = $pdo->query("SELECT COUNT(*) as count FROM properties WHERE is_active = 1");
    $totalProperties = $stmtProperties->fetch()['count'];

    // 3. Active Advertisements
    $stmtAds = $pdo->query("SELECT COUNT(*) as count FROM advertisements WHERE is_active = 1 AND (end_date IS NULL OR end_date >= CURDATE())");
    $totalAds = $stmtAds->fetch()['count'];

    // 4. Pending Enquiries
    $stmtEnquiries = $pdo->query("SELECT COUNT(*) as count FROM enquiries WHERE status = 'pending'");
    $totalEnquiries = $stmtEnquiries->fetch()['count'];

    // 5. Recent Enquiries (Last 5)
    $stmtRecent = $pdo->query("
        SELECT e.id, u.full_name as user_name, e.enquiry_type, e.status, e.created_at 
        FROM enquiries e 
        JOIN users u ON e.user_id = u.id 
        ORDER BY e.created_at DESC 
        LIMIT 5
    ");
    $recentEnquiries = $stmtRecent->fetchAll();

    // 6. Income Trend (Last 30 days)
    $stmtIncome = $pdo->query("
        SELECT DATE(updated_at) as date, SUM(amount) as total 
                FROM invoices 
                WHERE status = 'paid'
                    AND (payment_method IS NULL OR payment_method <> 'cashfree')
                    AND updated_at >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
        GROUP BY DATE(updated_at)
        ORDER BY date ASC
    ");
    $incomeTrend = $stmtIncome->fetchAll(PDO::FETCH_ASSOC);

    // 7. User Growth Trend (Last 30 days)
    $stmtGrowth = $pdo->query("
        SELECT DATE(created_at) as date, COUNT(*) as count 
        FROM users 
        WHERE is_deleted = 0 AND created_at >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
        GROUP BY DATE(created_at)
        ORDER BY date ASC
    ");
    $userGrowth = $stmtGrowth->fetchAll(PDO::FETCH_ASSOC);

    // 8. Total Points Distributed (Sum of all points from transactions)
    // 8. Total Subscription Amount (Sum of all paid Registration Fees)
    $stmtTotalPoints = $pdo->query("SELECT SUM(amount) as total FROM invoices WHERE description = 'Registration Fee' AND status = 'paid' AND (payment_method IS NULL OR payment_method <> 'cashfree')");
    $totalPoints = $stmtTotalPoints->fetchColumn() ?: 0;

    // 9. Total Investments (Sum of all investment amounts)
    $stmtTotalInvestments = $pdo->query("SELECT SUM(amount) as total FROM investments");
    $totalInvestments = $stmtTotalInvestments->fetchColumn() ?: 0;

    echo json_encode([
        'success' => true,
        'stats' => [
            'total_users' => $totalUsers,
            'active_properties' => $totalProperties,
            'active_ads' => $totalAds,
            'pending_enquiries' => $totalEnquiries,
            'total_points' => $totalPoints,
            'total_investments' => $totalInvestments
        ],
        'recent_enquiries' => $recentEnquiries,
        'analytics' => [
            'income_trend' => $incomeTrend,
            'user_growth' => $userGrowth
        ]
    ]);

} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?>

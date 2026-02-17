<?php
require_once '../includes/db.php';
$pdo = getDB();

echo "Creating test users for hierarchy demo...\n\n";

try {
    $pdo->beginTransaction();
    
    // Create 3 direct users (no referrer)
    $directUsers = [
        ['full_name' => 'Rahul Sharma', 'email' => 'rahul@test.com', 'phone' => '9876543210', 'referral_code' => 'RAHUL123'],
        ['full_name' => 'Priya Patel', 'email' => 'priya@test.com', 'phone' => '9876543211', 'referral_code' => 'PRIYA456'],
        ['full_name' => 'Amit Kumar', 'email' => 'amit@test.com', 'phone' => '9876543212', 'referral_code' => 'AMIT789'],
    ];
    
    $insertedIds = [];
    
    foreach ($directUsers as $user) {
        $stmt = $pdo->prepare("INSERT INTO users (full_name, email, phone, referral_code, referred_by, created_at) VALUES (?, ?, ?, ?, NULL, NOW())");
        $stmt->execute([$user['full_name'], $user['email'], $user['phone'], $user['referral_code']]);
        $insertedIds[$user['referral_code']] = $pdo->lastInsertId();
        echo "Created: {$user['full_name']} (Direct Join)\n";
    }
    
    // Create referrals under Rahul
    $rahulReferrals = [
        ['full_name' => 'Sneha Gupta', 'email' => 'sneha@test.com', 'phone' => '9876543213', 'referral_code' => 'SNEHA001'],
        ['full_name' => 'Vikram Singh', 'email' => 'vikram@test.com', 'phone' => '9876543214', 'referral_code' => 'VIKRAM02'],
    ];
    
    foreach ($rahulReferrals as $user) {
        $stmt = $pdo->prepare("INSERT INTO users (full_name, email, phone, referral_code, referred_by, created_at) VALUES (?, ?, ?, ?, ?, NOW())");
        $stmt->execute([$user['full_name'], $user['email'], $user['phone'], $user['referral_code'], $insertedIds['RAHUL123']]);
        $insertedIds[$user['referral_code']] = $pdo->lastInsertId();
        echo "Created: {$user['full_name']} (Referred by Rahul)\n";
    }
    
    // Create referrals under Sneha (2nd level)
    $snehaReferrals = [
        ['full_name' => 'Arjun Reddy', 'email' => 'arjun@test.com', 'phone' => '9876543215', 'referral_code' => 'ARJUN003'],
    ];
    
    foreach ($snehaReferrals as $user) {
        $stmt = $pdo->prepare("INSERT INTO users (full_name, email, phone, referral_code, referred_by, created_at) VALUES (?, ?, ?, ?, ?, NOW())");
        $stmt->execute([$user['full_name'], $user['email'], $user['phone'], $user['referral_code'], $insertedIds['SNEHA001']]);
        $insertedIds[$user['referral_code']] = $pdo->lastInsertId();
        echo "Created: {$user['full_name']} (Referred by Sneha - 2nd level)\n";
    }
    
    // Create referrals under Priya
    $priyaReferrals = [
        ['full_name' => 'Neha Verma', 'email' => 'neha@test.com', 'phone' => '9876543216', 'referral_code' => 'NEHA0004'],
        ['full_name' => 'Karan Mehta', 'email' => 'karan@test.com', 'phone' => '9876543217', 'referral_code' => 'KARAN005'],
        ['full_name' => 'Divya Joshi', 'email' => 'divya@test.com', 'phone' => '9876543218', 'referral_code' => 'DIVYA006'],
    ];
    
    foreach ($priyaReferrals as $user) {
        $stmt = $pdo->prepare("INSERT INTO users (full_name, email, phone, referral_code, referred_by, created_at) VALUES (?, ?, ?, ?, ?, NOW())");
        $stmt->execute([$user['full_name'], $user['email'], $user['phone'], $user['referral_code'], $insertedIds['PRIYA456']]);
        $insertedIds[$user['referral_code']] = $pdo->lastInsertId();
        echo "Created: {$user['full_name']} (Referred by Priya)\n";
    }
    
    // Create referral under Karan (2nd level under Priya)
    $karanReferrals = [
        ['full_name' => 'Rohan Das', 'email' => 'rohan@test.com', 'phone' => '9876543219', 'referral_code' => 'ROHAN007'],
    ];
    
    foreach ($karanReferrals as $user) {
        $stmt = $pdo->prepare("INSERT INTO users (full_name, email, phone, referral_code, referred_by, created_at) VALUES (?, ?, ?, ?, ?, NOW())");
        $stmt->execute([$user['full_name'], $user['email'], $user['phone'], $user['referral_code'], $insertedIds['KARAN005']]);
        echo "Created: {$user['full_name']} (Referred by Karan - 2nd level)\n";
    }
    
    $pdo->commit();
    
    echo "\nCreated 10 test users with hierarchy!\n";
    
} catch (Exception $e) {
    $pdo->rollBack();
    echo "Error: " . $e->getMessage() . "\n";
}
?>

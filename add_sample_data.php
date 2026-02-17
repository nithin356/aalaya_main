<?php
require_once 'includes/db.php';

try {
    $pdo = getDB();
    
    // 1. Add video_path column if it doesn't exist
    try {
        $pdo->query("SELECT video_path FROM properties LIMIT 1");
    } catch (PDOException $e) {
        $pdo->exec("ALTER TABLE properties ADD COLUMN video_path VARCHAR(255) AFTER image_path");
        echo "Added 'video_path' column to properties table.\n";
    }

    // 2. Insert Sample Property (High Quality)
    $stmt = $pdo->prepare("INSERT INTO properties (
        title, owner_name, description, property_type, price, location, 
        area, area_unit, bedrooms, bathrooms, image_path, video_path, 
        status, is_active, created_at
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())");

    $stmt->execute([
        'Luxury Villa with Private Pool',
        'Aalaya Reality',
        'Experience the epitome of luxury living in this stunning 4BHK villa. Features include a private swimming pool, landscaped gardens, home theater, and Italian marble flooring. Located in a gated community with 24/7 security and world-class amenities.',
        'residential',
        45000000.00, // 4.5 Cr
        'Gachibowli, Hyderabad',
        4500.00,
        'sqft',
        4,
        5,
        'https://images.unsplash.com/photo-1613977257363-707ba9348227?q=80&w=2070&auto=format&fit=crop', // High quality villa image
        'https://videos.pexels.com/video-files/3209041/3209041-hd_1920_1080_25fps.mp4', // Modern house video
        'available',
        1
    ]);
    echo "Inserted Sample Property.\n";

    // 3. Insert Sample Advertisement
    $stmtAd = $pdo->prepare("INSERT INTO advertisements (
        title, description, company_name, contact_email, contact_phone, 
        image_path, ad_type, start_date, end_date, is_active, created_at
    ) VALUES (?, ?, ?, ?, ?, ?, ?, CURDATE(), DATE_ADD(CURDATE(), INTERVAL 30 DAY), ?, NOW())");

    $stmtAd->execute([
        'Premium Interior Design Services',
        'Transform your home into a masterpiece with our award-winning interior design services. Get a free consultation today!',
        'Elite Interiors',
        'contact@eliteinteriors.com',
        '9876543210',
        'https://images.unsplash.com/photo-1618221195710-dd6b41faaea6?q=80&w=2000&auto=format&fit=crop', // High quality interior image
        'featured',
        1
    ]);
    echo "Inserted Sample Advertisement.\n";

} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>

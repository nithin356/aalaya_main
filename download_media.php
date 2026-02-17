<?php
require_once 'includes/db.php';
$pdo = getDB();

// Define Paths
$baseDir = __DIR__ . '/uploads';
$propImgDir = $baseDir . '/properties/images';
$propVidDir = $baseDir . '/properties/videos';
$adImgDir = $baseDir . '/advertisements/images';

// Create Directories
$dirs = [$propImgDir, $propVidDir, $adImgDir];
foreach ($dirs as $dir) {
    if (!is_dir($dir)) {
        mkdir($dir, 0777, true);
        echo "Created directory: $dir\n";
    }
}

// Function to download file
function downloadFile($url, $destination) {
    echo "Downloading: $url -> $destination\n";
    $content = file_get_contents($url);
    if ($content === false) {
        echo "Failed to download $url\n";
        return false;
    }
    file_put_contents($destination, $content);
    return true;
}

// 1. Download Property Video
$videoUrl = 'https://videos.pexels.com/video-files/3209041/3209041-hd_1920_1080_25fps.mp4';
$localVideoPath = 'uploads/properties/videos/sample_villa.mp4';
downloadFile($videoUrl, __DIR__ . '/' . $localVideoPath);

// 2. Download Property Image
$propImgUrl = 'https://images.unsplash.com/photo-1613977257363-707ba9348227?q=80&w=2070&auto=format&fit=crop';
$localPropImgPath = 'uploads/properties/images/sample_villa.jpg';
downloadFile($propImgUrl, __DIR__ . '/' . $localPropImgPath);

// 3. Download Advertisement Image
$adImgUrl = 'https://images.unsplash.com/photo-1618221195710-dd6b41faaea6?q=80&w=2000&auto=format&fit=crop';
$localAdImgPath = 'uploads/advertisements/images/interior_ad.jpg';
downloadFile($adImgUrl, __DIR__ . '/' . $localAdImgPath);

// 4. Update Database
try {
    // Update Property
    $pdo->prepare("UPDATE properties SET image_path = ?, video_path = ? WHERE title = 'Luxury Villa with Private Pool'")
        ->execute([$localPropImgPath, $localVideoPath]);
    echo "Updated properties table with local paths.\n";

    // Update Advertisement
    $pdo->prepare("UPDATE advertisements SET image_path = ? WHERE title = 'Premium Interior Design Services'")
        ->execute([$localAdImgPath]);
    echo "Updated advertisements table with local paths.\n";

} catch (PDOException $e) {
    echo "DB Error: " . $e->getMessage() . "\n";
}
?>

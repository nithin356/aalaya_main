<?php
/**
 * Common Functions for Aalaya
 */

/**
 * Resize and crop image to fixed dimensions
 */
function resizeImage($sourcePath, $targetPath, $targetWidth, $targetHeight) {
    // Check if GD is enabled
    if (!function_exists('imagecreatefromjpeg')) {
        // Fallback: Copy original file if GD is not available
        if (copy($sourcePath, $targetPath)) {
            return basename($targetPath);
        }
        return false;
    }

    $info = getimagesize($sourcePath);
    if (!$info) return false;

    $sourceWidth = $info[0];
    $sourceHeight = $info[1];
    $mime = $info['mime'];

    // Create source image object based on mime
    try {
        switch ($mime) {
            case 'image/jpeg': $sourceImg = @imagecreatefromjpeg($sourcePath); break;
            case 'image/png':  $sourceImg = @imagecreatefrompng($sourcePath); break;
            case 'image/webp': $sourceImg = @imagecreatefromwebp($sourcePath); break;
            case 'image/gif': 
                // GIFs: Bypass resize to preserve animation
                if (copy($sourcePath, $targetPath)) {
                    return basename($targetPath);
                }
                return false;
            default: return false;
        }

        if (!$sourceImg) {
            // If failed to create, fallback to copy
            copy($sourcePath, $targetPath);
            return basename($targetPath);
        }

        // Calculate scaling and cropping
        $sourceRatio = $sourceWidth / $sourceHeight;
        $targetRatio = $targetWidth / $targetHeight;

        if ($sourceRatio > $targetRatio) {
            $tempHeight = $sourceHeight;
            $tempWidth = (int)($sourceHeight * $targetRatio);
            $srcX = (int)(($sourceWidth - $tempWidth) / 2);
            $srcY = 0;
        } else {
            $tempWidth = $sourceWidth;
            $tempHeight = (int)($sourceWidth / $targetRatio);
            $srcX = 0;
            $srcY = (int)(($sourceHeight - $tempHeight) / 2);
        }

        // Create target image
        $targetImg = imagecreatetruecolor($targetWidth, $targetHeight);

        // Handle transparency
        if ($mime === 'image/png' || $mime === 'image/webp') {
            imagealphablending($targetImg, false);
            imagesavealpha($targetImg, true);
        }

        // Copy and resize
        imagecopyresampled(
            $targetImg, $sourceImg,
            0, 0, $srcX, $srcY,
            $targetWidth, $targetHeight, $tempWidth, $tempHeight
        );

        // Save
        $success = false;
        if (function_exists('imagewebp')) {
            $targetPath = preg_replace('/\.(jpg|jpeg|png)$/i', '.webp', $targetPath);
            $success = imagewebp($targetImg, $targetPath, 85);
        } else {
            $success = imagejpeg($targetImg, $targetPath, 90);
        }

        imagedestroy($sourceImg);
        imagedestroy($targetImg);

        return $success ? basename($targetPath) : false;

    } catch (Exception $e) {
        // Absolute fallback
        copy($sourcePath, $targetPath);
        return basename($targetPath);
    }
}


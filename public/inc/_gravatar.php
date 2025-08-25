<?php
/**
 * Gravatar Helper Class
 * 
 * Handles Gravatar image loading and local caching
 */

class Gravatar {
    
    /**
     * Get Gravatar URL for email
     * 
     * @param string $email User email
     * @param int $size Image size (default 80)
     * @return string Gravatar URL
     */
    public static function getGravatarUrl($email, $size = 80) {
        // Trim leading and trailing whitespace from
        // an email address and force all characters
        // to lower case
        $address = strtolower(trim($email));
        
        // Create an SHA256 hash of the final string
        $hash = hash('sha256', $address);
        
        // Grab the actual image URL
        return 'https://gravatar.com/avatar/' . $hash . '?s=' . $size . '&d=identicon';
    }
    
    /**
     * Get local cached Gravatar or use default
     * 
     * @param string $email User email
     * @param int $size Image size (default 80)
     * @return string Local file path or default avatar
     */
    public static function getCachedGravatar($email, $size = 80) {
        $address = strtolower(trim($email));
        $hash = hash('sha256', $address);
        $filename = 'gravatar_' . $hash . '_' . $size . '.jpg';
        $localPath = __DIR__ . '/../up/avatars/' . $filename;
        $webPath = 'up/avatars/' . $filename;
        $defaultPath = 'up/avatars/default.png';
        
        // Create directory if not exists
        $avatarDir = __DIR__ . '/../up/avatars/';
        if (!is_dir($avatarDir)) {
            mkdir($avatarDir, 0755, true);
        }
        
        // Check if cached file exists
        if (file_exists($localPath)) {
            return $webPath;
        }
        
        // Try to download once if network available (for initial setup)
        if (!file_exists(__DIR__ . '/../up/avatars/.offline_mode')) {
            try {
                $gravatarUrl = self::getGravatarUrl($email, $size);
                $context = stream_context_create([
                    'http' => [
                        'timeout' => 3, // 3 second timeout
                        'ignore_errors' => true
                    ]
                ]);
                $imageData = file_get_contents($gravatarUrl, false, $context);
                
                if ($imageData !== false) {
                    file_put_contents($localPath, $imageData);
                    return $webPath;
                }
            } catch (Exception $e) {
                // Mark as offline mode
                file_put_contents(__DIR__ . '/../up/avatars/.offline_mode', 'Offline mode enabled');
            }
        }
        
        // Create default avatar if not exists
        if (!file_exists(__DIR__ . '/../' . $defaultPath)) {
            self::createDefaultAvatar(__DIR__ . '/../' . $defaultPath);
        }
        
        return $defaultPath;
    }
    
    /**
     * Create a simple default avatar
     */
    public static function createDefaultAvatar($path) {
        // Create a simple colored circle as default avatar
        $img = imagecreatetruecolor(64, 64);
        $bg = imagecolorallocate($img, 108, 117, 125); // Bootstrap secondary color
        $fg = imagecolorallocate($img, 255, 255, 255);
        
        imagefill($img, 0, 0, $bg);
        imageellipse($img, 32, 32, 50, 50, $fg);
        
        // Add user icon (simple)
        imageellipse($img, 32, 25, 20, 20, $fg);
        imageellipse($img, 32, 45, 35, 20, $fg);
        
        imagepng($img, $path);
        imagedestroy($img);
    }
}

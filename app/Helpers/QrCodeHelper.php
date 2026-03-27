<?php
/**
 * QR Code Helper
 * Generates QR codes for website URLs
 */
namespace App\Helpers;

class QrCodeHelper
{
    /**
     * Generate QR code image using Google Charts API (free, no library needed)
     * @param string $data The data to encode
     * @param int $size Size in pixels (default 300)
     * @return string Base64 encoded image or URL
     */
    public static function generate($data, $size = 300, $format = 'url')
    {
        // Use Google Charts API for QR code generation (free, no library needed)
        $encodedData = urlencode($data);
        $qrUrl = "https://chart.googleapis.com/chart?chs={$size}x{$size}&cht=qr&chl={$encodedData}";
        
        if ($format === 'url') {
            return $qrUrl;
        }
        
        // Return base64 encoded image
        $imageData = @file_get_contents($qrUrl);
        if ($imageData) {
            return 'data:image/png;base64,' . base64_encode($imageData);
        }
        
        return null;
    }
    
    /**
     * Generate QR code for website URL
     * @param string $path Optional path to append to base URL
     * @param int $size Size in pixels
     * @return string QR code URL or base64 data
     */
    public static function generateWebsiteQr($path = '', $size = 300, $format = 'url')
    {
        $baseUrl = self::getBaseUrl();
        $fullUrl = !empty($path) ? rtrim($baseUrl, '/') . '/' . ltrim($path, '/') : $baseUrl;
        
        return self::generate($fullUrl, $size, $format);
    }
    
    /**
     * Get base website URL
     */
    private static function getBaseUrl()
    {
        $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https://' : 'http://';
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
        $scriptName = $_SERVER['SCRIPT_NAME'] ?? '';
        $basePath = dirname($scriptName);
        
        // Remove index.php from path if present
        $basePath = str_replace('/index.php', '', $basePath);
        $basePath = rtrim($basePath, '/');
        
        return $protocol . $host . $basePath;
    }
    
    /**
     * Save QR code to file
     * @param string $data The data to encode
     * @param string $filename Output filename
     * @param int $size Size in pixels
     * @return string|false File path on success, false on failure
     */
    public static function saveToFile($data, $filename, $size = 300)
    {
        $qrUrl = self::generate($data, $size, 'url');
        $imageData = @file_get_contents($qrUrl);
        
        if (!$imageData) {
            return false;
        }
        
        $storageDir = __DIR__ . '/../../storage/qrcodes/';
        if (!is_dir($storageDir)) {
            mkdir($storageDir, 0755, true);
        }
        
        $filepath = $storageDir . $filename;
        file_put_contents($filepath, $imageData);
        
        return 'storage/qrcodes/' . $filename;
    }
}

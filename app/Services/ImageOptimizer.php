<?php

namespace App\Services;

use Exception;

class ImageOptimizer
{
    private $uploadDir;
    private $supportedFormats = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
    
    public function __construct()
    {
        $this->uploadDir = __DIR__ . '/../../storage/uploads/';
    }
    
    /**
     * Compress an image file
     */
    public function compress($filename, $quality = 85, $maxWidth = null, $maxHeight = null)
    {
        $filepath = $this->uploadDir . basename($filename);
        
        if (!file_exists($filepath)) {
            throw new Exception("File not found: {$filename}");
        }
        
        $extension = strtolower(pathinfo($filepath, PATHINFO_EXTENSION));
        
        if (!in_array($extension, $this->supportedFormats)) {
            throw new Exception("Unsupported format: {$extension}");
        }
        
        // Get original image info
        $imageInfo = getimagesize($filepath);
        if (!$imageInfo) {
            throw new Exception("Invalid image file");
        }
        
        $originalWidth = $imageInfo[0];
        $originalHeight = $imageInfo[1];
        $mimeType = $imageInfo['mime'];
        $originalSize = filesize($filepath);
        
        // Calculate new dimensions if needed
        $newWidth = $originalWidth;
        $newHeight = $originalHeight;
        
        if ($maxWidth && $originalWidth > $maxWidth) {
            $ratio = $maxWidth / $originalWidth;
            $newWidth = $maxWidth;
            $newHeight = (int)($originalHeight * $ratio);
        }
        
        if ($maxHeight && $newHeight > $maxHeight) {
            $ratio = $maxHeight / $newHeight;
            $newWidth = (int)($newWidth * $ratio);
            $newHeight = $maxHeight;
        }
        
        // Create image resource
        switch ($mimeType) {
            case 'image/jpeg':
                $source = imagecreatefromjpeg($filepath);
                break;
            case 'image/png':
                $source = imagecreatefrompng($filepath);
                break;
            case 'image/gif':
                $source = imagecreatefromgif($filepath);
                break;
            case 'image/webp':
                $source = imagecreatefromwebp($filepath);
                break;
            default:
                throw new Exception("Unsupported MIME type: {$mimeType}");
        }
        
        if (!$source) {
            throw new Exception("Failed to create image resource");
        }
        
        // Create new image with calculated dimensions
        $destination = imagecreatetruecolor($newWidth, $newHeight);
        
        // Preserve transparency for PNG and GIF
        if ($extension === 'png' || $extension === 'gif') {
            imagealphablending($destination, false);
            imagesavealpha($destination, true);
            $transparent = imagecolorallocatealpha($destination, 255, 255, 255, 127);
            imagefilledrectangle($destination, 0, 0, $newWidth, $newHeight, $transparent);
        }
        
        // Resize image
        imagecopyresampled($destination, $source, 0, 0, 0, 0, $newWidth, $newHeight, $originalWidth, $originalHeight);
        
        // Create backup
        $backupPath = $filepath . '.backup.' . time();
        copy($filepath, $backupPath);
        
        // Save compressed image
        $saved = false;
        switch ($extension) {
            case 'jpg':
            case 'jpeg':
                $saved = imagejpeg($destination, $filepath, $quality);
                break;
            case 'png':
                // PNG quality is 0-9 (inverted)
                $pngQuality = (int)(9 - ($quality / 100) * 9);
                $saved = imagepng($destination, $filepath, $pngQuality);
                break;
            case 'gif':
                $saved = imagegif($destination, $filepath);
                break;
            case 'webp':
                $saved = imagewebp($destination, $filepath, $quality);
                break;
        }
        
        // Clean up
        imagedestroy($source);
        imagedestroy($destination);
        
        if (!$saved) {
            // Restore backup on failure
            if (file_exists($backupPath)) {
                copy($backupPath, $filepath);
                unlink($backupPath);
            }
            throw new Exception("Failed to save compressed image");
        }
        
        // Remove backup if successful
        if (file_exists($backupPath)) {
            unlink($backupPath);
        }
        
        $newSize = filesize($filepath);
        $savings = $originalSize - $newSize;
        $savingsPercent = $originalSize > 0 ? round(($savings / $originalSize) * 100, 2) : 0;
        
        return [
            'success' => true,
            'original_size' => $originalSize,
            'new_size' => $newSize,
            'savings' => $savings,
            'savings_percent' => $savingsPercent,
            'original_dimensions' => "{$originalWidth}x{$originalHeight}",
            'new_dimensions' => "{$newWidth}x{$newHeight}",
            'filename' => $filename
        ];
    }
    
    /**
     * Convert image format
     */
    public function convert($filename, $targetFormat, $quality = 85)
    {
        $filepath = $this->uploadDir . basename($filename);
        
        if (!file_exists($filepath)) {
            throw new Exception("File not found: {$filename}");
        }
        
        $extension = strtolower(pathinfo($filepath, PATHINFO_EXTENSION));
        
        if ($extension === $targetFormat) {
            throw new Exception("Image is already in {$targetFormat} format");
        }
        
        $imageInfo = getimagesize($filepath);
        if (!$imageInfo) {
            throw new Exception("Invalid image file");
        }
        
        $mimeType = $imageInfo['mime'];
        
        // Create source image
        switch ($mimeType) {
            case 'image/jpeg':
                $source = imagecreatefromjpeg($filepath);
                break;
            case 'image/png':
                $source = imagecreatefrompng($filepath);
                break;
            case 'image/gif':
                $source = imagecreatefromgif($filepath);
                break;
            case 'image/webp':
                $source = imagecreatefromwebp($filepath);
                break;
            default:
                throw new Exception("Unsupported source format");
        }
        
        if (!$source) {
            throw new Exception("Failed to create image resource");
        }
        
        // Generate new filename
        $newFilename = pathinfo($filename, PATHINFO_FILENAME) . '.' . $targetFormat;
        $newFilepath = $this->uploadDir . $newFilename;
        
        // Save in new format
        $saved = false;
        switch ($targetFormat) {
            case 'jpg':
            case 'jpeg':
                $saved = imagejpeg($source, $newFilepath, $quality);
                break;
            case 'png':
                $pngQuality = (int)(9 - ($quality / 100) * 9);
                $saved = imagepng($source, $newFilepath, $pngQuality);
                break;
            case 'gif':
                $saved = imagegif($source, $newFilepath);
                break;
            case 'webp':
                $saved = imagewebp($source, $newFilepath, $quality);
                break;
            default:
                throw new Exception("Unsupported target format: {$targetFormat}");
        }
        
        imagedestroy($source);
        
        if (!$saved) {
            throw new Exception("Failed to convert image");
        }
        
        $originalSize = filesize($filepath);
        $newSize = filesize($newFilepath);
        
        return [
            'success' => true,
            'original_filename' => $filename,
            'new_filename' => $newFilename,
            'original_size' => $originalSize,
            'new_size' => $newSize,
            'savings' => $originalSize - $newSize,
            'format' => $targetFormat
        ];
    }
    
    /**
     * Get image analysis
     */
    public function analyze($filename)
    {
        $filepath = $this->uploadDir . basename($filename);
        
        if (!file_exists($filepath)) {
            throw new Exception("File not found: {$filename}");
        }
        
        $imageInfo = getimagesize($filepath);
        if (!$imageInfo) {
            throw new Exception("Invalid image file");
        }
        
        $extension = strtolower(pathinfo($filepath, PATHINFO_EXTENSION));
        $size = filesize($filepath);
        
        // Calculate file size category
        $sizeCategory = 'small';
        if ($size > 2 * 1024 * 1024) {
            $sizeCategory = 'xlarge';
        } elseif ($size > 500 * 1024) {
            $sizeCategory = 'large';
        } elseif ($size > 100 * 1024) {
            $sizeCategory = 'medium';
        }
        
        // Check if optimization is recommended
        $needsOptimization = false;
        $optimizationReason = [];
        
        if ($size > 500 * 1024) {
            $needsOptimization = true;
            $optimizationReason[] = 'Large file size';
        }
        
        if ($imageInfo[0] > 2000 || $imageInfo[1] > 2000) {
            $needsOptimization = true;
            $optimizationReason[] = 'Large dimensions';
        }
        
        if ($extension === 'png' && $size > 200 * 1024) {
            $needsOptimization = true;
            $optimizationReason[] = 'PNG can be optimized';
        }
        
        return [
            'filename' => $filename,
            'size' => $size,
            'size_formatted' => $this->formatBytes($size),
            'size_category' => $sizeCategory,
            'width' => $imageInfo[0],
            'height' => $imageInfo[1],
            'dimensions' => "{$imageInfo[0]} × {$imageInfo[1]}",
            'format' => $extension,
            'mime_type' => $imageInfo['mime'],
            'aspect_ratio' => round($imageInfo[0] / $imageInfo[1], 2),
            'needs_optimization' => $needsOptimization,
            'optimization_reasons' => $optimizationReason,
            'date' => filemtime($filepath)
        ];
    }
    
    /**
     * Format bytes to human readable
     */
    private function formatBytes($bytes, $precision = 2)
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        
        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }
        
        return round($bytes, $precision) . ' ' . $units[$i];
    }
    
    /**
     * Find orphaned images (not used in database)
     */
    public function findOrphanedImages($usedImages = [])
    {
        $orphaned = [];
        $files = scandir($this->uploadDir);
        
        foreach ($files as $file) {
            if ($file === '.' || $file === '..' || is_dir($this->uploadDir . $file)) {
                continue;
            }
            
            $extension = strtolower(pathinfo($file, PATHINFO_EXTENSION));
            if (!in_array($extension, ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg'])) {
                continue;
            }
            
            // Check if image is used
            $isUsed = false;
            foreach ($usedImages as $usedImage) {
                if (basename($usedImage) === $file || strpos($usedImage, $file) !== false) {
                    $isUsed = true;
                    break;
                }
            }
            
            if (!$isUsed) {
                $orphaned[] = [
                    'filename' => $file,
                    'size' => filesize($this->uploadDir . $file),
                    'date' => filemtime($this->uploadDir . $file)
                ];
            }
        }
        
        return $orphaned;
    }
}


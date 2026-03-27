<?php

namespace App\Services;

class ColorExtractor
{
    /**
     * Extract dominant colors from an image
     * 
     * @param string $imagePath Path to the image file
     * @param int $colorCount Number of colors to extract (default: 5)
     * @return array Array of hex color codes
     */
    public function extractColors($imagePath, $colorCount = 5)
    {
        if (!file_exists($imagePath)) {
            return [];
        }

        // Get image info
        $imageInfo = @getimagesize($imagePath);
        if (!$imageInfo) {
            return [];
        }

        $mimeType = $imageInfo['mime'];
        
        // Create image resource based on type
        $image = null;
        switch ($mimeType) {
            case 'image/jpeg':
            case 'image/jpg':
                $image = @imagecreatefromjpeg($imagePath);
                break;
            case 'image/png':
                $image = @imagecreatefrompng($imagePath);
                break;
            case 'image/gif':
                $image = @imagecreatefromgif($imagePath);
                break;
            case 'image/webp':
                if (function_exists('imagecreatefromwebp')) {
                    $image = @imagecreatefromwebp($imagePath);
                }
                break;
            default:
                return [];
        }

        if (!$image) {
            return [];
        }

        // Resize image for faster processing (max 150x150)
        $width = imagesx($image);
        $height = imagesy($image);
        $maxSize = 150;
        
        if ($width > $maxSize || $height > $maxSize) {
            $ratio = min($maxSize / $width, $maxSize / $height);
            $newWidth = (int)($width * $ratio);
            $newHeight = (int)($height * $ratio);
            
            $resized = imagecreatetruecolor($newWidth, $newHeight);
            imagecopyresampled($resized, $image, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);
            imagedestroy($image);
            $image = $resized;
            $width = $newWidth;
            $height = $newHeight;
        }

        // Extract colors using frequency method
        $colorFrequency = [];
        
        for ($x = 0; $x < $width; $x++) {
            for ($y = 0; $y < $height; $y++) {
                $rgb = imagecolorat($image, $x, $y);
                $r = ($rgb >> 16) & 0xFF;
                $g = ($rgb >> 8) & 0xFF;
                $b = $rgb & 0xFF;
                
                // Skip very light or very dark colors (likely background/transparency)
                $brightness = ($r + $g + $b) / 3;
                if ($brightness < 20 || $brightness > 235) {
                    continue;
                }
                
                // Quantize colors to reduce similar shades
                $r = round($r / 10) * 10;
                $g = round($g / 10) * 10;
                $b = round($b / 10) * 10;
                
                $hex = sprintf('#%02x%02x%02x', $r, $g, $b);
                
                if (!isset($colorFrequency[$hex])) {
                    $colorFrequency[$hex] = 0;
                }
                $colorFrequency[$hex]++;
            }
        }

        imagedestroy($image);

        // Sort by frequency
        arsort($colorFrequency);

        // Get top colors
        $colors = array_slice(array_keys($colorFrequency), 0, $colorCount);

        // If we don't have enough colors, fill with defaults
        while (count($colors) < $colorCount) {
            $colors[] = '#2563eb'; // Default blue
        }

        return $colors;
    }

    /**
     * Get primary color (most dominant)
     * 
     * @param string $imagePath Path to the image file
     * @return string Hex color code
     */
    public function getPrimaryColor($imagePath)
    {
        $colors = $this->extractColors($imagePath, 1);
        return !empty($colors) ? $colors[0] : '#2563eb';
    }

    /**
     * Get color palette (primary + secondary colors)
     * 
     * @param string $imagePath Path to the image file
     * @return array Array with 'primary', 'secondary', 'accent', etc.
     */
    public function getColorPalette($imagePath)
    {
        $colors = $this->extractColors($imagePath, 5);
        
        return [
            'primary' => $colors[0] ?? '#2563eb',
            'secondary' => $colors[1] ?? '#1e40af',
            'accent' => $colors[2] ?? '#3b82f6',
            'tertiary' => $colors[3] ?? '#60a5fa',
            'quaternary' => $colors[4] ?? '#93c5fd',
        ];
    }

    /**
     * Convert hex to RGB
     * 
     * @param string $hex Hex color code
     * @return array ['r' => int, 'g' => int, 'b' => int]
     */
    public function hexToRgb($hex)
    {
        $hex = ltrim($hex, '#');
        return [
            'r' => hexdec(substr($hex, 0, 2)),
            'g' => hexdec(substr($hex, 2, 2)),
            'b' => hexdec(substr($hex, 4, 2)),
        ];
    }

    /**
     * Check if color is light or dark
     * 
     * @param string $hex Hex color code
     * @return bool True if light, false if dark
     */
    public function isLight($hex)
    {
        $rgb = $this->hexToRgb($hex);
        $brightness = ($rgb['r'] * 299 + $rgb['g'] * 587 + $rgb['b'] * 114) / 1000;
        return $brightness > 128;
    }
}


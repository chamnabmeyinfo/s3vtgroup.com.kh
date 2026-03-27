<?php
/**
 * Cache Service
 * Provides caching functionality for improved performance
 */
namespace App\Services;

class CacheService {
    private $cacheDir;
    private $defaultTTL = 3600; // 1 hour
    
    public function __construct() {
        $this->cacheDir = __DIR__ . '/../../storage/cache/';
        if (!is_dir($this->cacheDir)) {
            mkdir($this->cacheDir, 0755, true);
        }
    }
    
    /**
     * Get cached value
     */
    public function get($key) {
        $file = $this->getCacheFile($key);
        
        if (!file_exists($file)) {
            return null;
        }
        
        $data = json_decode(file_get_contents($file), true);
        
        // Check if expired
        if (isset($data['expires_at']) && $data['expires_at'] < time()) {
            $this->delete($key);
            return null;
        }
        
        return $data['value'] ?? null;
    }
    
    /**
     * Set cached value
     */
    public function set($key, $value, $ttl = null) {
        $ttl = $ttl ?? $this->defaultTTL;
        $file = $this->getCacheFile($key);
        
        $data = [
            'value' => $value,
            'created_at' => time(),
            'expires_at' => time() + $ttl
        ];
        
        file_put_contents($file, json_encode($data));
    }
    
    /**
     * Delete cached value
     */
    public function delete($key) {
        $file = $this->getCacheFile($key);
        if (file_exists($file)) {
            unlink($file);
        }
    }
    
    /**
     * Clear all cache
     */
    public function clear() {
        $files = glob($this->cacheDir . '*');
        foreach ($files as $file) {
            if (is_file($file)) {
                unlink($file);
            }
        }
    }
    
    /**
     * Remember - Get or set
     */
    public function remember($key, $callback, $ttl = null) {
        $value = $this->get($key);
        
        if ($value === null) {
            $value = $callback();
            $this->set($key, $value, $ttl);
        }
        
        return $value;
    }
    
    /**
     * Get cache file path
     */
    private function getCacheFile($key) {
        return $this->cacheDir . md5($key) . '.cache';
    }
}


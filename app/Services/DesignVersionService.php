<?php

namespace App\Services;

/**
 * Design Version Service
 * Handles versioning, rollback, and management of front-end design files
 */
class DesignVersionService
{
    private $backupDir;
    private $filesToVersion = [
        // CSS Files
        'assets/css/style.css',
        'assets/css/product-images.css',
        
        // JavaScript Files
        'assets/js/main.js',
        'assets/js/advanced-search.js',
        'assets/js/advanced-ux.js',
        'assets/js/smart-search.js',
        
        // Include Files
        'includes/header.php',
        'includes/footer.php',
        'includes/message.php',
        
        // Main Templates
        'index.php',
        'products.php',
        'product.php',
        'contact.php',
        'quote.php',
        'checkout.php',
        'cart.php',
    ];
    
    public function __construct()
    {
        $this->backupDir = __DIR__ . '/../../storage/design-backups';
        
        // Create backup directory if it doesn't exist
        if (!is_dir($this->backupDir)) {
            mkdir($this->backupDir, 0755, true);
        }
        
        // Create .gitkeep file
        if (!file_exists($this->backupDir . '/.gitkeep')) {
            file_put_contents($this->backupDir . '/.gitkeep', '');
        }
    }
    
    /**
     * Create a new design version snapshot
     */
    public function createVersion($description = 'Manual snapshot', $createdBy = 'system')
    {
        $versionId = 'v' . date('YmdHis') . '_' . uniqid();
        $versionDir = $this->backupDir . '/' . $versionId;
        
        if (!is_dir($versionDir)) {
            mkdir($versionDir, 0755, true);
        }
        
        $filesBackedUp = [];
        $basePath = __DIR__ . '/../..';
        
        // Backup each file
        foreach ($this->filesToVersion as $file) {
            $sourcePath = $basePath . '/' . $file;
            $destPath = $versionDir . '/' . str_replace('/', '_', $file);
            
            if (file_exists($sourcePath)) {
                // Create directory structure if needed
                $destDir = dirname($destPath);
                if (!is_dir($destDir)) {
                    mkdir($destDir, 0755, true);
                }
                
                if (copy($sourcePath, $destPath)) {
                    $filesBackedUp[] = $file;
                }
            }
        }
        
        // Create version info file
        $versionInfo = [
            'version_id' => $versionId,
            'description' => $description,
            'created_at' => date('Y-m-d H:i:s'),
            'created_by' => $createdBy,
            'files_count' => count($filesBackedUp),
            'files' => $filesBackedUp
        ];
        
        file_put_contents(
            $versionDir . '/version-info.json',
            json_encode($versionInfo, JSON_PRETTY_PRINT)
        );
        
        // Store in database
        $this->saveVersionToDatabase($versionInfo);
        
        return [
            'success' => true,
            'version_id' => $versionId,
            'files_backed_up' => count($filesBackedUp),
            'info' => $versionInfo
        ];
    }
    
    /**
     * Rollback to a specific version
     */
    public function rollbackToVersion($versionId)
    {
        $versionDir = $this->backupDir . '/' . $versionId;
        $versionInfoFile = $versionDir . '/version-info.json';
        
        if (!file_exists($versionInfoFile)) {
            return [
                'success' => false,
                'error' => 'Version not found'
            ];
        }
        
        $versionInfo = json_decode(file_get_contents($versionInfoFile), true);
        $basePath = __DIR__ . '/../..';
        $filesRestored = [];
        
        // Restore each file
        foreach ($versionInfo['files'] as $file) {
            $backupPath = $versionDir . '/' . str_replace('/', '_', $file);
            $targetPath = $basePath . '/' . $file;
            
            if (file_exists($backupPath)) {
                // Create directory if needed
                $targetDir = dirname($targetPath);
                if (!is_dir($targetDir)) {
                    mkdir($targetDir, 0755, true);
                }
                
                if (copy($backupPath, $targetPath)) {
                    $filesRestored[] = $file;
                }
            }
        }
        
        // Update database
        $this->updateVersionStatus($versionId, 'rolled_back', date('Y-m-d H:i:s'));
        
        return [
            'success' => true,
            'version_id' => $versionId,
            'files_restored' => count($filesRestored),
            'message' => 'Successfully rolled back to version ' . $versionId
        ];
    }
    
    /**
     * Delete a version
     */
    public function deleteVersion($versionId)
    {
        $versionDir = $this->backupDir . '/' . $versionId;
        
        if (!is_dir($versionDir)) {
            return [
                'success' => false,
                'error' => 'Version directory not found'
            ];
        }
        
        // Delete directory recursively
        $this->deleteDirectory($versionDir);
        
        // Remove from database
        $this->removeVersionFromDatabase($versionId);
        
        return [
            'success' => true,
            'message' => 'Version deleted successfully'
        ];
    }
    
    /**
     * Get all versions
     */
    public function getAllVersions()
    {
        $versions = [];
        
        // Get from database
        try {
            $dbVersions = \db()->fetchAll(
                "SELECT * FROM design_versions ORDER BY created_at DESC"
            );
            
            foreach ($dbVersions as $dbVersion) {
                $versionDir = $this->backupDir . '/' . $dbVersion['version_id'];
                $versionInfoFile = $versionDir . '/version-info.json';
                
                if (file_exists($versionInfoFile)) {
                    $info = json_decode(file_get_contents($versionInfoFile), true);
                    $info['id'] = $dbVersion['id'];
                    $info['status'] = $dbVersion['status'] ?? 'active';
                    $info['rolled_back_at'] = $dbVersion['rolled_back_at'] ?? null;
                    $versions[] = $info;
                }
            }
        } catch (\Exception $e) {
            // If table doesn't exist, scan directory
            if (is_dir($this->backupDir)) {
                $dirs = scandir($this->backupDir);
                foreach ($dirs as $dir) {
                    if ($dir === '.' || $dir === '..' || $dir === '.gitkeep') continue;
                    
                    $versionDir = $this->backupDir . '/' . $dir;
                    $versionInfoFile = $versionDir . '/version-info.json';
                    
                    if (file_exists($versionInfoFile)) {
                        $info = json_decode(file_get_contents($versionInfoFile), true);
                        $info['status'] = 'active';
                        $versions[] = $info;
                    }
                }
            }
        }
        
        // Sort by date descending
        usort($versions, function($a, $b) {
            return strtotime($b['created_at']) - strtotime($a['created_at']);
        });
        
        return $versions;
    }
    
    /**
     * Get version details
     */
    public function getVersionDetails($versionId)
    {
        $versionDir = $this->backupDir . '/' . $versionId;
        $versionInfoFile = $versionDir . '/version-info.json';
        
        if (!file_exists($versionInfoFile)) {
            return null;
        }
        
        $info = json_decode(file_get_contents($versionInfoFile), true);
        
        // Get status from database
        try {
            $dbVersion = \db()->fetchOne(
                "SELECT * FROM design_versions WHERE version_id = :id",
                ['id' => $versionId]
            );
            
            if ($dbVersion) {
                $info['status'] = $dbVersion['status'] ?? 'active';
                $info['rolled_back_at'] = $dbVersion['rolled_back_at'] ?? null;
            }
        } catch (\Exception $e) {
            $info['status'] = 'active';
        }
        
        return $info;
    }
    
    /**
     * Save version to database
     */
    private function saveVersionToDatabase($versionInfo)
    {
        try {
            // Check if table exists, create if not
            $this->ensureTableExists();
            
            \db()->insert('design_versions', [
                'version_id' => $versionInfo['version_id'],
                'description' => $versionInfo['description'],
                'created_at' => $versionInfo['created_at'],
                'created_by' => $versionInfo['created_by'],
                'files_count' => $versionInfo['files_count'],
                'status' => 'active'
            ]);
        } catch (\Exception $e) {
            // Table might not exist yet, that's okay
        }
    }
    
    /**
     * Update version status in database
     */
    private function updateVersionStatus($versionId, $status, $rolledBackAt = null)
    {
        try {
            $data = [
                'status' => $status
            ];
            
            if ($rolledBackAt) {
                $data['rolled_back_at'] = $rolledBackAt;
            }
            
            \db()->update(
                'design_versions',
                $data,
                'version_id = :id',
                ['id' => $versionId]
            );
        } catch (\Exception $e) {
            // Ignore if table doesn't exist
        }
    }
    
    /**
     * Remove version from database
     */
    private function removeVersionFromDatabase($versionId)
    {
        try {
            \db()->delete('design_versions', 'version_id = :id', ['id' => $versionId]);
        } catch (\Exception $e) {
            // Ignore if table doesn't exist
        }
    }
    
    /**
     * Ensure design_versions table exists
     */
    private function ensureTableExists()
    {
        try {
            // Get PDO instance to execute DDL
            // Using exec() for DDL statements (CREATE TABLE)
            $pdo = \db()->getPdo();
            $pdo->exec("
                CREATE TABLE IF NOT EXISTS design_versions (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    version_id VARCHAR(50) UNIQUE NOT NULL,
                    description TEXT,
                    created_at DATETIME NOT NULL,
                    created_by VARCHAR(100),
                    files_count INT DEFAULT 0,
                    status VARCHAR(20) DEFAULT 'active',
                    rolled_back_at DATETIME NULL,
                    INDEX idx_version_id (version_id),
                    INDEX idx_created_at (created_at)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
            ");
        } catch (\Exception $e) {
            // Table might already exist or database error
            // Continue anyway - file-based versioning will still work
            // The IF NOT EXISTS clause should prevent errors, but catch just in case
        }
    }
    
    /**
     * Delete directory recursively
     */
    private function deleteDirectory($dir)
    {
        if (!is_dir($dir)) {
            return false;
        }
        
        $files = array_diff(scandir($dir), ['.', '..']);
        
        foreach ($files as $file) {
            $path = $dir . '/' . $file;
            is_dir($path) ? $this->deleteDirectory($path) : unlink($path);
        }
        
        return rmdir($dir);
    }
    
    /**
     * Get current active version (latest)
     */
    public function getCurrentVersion()
    {
        $versions = $this->getAllVersions();
        return !empty($versions) ? $versions[0] : null;
    }
    
    /**
     * Compare two versions
     */
    public function compareVersions($versionId1, $versionId2)
    {
        $version1 = $this->getVersionDetails($versionId1);
        $version2 = $this->getVersionDetails($versionId2);
        
        if (!$version1 || !$version2) {
            return null;
        }
        
        $files1 = $version1['files'] ?? [];
        $files2 = $version2['files'] ?? [];
        
        $added = array_diff($files2, $files1);
        $removed = array_diff($files1, $files2);
        $common = array_intersect($files1, $files2);
        
        return [
            'added' => array_values($added),
            'removed' => array_values($removed),
            'common' => array_values($common),
            'version1' => $version1,
            'version2' => $version2
        ];
    }
}


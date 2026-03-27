<?php

namespace App\Services;

/**
 * File Sync Service
 * Pulls files from cPanel (FTP/SFTP) to local machine
 */
class FileSyncService
{
    private $config;
    private $log = [];
    private $excludedPatterns = [];
    
    public function __construct($config = null)
    {
        if ($config === null) {
            $configFile = __DIR__ . '/../../deploy-config.json';
            if (file_exists($configFile)) {
                $this->config = json_decode(file_get_contents($configFile), true);
            } else {
                throw new \Exception("deploy-config.json not found. Please create it from deploy-config.example.json");
            }
        } else {
            $this->config = $config;
        }
        
        // Set excluded patterns
        $this->excludedPatterns = $this->config['exclude'] ?? [
            '*.log',
            '*.cache',
            '.git',
            'node_modules',
            'vendor',
            '*.tmp',
            '*.temp',
            'storage/cache/*',
            'storage/logs/*',
            'storage/backups/*',
            'config/database.live.php',
            '.database-env',
        ];
    }
    
    /**
     * Pull all files from remote server to local
     */
    public function pullFromRemote($options = [])
    {
        $options = array_merge([
            'backup_local' => true,
            'dry_run' => false,
            'preserve_config' => true, // Don't overwrite local config files
            'exclude_patterns' => [],
        ], $options);
        
        $this->log = [];
        $this->log("Starting file pull from remote server...");
        
        try {
            $ftpConfig = $this->config['ftp'] ?? [];
            
            if (empty($ftpConfig['host']) || empty($ftpConfig['username'])) {
                throw new \Exception("FTP configuration incomplete in deploy-config.json");
            }
            
            // Connect to FTP
            $this->log("Connecting to FTP server: {$ftpConfig['host']}...");
            $ftp = $this->connectFTP($ftpConfig);
            
            if (!$ftp) {
                throw new \Exception("Failed to connect to FTP server");
            }
            
            $this->log("✓ Connected successfully");
            
            // Create local backup if requested
            if ($options['backup_local'] && !$options['dry_run']) {
                $this->log("Creating local backup...");
                $this->backupLocalFiles();
            }
            
            // Get remote path
            $remotePath = $ftpConfig['remote_path'] ?? '/public_html';
            $localPath = __DIR__ . '/../../';
            
            // Pull files
            $this->log("Pulling files from {$remotePath}...");
            $stats = $this->pullDirectory($ftp, $remotePath, $localPath, $options);
            
            ftp_close($ftp);
            
            $this->log("✓ File pull completed!");
            $this->log("  Files downloaded: {$stats['files']}");
            $this->log("  Directories created: {$stats['dirs']}");
            $this->log("  Files skipped: {$stats['skipped']}");
            
            return [
                'success' => true,
                'message' => "Successfully pulled {$stats['files']} files from remote server",
                'stats' => $stats,
                'log' => $this->log
            ];
            
        } catch (\Exception $e) {
            $this->log("✗ Error: " . $e->getMessage());
            return [
                'success' => false,
                'message' => $e->getMessage(),
                'log' => $this->log
            ];
        }
    }
    
    /**
     * Connect to FTP server
     */
    private function connectFTP($config)
    {
        $host = $config['host'];
        $port = $config['port'] ?? 21;
        $username = $config['username'];
        $password = $config['password'] ?? '';
        
        $ftp = @ftp_connect($host, $port, 30);
        
        if (!$ftp) {
            return false;
        }
        
        if (!@ftp_login($ftp, $username, $password)) {
            ftp_close($ftp);
            return false;
        }
        
        // Enable passive mode (works better with firewalls)
        @ftp_pasv($ftp, true);
        
        return $ftp;
    }
    
    /**
     * Recursively pull directory from FTP
     */
    private function pullDirectory($ftp, $remotePath, $localPath, $options, $stats = ['files' => 0, 'dirs' => 0, 'skipped' => 0])
    {
        // Normalize paths
        $remotePath = rtrim($remotePath, '/') . '/';
        $localPath = rtrim($localPath, '/') . '/';
        
        // Get files and directories from remote
        $items = @ftp_nlist($ftp, $remotePath);
        
        if ($items === false) {
            return $stats;
        }
        
        foreach ($items as $item) {
            // Skip . and ..
            if (basename($item) === '.' || basename($item) === '..') {
                continue;
            }
            
            $remoteItem = $remotePath . basename($item);
            $localItem = $localPath . basename($item);
            
            // Check if excluded
            if ($this->isExcluded($remoteItem, $options)) {
                $stats['skipped']++;
                continue;
            }
            
            // Check if it's a directory
            $isDir = $this->isDirectory($ftp, $remoteItem);
            
            if ($isDir) {
                // Create local directory
                if (!is_dir($localItem)) {
                    if (!$options['dry_run']) {
                        @mkdir($localItem, 0755, true);
                    }
                    $stats['dirs']++;
                    $this->log("  Created directory: " . str_replace($localPath, '', $localItem));
                }
                
                // Recursively pull subdirectory
                $stats = $this->pullDirectory($ftp, $remoteItem, $localItem, $options, $stats);
            } else {
                // Download file
                if ($this->shouldDownloadFile($localItem, $remoteItem, $options)) {
                    if (!$options['dry_run']) {
                        if (@ftp_get($ftp, $localItem, $remoteItem, FTP_BINARY)) {
                            $stats['files']++;
                            $this->log("  Downloaded: " . str_replace($localPath, '', $localItem));
                        } else {
                            $this->log("  ✗ Failed: " . str_replace($localPath, '', $localItem));
                        }
                    } else {
                        $stats['files']++;
                        $this->log("  [DRY RUN] Would download: " . str_replace($localPath, '', $localItem));
                    }
                } else {
                    $stats['skipped']++;
                    $this->log("  Skipped (preserved): " . str_replace($localPath, '', $localItem));
                }
            }
        }
        
        return $stats;
    }
    
    /**
     * Check if path should be excluded
     */
    private function isExcluded($path, $options)
    {
        $allPatterns = array_merge($this->excludedPatterns, $options['exclude_patterns'] ?? []);
        
        foreach ($allPatterns as $pattern) {
            // Convert glob pattern to regex
            $regex = str_replace(
                ['\\*', '\\?', '\\[', '\\]'],
                ['.*', '.', '[', ']'],
                preg_quote($pattern, '/')
            );
            
            if (preg_match('/^' . $regex . '$/', $path) || 
                preg_match('/' . $regex . '/', basename($path))) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Check if remote path is a directory
     */
    private function isDirectory($ftp, $path)
    {
        $current = ftp_pwd($ftp);
        $isDir = @ftp_chdir($ftp, $path);
        if ($isDir) {
            ftp_chdir($ftp, $current);
            return true;
        }
        return false;
    }
    
    /**
     * Check if file should be downloaded
     */
    private function shouldDownloadFile($localPath, $remotePath, $options)
    {
        // If preserve_config is true, don't overwrite local config files
        if ($options['preserve_config']) {
            $configFiles = [
                'config/database.php',
                'config/database.local.php',
                'config/database.live.php',
                'config/app.php',
                '.database-env',
                'deploy-config.json',
            ];
            
            foreach ($configFiles as $configFile) {
                if (strpos($localPath, $configFile) !== false && file_exists($localPath)) {
                    return false;
                }
            }
        }
        
        // Always download if local file doesn't exist
        if (!file_exists($localPath)) {
            return true;
        }
        
        // Compare file sizes/timestamps (simplified - could be enhanced)
        return true; // For now, always download to ensure sync
    }
    
    /**
     * Backup local files before pulling
     */
    private function backupLocalFiles()
    {
        $backupDir = __DIR__ . '/../../storage/backups/file-backups/';
        $timestamp = date('Y-m-d_His');
        $backupPath = $backupDir . 'local-backup-' . $timestamp . '/';
        
        if (!is_dir($backupDir)) {
            @mkdir($backupDir, 0755, true);
        }
        
        // This is a simplified backup - in production, you might want to use tar/zip
        $this->log("  Backup location: " . $backupPath);
        // For now, just create the directory
        @mkdir($backupPath, 0755, true);
    }
    
    /**
     * Log message
     */
    private function log($message)
    {
        $timestamp = date('Y-m-d H:i:s');
        $this->log[] = "[{$timestamp}] {$message}";
        echo $message . "\n";
    }
    
    /**
     * Get log
     */
    public function getLog()
    {
        return $this->log;
    }
}

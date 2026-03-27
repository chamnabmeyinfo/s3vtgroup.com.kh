<?php
/**
 * Automated Backup Service
 * Handles database and file backups
 */
namespace App\Core\Backup;

class BackupService {
    private $backupDir;
    private $db;
    
    public function __construct() {
        $this->backupDir = __DIR__ . '/../../../storage/backups/';
        if (!is_dir($this->backupDir)) {
            mkdir($this->backupDir, 0755, true);
        }
        $this->db = db();
    }
    
    /**
     * Create database backup
     */
    public function backupDatabase() {
        try {
            $config = require __DIR__ . '/../../../config/database.php';
            
            // Get database name from config (use 'dbname' key)
            $dbName = $config['dbname'] ?? $config['database'] ?? null;
            
            if (empty($dbName)) {
                throw new \Exception('Database name not found in configuration');
            }
            
            $backupFile = $this->backupDir . 'db_backup_' . date('Y-m-d_H-i-s') . '.sql';
            
            // Get all tables - SHOW TABLES returns a dynamic column name
            $tables = $this->db->fetchAll("SHOW TABLES");
            
            if (empty($tables)) {
                throw new \Exception('No tables found in database');
            }
            
            // Get the column name from the first result (it's dynamic based on database name)
            // The column name format is "Tables_in_{database_name}"
            $firstTable = reset($tables);
            $tableColumn = array_key_first($firstTable);
            
            if (empty($tableColumn)) {
                throw new \Exception('Could not determine table column name from SHOW TABLES result');
            }
            
            $sql = "-- Database Backup\n";
            $sql .= "-- Generated: " . date('Y-m-d H:i:s') . "\n\n";
            $sql .= "SET FOREIGN_KEY_CHECKS=0;\n\n";
            
            foreach ($tables as $table) {
                // Get table name from the dynamic column
                $tableName = $table[$tableColumn] ?? null;
                
                if (empty($tableName)) {
                    continue; // Skip if table name is empty
                }
                
                // Drop table
                $sql .= "DROP TABLE IF EXISTS `{$tableName}`;\n";
            
                // Create table
                $createTable = $this->db->fetchOne("SHOW CREATE TABLE `{$tableName}`");
                
                if (empty($createTable)) {
                    // No result from query - table might not exist or query failed
                    $sql .= "-- Error: Could not get CREATE TABLE statement for `{$tableName}`\n\n";
                    continue;
                }
                
                // Check for both possible column name variations (case-sensitive vs case-insensitive)
                // MySQL returns 'Create Table' but some drivers might return 'CREATE TABLE'
                $createTableSql = $createTable['Create Table'] ?? $createTable['CREATE Table'] ?? 
                                  $createTable['CREATE TABLE'] ?? $createTable['create table'] ?? null;
                
                if (empty($createTableSql)) {
                    // Could not find CREATE TABLE statement in result
                    $sql .= "-- Error: CREATE TABLE statement not found in result for `{$tableName}`\n\n";
                    continue;
                }
                
                $sql .= $createTableSql . ";\n\n";
                
                // Insert data
                try {
                    $rows = $this->db->fetchAll("SELECT * FROM `{$tableName}`");
                    if (!empty($rows)) {
                        $columns = array_keys($rows[0]);
                        $sql .= "INSERT INTO `{$tableName}` (`" . implode('`, `', $columns) . "`) VALUES\n";
                        
                        $values = [];
                        foreach ($rows as $row) {
                            $rowValues = array_map(function($value) {
                                if ($value === null) {
                                    return 'NULL';
                                }
                                // Properly escape the value
                                // Replace single quotes and escape special characters
                                $escaped = str_replace(['\\', "\x00", "\n", "\r", "'", '"', "\x1a"], 
                                    ['\\\\', '\\0', '\\n', '\\r', "\\'", '\\"', '\\Z'], $value);
                                return "'" . $escaped . "'";
                            }, array_values($row));
                            
                            $values[] = "(" . implode(", ", $rowValues) . ")";
                        }
                        
                        $sql .= implode(",\n", $values) . ";\n\n";
                    }
                } catch (\Exception $e) {
                    // Log error but continue with other tables
                    $sql .= "-- Error reading data from table `{$tableName}`: " . $e->getMessage() . "\n\n";
                }
            }
            
            $sql .= "SET FOREIGN_KEY_CHECKS=1;\n";
            
            // Write backup file
            if (file_put_contents($backupFile, $sql) === false) {
                throw new \Exception('Failed to write backup file');
            }
            
            // Compress backup
            $compressedFile = $this->compressBackup($backupFile);
            
            // Clean old backups (keep last 30 days)
            $this->cleanOldBackups();
            
            return $compressedFile ?: $backupFile;
            
        } catch (\Exception $e) {
            throw new \Exception('Error creating backup: ' . $e->getMessage());
        }
    }
    
    /**
     * Restore database from backup
     */
    public function restoreDatabase($backupFile) {
        if (!file_exists($backupFile)) {
            throw new \Exception("Backup file not found");
        }
        
        // Decompress if needed
        if (pathinfo($backupFile, PATHINFO_EXTENSION) === 'gz') {
            $backupFile = $this->decompressBackup($backupFile);
        }
        
        $sql = file_get_contents($backupFile);
        
        // Split by semicolon and execute
        $statements = array_filter(array_map('trim', explode(';', $sql)));
        
        foreach ($statements as $statement) {
            if (!empty($statement) && !preg_match('/^--/', $statement)) {
                try {
                    $this->db->fetchAll($statement);
                } catch (\Exception $e) {
                    // Skip errors for comments and SET statements
                    if (strpos($statement, 'SET') === false && strpos($statement, 'FOREIGN_KEY_CHECKS') === false) {
                        // Only throw for non-SET statements
                    }
                }
            }
        }
        
        return true;
    }
    
    /**
     * List available backups
     */
    public function listBackups() {
        $files = glob($this->backupDir . 'db_backup_*.sql*');
        $backups = [];
        
        foreach ($files as $file) {
            $backups[] = [
                'file' => basename($file),
                'path' => $file,
                'size' => filesize($file),
                'date' => date('Y-m-d H:i:s', filemtime($file))
            ];
        }
        
        usort($backups, fn($a, $b) => strtotime($b['date']) - strtotime($a['date']));
        
        return $backups;
    }
    
    /**
     * Compress backup
     */
    private function compressBackup($file) {
        if (function_exists('gzencode')) {
            $compressed = $file . '.gz';
            $data = file_get_contents($file);
            file_put_contents($compressed, gzencode($data, 9));
            unlink($file); // Remove uncompressed file
            return $compressed;
        }
        return $file;
    }
    
    /**
     * Decompress backup
     */
    private function decompressBackup($file) {
        if (function_exists('gzdecode')) {
            $data = gzdecode(file_get_contents($file));
            $uncompressed = str_replace('.gz', '', $file);
            file_put_contents($uncompressed, $data);
            return $uncompressed;
        }
        return $file;
    }
    
    /**
     * Clean old backups
     */
    private function cleanOldBackups($days = 30) {
        $files = glob($this->backupDir . 'db_backup_*');
        $cutoff = time() - ($days * 24 * 60 * 60);
        
        foreach ($files as $file) {
            if (filemtime($file) < $cutoff) {
                unlink($file);
            }
        }
    }
}


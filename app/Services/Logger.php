<?php
/**
 * Advanced Logging Service
 * Provides comprehensive logging functionality
 */
namespace App\Services;

class Logger {
    private $logDir;
    private $logLevels = ['debug', 'info', 'warning', 'error', 'critical'];
    private $minLevel = 'info';
    
    public function __construct() {
        $this->logDir = __DIR__ . '/../../storage/logs/';
        if (!is_dir($this->logDir)) {
            mkdir($this->logDir, 0755, true);
        }
    }
    
    /**
     * Log message
     */
    public function log($level, $message, $context = []) {
        if (!$this->shouldLog($level)) {
            return;
        }
        
        $logEntry = [
            'timestamp' => date('Y-m-d H:i:s'),
            'level' => strtoupper($level),
            'message' => $message,
            'context' => $context,
            'ip' => function_exists('get_real_ip') ? get_real_ip() : ($_SERVER['REMOTE_ADDR'] ?? 'unknown'),
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown',
            'request_uri' => $_SERVER['REQUEST_URI'] ?? 'unknown'
        ];
        
        $logFile = $this->logDir . date('Y-m-d') . '.log';
        $logLine = json_encode($logEntry) . PHP_EOL;
        
        file_put_contents($logFile, $logLine, FILE_APPEND);
        
        // Also log errors to error log if critical
        if ($level === 'error' || $level === 'critical') {
            error_log("[$level] $message - " . json_encode($context));
        }
    }
    
    /**
     * Debug log
     */
    public function debug($message, $context = []) {
        $this->log('debug', $message, $context);
    }
    
    /**
     * Info log
     */
    public function info($message, $context = []) {
        $this->log('info', $message, $context);
    }
    
    /**
     * Warning log
     */
    public function warning($message, $context = []) {
        $this->log('warning', $message, $context);
    }
    
    /**
     * Error log
     */
    public function error($message, $context = []) {
        $this->log('error', $message, $context);
    }
    
    /**
     * Critical log
     */
    public function critical($message, $context = []) {
        $this->log('critical', $message, $context);
    }
    
    /**
     * Log API request
     */
    public function logApiRequest($method, $endpoint, $params = [], $response = null) {
        $this->info('API Request', [
            'method' => $method,
            'endpoint' => $endpoint,
            'params' => $params,
            'response_status' => http_response_code()
        ]);
    }
    
    /**
     * Log database query
     */
    public function logQuery($query, $params = [], $executionTime = null) {
        if ($executionTime && $executionTime > 1.0) { // Log slow queries
            $this->warning('Slow Query', [
                'query' => $query,
                'params' => $params,
                'execution_time' => $executionTime . 's'
            ]);
        }
    }
    
    /**
     * Check if should log
     */
    private function shouldLog($level) {
        $levelIndex = array_search(strtolower($level), $this->logLevels);
        $minLevelIndex = array_search(strtolower($this->minLevel), $this->logLevels);
        
        return $levelIndex !== false && $levelIndex >= $minLevelIndex;
    }
    
    /**
     * Get logs
     */
    public function getLogs($date = null, $level = null, $limit = 100) {
        $date = $date ?? date('Y-m-d');
        $logFile = $this->logDir . $date . '.log';
        
        if (!file_exists($logFile)) {
            return [];
        }
        
        $lines = file($logFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        $logs = [];
        
        foreach ($lines as $line) {
            $log = json_decode($line, true);
            if ($log && (!$level || strtolower($log['level']) === strtolower($level))) {
                $logs[] = $log;
            }
        }
        
        return array_slice(array_reverse($logs), 0, $limit);
    }
}


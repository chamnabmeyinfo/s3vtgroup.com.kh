<?php
/**
 * Cron Job Scheduler
 * Run this file via cron job or scheduled task
 * Example: Run every 5 minutes: php /path/to/cron/scheduler.php
 */
require_once __DIR__ . '/../bootstrap/app.php';

use App\Core\Backup\BackupService;
use App\Services\Logger;

$logger = new Logger();
$logger->info('Cron scheduler started');

// Daily backup (runs at 2 AM if scheduled)
if (date('H') == '02' && date('i') < 5) {
    try {
        $backup = new BackupService();
        $backupFile = $backup->backupDatabase();
        $logger->info('Daily backup completed', ['file' => basename($backupFile)]);
    } catch (Exception $e) {
        $logger->error('Backup failed', ['error' => $e->getMessage()]);
    }
}

// Clean old cache files (daily)
if (date('H') == '03') {
    try {
        $cacheDir = __DIR__ . '/../storage/cache/';
        $files = glob($cacheDir . '*.cache');
        $cleaned = 0;
        
        foreach ($files as $file) {
            $data = json_decode(file_get_contents($file), true);
            if (isset($data['expires_at']) && $data['expires_at'] < time()) {
                unlink($file);
                $cleaned++;
            }
        }
        
        $logger->info('Cache cleaned', ['files_removed' => $cleaned]);
    } catch (Exception $e) {
        $logger->error('Cache cleanup failed', ['error' => $e->getMessage()]);
    }
}

// Process email queue (every 5 minutes)
try {
    processEmailQueue();
} catch (Exception $e) {
    $logger->error('Email queue processing failed', ['error' => $e->getMessage()]);
}

// Update product recommendations cache (hourly)
if (date('i') < 5) {
    try {
        updateRecommendationsCache();
        $logger->info('Recommendations cache updated');
    } catch (Exception $e) {
        $logger->error('Recommendations update failed', ['error' => $e->getMessage()]);
    }
}

$logger->info('Cron scheduler completed');

function processEmailQueue() {
    $db = db();
    
    // Process pending emails (limit 10 per run)
    $emails = $db->fetchAll(
        "SELECT * FROM email_queue WHERE status = 'pending' ORDER BY created_at ASC LIMIT 10"
    );
    
    foreach ($emails as $email) {
        // In a real app, send email here
        // For now, just mark as sent
        $db->update('email_queue', 
            ['status' => 'sent', 'sent_at' => date('Y-m-d H:i:s'), 'attempts' => $email['attempts'] + 1],
            'id = :id',
            ['id' => $email['id']]
        );
    }
}

function updateRecommendationsCache() {
    $db = db();
    
    // Clear expired recommendations
    $db->query("DELETE FROM smart_recommendations WHERE expires_at < NOW()");
    
    // Update trending products
    $db->query("
        UPDATE products p
        SET p.updated_at = NOW()
        WHERE p.id IN (
            SELECT product_id FROM (
                SELECT product_id FROM customer_behavior
                WHERE action_type = 'view'
                AND created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
                GROUP BY product_id
                ORDER BY COUNT(*) DESC
                LIMIT 10
            ) as trending
        )
    ");
}


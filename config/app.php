<?php
/**
 * Application Configuration
 * 
 * Copy this file to app.php and update with your production settings
 */
return [
    'name' => 'Forklift & Equipment Pro',
    'url' => 'https://s3vtgroup.com.kh',  // ⚠️ CHANGE to your actual domain
    'timezone' => 'UTC',
    'debug' => false,  // ⚠️ Set to false in production
    'uploads_dir' => __DIR__ . '/../storage/uploads',
    'cache_dir' => __DIR__ . '/../storage/cache',
];

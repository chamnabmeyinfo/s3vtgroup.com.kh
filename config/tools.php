<?php
return array (
  'clear_cache' => 
  array (
    'name' => 'Clear Cache',
    'description' => 'Clear all cached data and temporary files',
    'category' => 'Maintenance',
    'icon' => 'fa-trash-alt',
    'function' => 'clearCacheTool',
  ),
  'optimize_database' => 
  array (
    'name' => 'Optimize Database',
    'description' => 'Optimize database tables for better performance',
    'category' => 'Maintenance',
    'icon' => 'fa-database',
    'function' => 'optimizeDatabaseTool',
  ),
  'clean_old_logs' => 
  array (
    'name' => 'Clean Old Logs',
    'description' => 'Remove log files older than 30 days',
    'category' => 'Maintenance',
    'icon' => 'fa-file-alt',
    'function' => 'cleanOldLogsTool',
  ),
  'regenerate_thumbnails' => 
  array (
    'name' => 'Regenerate Thumbnails',
    'description' => 'Regenerate all product image thumbnails',
    'category' => 'Images',
    'icon' => 'fa-images',
    'function' => 'regenerateThumbnailsTool',
  ),
  'update_product_slugs' => 
  array (
    'name' => 'Update Product Slugs',
    'description' => 'Regenerate SEO-friendly slugs for all products',
    'category' => 'SEO',
    'icon' => 'fa-link',
    'function' => 'updateProductSlugsTool',
  ),
  'check_broken_images' => 
  array (
    'name' => 'Check Broken Images',
    'description' => 'Find and report broken image links',
    'category' => 'Images',
    'icon' => 'fa-exclamation-triangle',
    'function' => 'checkBrokenImagesTool',
  ),
);

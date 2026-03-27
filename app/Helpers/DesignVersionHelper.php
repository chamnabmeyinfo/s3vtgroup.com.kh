<?php

namespace App\Helpers;

use App\Services\DesignVersionService;

/**
 * Design Version Helper
 * Convenience functions for automatic versioning
 */
class DesignVersionHelper
{
    /**
     * Create a snapshot before making design changes
     * Call this before starting any front-end redesign
     */
    public static function snapshotBeforeChanges($description = 'Before design changes', $createdBy = 'system')
    {
        $service = new DesignVersionService();
        return $service->createVersion($description, $createdBy);
    }
    
    /**
     * Quick snapshot with auto-generated description
     */
    public static function quickSnapshot($createdBy = 'system')
    {
        $description = 'Auto-snapshot: ' . date('Y-m-d H:i:s');
        return self::snapshotBeforeChanges($description, $createdBy);
    }
}


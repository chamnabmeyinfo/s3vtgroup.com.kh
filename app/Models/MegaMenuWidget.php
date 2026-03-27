<?php
/**
 * Mega Menu Widget Model
 * Manages custom content blocks for mega menus
 */

namespace App\Models;

use App\Database\Connection;

class MegaMenuWidget
{
    protected $db;
    
    public function __construct()
    {
        $this->db = Connection::getInstance();
    }
    
    public function getByMenuItemId($menuItemId)
    {
        try {
            return $this->db->fetchAll(
                "SELECT * FROM mega_menu_widgets 
                 WHERE menu_item_id = :menu_item_id AND is_active = 1 
                 ORDER BY widget_column ASC, widget_order ASC",
                ['menu_item_id' => $menuItemId]
            );
        } catch (\Exception $e) {
            return [];
        }
    }
    
    public function getById($id)
    {
        try {
            return $this->db->fetchOne(
                "SELECT * FROM mega_menu_widgets WHERE id = :id",
                ['id' => $id]
            );
        } catch (\Exception $e) {
            return null;
        }
    }
    
    public function create($data)
    {
        try {
            $fields = [
                'menu_item_id', 'widget_type', 'widget_title', 'widget_content',
                'widget_image', 'widget_url', 'widget_column', 'widget_order',
                'widget_width', 'widget_style', 'is_active'
            ];
            
            $insertData = [];
            foreach ($fields as $field) {
                if (isset($data[$field])) {
                    $insertData[$field] = $data[$field];
                }
            }
            
            // Set defaults
            if (!isset($insertData['widget_type'])) {
                $insertData['widget_type'] = 'text';
            }
            if (!isset($insertData['widget_column'])) {
                $insertData['widget_column'] = 1;
            }
            if (!isset($insertData['widget_order'])) {
                $insertData['widget_order'] = 0;
            }
            if (!isset($insertData['widget_width'])) {
                $insertData['widget_width'] = 'full';
            }
            if (!isset($insertData['is_active'])) {
                $insertData['is_active'] = 1;
            }
            
            return $this->db->insert('mega_menu_widgets', $insertData);
        } catch (\Exception $e) {
            error_log('MegaMenuWidget create error: ' . $e->getMessage());
            return false;
        }
    }
    
    public function update($id, $data)
    {
        try {
            $fields = [
                'widget_type', 'widget_title', 'widget_content',
                'widget_image', 'widget_url', 'widget_column', 'widget_order',
                'widget_width', 'widget_style', 'is_active'
            ];
            
            $updateData = [];
            foreach ($fields as $field) {
                if (isset($data[$field])) {
                    $updateData[$field] = $data[$field];
                }
            }
            
            return $this->db->update('mega_menu_widgets', $updateData, 'id = :id', ['id' => $id]);
        } catch (\Exception $e) {
            return false;
        }
    }
    
    public function delete($id)
    {
        try {
            return $this->db->delete('mega_menu_widgets', 'id = :id', ['id' => $id]);
        } catch (\Exception $e) {
            return false;
        }
    }
    
    public function reorder($menuItemId, $widgetOrders)
    {
        try {
            foreach ($widgetOrders as $widgetId => $orderData) {
                $widgetId = (int)$widgetId;
                if ($widgetId <= 0) continue;
                
                $updateData = [
                    'widget_column' => (int)($orderData['column'] ?? 1),
                    'widget_order' => (int)($orderData['order'] ?? 0)
                ];
                
                $this->db->update('mega_menu_widgets', $updateData, 'id = :id AND menu_item_id = :menu_item_id', [
                    'id' => $widgetId,
                    'menu_item_id' => $menuItemId
                ]);
            }
            return true;
        } catch (\Exception $e) {
            error_log('MegaMenuWidget reorder error: ' . $e->getMessage());
            return false;
        }
    }
}

<?php
/**
 * Menu Item Model
 * Individual menu item model
 */

namespace App\Models;

use App\Database\Connection;

class MenuItem
{
    protected $db;
    
    public function __construct()
    {
        $this->db = Connection::getInstance();
    }
    
    public function getByMenuId($menuId, $activeOnly = false)
    {
        try {
            $sql = "SELECT * FROM menu_items WHERE menu_id = :menu_id";
            $params = ['menu_id' => $menuId];
            
            if ($activeOnly) {
                $sql .= " AND is_active = 1";
            }
            
            $sql .= " ORDER BY sort_order ASC, id ASC";
            
            return $this->db->fetchAll($sql, $params);
        } catch (\Exception $e) {
            return [];
        }
    }
    
    public function getById($id)
    {
        try {
            return $this->db->fetchOne("SELECT * FROM menu_items WHERE id = :id", ['id' => $id]);
        } catch (\Exception $e) {
            return null;
        }
    }
    
    public function getChildren($parentId)
    {
        try {
            return $this->db->fetchAll(
                "SELECT * FROM menu_items WHERE parent_id = :parent_id AND is_active = 1 ORDER BY sort_order ASC",
                ['parent_id' => $parentId]
            );
        } catch (\Exception $e) {
            return [];
        }
    }
    
    public function create($data)
    {
        try {
            $fields = [
                'menu_id', 'parent_id', 'title', 'url', 'type', 'object_id',
                'target', 'css_classes', 'icon', 'description', 'sort_order', 'is_active',
                'mega_menu_enabled', 'mega_menu_layout', 'mega_menu_width', 'mega_menu_columns',
                'mega_menu_content', 'mega_menu_background', 'mega_menu_custom_css'
            ];
            
            $insertData = [];
            foreach ($fields as $field) {
                if (isset($data[$field])) {
                    $insertData[$field] = $data[$field];
                }
            }
            
            // Validate required fields
            if (empty($insertData['menu_id']) || empty($insertData['title'])) {
                error_log('MenuItem create: Missing required fields');
                return false;
            }
            
            // Handle parent_id - can be null, empty string, or 0
            if (isset($insertData['parent_id'])) {
                if ($insertData['parent_id'] === '' || $insertData['parent_id'] === 'null' || $insertData['parent_id'] === 0) {
                    $insertData['parent_id'] = null;
                } else {
                    $insertData['parent_id'] = (int)$insertData['parent_id'];
                    // Validate parent exists
                    $parent = $this->getById($insertData['parent_id']);
                    if (!$parent || $parent['menu_id'] != $insertData['menu_id']) {
                        $insertData['parent_id'] = null; // Invalid parent, set to null
                    }
                }
            }
            
            // Auto-calculate sort_order if not provided
            if (!isset($insertData['sort_order']) || $insertData['sort_order'] === 0) {
                $maxOrder = $this->db->fetchOne(
                    "SELECT MAX(sort_order) as max_order FROM menu_items WHERE menu_id = :menu_id",
                    ['menu_id' => $insertData['menu_id']]
                );
                $insertData['sort_order'] = ($maxOrder['max_order'] ?? 0) + 1;
            }
            
            // Set defaults
            if (!isset($insertData['is_active'])) {
                $insertData['is_active'] = 1;
            }
            if (!isset($insertData['target'])) {
                $insertData['target'] = '_self';
            }
            if (!isset($insertData['type'])) {
                $insertData['type'] = 'custom';
            }
            
            // Ensure URL is set for custom type
            if ($insertData['type'] === 'custom' && empty($insertData['url'])) {
                $insertData['url'] = '#';
            }
            
            return $this->db->insert('menu_items', $insertData);
        } catch (\Exception $e) {
            error_log('MenuItem create error: ' . $e->getMessage());
            return false;
        }
    }
    
    public function update($id, $data)
    {
        try {
            $fields = [
                'parent_id', 'title', 'url', 'type', 'object_id',
                'target', 'css_classes', 'icon', 'description', 'sort_order', 'is_active',
                'mega_menu_enabled', 'mega_menu_layout', 'mega_menu_width', 'mega_menu_columns',
                'mega_menu_content', 'mega_menu_background', 'mega_menu_custom_css'
            ];
            
            $updateData = [];
            foreach ($fields as $field) {
                if (isset($data[$field])) {
                    $updateData[$field] = $data[$field];
                }
            }
            
            return $this->db->update('menu_items', $updateData, 'id = :id', ['id' => $id]);
        } catch (\Exception $e) {
            return false;
        }
    }
    
    public function delete($id)
    {
        try {
            return $this->db->delete('menu_items', 'id = :id', ['id' => $id]);
        } catch (\Exception $e) {
            return false;
        }
    }
    
    public function reorder($menuId, $itemOrders)
    {
        try {
            $pdo = $this->db->getPdo();
            $pdo->beginTransaction();
            
            // Validate menu exists
            $menu = $this->db->fetchOne("SELECT id FROM menus WHERE id = :id", ['id' => $menuId]);
            if (!$menu) {
                throw new \Exception('Menu not found');
            }
            
            foreach ($itemOrders as $itemId => $orderData) {
                $itemId = (int)$itemId;
                if ($itemId <= 0) continue;
                
                // Validate item belongs to menu
                $item = $this->db->fetchOne(
                    "SELECT id FROM menu_items WHERE id = :id AND menu_id = :menu_id",
                    ['id' => $itemId, 'menu_id' => $menuId]
                );
                if (!$item) continue;
                
                $updateData = ['sort_order' => (int)($orderData['order'] ?? 0)];
                
                // Handle parent_id - can be null, empty string, or integer
                if (isset($orderData['parent_id'])) {
                    $parentId = $orderData['parent_id'];
                    if ($parentId === '' || $parentId === null || $parentId === 'null' || $parentId === 0) {
                        $updateData['parent_id'] = null;
                    } else {
                        $parentId = (int)$parentId;
                        // Validate parent exists and belongs to same menu
                        if ($parentId > 0) {
                            $parent = $this->db->fetchOne(
                                "SELECT id FROM menu_items WHERE id = :id AND menu_id = :menu_id",
                                ['id' => $parentId, 'menu_id' => $menuId]
                            );
                            if ($parent && $parentId != $itemId) {
                                $updateData['parent_id'] = $parentId;
                            } else {
                                $updateData['parent_id'] = null;
                            }
                        } else {
                            $updateData['parent_id'] = null;
                        }
                    }
                }
                
                $this->db->update('menu_items', $updateData, 'id = :id', ['id' => $itemId]);
            }
            
            $pdo->commit();
            return true;
        } catch (\Exception $e) {
            if (isset($pdo)) {
                $pdo->rollBack();
            }
            error_log('Menu reorder error: ' . $e->getMessage());
            return false;
        }
    }
    
    public function duplicate($itemId, $newMenuId, $newParentId = null)
    {
        try {
            $item = $this->getById($itemId);
            if (!$item) return false;
            
            $newData = [
                'menu_id' => $newMenuId,
                'parent_id' => $newParentId,
                'title' => $item['title'],
                'url' => $item['url'],
                'type' => $item['type'],
                'object_id' => $item['object_id'],
                'target' => $item['target'],
                'css_classes' => $item['css_classes'],
                'icon' => $item['icon'],
                'description' => $item['description'],
                'is_active' => $item['is_active']
            ];
            
            $newId = $this->create($newData);
            
            // Duplicate children
            $children = $this->getChildren($itemId);
            foreach ($children as $child) {
                $this->duplicate($child['id'], $newMenuId, $newId);
            }
            
            return $newId;
        } catch (\Exception $e) {
            return false;
        }
    }
}

<?php
/**
 * Menu Model
 * WordPress-style menu container model
 */

namespace App\Models;

use App\Database\Connection;

class Menu
{
    protected $db;
    
    public function __construct()
    {
        $this->db = Connection::getInstance();
    }
    
    public function getAll()
    {
        try {
            return $this->db->fetchAll("SELECT * FROM menus ORDER BY name ASC");
        } catch (\Exception $e) {
            return [];
        }
    }
    
    public function getById($id)
    {
        try {
            return $this->db->fetchOne("SELECT * FROM menus WHERE id = :id", ['id' => $id]);
        } catch (\Exception $e) {
            return null;
        }
    }
    
    public function getBySlug($slug)
    {
        try {
            return $this->db->fetchOne("SELECT * FROM menus WHERE slug = :slug", ['slug' => $slug]);
        } catch (\Exception $e) {
            return null;
        }
    }
    
    public function create($data)
    {
        try {
            $fields = ['name', 'slug', 'description'];
            $insertData = [];
            foreach ($fields as $field) {
                if (isset($data[$field])) {
                    $insertData[$field] = $data[$field];
                }
            }
            
            if (empty($insertData['slug']) && !empty($insertData['name'])) {
                $insertData['slug'] = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $insertData['name'])));
            }
            
            return $this->db->insert('menus', $insertData);
        } catch (\Exception $e) {
            return false;
        }
    }
    
    public function update($id, $data)
    {
        try {
            $fields = ['name', 'slug', 'description'];
            $updateData = [];
            foreach ($fields as $field) {
                if (isset($data[$field])) {
                    $updateData[$field] = $data[$field];
                }
            }
            
            return $this->db->update('menus', $updateData, 'id = :id', ['id' => $id]);
        } catch (\Exception $e) {
            return false;
        }
    }
    
    public function delete($id)
    {
        try {
            return $this->db->delete('menus', 'id = :id', ['id' => $id]);
        } catch (\Exception $e) {
            return false;
        }
    }
    
    public function duplicate($id)
    {
        try {
            $menu = $this->getById($id);
            if (!$menu) return false;
            
            // Generate unique slug
            $baseSlug = $menu['slug'] . '-copy';
            $newSlug = $baseSlug;
            $counter = 1;
            while ($this->getBySlug($newSlug)) {
                $newSlug = $baseSlug . '-' . $counter;
                $counter++;
            }
            
            $newData = [
                'name' => $menu['name'] . ' (Copy)',
                'slug' => $newSlug,
                'description' => $menu['description']
            ];
            
            $newId = $this->create($newData);
            if ($newId) {
                // Duplicate menu items (only top-level, children will be duplicated recursively)
                $itemModel = new MenuItem();
                $items = $itemModel->getByMenuId($id);
                $topLevelItems = array_filter($items, function($item) {
                    return empty($item['parent_id']);
                });
                
                foreach ($topLevelItems as $item) {
                    $itemModel->duplicate($item['id'], $newId);
                }
            }
            
            return $newId;
        } catch (\Exception $e) {
            error_log('Menu duplicate error: ' . $e->getMessage());
            return false;
        }
    }
}

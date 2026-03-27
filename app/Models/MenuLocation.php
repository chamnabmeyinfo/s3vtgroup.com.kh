<?php
/**
 * Menu Location Model
 * Menu location assignment model
 */

namespace App\Models;

use App\Database\Connection;

class MenuLocation
{
    protected $db;
    
    public function __construct()
    {
        $this->db = Connection::getInstance();
    }
    
    public function getAll()
    {
        try {
            // Check if area column exists
            $hasArea = false;
            try {
                $this->db->fetchOne("SELECT area FROM menu_locations LIMIT 1");
                $hasArea = true;
            } catch (\Exception $e) {
                $hasArea = false;
            }
            
            $orderBy = $hasArea ? "ORDER BY ml.area ASC, ml.location ASC" : "ORDER BY ml.location ASC";
            
            return $this->db->fetchAll("SELECT ml.*, m.name as menu_name, m.slug as menu_slug 
                                       FROM menu_locations ml 
                                       LEFT JOIN menus m ON ml.menu_id = m.id 
                                       {$orderBy}");
        } catch (\Exception $e) {
            return [];
        }
    }
    
    public function getByLocation($location)
    {
        try {
            $result = $this->db->fetchOne(
                "SELECT ml.*, m.* FROM menu_locations ml 
                 LEFT JOIN menus m ON ml.menu_id = m.id 
                 WHERE ml.location = :location",
                ['location' => $location]
            );
            
            // If menu_id is null or menu doesn't exist, return null
            if ($result && empty($result['menu_id'])) {
                return null;
            }
            
            return $result;
        } catch (\Exception $e) {
            return null;
        }
    }
    
    public function assign($location, $menuId)
    {
        try {
            $existing = $this->db->fetchOne(
                "SELECT id FROM menu_locations WHERE location = :location",
                ['location' => $location]
            );
            
            if ($existing) {
                return $this->db->update(
                    'menu_locations',
                    ['menu_id' => $menuId ? (int)$menuId : null],
                    'location = :location',
                    ['location' => $location]
                );
            } else {
                return $this->db->insert('menu_locations', [
                    'location' => $location,
                    'menu_id' => $menuId ? (int)$menuId : null
                ]);
            }
        } catch (\Exception $e) {
            return false;
        }
    }
    
    public function create($data)
    {
        try {
            // Check if area column exists
            $hasArea = false;
            try {
                $this->db->fetchOne("SELECT area FROM menu_locations LIMIT 1");
                $hasArea = true;
            } catch (\Exception $e) {
                $hasArea = false;
            }
            
            $insertData = [
                'location' => $data['location'] ?? '',
                'description' => $data['description'] ?? null,
                'menu_id' => !empty($data['menu_id']) ? (int)$data['menu_id'] : null
            ];
            
            // Only add area if column exists
            if ($hasArea) {
                $insertData['area'] = $data['area'] ?? 'main';
            }
            
            if (empty($insertData['location'])) {
                return false;
            }
            
            // Check if location already exists
            $existing = $this->db->fetchOne(
                "SELECT id FROM menu_locations WHERE location = :location",
                ['location' => $insertData['location']]
            );
            
            if ($existing) {
                return false; // Location already exists
            }
            
            return $this->db->insert('menu_locations', $insertData);
        } catch (\Exception $e) {
            error_log('MenuLocation create error: ' . $e->getMessage());
            return false;
        }
    }
    
    public function update($id, $data)
    {
        try {
            $updateData = [];
            
            if (isset($data['location'])) {
                $updateData['location'] = $data['location'];
            }
            if (isset($data['area'])) {
                $updateData['area'] = $data['area'];
            }
            if (isset($data['description'])) {
                $updateData['description'] = $data['description'];
            }
            if (isset($data['menu_id'])) {
                $updateData['menu_id'] = !empty($data['menu_id']) ? (int)$data['menu_id'] : null;
            }
            
            if (empty($updateData)) {
                return false;
            }
            
            // If updating location, check for duplicates
            if (isset($updateData['location'])) {
                $existing = $this->db->fetchOne(
                    "SELECT id FROM menu_locations WHERE location = :location AND id != :id",
                    ['location' => $updateData['location'], 'id' => $id]
                );
                
                if ($existing) {
                    return false; // Location already exists
                }
            }
            
            return $this->db->update('menu_locations', $updateData, 'id = :id', ['id' => $id]);
        } catch (\Exception $e) {
            error_log('MenuLocation update error: ' . $e->getMessage());
            return false;
        }
    }
    
    public function delete($id)
    {
        try {
            return $this->db->delete('menu_locations', 'id = :id', ['id' => $id]);
        } catch (\Exception $e) {
            error_log('MenuLocation delete error: ' . $e->getMessage());
            return false;
        }
    }
    
    public function getById($id)
    {
        try {
            return $this->db->fetchOne(
                "SELECT ml.*, m.name as menu_name, m.slug as menu_slug 
                 FROM menu_locations ml 
                 LEFT JOIN menus m ON ml.menu_id = m.id 
                 WHERE ml.id = :id",
                ['id' => $id]
            );
        } catch (\Exception $e) {
            return null;
        }
    }
    
    public function getByArea($area)
    {
        try {
            return $this->db->fetchAll(
                "SELECT ml.*, m.name as menu_name, m.slug as menu_slug 
                 FROM menu_locations ml 
                 LEFT JOIN menus m ON ml.menu_id = m.id 
                 WHERE ml.area = :area 
                 ORDER BY ml.location ASC",
                ['area' => $area]
            );
        } catch (\Exception $e) {
            return [];
        }
    }
    
    public function unassign($location)
    {
        try {
            return $this->assign($location, null);
        } catch (\Exception $e) {
            return false;
        }
    }
}

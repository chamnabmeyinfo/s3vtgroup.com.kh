<?php

namespace App\Models;

use App\Database\Connection;

class HeroSlider
{
    private $db;

    public function __construct()
    {
        $this->db = Connection::getInstance();
    }

    public function getAll($activeOnly = false, $filterScheduled = true)
    {
        $where = $activeOnly ? "WHERE is_active = 1" : "WHERE 1=1";
        
        // Filter scheduled slides
        if ($filterScheduled) {
            $where .= " AND (
                (scheduled_start IS NULL AND scheduled_end IS NULL) OR
                (scheduled_start IS NULL AND (scheduled_end IS NULL OR scheduled_end >= NOW())) OR
                (scheduled_end IS NULL AND (scheduled_start IS NULL OR scheduled_start <= NOW())) OR
                (NOW() BETWEEN scheduled_start AND scheduled_end)
            )";
        }
        
        $sql = "SELECT * FROM hero_slides $where ORDER BY display_order ASC, id ASC";
        return $this->db->fetchAll($sql);
    }
    
    public function getByGroup($group, $activeOnly = true)
    {
        $where = "WHERE slide_group = :group";
        if ($activeOnly) {
            $where .= " AND is_active = 1";
        }
        $sql = "SELECT * FROM hero_slides $where ORDER BY display_order ASC, id ASC";
        return $this->db->fetchAll($sql, ['group' => $group]);
    }

    public function getById($id)
    {
        $sql = "SELECT * FROM hero_slides WHERE id = :id";
        return $this->db->fetchOne($sql, ['id' => $id]);
    }

    public function create($data)
    {
        // Get all available columns dynamically
        $columns = $this->getAvailableColumns();
        $fields = [];
        $placeholders = [];
        $params = [];
        
        foreach ($columns as $column) {
            if (isset($data[$column]) || in_array($column, ['is_active', 'display_order', 'content_transparency'])) {
                $fields[] = $column;
                $placeholders[] = ":$column";
                
                // Set default values for required fields
                if ($column === 'is_active') {
                    $params[$column] = isset($data[$column]) ? (int)$data[$column] : 1;
                } elseif ($column === 'display_order') {
                    $params[$column] = isset($data[$column]) ? (int)$data[$column] : 0;
                } elseif ($column === 'content_transparency') {
                    $params[$column] = isset($data[$column]) ? (float)$data[$column] : 0.10;
                } elseif (in_array($column, ['parallax_enabled', 'social_sharing', 'countdown_enabled', 'auto_height', 'dark_mode'])) {
                    $params[$column] = isset($data[$column]) ? (int)$data[$column] : 0;
                } elseif (in_array($column, ['scheduled_start', 'scheduled_end', 'countdown_date']) && !empty($data[$column])) {
                    $params[$column] = $data[$column];
                } elseif (!empty($data[$column])) {
                    $params[$column] = $data[$column];
                } else {
                    $params[$column] = null;
                }
            }
        }
        
        $sql = "INSERT INTO hero_slides (" . implode(', ', $fields) . ") VALUES (" . implode(', ', $placeholders) . ")";
        $this->db->query($sql, $params);
        return $this->db->lastInsertId();
    }
    
    private function getAvailableColumns()
    {
        try {
            $result = $this->db->fetchAll("SHOW COLUMNS FROM hero_slides");
            return array_column($result, 'Field');
        } catch (Exception $e) {
            // Fallback to basic columns if table structure can't be read
            return [
                'title', 'description', 'button1_text', 'button1_url', 
                'button2_text', 'button2_url', 'background_image',
                'background_gradient_start', 'background_gradient_end',
                'content_transparency', 'is_active', 'display_order'
            ];
        }
    }

    public function update($id, $data)
    {
        // Get all available columns dynamically
        $columns = $this->getAvailableColumns();
        $updates = [];
        $params = ['id' => $id];
        
        foreach ($columns as $column) {
            if ($column === 'id' || $column === 'created_at') {
                continue; // Skip these columns
            }
            
            if ($column === 'updated_at') {
                $updates[] = "updated_at = CURRENT_TIMESTAMP";
                continue;
            }
            
            if (isset($data[$column])) {
                $updates[] = "$column = :$column";
                
                // Handle different data types
                if (in_array($column, ['is_active', 'parallax_enabled', 'social_sharing', 'countdown_enabled', 'auto_height', 'dark_mode'])) {
                    $params[$column] = (int)$data[$column];
                } elseif ($column === 'content_transparency') {
                    $params[$column] = (float)$data[$column];
                } elseif ($column === 'display_order') {
                    $params[$column] = (int)$data[$column];
                } elseif (in_array($column, ['scheduled_start', 'scheduled_end', 'countdown_date']) && !empty($data[$column])) {
                    $params[$column] = $data[$column];
                } elseif (empty($data[$column]) && $data[$column] !== '0') {
                    $params[$column] = null;
                } else {
                    $params[$column] = $data[$column];
                }
            }
        }
        
        if (empty($updates)) {
            return false;
        }
        
        $sql = "UPDATE hero_slides SET " . implode(', ', $updates) . " WHERE id = :id";
        return $this->db->query($sql, $params);
    }
    
    public function trackView($slideId)
    {
        // Track slide views for analytics
        try {
            $sql = "UPDATE hero_slides SET views = COALESCE(views, 0) + 1 WHERE id = :id";
            $this->db->query($sql, ['id' => $slideId]);
        } catch (Exception $e) {
            // Views column might not exist yet, ignore
        }
    }
    
    public function bulkUpdate($ids, $data)
    {
        // Bulk update multiple slides
        $ids = array_map('intval', $ids);
        if (empty($ids)) return false;
        
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $params = array_values($ids);
        
        $updates = [];
        foreach ($data as $key => $value) {
            if ($key !== 'id') {
                $updates[] = "$key = ?";
                $params[] = $value;
            }
        }
        
        if (empty($updates)) return false;
        
        $sql = "UPDATE hero_slides SET " . implode(', ', $updates) . " WHERE id IN ($placeholders)";
        return $this->db->query($sql, $params);
    }

    public function delete($id)
    {
        $sql = "DELETE FROM hero_slides WHERE id = :id";
        return $this->db->query($sql, ['id' => $id]);
    }

    public function toggleActive($id)
    {
        $sql = "UPDATE hero_slides SET is_active = NOT is_active WHERE id = :id";
        return $this->db->query($sql, ['id' => $id]);
    }

    public function updateOrder($id, $order)
    {
        $sql = "UPDATE hero_slides SET display_order = :order WHERE id = :id";
        return $this->db->query($sql, ['id' => $id, 'order' => $order]);
    }
}


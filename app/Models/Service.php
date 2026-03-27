<?php

namespace App\Models;

use App\Database\Connection;

class Service
{
    private $db;

    public function __construct()
    {
        $this->db = Connection::getInstance();
    }

    public function getAll($activeOnly = false)
    {
        $where = $activeOnly ? "WHERE is_active = 1" : "WHERE 1=1";
        $sql = "SELECT * FROM services $where ORDER BY sort_order ASC, id ASC";
        return $this->db->fetchAll($sql);
    }

    public function getById($id)
    {
        $sql = "SELECT * FROM services WHERE id = :id";
        return $this->db->fetchOne($sql, ['id' => $id]);
    }

    public function getBySlug($slug)
    {
        $sql = "SELECT * FROM services WHERE slug = :slug AND is_active = 1";
        return $this->db->fetchOne($sql, ['slug' => $slug]);
    }

    public function create($data)
    {
        // Generate slug if not provided
        if (empty($data['slug']) && !empty($data['title'])) {
            $data['slug'] = $this->generateSlug($data['title']);
        }

        // Ensure slug is unique
        if (!empty($data['slug'])) {
            $data['slug'] = $this->ensureUniqueSlug($data['slug']);
        }

        $fields = ['title', 'slug', 'description', 'content', 'icon', 'image', 'sort_order', 'is_active', 'meta_title', 'meta_description'];
        $placeholders = [];
        $values = [];

        foreach ($fields as $field) {
            if (isset($data[$field])) {
                $placeholders[] = ":$field";
                $values[$field] = $data[$field];
            }
        }

        // Set defaults
        if (!isset($values['sort_order'])) {
            $values['sort_order'] = 0;
        }
        if (!isset($values['is_active'])) {
            $values['is_active'] = 1;
        }

        $sql = "INSERT INTO services (" . implode(', ', $fields) . ") VALUES (" . implode(', ', $placeholders) . ")";
        return $this->db->insert($sql, $values);
    }

    public function update($id, $data)
    {
        // Generate slug if title changed and slug not provided
        if (isset($data['title']) && empty($data['slug'])) {
            $data['slug'] = $this->generateSlug($data['title']);
        }

        // Ensure slug is unique (excluding current record)
        if (!empty($data['slug'])) {
            $data['slug'] = $this->ensureUniqueSlug($data['slug'], $id);
        }

        $fields = ['title', 'slug', 'description', 'content', 'icon', 'image', 'sort_order', 'is_active', 'meta_title', 'meta_description'];
        $updates = [];
        $values = ['id' => $id];

        foreach ($fields as $field) {
            if (isset($data[$field])) {
                $updates[] = "$field = :$field";
                $values[$field] = $data[$field];
            }
        }

        if (empty($updates)) {
            return false;
        }

        $sql = "UPDATE services SET " . implode(', ', $updates) . " WHERE id = :id";
        return $this->db->update('services', $values, 'id = :id', ['id' => $id]);
    }

    public function delete($id)
    {
        $sql = "DELETE FROM services WHERE id = :id";
        return $this->db->delete('services', 'id = :id', ['id' => $id]);
    }

    private function generateSlug($title)
    {
        $slug = strtolower(trim($title));
        $slug = preg_replace('/[^a-z0-9-]/', '-', $slug);
        $slug = preg_replace('/-+/', '-', $slug);
        $slug = trim($slug, '-');
        return $slug;
    }

    private function ensureUniqueSlug($slug, $excludeId = null)
    {
        $originalSlug = $slug;
        $counter = 1;

        while (true) {
            $params = ['slug' => $slug];
            $sql = "SELECT id FROM services WHERE slug = :slug";
            
            if ($excludeId) {
                $sql .= " AND id != :exclude_id";
                $params['exclude_id'] = $excludeId;
            }

            $existing = $this->db->fetchOne($sql, $params);
            
            if (!$existing) {
                return $slug;
            }

            $slug = $originalSlug . '-' . $counter;
            $counter++;
        }
    }
}

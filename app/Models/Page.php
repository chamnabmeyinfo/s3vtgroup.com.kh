<?php

namespace App\Models;

use App\Database\Connection;

class Page
{
    private $db;

    public function __construct()
    {
        $this->db = Connection::getInstance();
    }

    /**
     * Get all pages
     * @param bool $activeOnly Only return active pages
     * @return array
     */
    public function getAll($activeOnly = false)
    {
        $where = $activeOnly ? "WHERE is_active = 1" : "";
        return $this->db->fetchAll("SELECT * FROM pages {$where} ORDER BY created_at DESC");
    }

    /**
     * Get page by ID
     * @param int $id
     * @return array|null
     */
    public function getById($id)
    {
        $result = $this->db->fetchOne("SELECT * FROM pages WHERE id = :id", ['id' => $id]);
        return $result ?: null;
    }

    /**
     * Get page by slug
     * @param string $slug
     * @return array|null
     */
    public function getBySlug($slug)
    {
        $result = $this->db->fetchOne("SELECT * FROM pages WHERE slug = :slug AND is_active = 1", ['slug' => $slug]);
        return $result ?: null;
    }

    /**
     * Create a new page
     * @param array $data
     * @return int|false Page ID on success, false on failure
     */
    public function create($data)
    {
        try {
            // Generate slug if not provided
            if (empty($data['slug']) && !empty($data['title'])) {
                $data['slug'] = $this->generateSlug($data['title']);
            }

            // Ensure slug is unique
            $data['slug'] = $this->ensureUniqueSlug($data['slug']);

            $insertData = [
                'title' => $data['title'] ?? '',
                'slug' => $data['slug'] ?? '',
                'content' => $data['content'] ?? '',
                'meta_title' => $data['meta_title'] ?? null,
                'meta_description' => $data['meta_description'] ?? null,
                'is_active' => isset($data['is_active']) ? (int)$data['is_active'] : 1
            ];

            return $this->db->insert('pages', $insertData);
        } catch (\Exception $e) {
            error_log('Page create error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Update a page
     * @param int $id
     * @param array $data
     * @return bool
     */
    public function update($id, $data)
    {
        try {
            // Generate slug if not provided
            if (empty($data['slug']) && !empty($data['title'])) {
                $data['slug'] = $this->generateSlug($data['title']);
            }

            // Ensure slug is unique (excluding current page)
            $data['slug'] = $this->ensureUniqueSlug($data['slug'], $id);

            $updateData = [
                'title' => $data['title'] ?? '',
                'slug' => $data['slug'] ?? '',
                'content' => $data['content'] ?? '',
                'meta_title' => $data['meta_title'] ?? null,
                'meta_description' => $data['meta_description'] ?? null,
                'is_active' => isset($data['is_active']) ? (int)$data['is_active'] : 1
            ];

            // Remove null values
            $updateData = array_filter($updateData, function($value) {
                return $value !== null;
            });

            return $this->db->update('pages', $updateData, 'id = :id', ['id' => $id]);
        } catch (\Exception $e) {
            error_log('Page update error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Delete a page
     * @param int $id
     * @return bool
     */
    public function delete($id)
    {
        try {
            return $this->db->delete('pages', 'id = :id', ['id' => $id]);
        } catch (\Exception $e) {
            error_log('Page delete error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Generate slug from title
     * @param string $title
     * @return string
     */
    private function generateSlug($title)
    {
        $slug = strtolower(trim($title));
        $slug = preg_replace('/[^a-z0-9-]+/', '-', $slug);
        $slug = preg_replace('/-+/', '-', $slug);
        $slug = trim($slug, '-');
        return $slug;
    }

    /**
     * Ensure slug is unique
     * @param string $slug
     * @param int|null $excludeId Exclude this ID from uniqueness check
     * @return string
     */
    private function ensureUniqueSlug($slug, $excludeId = null)
    {
        $originalSlug = $slug;
        $counter = 1;

        while (true) {
            $where = "slug = :slug";
            $params = ['slug' => $slug];

            if ($excludeId) {
                $where .= " AND id != :exclude_id";
                $params['exclude_id'] = $excludeId;
            }

            $existing = $this->db->fetchOne("SELECT id FROM pages WHERE {$where}", $params);

            if (!$existing) {
                return $slug;
            }

            $slug = $originalSlug . '-' . $counter;
            $counter++;
        }
    }

    /**
     * Get total count of pages
     * @param bool $activeOnly
     * @return int
     */
    public function getCount($activeOnly = false)
    {
        $where = $activeOnly ? "WHERE is_active = 1" : "";
        $result = $this->db->fetchOne("SELECT COUNT(*) as count FROM pages {$where}");
        return (int)($result['count'] ?? 0);
    }
}

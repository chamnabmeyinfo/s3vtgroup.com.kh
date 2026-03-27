<?php

namespace App\Models;

use App\Database\Connection;

class Footer
{
    private $db;

    public function __construct()
    {
        $this->db = Connection::getInstance();
    }

    /**
     * Check if footer_content table exists
     */
    private function tableExists()
    {
        try {
            $this->db->fetchOne("SELECT 1 FROM footer_content LIMIT 1");
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Get all footer content by section type
     */
    public function getBySection($sectionType, $activeOnly = true)
    {
        if (!$this->tableExists()) {
            return [];
        }
        
        try {
            $sql = "SELECT * FROM footer_content WHERE section_type = :section_type";
            $params = ['section_type' => $sectionType];
            
            if ($activeOnly) {
                $sql .= " AND is_active = 1";
            }
            
            $sql .= " ORDER BY display_order ASC, sort_order ASC";
            
            return $this->db->fetchAll($sql, $params);
        } catch (\Exception $e) {
            return [];
        }
    }

    /**
     * Get all footer content
     */
    public function getAll($activeOnly = true)
    {
        if (!$this->tableExists()) {
            return [];
        }
        
        try {
            $sql = "SELECT * FROM footer_content WHERE 1=1";
            $params = [];
            
            if ($activeOnly) {
                $sql .= " AND is_active = 1";
            }
            
            $sql .= " ORDER BY display_order ASC, sort_order ASC";
            
            return $this->db->fetchAll($sql, $params);
        } catch (\Exception $e) {
            return [];
        }
    }

    /**
     * Get footer content by ID
     */
    public function getById($id)
    {
        if (!$this->tableExists()) {
            return null;
        }
        
        try {
            return $this->db->fetchOne(
                "SELECT * FROM footer_content WHERE id = :id",
                ['id' => $id]
            );
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Get organized footer content by sections
     */
    public function getOrganized($activeOnly = true)
    {
        $allContent = $this->getAll($activeOnly);
        $organized = [];
        
        foreach ($allContent as $item) {
            $section = $item['section_type'];
            if (!isset($organized[$section])) {
                $organized[$section] = [];
            }
            $organized[$section][] = $item;
        }
        
        return $organized;
    }

    /**
     * Create footer content
     */
    public function create($data)
    {
        if (!$this->tableExists()) {
            throw new \Exception('footer_content table does not exist. Please run database/footer-management.sql first.');
        }
        return $this->db->insert('footer_content', $data);
    }

    /**
     * Update footer content
     */
    public function update($id, $data)
    {
        if (!$this->tableExists()) {
            throw new \Exception('footer_content table does not exist. Please run database/footer-management.sql first.');
        }
        return $this->db->update('footer_content', $data, 'id = :id', ['id' => $id]);
    }

    /**
     * Delete footer content
     */
    public function delete($id)
    {
        if (!$this->tableExists()) {
            throw new \Exception('footer_content table does not exist. Please run database/footer-management.sql first.');
        }
        return $this->db->delete('footer_content', 'id = :id', ['id' => $id]);
    }

    /**
     * Toggle active status
     */
    public function toggleActive($id)
    {
        $item = $this->getById($id);
        if ($item) {
            $newStatus = $item['is_active'] ? 0 : 1;
            return $this->update($id, ['is_active' => $newStatus]);
        }
        return false;
    }

    /**
     * Get company info
     */
    public function getCompanyInfo()
    {
        $items = $this->getBySection('company_info');
        return $items[0] ?? null;
    }

    /**
     * Get quick links
     */
    public function getQuickLinks()
    {
        return $this->getBySection('quick_links');
    }

    /**
     * Get social media links
     */
    public function getSocialMedia()
    {
        return $this->getBySection('social_media');
    }

    /**
     * Get bottom text and links
     */
    public function getBottomContent()
    {
        return $this->getBySection('bottom_text');
    }
}

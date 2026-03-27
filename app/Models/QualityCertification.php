<?php

namespace App\Models;

use App\Database\Connection;

class QualityCertification
{
    private $db;

    public function __construct()
    {
        $this->db = Connection::getInstance();
    }

    public function getAll($activeOnly = false)
    {
        $where = $activeOnly ? "WHERE is_active = 1" : "WHERE 1=1";
        $sql = "SELECT * FROM quality_certifications $where ORDER BY sort_order ASC, id ASC";
        return $this->db->fetchAll($sql);
    }

    public function getById($id)
    {
        $sql = "SELECT * FROM quality_certifications WHERE id = :id";
        return $this->db->fetchOne($sql, ['id' => $id]);
    }

    public function create($data)
    {
        $fields = ['name', 'logo', 'reference_url', 'description', 'sort_order', 'is_active'];
        $values = [];

        foreach ($fields as $field) {
            if (isset($data[$field])) {
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

        return $this->db->insert('quality_certifications', $values);
    }

    public function update($id, $data)
    {
        $fields = ['name', 'logo', 'reference_url', 'description', 'sort_order', 'is_active'];
        $updateData = [];

        foreach ($fields as $field) {
            if (isset($data[$field])) {
                $updateData[$field] = $data[$field];
            }
        }

        if (empty($updateData)) {
            return false;
        }

        return $this->db->update('quality_certifications', $updateData, 'id = :id', ['id' => $id]);
    }

    public function delete($id)
    {
        $sql = "DELETE FROM quality_certifications WHERE id = :id";
        return $this->db->delete('quality_certifications', 'id = :id', ['id' => $id]);
    }
}

<?php

namespace App\Models;

use App\Database\Connection;

class CeoMessage
{
    private $db;

    public function __construct()
    {
        $this->db = Connection::getInstance();
    }

    /**
     * Get the active CEO message
     * @return array|null
     */
    public function getActive()
    {
        $sql = "SELECT * FROM ceo_message WHERE is_active = 1 ORDER BY updated_at DESC LIMIT 1";
        return $this->db->fetchOne($sql);
    }

    /**
     * Get CEO message by ID
     * @param int $id
     * @return array|null
     */
    public function getById($id)
    {
        $sql = "SELECT * FROM ceo_message WHERE id = :id";
        return $this->db->fetchOne($sql, ['id' => $id]);
    }

    /**
     * Get all CEO messages
     * @return array
     */
    public function getAll()
    {
        $sql = "SELECT * FROM ceo_message ORDER BY updated_at DESC";
        return $this->db->fetchAll($sql);
    }

    /**
     * Create a new CEO message
     * @param array $data
     * @return int|false
     */
    public function create($data)
    {
        $fields = ['ceo_name', 'ceo_title', 'ceo_photo', 'greeting', 'message_content', 'signature_name', 'signature_title', 'is_active'];
        $values = [];

        foreach ($fields as $field) {
            if (isset($data[$field])) {
                $values[$field] = $data[$field];
            }
        }

        // Set defaults
        if (!isset($values['is_active'])) {
            $values['is_active'] = 1;
        }

        return $this->db->insert('ceo_message', $values);
    }

    /**
     * Update CEO message
     * @param int $id
     * @param array $data
     * @return bool
     */
    public function update($id, $data)
    {
        $fields = ['ceo_name', 'ceo_title', 'ceo_photo', 'greeting', 'message_content', 'signature_name', 'signature_title', 'is_active'];
        $values = [];

        foreach ($fields as $field) {
            if (isset($data[$field])) {
                $values[$field] = $data[$field];
            }
        }

        if (empty($values)) {
            return false;
        }

        return $this->db->update('ceo_message', $values, 'id = :id', ['id' => $id]) > 0;
    }

    /**
     * Delete CEO message
     * @param int $id
     * @return bool
     */
    public function delete($id)
    {
        $sql = "DELETE FROM ceo_message WHERE id = :id";
        return $this->db->delete('ceo_message', 'id = :id', ['id' => $id]);
    }
}

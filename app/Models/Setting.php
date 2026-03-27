<?php

namespace App\Models;

use App\Database\Connection;

class Setting
{
    private $db;
    private static $cache = [];

    public function __construct()
    {
        $this->db = Connection::getInstance();
    }

    public function get($key, $default = null)
    {
        if (isset(self::$cache[$key])) {
            $value = self::$cache[$key];
            return $value ?? $default;
        }

        try {
        $setting = $this->db->fetchOne("SELECT value FROM settings WHERE `key` = :key", ['key' => $key]);
        
        if ($setting && $setting['value'] !== null) {
            $value = $setting['value'];
            self::$cache[$key] = $value;
            return $value;
            }
        } catch (\Exception $e) {
            // If table doesn't exist or query fails, return default
            // Don't break the application if settings table is missing
            return $default;
        }
        
        return $default;
    }

    public function set($key, $value, $type = 'text')
    {
        $existing = $this->db->fetchOne("SELECT id FROM settings WHERE `key` = :key", ['key' => $key]);
        
        if ($existing) {
            $this->db->update('settings', ['value' => $value], '`key` = :key', ['key' => $key]);
        } else {
            $this->db->insert('settings', [
                'key' => $key,
                'value' => $value,
                'type' => $type
            ]);
        }
        
        self::$cache[$key] = $value;
    }

    public function getAll()
    {
        $settings = $this->db->fetchAll("SELECT `key`, value FROM settings");
        $result = [];
        
        foreach ($settings as $setting) {
            $result[$setting['key']] = $setting['value'];
        }
        
        return $result;
    }
}


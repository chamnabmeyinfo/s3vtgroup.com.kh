<?php

namespace App\Database;

use PDO;
use PDOException;

class Connection
{
    private static $instance = null;
    private $pdo;

    private function __construct()
    {
        $config = require __DIR__ . '/../../config/database.php';
        
        try {
            $dsn = "mysql:host={$config['host']};dbname={$config['dbname']};charset={$config['charset']}";
            $this->pdo = new PDO(
                $dsn,
                $config['username'],
                $config['password'],
                $config['options']
            );
        } catch (PDOException $e) {
            // Security: Don't expose database errors in production
            error_log("Database connection failed: " . $e->getMessage());
            if (config('app.debug', false)) {
                die("Database connection failed: " . $e->getMessage());
            } else {
                die("Database connection failed. Please contact support.");
            }
        }
    }

    public static function getInstance()
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function getPdo()
    {
        return $this->pdo;
    }

    public function query($sql, $params = [])
    {
        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            return $stmt;
        } catch (PDOException $e) {
            // Log the error for debugging
            error_log("Database query error: " . $e->getMessage());
            error_log("SQL: " . $sql);
            error_log("Params: " . json_encode($params));
            // Re-throw so calling code can handle it
            throw $e;
        }
    }

    public function fetchAll($sql, $params = [])
    {
        return $this->query($sql, $params)->fetchAll();
    }

    public function fetchOne($sql, $params = [])
    {
        return $this->query($sql, $params)->fetch();
    }

    public function insert($table, $data)
    {
        $fields = array_keys($data);
        $escapedFields = array_map(fn($field) => "`{$field}`", $fields);
        $placeholders = array_map(fn($field) => ":$field", $fields);
        
        $sql = "INSERT INTO `{$table}` (" . implode(', ', $escapedFields) . ") 
                VALUES (" . implode(', ', $placeholders) . ")";
        
        $this->query($sql, $data);
        return $this->pdo->lastInsertId();
    }

    /**
     * Update records in a table
     * SECURITY: WHERE clause must use parameterized placeholders (e.g., "id = :id")
     * Never pass user input directly in $where - always use parameters in $whereParams
     * 
     * @param string $table Table name
     * @param array $data Data to update [field => value]
     * @param string $where WHERE clause with placeholders (e.g., "id = :id AND status = :status")
     * @param array $whereParams Parameters for WHERE clause
     * @return int Number of affected rows
     */
    public function update($table, $data, $where, $whereParams = [])
    {
        // Security: Validate table name (alphanumeric, underscore, hyphen only)
        if (!preg_match('/^[a-zA-Z0-9_-]+$/', $table)) {
            throw new \InvalidArgumentException("Invalid table name");
        }
        
        // Security: Ensure WHERE clause contains at least one parameter placeholder
        // This prevents direct SQL injection via $where
        if (empty($whereParams) && !preg_match('/:\w+/', $where)) {
            throw new \InvalidArgumentException("WHERE clause must use parameterized placeholders for security");
        }
        
        $set = [];
        foreach (array_keys($data) as $field) {
            // Security: Validate field names
            if (!preg_match('/^[a-zA-Z0-9_-]+$/', $field)) {
                throw new \InvalidArgumentException("Invalid field name: $field");
            }
            $set[] = "`{$field}` = :$field";
        }
        
        $sql = "UPDATE `{$table}` SET " . implode(', ', $set) . " WHERE $where";
        $params = array_merge($data, $whereParams);
        
        return $this->query($sql, $params)->rowCount();
    }

    /**
     * Delete records from a table
     * SECURITY: WHERE clause must use parameterized placeholders (e.g., "id = :id")
     * Never pass user input directly in $where - always use parameters in $params
     * 
     * @param string $table Table name
     * @param string $where WHERE clause with placeholders (e.g., "id = :id")
     * @param array $params Parameters for WHERE clause
     * @return int Number of affected rows
     */
    public function delete($table, $where, $params = [])
    {
        // Security: Validate table name
        if (!preg_match('/^[a-zA-Z0-9_-]+$/', $table)) {
            throw new \InvalidArgumentException("Invalid table name");
        }
        
        // Security: Ensure WHERE clause contains at least one parameter placeholder
        if (empty($params) && !preg_match('/:\w+/', $where)) {
            throw new \InvalidArgumentException("WHERE clause must use parameterized placeholders for security");
        }
        
        $sql = "DELETE FROM `{$table}` WHERE $where";
        return $this->query($sql, $params)->rowCount();
    }
}


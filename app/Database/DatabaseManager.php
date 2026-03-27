<?php

namespace App\Database;

use Doctrine\DBAL\Connection as DBALConnection;
use Doctrine\DBAL\DriverManager;
use Doctrine\DBAL\Exception;

/**
 * Enhanced Database Manager using Doctrine DBAL
 * Provides advanced database operations via Composer packages
 */
class DatabaseManager
{
    private static $dbalConnection = null;
    
    /**
     * Get Doctrine DBAL connection
     * This provides advanced database features via Composer
     */
    public static function getDbalConnection(): DBALConnection
    {
        if (self::$dbalConnection === null) {
            $config = require __DIR__ . '/../../config/database.php';
            
            $connectionParams = [
                'dbname' => $config['dbname'],
                'user' => $config['username'],
                'password' => $config['password'],
                'host' => $config['host'],
                'driver' => 'pdo_mysql',
                'charset' => $config['charset'],
            ];
            
            try {
                self::$dbalConnection = DriverManager::getConnection($connectionParams);
            } catch (Exception $e) {
                throw new \RuntimeException("DBAL Connection failed: " . $e->getMessage());
            }
        }
        
        return self::$dbalConnection;
    }
    
    /**
     * Execute raw SQL query with Doctrine DBAL
     */
    public static function executeQuery(string $sql, array $params = []): array
    {
        $conn = self::getDbalConnection();
        return $conn->fetchAllAssociative($sql, $params);
    }
    
    /**
     * Execute SQL statement (INSERT, UPDATE, DELETE)
     */
    public static function executeStatement(string $sql, array $params = []): int
    {
        $conn = self::getDbalConnection();
        return $conn->executeStatement($sql, $params);
    }
    
    /**
     * Get table schema information
     */
    public static function getTableSchema(string $tableName): array
    {
        $conn = self::getDbalConnection();
        $schemaManager = $conn->createSchemaManager();
        
        return [
            'columns' => $schemaManager->listTableColumns($tableName),
            'indexes' => $schemaManager->listTableIndexes($tableName),
            'foreign_keys' => $schemaManager->listTableForeignKeys($tableName),
        ];
    }
    
    /**
     * Get all tables in database
     */
    public static function getTables(): array
    {
        $conn = self::getDbalConnection();
        $schemaManager = $conn->createSchemaManager();
        return $schemaManager->listTables();
    }
    
    /**
     * Create table using Doctrine Schema
     */
    public static function createTable(string $tableName, array $columns): void
    {
        $conn = self::getDbalConnection();
        $schemaManager = $conn->createSchemaManager();
        
        // This is a simplified version - you'd use Doctrine Schema Builder for full implementation
        // For now, we'll use raw SQL
        $sql = "CREATE TABLE IF NOT EXISTS `{$tableName}` (";
        // Add column definitions here
        $sql .= " id INT AUTO_INCREMENT PRIMARY KEY";
        $sql .= ")";
        
        $conn->executeStatement($sql);
    }
    
    /**
     * Export table data to array
     */
    public static function exportTable(string $tableName, array $conditions = []): array
    {
        $conn = self::getDbalConnection();
        $queryBuilder = $conn->createQueryBuilder();
        
        $queryBuilder->select('*')->from($tableName);
        
        foreach ($conditions as $field => $value) {
            $queryBuilder->andWhere("{$field} = :{$field}")
                        ->setParameter($field, $value);
        }
        
        return $queryBuilder->executeQuery()->fetchAllAssociative();
    }
    
    /**
     * Import data into table
     */
    public static function importTable(string $tableName, array $data): int
    {
        $conn = self::getDbalConnection();
        $queryBuilder = $conn->createQueryBuilder();
        
        $inserted = 0;
        foreach ($data as $row) {
            $queryBuilder->insert($tableName);
            foreach ($row as $column => $value) {
                $queryBuilder->setValue($column, ':' . $column)
                           ->setParameter($column, $value);
            }
            $queryBuilder->executeStatement();
            $inserted++;
        }
        
        return $inserted;
    }
}

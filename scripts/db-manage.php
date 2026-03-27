#!/usr/bin/env php
<?php
/**
 * Database Management Script using Composer Packages
 * 
 * Usage: php scripts/db-manage.php [command] [options]
 * 
 * Commands:
 *   schema [table]     - Show table schema
 *   tables             - List all tables
 *   export [table]     - Export table to JSON
 *   query "SQL"        - Execute SQL query
 */

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../bootstrap/app.php';

use App\Database\DatabaseManager;

// Only allow CLI execution
if (php_sapi_name() !== 'cli') {
    die("This script can only be run from command line.\n");
}

$command = $argv[1] ?? null;
$args = array_slice($argv, 2);

if (empty($command)) {
    showHelp();
    exit(0);
}

try {
    switch ($command) {
        case 'schema':
            if (empty($args[0])) {
                echo "❌ Error: Please provide a table name.\n";
                echo "Usage: php scripts/db-manage.php schema products\n";
                exit(1);
            }
            showTableSchema($args[0]);
            break;
            
        case 'tables':
            listAllTables();
            break;
            
        case 'export':
            if (empty($args[0])) {
                echo "❌ Error: Please provide a table name.\n";
                echo "Usage: php scripts/db-manage.php export products\n";
                exit(1);
            }
            exportTable($args[0]);
            break;
            
        case 'query':
            if (empty($args)) {
                echo "❌ Error: Please provide a SQL query.\n";
                echo "Usage: php scripts/db-manage.php query \"SELECT * FROM products;\"\n";
                exit(1);
            }
            executeQuery(implode(' ', $args));
            break;
            
        default:
            echo "❌ Unknown command: {$command}\n\n";
            showHelp();
            exit(1);
    }
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    exit(1);
}

function showHelp() {
    echo "\n";
    echo "Database Management Tool (Using Composer Packages)\n\n";
    echo "Usage: php scripts/db-manage.php [command] [options]\n\n";
    echo "Commands:\n";
    echo "  schema [table]     Show table schema with columns and indexes\n";
    echo "  tables             List all tables in database\n";
    echo "  export [table]     Export table data to JSON file\n";
    echo "  query \"SQL\"        Execute SQL query\n\n";
    echo "Examples:\n";
    echo "  php scripts/db-manage.php schema products\n";
    echo "  php scripts/db-manage.php tables\n";
    echo "  php scripts/db-manage.php export products\n";
    echo "  php scripts/db-manage.php query \"SELECT * FROM products LIMIT 5;\"\n\n";
}

function showTableSchema($tableName) {
    try {
        $schema = DatabaseManager::getTableSchema($tableName);
        
        echo "\nTable Schema: {$tableName}\n";
        echo str_repeat("=", 60) . "\n\n";
        
        echo "Columns:\n";
        foreach ($schema['columns'] as $column) {
            $type = $column->getType()->getName();
            $length = $column->getLength();
            $notNull = $column->getNotnull() ? 'NOT NULL' : 'NULL';
            $default = $column->getDefault() !== null ? " DEFAULT '{$column->getDefault()}'" : '';
            
            echo "  - {$column->getName()}: {$type}";
            if ($length) echo "({$length})";
            echo " {$notNull}{$default}\n";
        }
        
        echo "\nIndexes:\n";
        foreach ($schema['indexes'] as $index) {
            echo "  - {$index->getName()}: " . implode(', ', $index->getColumns()) . "\n";
        }
        
        echo "\n";
    } catch (Exception $e) {
        echo "❌ Error getting schema: " . $e->getMessage() . "\n";
        exit(1);
    }
}

function listAllTables() {
    try {
        $tables = DatabaseManager::getTables();
        
        echo "\nDatabase Tables:\n";
        echo str_repeat("=", 60) . "\n\n";
        
        foreach ($tables as $table) {
            $columns = $table->getColumns();
            echo "  {$table->getName()} (" . count($columns) . " columns)\n";
        }
        
        echo "\nTotal: " . count($tables) . " tables\n\n";
    } catch (Exception $e) {
        echo "❌ Error listing tables: " . $e->getMessage() . "\n";
        exit(1);
    }
}

function exportTable($tableName) {
    try {
        $data = DatabaseManager::exportTable($tableName);
        
        $filename = "storage/exports/{$tableName}_" . date('Y-m-d_His') . ".json";
        $dir = dirname($filename);
        
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
        
        file_put_contents($filename, json_encode($data, JSON_PRETTY_PRINT));
        
        echo "✓ Exported {$tableName} to {$filename}\n";
        echo "  Rows exported: " . count($data) . "\n";
    } catch (Exception $e) {
        echo "❌ Error exporting table: " . $e->getMessage() . "\n";
        exit(1);
    }
}

function executeQuery($sql) {
    try {
        $isSelect = stripos(trim($sql), 'SELECT') === 0;
        
        if ($isSelect) {
            $result = DatabaseManager::executeQuery($sql);
            
            if (empty($result)) {
                echo "✓ Query executed. No results.\n";
            } else {
                echo "\n" . str_repeat("=", 100) . "\n";
                $columns = array_keys($result[0]);
                echo implode(" | ", $columns) . "\n";
                echo str_repeat("=", 100) . "\n";
                
                foreach ($result as $row) {
                    $values = array_map(function($val) {
                        return is_null($val) ? 'NULL' : (strlen($val) > 30 ? substr($val, 0, 27) . '...' : $val);
                    }, array_values($row));
                    echo implode(" | ", $values) . "\n";
                }
                
                echo str_repeat("=", 100) . "\n";
                echo "Total rows: " . count($result) . "\n";
            }
        } else {
            $affected = DatabaseManager::executeStatement($sql);
            echo "✓ Query executed. Affected rows: {$affected}\n";
        }
    } catch (Exception $e) {
        echo "❌ Error executing query: " . $e->getMessage() . "\n";
        exit(1);
    }
}

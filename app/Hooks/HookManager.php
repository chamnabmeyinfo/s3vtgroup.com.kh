<?php
/**
 * Hook Manager
 * WordPress-like hook system for extensibility
 */

namespace App\Hooks;

class HookManager
{
    private static $actions = [];
    private static $filters = [];
    private static $actionCount = 0;
    private static $filterCount = 0;
    
    /**
     * Add an action hook
     */
    public static function addAction($hook, $callback, $priority = 10, $acceptedArgs = 1)
    {
        if (!isset(self::$actions[$hook])) {
            self::$actions[$hook] = [];
        }
        
        if (!isset(self::$actions[$hook][$priority])) {
            self::$actions[$hook][$priority] = [];
        }
        
        self::$actions[$hook][$priority][] = [
            'callback' => $callback,
            'accepted_args' => $acceptedArgs
        ];
        
        // Sort by priority
        ksort(self::$actions[$hook]);
    }
    
    /**
     * Execute an action hook
     */
    public static function doAction($hook, ...$args)
    {
        if (!isset(self::$actions[$hook])) {
            return;
        }
        
        self::$actionCount++;
        
        foreach (self::$actions[$hook] as $priority => $callbacks) {
            foreach ($callbacks as $callbackData) {
                $callback = $callbackData['callback'];
                $acceptedArgs = $callbackData['accepted_args'];
                
                $callbackArgs = array_slice($args, 0, $acceptedArgs);
                
                if (is_callable($callback)) {
                    call_user_func_array($callback, $callbackArgs);
                }
            }
        }
    }
    
    /**
     * Add a filter hook
     */
    public static function addFilter($hook, $callback, $priority = 10, $acceptedArgs = 1)
    {
        if (!isset(self::$filters[$hook])) {
            self::$filters[$hook] = [];
        }
        
        if (!isset(self::$filters[$hook][$priority])) {
            self::$filters[$hook][$priority] = [];
        }
        
        self::$filters[$hook][$priority][] = [
            'callback' => $callback,
            'accepted_args' => $acceptedArgs
        ];
        
        // Sort by priority
        ksort(self::$filters[$hook]);
    }
    
    /**
     * Apply a filter hook
     */
    public static function applyFilters($hook, $value, ...$args)
    {
        if (!isset(self::$filters[$hook])) {
            return $value;
        }
        
        self::$filterCount++;
        
        foreach (self::$filters[$hook] as $priority => $callbacks) {
            foreach ($callbacks as $callbackData) {
                $callback = $callbackData['callback'];
                $acceptedArgs = $callbackData['accepted_args'];
                
                $callbackArgs = array_merge([$value], array_slice($args, 0, $acceptedArgs - 1));
                
                if (is_callable($callback)) {
                    $value = call_user_func_array($callback, $callbackArgs);
                }
            }
        }
        
        return $value;
    }
    
    /**
     * Remove an action
     */
    public static function removeAction($hook, $callback, $priority = 10)
    {
        if (!isset(self::$actions[$hook][$priority])) {
            return false;
        }
        
        foreach (self::$actions[$hook][$priority] as $key => $callbackData) {
            if ($callbackData['callback'] === $callback) {
                unset(self::$actions[$hook][$priority][$key]);
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Remove a filter
     */
    public static function removeFilter($hook, $callback, $priority = 10)
    {
        if (!isset(self::$filters[$hook][$priority])) {
            return false;
        }
        
        foreach (self::$filters[$hook][$priority] as $key => $callbackData) {
            if ($callbackData['callback'] === $callback) {
                unset(self::$filters[$hook][$priority][$key]);
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Check if action exists
     */
    public static function hasAction($hook, $callback = false)
    {
        if (!isset(self::$actions[$hook])) {
            return false;
        }
        
        if ($callback === false) {
            return true;
        }
        
        foreach (self::$actions[$hook] as $priority => $callbacks) {
            foreach ($callbacks as $callbackData) {
                if ($callbackData['callback'] === $callback) {
                    return true;
                }
            }
        }
        
        return false;
    }
    
    /**
     * Check if filter exists
     */
    public static function hasFilter($hook, $callback = false)
    {
        if (!isset(self::$filters[$hook])) {
            return false;
        }
        
        if ($callback === false) {
            return true;
        }
        
        foreach (self::$filters[$hook] as $priority => $callbacks) {
            foreach ($callbacks as $callbackData) {
                if ($callbackData['callback'] === $callback) {
                    return true;
                }
            }
        }
        
        return false;
    }
    
    /**
     * Get all registered hooks (for debugging)
     */
    public static function getHooks()
    {
        return [
            'actions' => self::$actions,
            'filters' => self::$filters,
            'action_count' => self::$actionCount,
            'filter_count' => self::$filterCount
        ];
    }
}

// Helper functions for global use
if (!function_exists('add_action')) {
    function add_action($hook, $callback, $priority = 10, $acceptedArgs = 1) {
        \App\Hooks\HookManager::addAction($hook, $callback, $priority, $acceptedArgs);
    }
}

if (!function_exists('do_action')) {
    function do_action($hook, ...$args) {
        \App\Hooks\HookManager::doAction($hook, ...$args);
    }
}

if (!function_exists('add_filter')) {
    function add_filter($hook, $callback, $priority = 10, $acceptedArgs = 1) {
        \App\Hooks\HookManager::addFilter($hook, $callback, $priority, $acceptedArgs);
    }
}

if (!function_exists('apply_filters')) {
    function apply_filters($hook, $value, ...$args) {
        return \App\Hooks\HookManager::applyFilters($hook, $value, ...$args);
    }
}

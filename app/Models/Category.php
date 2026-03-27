<?php

namespace App\Models;

use App\Database\Connection;

class Category
{
    private $db;

    public function __construct()
    {
        $this->db = Connection::getInstance();
    }

    public function getAll($activeOnly = true)
    {
        $where = $activeOnly ? "WHERE is_active = 1" : "";
        $sql = "SELECT * FROM categories $where ORDER BY sort_order ASC, name ASC";
        return $this->db->fetchAll($sql);
    }

    public function getById($id)
    {
        $sql = "SELECT * FROM categories WHERE id = :id";
        return $this->db->fetchOne($sql, ['id' => $id]);
    }

    public function getBySlug($slug)
    {
        $sql = "SELECT * FROM categories WHERE slug = :slug AND is_active = 1";
        return $this->db->fetchOne($sql, ['slug' => $slug]);
    }

    public function getChildren($parentId, $activeOnly = true)
    {
        $where = "WHERE parent_id = :parent_id";
        if ($activeOnly) {
            $where .= " AND is_active = 1";
        }
        $sql = "SELECT * FROM categories $where ORDER BY sort_order ASC, name ASC";
        return $this->db->fetchAll($sql, ['parent_id' => $parentId]);
    }

    public function getParent($categoryId)
    {
        $category = $this->getById($categoryId);
        if ($category && !empty($category['parent_id'])) {
            return $this->getById($category['parent_id']);
        }
        return null;
    }

    public function getAncestors($categoryId)
    {
        $ancestors = [];
        $category = $this->getById($categoryId);
        
        while ($category && !empty($category['parent_id'])) {
            $parent = $this->getById($category['parent_id']);
            if ($parent) {
                array_unshift($ancestors, $parent);
                $category = $parent;
            } else {
                break;
            }
        }
        
        return $ancestors;
    }

    public function getDescendants($categoryId, $includeSelf = false)
    {
        $descendants = [];
        $children = $this->getChildren($categoryId, false);
        
        foreach ($children as $child) {
            $descendants[] = $child['id'];
            $childDescendants = $this->getDescendants($child['id'], false);
            $descendants = array_merge($descendants, $childDescendants);
        }
        
        return array_unique($descendants);
    }

    public function getTree($parentId = null, $activeOnly = true)
    {
        $categories = $this->getAll($activeOnly);
        return $this->buildTree($categories, $parentId);
    }

    private function buildTree($categories, $parentId = null)
    {
        $branch = [];
        foreach ($categories as $category) {
            $catParentId = $category['parent_id'] ?? null;
            if (($catParentId === null && $parentId === null) || 
                ($catParentId !== null && (int)$catParentId === (int)$parentId)) {
                $children = $this->buildTree($categories, $category['id']);
                if (!empty($children)) {
                    $category['children'] = $children;
                }
                $branch[] = $category;
            }
        }
        return $branch;
    }

    public function getFlatTree($parentId = null, $activeOnly = true, $level = 0, $excludeId = null)
    {
        $result = [];
        $categories = $this->getAll($activeOnly);
        
        foreach ($categories as $category) {
            if ($excludeId && $category['id'] == $excludeId) {
                continue;
            }
            
            $catParentId = $category['parent_id'] ?? null;
            if (($catParentId === null && $parentId === null) || 
                ($catParentId !== null && (int)$catParentId === (int)$parentId)) {
                $category['level'] = $level;
                $result[] = $category;
                
                $children = $this->getFlatTree($category['id'], $activeOnly, $level + 1, $excludeId);
                $result = array_merge($result, $children);
            }
        }
        
        return $result;
    }

    public function create($data)
    {
        try {
            $fields = [
                'name', 'slug', 'description', 'image', 'parent_id', 
                'sort_order', 'is_active'
            ];
            
            $insertData = [];
            foreach ($fields as $field) {
                if (isset($data[$field])) {
                    $insertData[$field] = $data[$field];
                }
            }
            
            // Validate required fields
            if (empty($insertData['name'])) {
                return false;
            }
            
            // Generate slug if not provided
            if (empty($insertData['slug']) && !empty($insertData['name'])) {
                $insertData['slug'] = strtolower(trim(preg_replace('/[^a-z0-9-]+/', '-', $insertData['name']), '-'));
            }
            
            // Handle parent_id
            if (isset($insertData['parent_id'])) {
                if ($insertData['parent_id'] === '' || $insertData['parent_id'] === 0) {
                    $insertData['parent_id'] = null;
                } else {
                    $insertData['parent_id'] = (int)$insertData['parent_id'];
                    // Validate parent exists
                    $parent = $this->getById($insertData['parent_id']);
                    if (!$parent) {
                        $insertData['parent_id'] = null;
                    }
                }
            }
            
            // Set defaults
            if (!isset($insertData['is_active'])) {
                $insertData['is_active'] = 1;
            }
            if (!isset($insertData['sort_order'])) {
                $insertData['sort_order'] = 0;
            }
            
            return $this->db->insert('categories', $insertData);
        } catch (\Exception $e) {
            error_log('Category create error: ' . $e->getMessage());
            return false;
        }
    }

    public function update($id, $data)
    {
        try {
            $fields = [
                'name', 'slug', 'description', 'image', 'parent_id', 
                'sort_order', 'is_active'
            ];
            
            // Check if short_description field exists
            try {
                $this->db->fetchOne("SELECT short_description FROM categories LIMIT 1");
                $fields[] = 'short_description';
            } catch (\Exception $e) {
                // Field doesn't exist, skip it
            }
            
            $updateData = [];
            $validationError = null;
            
            foreach ($fields as $field) {
                if (isset($data[$field])) {
                    $updateData[$field] = $data[$field];
                }
            }
            
            // Check for duplicate slug if slug is being updated
            if (isset($updateData['slug']) && !empty($updateData['slug'])) {
                $existing = $this->db->fetchOne(
                    "SELECT id FROM categories WHERE slug = :slug AND id != :id",
                    ['slug' => $updateData['slug'], 'id' => $id]
                );
                if ($existing) {
                    $validationError = 'A category with the slug "' . $updateData['slug'] . '" already exists. Please choose a different slug.';
                    unset($updateData['slug']); // Remove slug from update to prevent constraint violation
                }
            }
            
            // Handle parent_id validation
            if (isset($updateData['parent_id'])) {
                if ($updateData['parent_id'] === '' || $updateData['parent_id'] === 0) {
                    $updateData['parent_id'] = null;
                } else {
                    $updateData['parent_id'] = (int)$updateData['parent_id'];
                    
                    // Prevent self-reference
                    if ($updateData['parent_id'] == $id) {
                        $validationError = 'Category cannot be its own parent.';
                        unset($updateData['parent_id']);
                    } else {
                        // Prevent circular reference - check if the new parent is a descendant
                        $descendants = $this->getDescendants($id, false);
                        if (in_array($updateData['parent_id'], $descendants)) {
                            $validationError = 'Cannot set parent: This would create a circular reference (the selected parent is a child of this category).';
                            unset($updateData['parent_id']);
                        } else {
                            // Validate parent exists
                            $parent = $this->getById($updateData['parent_id']);
                            if (!$parent) {
                                $validationError = 'Selected parent category does not exist.';
                                unset($updateData['parent_id']);
                            }
                        }
                    }
                }
            }
            
            // If there's a validation error, log it but still allow other fields to update
            if ($validationError) {
                error_log('Category update validation: ' . $validationError . ' (Category ID: ' . $id . ')');
            }
            
            // If updateData is empty, check if we had validation issues
            if (empty($updateData)) {
                // If there was a validation error, we should still return false to indicate the update didn't work as expected
                if ($validationError) {
                    // Store error for retrieval
                    if (session_status() === PHP_SESSION_NONE) {
                        session_start();
                    }
                    $_SESSION['category_update_error'] = $validationError;
                    return false;
                }
                // No validation error and nothing to update - return true (no changes needed)
                return true;
            }
            
            try {
                $result = $this->db->update('categories', $updateData, 'id = :id', ['id' => $id]);
                
                // Store validation warning if update succeeded but had a warning
                if ($validationError && $result) {
                    if (session_status() === PHP_SESSION_NONE) {
                        session_start();
                    }
                    $_SESSION['category_update_warning'] = $validationError;
                }
                
                // If update failed and we have validation error, store it
                if (!$result && $validationError) {
                    if (session_status() === PHP_SESSION_NONE) {
                        session_start();
                    }
                    $_SESSION['category_update_error'] = $validationError;
                }
                
                // If update returned 0 rows (no changes or category not found), check why
                if ($result === 0 && empty($validationError)) {
                    // Check if category still exists
                    $existing = $this->getById($id);
                    if (!$existing) {
                        if (session_status() === PHP_SESSION_NONE) {
                            session_start();
                        }
                        $_SESSION['category_update_error'] = 'Category not found. It may have been deleted.';
                        return false;
                    }
                    // Category exists but no rows updated - might be duplicate data
                    // This is actually OK, return true (no changes needed)
                    return true;
                }
                
                return $result > 0;
            } catch (\PDOException $e) {
                // Capture actual database error
                $errorCode = $e->getCode();
                $errorMessage = $e->getMessage();
                
                error_log('Category update PDO error: ' . $errorMessage);
                error_log('Category update error code: ' . $errorCode);
                
                if (session_status() === PHP_SESSION_NONE) {
                    session_start();
                }
                
                // Check for common database errors
                if ($errorCode == 23000 || strpos($errorMessage, 'Duplicate entry') !== false) {
                    // Unique constraint violation - likely duplicate slug
                    if (strpos($errorMessage, 'slug') !== false) {
                        $_SESSION['category_update_error'] = 'A category with this slug already exists. Please choose a different slug.';
                    } else {
                        $_SESSION['category_update_error'] = 'Duplicate entry detected. This category may conflict with an existing one.';
                    }
                } elseif (strpos($errorMessage, 'foreign key') !== false || strpos($errorMessage, 'constraint') !== false) {
                    $_SESSION['category_update_error'] = 'Cannot update category due to database constraint. Please check parent category relationships.';
                } else {
                    // Generic database error - show user-friendly message
                    $_SESSION['category_update_error'] = 'Database error: ' . (config('app.debug', false) ? $errorMessage : 'Please check that all fields are valid and try again.');
                }
                
                return false;
            }
        } catch (\Exception $e) {
            error_log('Category update error: ' . $e->getMessage());
            error_log('Category update error trace: ' . $e->getTraceAsString());
            
            if (session_status() === PHP_SESSION_NONE) {
                session_start();
            }
            $_SESSION['category_update_error'] = 'Error updating category: ' . (config('app.debug', false) ? $e->getMessage() : 'Please try again or contact support.');
            
            return false;
        }
    }

    public function delete($id)
    {
        try {
            // Check if category has products
            $productCount = $this->db->fetchOne(
                "SELECT COUNT(*) as count FROM products WHERE category_id = :id",
                ['id' => $id]
            )['count'] ?? 0;
            
            if ($productCount > 0) {
                return false; // Has products, cannot delete
            }
            
            // Check if category has children
            $children = $this->getChildren($id, false);
            if (!empty($children)) {
                return false; // Has sub-categories, cannot delete
            }
            
            return $this->db->delete('categories', 'id = :id', ['id' => $id]);
        } catch (\Exception $e) {
            error_log('Category delete error: ' . $e->getMessage());
            return false;
        }
    }

    public function getPath($categoryId, $separator = ' > ')
    {
        $ancestors = $this->getAncestors($categoryId);
        $category = $this->getById($categoryId);
        
        $path = [];
        foreach ($ancestors as $ancestor) {
            $path[] = $ancestor['name'];
        }
        if ($category) {
            $path[] = $category['name'];
        }
        
        return implode($separator, $path);
    }

    public function getBreadcrumbs($categoryId)
    {
        $breadcrumbs = [];
        $ancestors = $this->getAncestors($categoryId);
        $category = $this->getById($categoryId);
        
        $breadcrumbs[] = [
            'name' => 'Home',
            'url' => '/',
            'slug' => ''
        ];
        
        foreach ($ancestors as $ancestor) {
            $breadcrumbs[] = [
                'name' => $ancestor['name'],
                'url' => '/products.php?category=' . $ancestor['slug'],
                'slug' => $ancestor['slug']
            ];
        }
        
        if ($category) {
            $breadcrumbs[] = [
                'name' => $category['name'],
                'url' => '/products.php?category=' . $category['slug'],
                'slug' => $category['slug']
            ];
        }
        
        return $breadcrumbs;
    }

    public function getProductCount($categoryId, $includeSubcategories = true)
    {
        try {
            if ($includeSubcategories) {
                $descendants = $this->getDescendants($categoryId, true);
                $descendants[] = $categoryId;
                $placeholders = implode(',', array_fill(0, count($descendants), '?'));
                $sql = "SELECT COUNT(*) as count FROM products WHERE category_id IN ($placeholders)";
                $result = $this->db->fetchOne($sql, $descendants);
            } else {
                $result = $this->db->fetchOne(
                    "SELECT COUNT(*) as count FROM products WHERE category_id = :id",
                    ['id' => $categoryId]
                );
            }
            return $result['count'] ?? 0;
        } catch (\Exception $e) {
            return 0;
        }
    }

    public function reorder($orderData)
    {
        try {
            $pdo = $this->db->getPdo();
            $pdo->beginTransaction();
            
            foreach ($orderData as $item) {
                $categoryId = (int)($item['id'] ?? 0);
                if ($categoryId <= 0) continue;
                
                $updateData = [
                    'sort_order' => (int)($item['sort_order'] ?? 0)
                ];
                
                // Handle parent_id - can be null, empty string, or integer
                if (isset($item['parent_id'])) {
                    $parentId = $item['parent_id'];
                    if ($parentId === '' || $parentId === null || $parentId === 'null' || $parentId === 0) {
                        $updateData['parent_id'] = null;
                    } else {
                        $parentId = (int)$parentId;
                        // Validate parent exists
                        if ($parentId > 0) {
                            $parent = $this->getById($parentId);
                            if ($parent && $parentId != $categoryId) {
                                // Prevent circular reference
                                $descendants = $this->getDescendants($categoryId, false);
                                if (!in_array($parentId, $descendants)) {
                                    $updateData['parent_id'] = $parentId;
                                } else {
                                    $updateData['parent_id'] = null; // Circular reference, set to null
                                }
                            } else {
                                $updateData['parent_id'] = null; // Invalid parent, set to null
                            }
                        } else {
                            $updateData['parent_id'] = null;
                        }
                    }
                }
                
                $this->db->update('categories', $updateData, 'id = :id', ['id' => $categoryId]);
            }
            
            $pdo->commit();
            return true;
        } catch (\Exception $e) {
            if (isset($pdo)) {
                $pdo->rollBack();
            }
            error_log('Category reorder error: ' . $e->getMessage());
            return false;
        }
    }
}


<?php
require_once __DIR__ . '/../bootstrap/app.php';
require_once __DIR__ . '/includes/auth.php';

use App\Models\Category;

$categoryId = $_GET['id'] ?? null;
$categoryModel = new Category();

if (!$categoryId) {
    header('Location: ' . url('admin/categories.php'));
    exit;
}

$category = $categoryModel->getById($categoryId);

if (!$category) {
    header('Location: ' . url('admin/categories.php'));
    exit;
}

// Create duplicate
$newData = [
    'name' => $category['name'] . ' (Copy)',
    'slug' => $category['slug'] . '-copy-' . time(),
    'description' => $category['description'] ?? '',
    'image' => $category['image'] ?? null,
    'is_active' => 0, // Make inactive by default
    'meta_title' => $category['meta_title'] ?? null,
    'meta_description' => $category['meta_description'] ?? null,
];

$newId = $categoryModel->create($newData);

header('Location: ' . url('admin/category-edit.php?id=' . $newId . '&message=Category duplicated successfully'));
exit;


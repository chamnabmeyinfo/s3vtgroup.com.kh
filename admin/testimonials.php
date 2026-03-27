<?php
require_once __DIR__ . '/../bootstrap/app.php';
require_once __DIR__ . '/includes/auth.php';

$pageTitle = 'Manage Testimonials';
include __DIR__ . '/includes/header.php';

// Get all testimonials
$testimonials = db()->fetchAll("SELECT * FROM testimonials ORDER BY display_order, created_at DESC");
?>

<div class="p-6">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-2xl font-bold">Customer Testimonials</h1>
        <a href="testimonial-edit.php" class="btn-primary">
            <i class="fas fa-plus mr-2"></i> Add New Testimonial
        </a>
    </div>
    
    <div class="bg-white rounded-lg shadow-md overflow-hidden">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Customer</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Company</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Rating</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Featured</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Actions</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                <?php if (empty($testimonials)): ?>
                    <tr>
                        <td colspan="6" class="px-6 py-4 text-center text-gray-500">No testimonials found. <a href="testimonial-edit.php" class="text-blue-600 hover:underline">Add your first testimonial</a></td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($testimonials as $testimonial): ?>
                    <tr>
                        <td class="px-6 py-4">
                            <div class="text-sm font-medium"><?= escape($testimonial['customer_name']) ?></div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm"><?= escape($testimonial['company'] ?? '-') ?></td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm">
                            <?php for ($i = 1; $i <= 5; $i++): ?>
                                <i class="fas fa-star <?= $i <= $testimonial['rating'] ? 'text-yellow-400' : 'text-gray-300' ?>"></i>
                            <?php endfor; ?>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="px-2 py-1 text-xs rounded <?= $testimonial['is_featured'] ? 'bg-yellow-100 text-yellow-800' : 'bg-gray-100 text-gray-800' ?>">
                                <?= $testimonial['is_featured'] ? 'Featured' : '-' ?>
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="px-2 py-1 text-xs rounded <?= $testimonial['is_active'] ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800' ?>">
                                <?= $testimonial['is_active'] ? 'Active' : 'Inactive' ?>
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm space-x-2">
                            <a href="testimonial-edit.php?id=<?= $testimonial['id'] ?>" class="text-blue-600 hover:underline">Edit</a>
                            <a href="testimonial-delete.php?id=<?= $testimonial['id'] ?>" onclick="return confirm('Delete this testimonial?')" class="text-red-600 hover:underline">Delete</a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>


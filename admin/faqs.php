<?php
require_once __DIR__ . '/../bootstrap/app.php';
require_once __DIR__ . '/includes/auth.php';

$pageTitle = 'Manage FAQs';
include __DIR__ . '/includes/header.php';

// Get all FAQs
$faqs = db()->fetchAll("SELECT * FROM faqs ORDER BY display_order, id DESC");
?>

<div class="p-6">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-2xl font-bold">Frequently Asked Questions</h1>
        <a href="faq-edit.php" class="btn-primary">
            <i class="fas fa-plus mr-2"></i> Add New FAQ
        </a>
    </div>
    
    <div class="bg-white rounded-lg shadow-md overflow-hidden">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Question</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Category</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Order</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Actions</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                <?php if (empty($faqs)): ?>
                    <tr>
                        <td colspan="5" class="px-6 py-4 text-center text-gray-500">No FAQs found. <a href="faq-edit.php" class="text-blue-600 hover:underline">Add your first FAQ</a></td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($faqs as $faq): ?>
                    <tr>
                        <td class="px-6 py-4">
                            <div class="text-sm font-medium"><?= escape(substr($faq['question'], 0, 60)) ?>...</div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm"><?= escape($faq['category'] ?? '-') ?></td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm"><?= escape($faq['display_order']) ?></td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="px-2 py-1 text-xs rounded <?= $faq['is_active'] ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800' ?>">
                                <?= $faq['is_active'] ? 'Active' : 'Inactive' ?>
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm space-x-2">
                            <a href="faq-edit.php?id=<?= $faq['id'] ?>" class="text-blue-600 hover:underline">Edit</a>
                            <a href="faq-delete.php?id=<?= $faq['id'] ?>" onclick="return confirm('Delete this FAQ?')" class="text-red-600 hover:underline">Delete</a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>


<?php
require_once __DIR__ . '/../bootstrap/app.php';
require_once __DIR__ . '/includes/auth.php';

use App\Models\Footer;
use App\Models\Setting;

$footerModel = new Footer();
$settingModel = new Setting();
$message = '';
$error = '';

// Check if table exists
$tableExists = false;
try {
    db()->fetchOne("SELECT 1 FROM footer_content LIMIT 1");
    $tableExists = true;
} catch (\Exception $e) {
    $tableExists = false;
    $error = 'Footer management table does not exist. Please <a href="' . url('admin/setup-footer.php') . '" class="underline font-semibold">set it up first</a>.';
}

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    try {
        if ($action === 'save_company_info') {
            // Update company description
            $content = trim($_POST['company_description'] ?? '');
            $companyInfo = $footerModel->getCompanyInfo();
            
            if ($companyInfo) {
                $footerModel->update($companyInfo['id'], ['content' => $content]);
            } else {
                $footerModel->create([
                    'section_type' => 'company_info',
                    'content' => $content,
                    'display_order' => 1,
                    'is_active' => 1
                ]);
            }
            $message = 'Company information updated successfully.';
        }
        
        elseif ($action === 'add_link') {
            $sectionType = $_POST['link_section'] ?? 'quick_links';
            $linkText = trim($_POST['link_text'] ?? '');
            $linkUrl = trim($_POST['link_url'] ?? '');
            $icon = trim($_POST['icon'] ?? '');
            $sortOrder = (int)($_POST['sort_order'] ?? 0);
            
            if (empty($linkText) || empty($linkUrl)) {
                $error = 'Link text and URL are required.';
            } else {
                $footerModel->create([
                    'section_type' => $sectionType,
                    'link_text' => $linkText,
                    'link_url' => $linkUrl,
                    'icon' => $icon,
                    'sort_order' => $sortOrder,
                    'is_active' => 1,
                    'display_order' => $sectionType === 'quick_links' ? 2 : ($sectionType === 'bottom_text' ? 5 : 0)
                ]);
                $message = 'Link added successfully.';
            }
        }
        
        elseif ($action === 'update_link') {
            $id = (int)($_POST['id'] ?? 0);
            $linkText = trim($_POST['link_text'] ?? '');
            $linkUrl = trim($_POST['link_url'] ?? '');
            $icon = trim($_POST['icon'] ?? '');
            $sortOrder = (int)($_POST['sort_order'] ?? 0);
            
            if ($id > 0) {
                $footerModel->update($id, [
                    'link_text' => $linkText,
                    'link_url' => $linkUrl,
                    'icon' => $icon,
                    'sort_order' => $sortOrder
                ]);
                $message = 'Link updated successfully.';
            }
        }
        
        elseif ($action === 'delete_link') {
            $id = (int)($_POST['id'] ?? 0);
            if ($id > 0) {
                $footerModel->delete($id);
                $message = 'Link deleted successfully.';
            }
        }
        
        elseif ($action === 'toggle_active') {
            $id = (int)($_POST['id'] ?? 0);
            if ($id > 0) {
                $footerModel->toggleActive($id);
                $message = 'Status updated successfully.';
            }
        }
        
        elseif ($action === 'save_bottom_text') {
            $bottomText = trim($_POST['bottom_text'] ?? '');
            $settingModel->set('footer_text', $bottomText);
            $message = 'Bottom text updated successfully.';
        }
        
        elseif ($action === 'update_order') {
            $orders = $_POST['order'] ?? [];
            foreach ($orders as $id => $order) {
                $footerModel->update((int)$id, ['sort_order' => (int)$order]);
            }
            $message = 'Order updated successfully.';
        }
    } catch (\Exception $e) {
        $error = 'Error: ' . $e->getMessage();
    }
}

// Get all footer content organized by sections (only if table exists)
$organized = $tableExists ? $footerModel->getOrganized(false) : [];
$companyInfo = $tableExists ? $footerModel->getCompanyInfo() : null;
$quickLinks = $tableExists ? $footerModel->getQuickLinks() : [];
$socialMedia = $tableExists ? $footerModel->getSocialMedia() : [];
$bottomLinks = $tableExists ? $footerModel->getBottomContent() : [];
$footerText = $settingModel->get('footer_text', '© ' . date('Y') . ' ' . $settingModel->get('site_name', 'ForkliftPro') . '. All rights reserved.');

$pageTitle = 'Footer Management';
include __DIR__ . '/includes/header.php';
?>

<div class="w-full">
    <!-- Header -->
    <div class="bg-gradient-to-r from-indigo-600 via-purple-600 to-pink-600 rounded-xl shadow-xl p-4 md:p-6 lg:p-8 mb-4 md:mb-6 text-white">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl md:text-3xl font-bold mb-1 md:mb-2">
                    <i class="fas fa-sitemap mr-2 md:mr-3"></i>
                    Footer Management
                </h1>
                <p class="text-gray-200 text-sm md:text-lg">Manage your website footer content and links</p>
            </div>
        </div>
    </div>

    <?php if (!empty($message)): ?>
    <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 rounded-lg mb-6">
        <div class="flex items-center">
            <i class="fas fa-check-circle mr-2 text-xl"></i>
            <span class="font-semibold"><?= escape($message) ?></span>
        </div>
    </div>
    <?php endif; ?>

    <?php if (!empty($error)): ?>
    <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 rounded-lg mb-6">
        <div class="flex items-center">
            <i class="fas fa-exclamation-circle mr-2 text-xl"></i>
            <span class="font-semibold"><?= $error ?></span>
        </div>
    </div>
    <?php endif; ?>

    <?php if (!$tableExists): ?>
    <div class="bg-yellow-100 border-l-4 border-yellow-500 text-yellow-700 p-4 rounded-lg mb-6">
        <div class="flex items-center justify-between">
            <div class="flex items-center">
                <i class="fas fa-exclamation-triangle mr-2 text-xl"></i>
                <div>
                    <p class="font-semibold">Footer Management Table Not Found</p>
                    <p class="text-sm mt-1">You need to set up the footer_content table before you can manage footer content.</p>
                </div>
            </div>
            <a href="<?= url('admin/setup-footer.php') ?>" class="bg-yellow-600 text-white px-4 py-2 rounded-lg hover:bg-yellow-700 transition-colors font-semibold">
                <i class="fas fa-tools mr-2"></i>Set Up Now
            </a>
        </div>
    </div>
    <?php endif; ?>

    <!-- Tabs Navigation -->
    <div class="bg-white rounded-xl shadow-lg mb-6">
        <div class="border-b border-gray-200">
            <nav class="flex flex-wrap -mb-px" id="footer-tabs">
                <button onclick="showTab('company')" class="tab-button active px-6 py-4 text-sm font-medium border-b-2 border-indigo-600 text-indigo-600">
                    <i class="fas fa-building mr-2"></i>Company Info
                </button>
                <button onclick="showTab('quick-links')" class="tab-button px-6 py-4 text-sm font-medium border-b-2 border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300">
                    <i class="fas fa-link mr-2"></i>Quick Links
                </button>
                <button onclick="showTab('social')" class="tab-button px-6 py-4 text-sm font-medium border-b-2 border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300">
                    <i class="fas fa-share-alt mr-2"></i>Social Media
                </button>
                <button onclick="showTab('bottom')" class="tab-button px-6 py-4 text-sm font-medium border-b-2 border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300">
                    <i class="fas fa-copyright mr-2"></i>Bottom Text & Links
                </button>
            </nav>
        </div>
    </div>

    <!-- Tab Content -->
    <?php if ($tableExists): ?>
    <div class="space-y-6">
        <!-- Company Info Tab -->
        <div id="tab-company" class="tab-content">
            <div class="bg-white rounded-xl shadow-lg p-6">
                <h2 class="text-xl font-bold mb-4 flex items-center">
                    <i class="fas fa-building text-indigo-600 mr-2"></i>
                    Company Information
                </h2>
                <form method="POST" class="space-y-4">
                    <input type="hidden" name="action" value="save_company_info">
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">
                            Company Description
                        </label>
                        <textarea name="company_description" rows="4" 
                                  class="w-full px-4 py-3 border-2 border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-all"
                                  placeholder="Enter company description that appears in the footer..."><?= escape($companyInfo['content'] ?? '') ?></textarea>
                        <p class="text-xs text-gray-500 mt-1">This description appears below the company name in the footer.</p>
                    </div>
                    <button type="submit" class="bg-gradient-to-r from-indigo-600 to-purple-600 text-white px-6 py-3 rounded-lg font-semibold hover:from-indigo-700 hover:to-purple-700 transition-all duration-300 shadow-lg hover:shadow-xl transform hover:scale-105">
                        <i class="fas fa-save mr-2"></i>Save Company Info
                    </button>
                </form>
            </div>
        </div>

        <!-- Quick Links Tab -->
        <div id="tab-quick-links" class="tab-content hidden">
            <div class="bg-white rounded-xl shadow-lg p-6">
                <div class="flex justify-between items-center mb-6">
                    <h2 class="text-xl font-bold flex items-center">
                        <i class="fas fa-link text-indigo-600 mr-2"></i>
                        Quick Links
                    </h2>
                    <button onclick="showAddLinkModal('quick_links')" class="bg-indigo-600 text-white px-4 py-2 rounded-lg hover:bg-indigo-700 transition-colors">
                        <i class="fas fa-plus mr-2"></i>Add Link
                    </button>
                </div>
                
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Icon</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Link Text</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">URL</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Order</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php if (empty($quickLinks)): ?>
                                <tr>
                                    <td colspan="6" class="px-4 py-8 text-center text-gray-500">No quick links found. Add your first link above.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($quickLinks as $link): ?>
                                <tr>
                                    <td class="px-4 py-3">
                                        <?php if ($link['icon']): ?>
                                            <i class="<?= escape($link['icon']) ?> text-indigo-600"></i>
                                        <?php else: ?>
                                            <span class="text-gray-400">-</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="px-4 py-3 text-sm font-medium"><?= escape($link['link_text']) ?></td>
                                    <td class="px-4 py-3 text-sm text-gray-600"><?= escape($link['link_url']) ?></td>
                                    <td class="px-4 py-3 text-sm"><?= $link['sort_order'] ?></td>
                                    <td class="px-4 py-3">
                                        <form method="POST" class="inline">
                                            <input type="hidden" name="action" value="toggle_active">
                                            <input type="hidden" name="id" value="<?= $link['id'] ?>">
                                            <button type="submit" class="px-2 py-1 text-xs rounded <?= $link['is_active'] ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800' ?>">
                                                <?= $link['is_active'] ? 'Active' : 'Inactive' ?>
                                            </button>
                                        </form>
                                    </td>
                                    <td class="px-4 py-3 text-sm space-x-2">
                                        <button onclick="editLink(<?= htmlspecialchars(json_encode($link)) ?>)" class="text-blue-600 hover:underline">
                                            <i class="fas fa-edit"></i> Edit
                                        </button>
                                        <form method="POST" class="inline" onsubmit="return confirm('Delete this link?')">
                                            <input type="hidden" name="action" value="delete_link">
                                            <input type="hidden" name="id" value="<?= $link['id'] ?>">
                                            <button type="submit" class="text-red-600 hover:underline">
                                                <i class="fas fa-trash"></i> Delete
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Social Media Tab -->
        <div id="tab-social" class="tab-content hidden">
            <div class="bg-white rounded-xl shadow-lg p-6">
                <div class="flex justify-between items-center mb-6">
                    <h2 class="text-xl font-bold flex items-center">
                        <i class="fas fa-share-alt text-indigo-600 mr-2"></i>
                        Social Media Links
                    </h2>
                    <button onclick="showAddLinkModal('social_media')" class="bg-indigo-600 text-white px-4 py-2 rounded-lg hover:bg-indigo-700 transition-colors">
                        <i class="fas fa-plus mr-2"></i>Add Social Link
                    </button>
                </div>
                
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Icon</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Platform</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">URL</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php if (empty($socialMedia)): ?>
                                <tr>
                                    <td colspan="5" class="px-4 py-8 text-center text-gray-500">No social media links found. Add your first link above.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($socialMedia as $link): ?>
                                <tr>
                                    <td class="px-4 py-3">
                                        <?php if ($link['icon']): ?>
                                            <i class="<?= escape($link['icon']) ?> text-2xl text-indigo-600"></i>
                                        <?php else: ?>
                                            <span class="text-gray-400">-</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="px-4 py-3 text-sm font-medium"><?= escape($link['link_text']) ?></td>
                                    <td class="px-4 py-3 text-sm text-gray-600"><?= escape($link['link_url']) ?></td>
                                    <td class="px-4 py-3">
                                        <form method="POST" class="inline">
                                            <input type="hidden" name="action" value="toggle_active">
                                            <input type="hidden" name="id" value="<?= $link['id'] ?>">
                                            <button type="submit" class="px-2 py-1 text-xs rounded <?= $link['is_active'] ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800' ?>">
                                                <?= $link['is_active'] ? 'Active' : 'Inactive' ?>
                                            </button>
                                        </form>
                                    </td>
                                    <td class="px-4 py-3 text-sm space-x-2">
                                        <button onclick="editLink(<?= htmlspecialchars(json_encode($link)) ?>)" class="text-blue-600 hover:underline">
                                            <i class="fas fa-edit"></i> Edit
                                        </button>
                                        <form method="POST" class="inline" onsubmit="return confirm('Delete this link?')">
                                            <input type="hidden" name="action" value="delete_link">
                                            <input type="hidden" name="id" value="<?= $link['id'] ?>">
                                            <button type="submit" class="text-red-600 hover:underline">
                                                <i class="fas fa-trash"></i> Delete
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Bottom Text Tab -->
        <div id="tab-bottom" class="tab-content hidden">
            <div class="bg-white rounded-xl shadow-lg p-6 space-y-6">
                <div>
                    <h2 class="text-xl font-bold mb-4 flex items-center">
                        <i class="fas fa-copyright text-indigo-600 mr-2"></i>
                        Footer Bottom Text
                    </h2>
                    <form method="POST" class="space-y-4">
                        <input type="hidden" name="action" value="save_bottom_text">
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">
                                Copyright Text
                            </label>
                            <textarea name="bottom_text" rows="2" 
                                      class="w-full px-4 py-3 border-2 border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-all"
                                      placeholder="© 2024 Your Company. All rights reserved."><?= escape($footerText) ?></textarea>
                        </div>
                        <button type="submit" class="bg-gradient-to-r from-indigo-600 to-purple-600 text-white px-6 py-3 rounded-lg font-semibold hover:from-indigo-700 hover:to-purple-700 transition-all duration-300 shadow-lg hover:shadow-xl transform hover:scale-105">
                            <i class="fas fa-save mr-2"></i>Save Bottom Text
                        </button>
                    </form>
                </div>

                <div>
                    <div class="flex justify-between items-center mb-4">
                        <h3 class="text-lg font-bold flex items-center">
                            <i class="fas fa-link text-indigo-600 mr-2"></i>
                            Bottom Links (Privacy Policy, Terms, etc.)
                        </h3>
                        <button onclick="showAddLinkModal('bottom_text')" class="bg-indigo-600 text-white px-4 py-2 rounded-lg hover:bg-indigo-700 transition-colors">
                            <i class="fas fa-plus mr-2"></i>Add Link
                        </button>
                    </div>
                    
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Link Text</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">URL</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <?php if (empty($bottomLinks)): ?>
                                    <tr>
                                        <td colspan="4" class="px-4 py-8 text-center text-gray-500">No bottom links found. Add your first link above.</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($bottomLinks as $link): ?>
                                    <tr>
                                        <td class="px-4 py-3 text-sm font-medium"><?= escape($link['link_text']) ?></td>
                                        <td class="px-4 py-3 text-sm text-gray-600"><?= escape($link['link_url']) ?></td>
                                        <td class="px-4 py-3">
                                            <form method="POST" class="inline">
                                                <input type="hidden" name="action" value="toggle_active">
                                                <input type="hidden" name="id" value="<?= $link['id'] ?>">
                                                <button type="submit" class="px-2 py-1 text-xs rounded <?= $link['is_active'] ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800' ?>">
                                                    <?= $link['is_active'] ? 'Active' : 'Inactive' ?>
                                                </button>
                                            </form>
                                        </td>
                                        <td class="px-4 py-3 text-sm space-x-2">
                                            <button onclick="editLink(<?= htmlspecialchars(json_encode($link)) ?>)" class="text-blue-600 hover:underline">
                                                <i class="fas fa-edit"></i> Edit
                                            </button>
                                            <form method="POST" class="inline" onsubmit="return confirm('Delete this link?')">
                                                <input type="hidden" name="action" value="delete_link">
                                                <input type="hidden" name="id" value="<?= $link['id'] ?>">
                                                <button type="submit" class="text-red-600 hover:underline">
                                                    <i class="fas fa-trash"></i> Delete
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php else: ?>
    <div class="bg-white rounded-xl shadow-lg p-8 text-center">
        <i class="fas fa-database text-gray-400 text-6xl mb-4"></i>
        <h3 class="text-xl font-bold text-gray-800 mb-2">Table Not Set Up</h3>
        <p class="text-gray-600 mb-6">Please set up the footer_content table first.</p>
        <a href="<?= url('admin/setup-footer.php') ?>" class="inline-block bg-indigo-600 text-white px-6 py-3 rounded-lg font-semibold hover:bg-indigo-700 transition-colors">
            <i class="fas fa-tools mr-2"></i>Set Up Footer Management
        </a>
    </div>
    <?php endif; ?>
</div>

<!-- Add/Edit Link Modal -->
<div id="linkModal" class="fixed inset-0 bg-black bg-opacity-50 z-50 hidden flex items-center justify-center p-4">
    <div class="bg-white rounded-xl shadow-2xl max-w-md w-full p-6">
        <div class="flex justify-between items-center mb-4">
            <h3 class="text-xl font-bold" id="modalTitle">Add Link</h3>
            <button onclick="closeLinkModal()" class="text-gray-400 hover:text-gray-600">
                <i class="fas fa-times text-xl"></i>
            </button>
        </div>
        <form method="POST" id="linkForm" class="space-y-4">
            <input type="hidden" name="action" id="linkAction" value="add_link">
            <input type="hidden" name="id" id="linkId">
            <input type="hidden" name="link_section" id="linkSection">
            
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-2">Link Text</label>
                <input type="text" name="link_text" id="linkText" required
                       class="w-full px-4 py-2 border-2 border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
            </div>
            
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-2">URL</label>
                <input type="text" name="link_url" id="linkUrl" required
                       class="w-full px-4 py-2 border-2 border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                       placeholder="/page.php or https://example.com">
            </div>
            
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-2">Icon (Font Awesome class)</label>
                <input type="text" name="icon" id="linkIcon"
                       class="w-full px-4 py-2 border-2 border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                       placeholder="fas fa-home">
                <p class="text-xs text-gray-500 mt-1">Example: fas fa-home, fab fa-facebook-f</p>
            </div>
            
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-2">Sort Order</label>
                <input type="number" name="sort_order" id="linkSortOrder" value="0"
                       class="w-full px-4 py-2 border-2 border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
            </div>
            
            <div class="flex gap-3 pt-4">
                <button type="submit" class="flex-1 bg-indigo-600 text-white px-4 py-2 rounded-lg hover:bg-indigo-700 transition-colors font-semibold">
                    <i class="fas fa-save mr-2"></i>Save
                </button>
                <button type="button" onclick="closeLinkModal()" class="flex-1 bg-gray-200 text-gray-700 px-4 py-2 rounded-lg hover:bg-gray-300 transition-colors">
                    Cancel
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function showTab(tabName) {
    // Hide all tabs
    document.querySelectorAll('.tab-content').forEach(tab => {
        tab.classList.add('hidden');
    });
    
    // Remove active class from all buttons
    document.querySelectorAll('.tab-button').forEach(btn => {
        btn.classList.remove('active', 'border-indigo-600', 'text-indigo-600');
        btn.classList.add('border-transparent', 'text-gray-500');
    });
    
    // Show selected tab
    document.getElementById('tab-' + tabName).classList.remove('hidden');
    
    // Activate button
    event.target.classList.add('active', 'border-indigo-600', 'text-indigo-600');
    event.target.classList.remove('border-transparent', 'text-gray-500');
}

function showAddLinkModal(sectionType) {
    document.getElementById('linkModal').classList.remove('hidden');
    document.getElementById('linkModal').classList.add('flex');
    document.getElementById('modalTitle').textContent = 'Add Link';
    document.getElementById('linkAction').value = 'add_link';
    document.getElementById('linkSection').value = sectionType;
    document.getElementById('linkId').value = '';
    document.getElementById('linkForm').reset();
}

function editLink(link) {
    document.getElementById('linkModal').classList.remove('hidden');
    document.getElementById('linkModal').classList.add('flex');
    document.getElementById('modalTitle').textContent = 'Edit Link';
    document.getElementById('linkAction').value = 'update_link';
    document.getElementById('linkId').value = link.id;
    document.getElementById('linkSection').value = link.section_type;
    document.getElementById('linkText').value = link.link_text || '';
    document.getElementById('linkUrl').value = link.link_url || '';
    document.getElementById('linkIcon').value = link.icon || '';
    document.getElementById('linkSortOrder').value = link.sort_order || 0;
}

function closeLinkModal() {
    document.getElementById('linkModal').classList.add('hidden');
    document.getElementById('linkModal').classList.remove('flex');
}
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>

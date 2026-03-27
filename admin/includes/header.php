<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="robots" content="noindex, nofollow">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <!-- Prevent browser caching -->
    <meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate">
    <meta http-equiv="Pragma" content="no-cache">
    <meta http-equiv="Expires" content="0">
    <title><?= escape($pageTitle ?? 'Admin Panel') ?> - ForkliftPro</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- Font Awesome Icons - Primary CDN -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" integrity="sha512-iecdLmaskl7CVkqkXNQ/ZH/XLlvWZOJyj7Yy7tcenmpD1ypASozpmT/E0iPtmFIB46ZmdtAc9eNBvH0H/ZpiBw==" crossorigin="anonymous" referrerpolicy="no-referrer">
    <!-- Clean Admin Design -->
    <link rel="stylesheet" href="<?= url('assets/css/admin-clean.css') ?>">
    <style>
        .sidebar-transition {
            transition: transform 0.3s ease-in-out, width 0.3s ease-in-out;
        }
        .sidebar-collapsed {
            transform: translateX(-100%);
        }
        .sidebar-expanded {
            transform: translateX(0);
        }
        .main-expanded {
            margin-left: 0;
        }
        .main-collapsed {
            margin-left: 0;
        }
        @media (min-width: 1024px) {
            .sidebar-collapsed {
                transform: translateX(0);
                width: 0;
                overflow: hidden;
            }
            .main-expanded {
                margin-left: 0;
            }
        }
        .ribbon-item {
            transition: all 0.2s ease;
        }
        .ribbon-item:hover {
            transform: translateY(-2px);
        }
        .notification-badge {
            animation: pulse 2s infinite;
        }
        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.7; }
        }
        
        /* Custom Modal Styles */
        .custom-modal-overlay {
            animation: fadeIn 0.2s ease-out;
        }
        
        .custom-modal {
            animation: slideUp 0.3s ease-out;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }
        
        @keyframes slideUp {
            from {
                opacity: 0;
                transform: translateY(20px) scale(0.95);
            }
            to {
                opacity: 1;
                transform: translateY(0) scale(1);
            }
        }
        
        /* Interactive Button Classes */
        .btn-primary, .btn-secondary, .btn-danger, .btn-success, .btn-warning, .btn-info {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            padding: 0.625rem 1.25rem;
            font-size: 0.875rem;
            font-weight: 600;
            border-radius: 0.5rem;
            border: none;
            cursor: pointer;
            transition: all 0.2s ease-in-out;
            text-decoration: none;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
            color: white;
        }
        .btn-primary:hover {
            background: linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%);
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(59, 130, 246, 0.4);
        }
        .btn-primary:active {
            transform: translateY(0);
            box-shadow: 0 2px 4px rgba(59, 130, 246, 0.3);
        }
        
        .btn-secondary {
            background: linear-gradient(135deg, #6b7280 0%, #4b5563 100%);
            color: white;
        }
        .btn-secondary:hover {
            background: linear-gradient(135deg, #4b5563 0%, #374151 100%);
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(107, 114, 128, 0.4);
        }
        .btn-secondary:active {
            transform: translateY(0);
            box-shadow: 0 2px 4px rgba(107, 114, 128, 0.3);
        }
        
        .btn-danger {
            background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
            color: white;
        }
        .btn-danger:hover {
            background: linear-gradient(135deg, #dc2626 0%, #b91c1c 100%);
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(239, 68, 68, 0.4);
        }
        .btn-danger:active {
            transform: translateY(0);
            box-shadow: 0 2px 4px rgba(239, 68, 68, 0.3);
        }
        
        .btn-success {
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            color: white;
        }
        .btn-success:hover {
            background: linear-gradient(135deg, #059669 0%, #047857 100%);
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(16, 185, 129, 0.4);
        }
        .btn-success:active {
            transform: translateY(0);
            box-shadow: 0 2px 4px rgba(16, 185, 129, 0.3);
        }
        
        .btn-warning {
            background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
            color: white;
        }
        .btn-warning:hover {
            background: linear-gradient(135deg, #d97706 0%, #b45309 100%);
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(245, 158, 11, 0.4);
        }
        .btn-warning:active {
            transform: translateY(0);
            box-shadow: 0 2px 4px rgba(245, 158, 11, 0.3);
        }
        
        .btn-info {
            background: linear-gradient(135deg, #06b6d4 0%, #0891b2 100%);
            color: white;
        }
        .btn-info:hover {
            background: linear-gradient(135deg, #0891b2 0%, #0e7490 100%);
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(6, 182, 212, 0.4);
        }
        .btn-info:active {
            transform: translateY(0);
            box-shadow: 0 2px 4px rgba(6, 182, 212, 0.3);
        }
        
        /* Button Sizes */
        .btn-sm {
            padding: 0.375rem 0.75rem;
            font-size: 0.75rem;
        }
        .btn-lg {
            padding: 0.875rem 1.75rem;
            font-size: 1rem;
        }
        
        /* Icon-only buttons */
        .btn-icon {
            padding: 0.5rem;
            width: 2.5rem;
            height: 2.5rem;
        }
        
        /* Disabled state */
        .btn-primary:disabled, .btn-secondary:disabled, .btn-danger:disabled,
        .btn-success:disabled, .btn-warning:disabled, .btn-info:disabled {
            opacity: 0.5;
            cursor: not-allowed;
            transform: none !important;
        }
        
        /* Action button groups */
        .btn-group {
            display: inline-flex;
            gap: 0.5rem;
        }
        
        /* Action buttons in tables */
        .action-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 2rem;
            height: 2rem;
            padding: 0;
            border-radius: 0.375rem;
            transition: all 0.2s ease;
            text-decoration: none;
            cursor: pointer;
        }
        .action-btn:hover {
            transform: translateY(-2px) scale(1.05);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.15);
        }
        .action-btn-edit {
            background-color: #dbeafe;
            color: #1e40af;
        }
        .action-btn-edit:hover {
            background-color: #bfdbfe;
        }
        .action-btn-delete {
            background-color: #fee2e2;
            color: #991b1b;
        }
        .action-btn-delete:hover {
            background-color: #fecaca;
        }
        .action-btn-view {
            background-color: #d1fae5;
            color: #065f46;
        }
        .action-btn-view:hover {
            background-color: #a7f3d0;
        }
    </style>
</head>
<body class="bg-gray-100">
    <!-- Mobile Menu Overlay -->
    <div id="mobileMenuOverlay" class="fixed inset-0 bg-black bg-opacity-50 z-40 hidden lg:hidden" onclick="toggleSidebar()"></div>
    
    <!-- Advanced Interactive Ribbon -->
    <div class="bg-gradient-to-r from-blue-600 via-blue-700 to-indigo-700 text-white shadow-lg sticky top-0 z-50">
        <div class="px-4 py-3">
            <div class="flex items-center justify-between gap-4">
                <!-- Left Section: Toggle & Logo -->
                <div class="flex items-center gap-3">
                    <button onclick="toggleSidebar()" 
                            class="p-2 rounded-lg hover:bg-white/20 transition-colors group"
                            title="Toggle Sidebar">
                        <i class="fas fa-bars text-lg group-hover:scale-110 transition-transform"></i>
                    </button>
                    <a href="<?= url('admin/index.php') ?>" class="flex items-center gap-2 font-bold text-lg hover:opacity-80 transition-opacity">
                        <i class="fas fa-tachometer-alt"></i>
                        <span class="hidden sm:inline">Admin Panel</span>
                    </a>
                </div>
                
                <!-- Center Section: Quick Search & Actions -->
                <div class="flex-1 max-w-2xl hidden md:flex items-center gap-3">
                    <!-- Quick Search -->
                    <div class="flex-1 relative">
                        <i class="fas fa-search absolute left-3 top-1/2 transform -translate-y-1/2 text-blue-200"></i>
                        <input type="text" 
                               id="quickSearch" 
                               placeholder="Quick search..." 
                               class="w-full pl-10 pr-4 py-2 bg-white/10 border border-white/20 rounded-lg text-white placeholder-blue-200 focus:outline-none focus:ring-2 focus:ring-white/50 focus:bg-white/20 transition-all"
                               onkeyup="handleQuickSearch(event)">
                        <div id="quickSearchResults" class="hidden absolute top-full mt-2 w-full bg-white rounded-lg shadow-xl z-50 max-h-96 overflow-y-auto"></div>
                    </div>
                    
                    <!-- Quick Actions -->
                    <div class="flex items-center gap-2">
                        <button onclick="window.location.href='<?= url('admin/product-edit.php') ?>'" 
                                class="ribbon-item p-2 rounded-lg hover:bg-white/20 transition-all" 
                                title="Add Product">
                            <i class="fas fa-plus"></i>
                        </button>
                        <button onclick="window.location.href='<?= url('admin/orders.php') ?>'" 
                                class="ribbon-item p-2 rounded-lg hover:bg-white/20 transition-all relative" 
                                title="Orders">
                            <i class="fas fa-shopping-cart"></i>
                            <?php 
                            try {
                                $pendingOrders = db()->fetchOne("SELECT COUNT(*) as count FROM orders WHERE status = 'pending'")['count'] ?? 0;
                                if ($pendingOrders > 0): 
                            ?>
                                <span class="absolute -top-1 -right-1 bg-red-500 text-white text-xs rounded-full w-5 h-5 flex items-center justify-center notification-badge">
                                    <?= $pendingOrders > 9 ? '9+' : $pendingOrders ?>
                                </span>
                            <?php endif; } catch (Exception $e) {} ?>
                        </button>
                        <button onclick="window.location.href='<?= url('admin/messages.php') ?>'" 
                                class="ribbon-item p-2 rounded-lg hover:bg-white/20 transition-all relative" 
                                title="Messages">
                            <i class="fas fa-envelope"></i>
                            <?php 
                            try {
                                $unreadMessages = db()->fetchOne("SELECT COUNT(*) as count FROM contact_messages WHERE is_read = 0")['count'] ?? 0;
                                if ($unreadMessages > 0): 
                            ?>
                                <span class="absolute -top-1 -right-1 bg-red-500 text-white text-xs rounded-full w-5 h-5 flex items-center justify-center notification-badge">
                                    <?= $unreadMessages > 9 ? '9+' : $unreadMessages ?>
                                </span>
                            <?php endif; } catch (Exception $e) {} ?>
                        </button>
                    </div>
                </div>
                
                <!-- Right Section: User Info & Actions -->
                <div class="flex items-center gap-3">
                    <!-- Notifications Dropdown -->
                    <div class="relative">
                        <button onclick="toggleNotifications()" 
                                class="ribbon-item p-2 rounded-lg hover:bg-white/20 transition-all relative" 
                                title="Notifications">
                            <i class="fas fa-bell"></i>
                            <span id="notificationCount" class="absolute -top-1 -right-1 bg-red-500 text-white text-xs rounded-full w-5 h-5 flex items-center justify-center notification-badge hidden">
                                0
                            </span>
                        </button>
                        <div id="notificationsDropdown" class="hidden absolute right-0 mt-2 w-80 bg-white rounded-lg shadow-xl z-50 border border-gray-200">
                            <div class="p-4 border-b border-gray-200">
                                <h3 class="font-semibold text-gray-800">Notifications</h3>
                            </div>
                            <div id="notificationsList" class="max-h-96 overflow-y-auto">
                                <div class="p-4 text-center text-gray-500 text-sm">No new notifications</div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- User Menu -->
                    <div class="relative">
                        <button onclick="toggleUserMenu()" 
                                class="flex items-center gap-2 p-2 rounded-lg hover:bg-white/20 transition-all">
                            <div class="w-8 h-8 bg-white/20 rounded-full flex items-center justify-center">
                                <i class="fas fa-user text-sm"></i>
                            </div>
                            <span class="hidden sm:inline text-sm font-medium"><?= escape(session('admin_username') ?? 'Admin') ?></span>
                            <i class="fas fa-chevron-down text-xs"></i>
                        </button>
                        <div id="userMenuDropdown" class="hidden absolute right-0 mt-2 w-48 bg-white rounded-lg shadow-xl z-50 border border-gray-200">
                            <div class="p-2">
                                <?php if (session('admin_role_name')): ?>
                                <div class="px-3 py-2 text-xs text-gray-500 border-b border-gray-200">
                                    Role: <?= escape(session('admin_role_name')) ?>
                                </div>
                                <?php endif; ?>
                                <a href="<?= url('admin/settings.php') ?>" class="block px-3 py-2 text-sm text-gray-700 hover:bg-gray-100 rounded">
                                    <i class="fas fa-cog mr-2"></i> Settings
                                </a>
                                <a href="<?= url('admin/change-password.php') ?>" class="block px-3 py-2 text-sm text-gray-700 hover:bg-gray-100 rounded">
                                    <i class="fas fa-key mr-2"></i> Change Password
                                </a>
                                <div class="border-t border-gray-200 my-1"></div>
                                <?php if (session('admin_role_slug') === 'super_admin'): ?>
                                <a href="<?= url('developer/index.php') ?>" class="block px-3 py-2 text-sm text-purple-700 hover:bg-purple-50 rounded">
                                    <i class="fas fa-code mr-2"></i> Developer Panel
                                </a>
                                <?php endif; ?>
                                <a href="<?= url() ?>" target="_blank" class="block px-3 py-2 text-sm text-gray-700 hover:bg-gray-100 rounded">
                                    <i class="fas fa-external-link-alt mr-2"></i> View Website
                                </a>
                                <div class="border-t border-gray-200 my-1"></div>
                                <a href="<?= url('admin/logout.php') ?>" class="block px-3 py-2 text-sm text-red-700 hover:bg-red-50 rounded">
                                    <i class="fas fa-sign-out-alt mr-2"></i> Logout
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="flex relative">
        <!-- Sidebar - Toggleable on all screen sizes -->
        <aside id="sidebar" class="fixed lg:static inset-y-0 left-0 z-40 w-64 bg-gray-800 text-white min-h-screen sidebar-transition sidebar-expanded lg:translate-x-0">
            <div class="flex items-center justify-between p-4 border-b border-gray-700">
                <span class="font-bold text-lg">Menu</span>
                <button onclick="toggleSidebar()" class="p-2 rounded hover:bg-gray-700 lg:hidden">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <nav class="p-4 space-y-1 overflow-y-auto h-[calc(100vh-73px)]">
                <a href="<?= url('admin/index.php') ?>" class="flex items-center gap-3 px-4 py-3 rounded-lg hover:bg-gray-700 transition-colors <?= (basename($_SERVER['PHP_SELF']) == 'index.php') ? 'bg-gray-700' : '' ?>">
                    <i class="fas fa-dashboard w-5"></i>
                    <span>Dashboard</span>
                </a>
                <a href="<?= url('admin/products.php') ?>" class="flex items-center gap-3 px-4 py-3 rounded-lg hover:bg-gray-700 transition-colors <?= (basename($_SERVER['PHP_SELF']) == 'products.php') ? 'bg-gray-700' : '' ?>">
                    <i class="fas fa-box w-5"></i>
                    <span>Products</span>
                </a>
                <a href="<?= url('admin/categories.php') ?>" class="flex items-center gap-3 px-4 py-3 rounded-lg hover:bg-gray-700 transition-colors <?= (basename($_SERVER['PHP_SELF']) == 'categories.php') ? 'bg-gray-700' : '' ?>">
                    <i class="fas fa-tags w-5"></i>
                    <span>Categories</span>
                </a>
                <a href="<?= url('admin/pages.php') ?>" class="flex items-center gap-3 px-4 py-3 rounded-lg hover:bg-gray-700 transition-colors <?= (in_array(basename($_SERVER['PHP_SELF']), ['pages.php', 'page-edit.php'])) ? 'bg-gray-700' : '' ?>">
                    <i class="fas fa-file-alt w-5"></i>
                    <span>Pages</span>
                </a>
                <a href="<?= url('admin/hero-slider.php') ?>" class="flex items-center gap-3 px-4 py-3 rounded-lg hover:bg-gray-700 transition-colors <?= (in_array(basename($_SERVER['PHP_SELF']), ['hero-slider.php', 'hero-slider-edit.php'])) ? 'bg-gray-700' : '' ?>">
                    <i class="fas fa-images w-5"></i>
                    <span>Hero Slider</span>
                </a>
                <a href="<?= url('admin/partners.php') ?>" class="flex items-center gap-3 px-4 py-3 rounded-lg hover:bg-gray-700 transition-colors <?= (basename($_SERVER['PHP_SELF']) == 'partners.php') ? 'bg-gray-700' : '' ?>">
                    <i class="fas fa-handshake w-5"></i>
                    <span>Partners</span>
                </a>
                <a href="<?= url('admin/clients.php') ?>" class="flex items-center gap-3 px-4 py-3 rounded-lg hover:bg-gray-700 transition-colors <?= (basename($_SERVER['PHP_SELF']) == 'clients.php') ? 'bg-gray-700' : '' ?>">
                    <i class="fas fa-building w-5"></i>
                    <span>Clients</span>
                </a>
                <a href="<?= url('admin/quality-certifications.php') ?>" class="flex items-center gap-3 px-4 py-3 rounded-lg hover:bg-gray-700 transition-colors <?= (basename($_SERVER['PHP_SELF']) == 'quality-certifications.php') ? 'bg-gray-700' : '' ?>">
                    <i class="fas fa-certificate w-5"></i>
                    <span>Quality Certifications</span>
                </a>
                <a href="<?= url('admin/services.php') ?>" class="flex items-center gap-3 px-4 py-3 rounded-lg hover:bg-gray-700 transition-colors <?= (basename($_SERVER['PHP_SELF']) == 'services.php') ? 'bg-gray-700' : '' ?>">
                    <i class="fas fa-concierge-bell w-5"></i>
                    <span>Services</span>
                </a>
                <a href="<?= url('admin/ceo-message.php') ?>" class="flex items-center gap-3 px-4 py-3 rounded-lg hover:bg-gray-700 transition-colors <?= (basename($_SERVER['PHP_SELF']) == 'ceo-message.php') ? 'bg-gray-700' : '' ?>">
                    <i class="fas fa-user-tie w-5"></i>
                    <span>CEO Message</span>
                </a>
                <a href="<?= url('admin/mission-vision.php') ?>" class="flex items-center gap-3 px-4 py-3 rounded-lg hover:bg-gray-700 transition-colors <?= (basename($_SERVER['PHP_SELF']) == 'mission-vision.php') ? 'bg-gray-700' : '' ?>">
                    <i class="fas fa-bullseye w-5"></i>
                    <span>Mission & Vision</span>
                </a>
                <a href="<?= url('admin/menus.php') ?>" class="flex items-center gap-3 px-4 py-3 rounded-lg hover:bg-gray-700 transition-colors <?= (in_array(basename($_SERVER['PHP_SELF']), ['menus.php', 'menu-edit.php', 'menu-locations.php', 'menu-categories.php', 'mega-menu-manager.php'])) ? 'bg-gray-700' : '' ?>">
                    <i class="fas fa-bars w-5"></i>
                    <span>Menus</span>
                </a>
                <a href="<?= url('admin/menu-categories.php') ?>" class="flex items-center gap-3 px-4 py-3 rounded-lg hover:bg-gray-700 transition-colors <?= (basename($_SERVER['PHP_SELF']) == 'menu-categories.php') ? 'bg-gray-700' : '' ?>">
                    <i class="fas fa-list-ul w-5"></i>
                    <span>Menu Categories</span>
                </a>
                <a href="<?= url('admin/orders.php') ?>" class="flex items-center gap-3 px-4 py-3 rounded-lg hover:bg-gray-700 transition-colors <?= (basename($_SERVER['PHP_SELF']) == 'orders.php') ? 'bg-gray-700' : '' ?>">
                    <i class="fas fa-shopping-cart w-5"></i>
                    <span>Orders</span>
                </a>
                <a href="<?= url('admin/quotes.php') ?>" class="flex items-center gap-3 px-4 py-3 rounded-lg hover:bg-gray-700 transition-colors <?= (basename($_SERVER['PHP_SELF']) == 'quotes.php') ? 'bg-gray-700' : '' ?>">
                    <i class="fas fa-calculator w-5"></i>
                    <span>Quote Requests</span>
                </a>
                <a href="<?= url('admin/messages.php') ?>" class="flex items-center gap-3 px-4 py-3 rounded-lg hover:bg-gray-700 transition-colors <?= (basename($_SERVER['PHP_SELF']) == 'messages.php') ? 'bg-gray-700' : '' ?>">
                    <i class="fas fa-envelope w-5"></i>
                    <span>Messages</span>
                </a>
                <a href="<?= url('admin/reviews.php') ?>" class="flex items-center gap-3 px-4 py-3 rounded-lg hover:bg-gray-700 transition-colors <?= (basename($_SERVER['PHP_SELF']) == 'reviews.php') ? 'bg-gray-700' : '' ?>">
                    <i class="fas fa-star w-5"></i>
                    <span>Reviews</span>
                </a>
                <a href="<?= url('admin/newsletter.php') ?>" class="flex items-center gap-3 px-4 py-3 rounded-lg hover:bg-gray-700 transition-colors <?= (basename($_SERVER['PHP_SELF']) == 'newsletter.php') ? 'bg-gray-700' : '' ?>">
                    <i class="fas fa-paper-plane w-5"></i>
                    <span>Newsletter</span>
                </a>
                <div class="border-t border-gray-700 my-2"></div>
                <div class="px-4 py-2 text-xs text-gray-400 uppercase font-semibold">Analytics</div>
                <a href="<?= url('admin/analytics.php') ?>" class="flex items-center gap-3 px-4 py-3 rounded-lg hover:bg-gray-700 transition-colors">
                    <i class="fas fa-chart-line w-5"></i>
                    <span>Analytics</span>
                </a>
                <a href="<?= url('admin/advanced-analytics.php') ?>" class="flex items-center gap-3 px-4 py-3 rounded-lg hover:bg-gray-700 transition-colors">
                    <i class="fas fa-chart-bar w-5"></i>
                    <span>Advanced Analytics</span>
                </a>
                <div class="border-t border-gray-700 my-2"></div>
                <div class="px-4 py-2 text-xs text-gray-400 uppercase font-semibold">System</div>
                <a href="<?= url('admin/under-construction.php') ?>" class="flex items-center gap-3 px-4 py-3 rounded-lg hover:bg-gray-700 transition-colors <?= (basename($_SERVER['PHP_SELF']) == 'under-construction.php') ? 'bg-gray-700' : '' ?>">
                    <i class="fas fa-tools w-5"></i>
                    <span>Maintenance Mode</span>
                </a>
                <a href="<?= url('admin/images.php') ?>" class="flex items-center gap-3 px-4 py-3 rounded-lg hover:bg-gray-700 transition-colors">
                    <i class="fas fa-images w-5"></i>
                    <span>Images</span>
                </a>
                <a href="<?= url('admin/logs.php') ?>" class="flex items-center gap-3 px-4 py-3 rounded-lg hover:bg-gray-700 transition-colors">
                    <i class="fas fa-file-alt w-5"></i>
                    <span>System Logs</span>
                </a>
                <?php 
                if (function_exists('hasPermission')) {
                    try {
                        db()->fetchOne("SELECT 1 FROM roles LIMIT 1");
                        if (hasPermission('view_users')): 
                ?>
                <div class="border-t border-gray-700 my-2"></div>
                <div class="px-4 py-2 text-xs text-gray-400 uppercase font-semibold">User Management</div>
                <a href="<?= url('admin/users.php') ?>" class="flex items-center gap-3 px-4 py-3 rounded-lg hover:bg-gray-700 transition-colors">
                    <i class="fas fa-users w-5"></i>
                    <span>Users</span>
                </a>
                <?php if (hasPermission('view_roles')): ?>
                <a href="<?= url('admin/roles.php') ?>" class="flex items-center gap-3 px-4 py-3 rounded-lg hover:bg-gray-700 transition-colors">
                    <i class="fas fa-user-shield w-5"></i>
                    <span>Roles & Permissions</span>
                </a>
                <?php endif; ?>
                <?php 
                        endif;
                    } catch (\Exception $e) {}
                }
                ?>
                <div class="border-t border-gray-700 my-2"></div>
                <a href="<?= url('admin/footer.php') ?>" class="flex items-center gap-3 px-4 py-3 rounded-lg hover:bg-gray-700 transition-colors <?= (basename($_SERVER['PHP_SELF']) == 'footer.php') ? 'bg-gray-700' : '' ?>">
                    <i class="fas fa-sitemap w-5"></i>
                    <span>Footer</span>
                </a>
                <a href="<?= url('admin/settings.php') ?>" class="flex items-center gap-3 px-4 py-3 rounded-lg hover:bg-gray-700 transition-colors <?= (basename($_SERVER['PHP_SELF']) == 'settings.php') ? 'bg-gray-700' : '' ?>">
                    <i class="fas fa-cog w-5"></i>
                    <span>Settings</span>
                </a>
            </nav>
        </aside>
        
        <main id="mainContent" class="flex-1 p-4 md:p-6 lg:p-8 w-full min-w-0 transition-all duration-300">
            <script>
            // Sidebar Toggle Functionality
            let sidebarOpen = localStorage.getItem('sidebarOpen') !== 'false';
            
            function initSidebar() {
                const sidebar = document.getElementById('sidebar');
                const main = document.getElementById('mainContent');
                const overlay = document.getElementById('mobileMenuOverlay');
                
                if (window.innerWidth >= 1024) {
                    // Desktop: Show/hide sidebar
                    if (sidebarOpen) {
                        sidebar.classList.remove('sidebar-collapsed');
                        sidebar.classList.add('sidebar-expanded');
                        sidebar.style.width = '256px';
                    } else {
                        sidebar.classList.remove('sidebar-expanded');
                        sidebar.classList.add('sidebar-collapsed');
                        sidebar.style.width = '0';
                    }
                } else {
                    // Mobile: Slide in/out
                    if (sidebarOpen) {
                        sidebar.classList.remove('-translate-x-full');
                        overlay.classList.remove('hidden');
                    } else {
                        sidebar.classList.add('-translate-x-full');
                        overlay.classList.add('hidden');
                    }
                }
            }
            
            function toggleSidebar() {
                sidebarOpen = !sidebarOpen;
                localStorage.setItem('sidebarOpen', sidebarOpen);
                initSidebar();
            }
            
            // Initialize sidebar on load
            initSidebar();
            
            // Handle window resize
            window.addEventListener('resize', initSidebar);
            
            // Quick Search Functionality
            function handleQuickSearch(event) {
                const query = event.target.value.trim();
                const resultsDiv = document.getElementById('quickSearchResults');
                
                if (query.length < 2) {
                    resultsDiv.classList.add('hidden');
                    return;
                }
                
                // Simple search implementation - can be enhanced with AJAX
                resultsDiv.classList.remove('hidden');
                resultsDiv.innerHTML = `
                    <div class="p-4">
                        <div class="text-sm text-gray-600 mb-2">Search results for: "${query}"</div>
                        <a href="<?= url('admin/products.php?search=') ?>${encodeURIComponent(query)}" class="block p-2 hover:bg-gray-100 rounded">
                            <i class="fas fa-box text-blue-600 mr-2"></i> Search Products
                        </a>
                        <a href="<?= url('admin/orders.php?search=') ?>${encodeURIComponent(query)}" class="block p-2 hover:bg-gray-100 rounded">
                            <i class="fas fa-shopping-cart text-purple-600 mr-2"></i> Search Orders
                        </a>
                        <a href="<?= url('admin/messages.php?search=') ?>${encodeURIComponent(query)}" class="block p-2 hover:bg-gray-100 rounded">
                            <i class="fas fa-envelope text-red-600 mr-2"></i> Search Messages
                        </a>
                    </div>
                `;
            }
            
            // Close search results when clicking outside
            document.addEventListener('click', function(event) {
                const searchInput = document.getElementById('quickSearch');
                const resultsDiv = document.getElementById('quickSearchResults');
                if (!searchInput.contains(event.target) && !resultsDiv.contains(event.target)) {
                    resultsDiv.classList.add('hidden');
                }
            });
            
            // Notifications Dropdown
            function toggleNotifications() {
                const dropdown = document.getElementById('notificationsDropdown');
                dropdown.classList.toggle('hidden');
                loadNotifications();
            }
            
            function loadNotifications() {
                // Load notifications via AJAX or static
                const list = document.getElementById('notificationsList');
                // This can be enhanced with real-time notifications
            }
            
            // User Menu Dropdown
            function toggleUserMenu() {
                const dropdown = document.getElementById('userMenuDropdown');
                dropdown.classList.toggle('hidden');
            }
            
            // Close dropdowns when clicking outside
            document.addEventListener('click', function(event) {
                const notificationsBtn = event.target.closest('[onclick="toggleNotifications()"]');
                const userMenuBtn = event.target.closest('[onclick="toggleUserMenu()"]');
                
                if (!notificationsBtn) {
                    document.getElementById('notificationsDropdown').classList.add('hidden');
                }
                if (!userMenuBtn) {
                    document.getElementById('userMenuDropdown').classList.add('hidden');
                }
            });
            </script>
            <?php 
            // Ensure message and error variables are defined
            if (!isset($message)) $message = '';
            if (!isset($error)) $error = '';
            
            if (!empty($message)): ?>
                <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
                    <?= escape($message) ?>
                </div>
            <?php endif; ?>
            
            <?php if (!empty($error)): ?>
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
                    <?= escape($error) ?>
                </div>
            <?php endif; ?>

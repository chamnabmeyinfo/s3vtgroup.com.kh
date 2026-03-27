<?php
// Developer Panel - Main Dashboard
// Note: Developer login removed - only accessible by super admins from admin panel
require_once __DIR__ . '/../bootstrap/app.php';
require_once __DIR__ . '/includes/auth.php';

$pageTitle = 'Developer Panel';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= escape($pageTitle) ?> - S3VGroup</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-gray-100">
    <div class="min-h-screen">
        <!-- Header -->
        <header class="bg-gradient-to-r from-purple-600 to-indigo-600 text-white shadow-lg">
            <div class="container mx-auto px-4 py-4">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <i class="fas fa-code text-2xl"></i>
                        <h1 class="text-2xl font-bold">Developer Panel</h1>
                    </div>
                    <div class="flex items-center gap-4">
                        <span class="text-purple-100">
                            <i class="fas fa-user mr-2"></i>
                            <?= escape(session('admin_username') ?? 'Super Admin') ?>
                            <span class="ml-2 px-2 py-1 bg-yellow-500 text-white text-xs rounded-full font-semibold">
                                <i class="fas fa-crown mr-1"></i>Super Admin
                            </span>
                        </span>
                        <a href="<?= url('admin/index.php') ?>" class="bg-white/20 hover:bg-white/30 px-4 py-2 rounded-lg transition-all">
                            <i class="fas fa-tachometer-alt mr-2"></i>Admin Panel
                        </a>
                        <a href="<?= url('admin/logout.php') ?>" class="bg-white/20 hover:bg-white/30 px-4 py-2 rounded-lg transition-all">
                            <i class="fas fa-sign-out-alt mr-2"></i>Logout
                        </a>
                    </div>
                </div>
            </div>
        </header>
        
        <!-- Main Content -->
        <main class="container mx-auto px-4 py-8">
            <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-6">
                <!-- Database Tools -->
                <div class="bg-white rounded-xl shadow-lg p-6 hover:shadow-xl transition-all">
                    <div class="flex items-center gap-3 mb-4">
                        <div class="bg-blue-100 rounded-lg p-3">
                            <i class="fas fa-database text-blue-600 text-xl"></i>
                        </div>
                        <h2 class="text-xl font-bold text-gray-800">Database Tools</h2>
                    </div>
                    <p class="text-gray-600 mb-4">Manage database operations and backups</p>
                    <div class="space-y-2">
                        <a href="<?= url('admin/database-upload.php') ?>" class="block btn-primary">
                            <i class="fas fa-upload"></i> Database Upload
                        </a>
                    </div>
                </div>
                
                <!-- Deployment Tools -->
                <div class="bg-white rounded-xl shadow-lg p-6 hover:shadow-xl transition-all">
                    <div class="flex items-center gap-3 mb-4">
                        <div class="bg-green-100 rounded-lg p-3">
                            <i class="fas fa-rocket text-green-600 text-xl"></i>
                        </div>
                        <h2 class="text-xl font-bold text-gray-800">Deployment</h2>
                    </div>
                    <p class="text-gray-600 mb-4">Deploy and manage production updates</p>
                    <div class="space-y-2">
                        <a href="<?= url('admin/backup.php') ?>" class="block btn-secondary">
                            <i class="fas fa-download"></i> Backup & Restore
                        </a>
                    </div>
                </div>
                
                <!-- System Info -->
                <div class="bg-white rounded-xl shadow-lg p-6 hover:shadow-xl transition-all">
                    <div class="flex items-center gap-3 mb-4">
                        <div class="bg-purple-100 rounded-lg p-3">
                            <i class="fas fa-info-circle text-purple-600 text-xl"></i>
                        </div>
                        <h2 class="text-xl font-bold text-gray-800">System Info</h2>
                    </div>
                    <div class="space-y-2 text-sm">
                        <div class="flex justify-between">
                            <span class="text-gray-600">PHP Version:</span>
                            <span class="font-semibold"><?= PHP_VERSION ?></span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-600">Server:</span>
                            <span class="font-semibold"><?= $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown' ?></span>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Quick Links -->
            <div class="mt-8 bg-white rounded-xl shadow-lg p-6">
                <h2 class="text-xl font-bold text-gray-800 mb-4">
                    <i class="fas fa-link mr-2 text-purple-600"></i>Quick Links
                </h2>
                <div class="grid md:grid-cols-2 gap-4">
                    <a href="<?= url('admin/index.php') ?>" class="btn-secondary">
                        <i class="fas fa-tachometer-alt"></i> Admin Dashboard
                    </a>
                    <a href="<?= url('admin/products.php') ?>" class="btn-secondary">
                        <i class="fas fa-box"></i> Products
                    </a>
                    <a href="<?= url('admin/categories.php') ?>" class="btn-secondary">
                        <i class="fas fa-tags"></i> Categories
                    </a>
                    <a href="<?= url('admin/orders.php') ?>" class="btn-secondary">
                        <i class="fas fa-shopping-cart"></i> Orders
                    </a>
                </div>
            </div>
        </main>
    </div>
    
    <style>
        .btn-primary, .btn-secondary {
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
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }
        .btn-primary:hover {
            background: linear-gradient(135deg, #5568d3 0%, #6a3d8f 100%);
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(102, 126, 234, 0.4);
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
    </style>
</body>
</html>

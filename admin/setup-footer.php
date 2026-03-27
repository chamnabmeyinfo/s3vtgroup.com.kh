<?php
/**
 * Footer Management Setup Script
 * 
 * This script will create the footer_content table and insert default data.
 * Run this once to set up the footer management system.
 */

require_once __DIR__ . '/../bootstrap/app.php';
require_once __DIR__ . '/includes/auth.php';

$message = '';
$error = '';
$success = false;

// Check if table already exists
$tableExists = false;
try {
    db()->fetchOne("SELECT 1 FROM footer_content LIMIT 1");
    $tableExists = true;
} catch (\Exception $e) {
    $tableExists = false;
}

// Handle setup
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['setup'])) {
    try {
        $sqlFile = __DIR__ . '/../database/footer-management.sql';
        
        if (!file_exists($sqlFile)) {
            throw new \Exception('SQL file not found: ' . $sqlFile);
        }
        
        $sql = file_get_contents($sqlFile);
        
        // Remove CREATE DATABASE and USE statements if present
        $sql = preg_replace('/CREATE DATABASE.*?;/is', '', $sql);
        $sql = preg_replace('/USE.*?;/is', '', $sql);
        
        // Split by semicolon and execute each statement
        $statements = array_filter(array_map('trim', explode(';', $sql)));
        
        foreach ($statements as $statement) {
            if (!empty($statement) && !preg_match('/^--/', $statement)) {
                try {
                    db()->query($statement, []);
                } catch (\Exception $e) {
                    // Ignore errors for existing tables/inserts
                    if (strpos($e->getMessage(), 'already exists') === false && 
                        strpos($e->getMessage(), 'Duplicate entry') === false) {
                        throw $e;
                    }
                }
            }
        }
        
        $success = true;
        $message = 'Footer management table created successfully! You can now manage footer content from the Footer page.';
        $tableExists = true;
    } catch (\Exception $e) {
        $error = 'Error setting up footer table: ' . $e->getMessage();
    }
}

$pageTitle = 'Setup Footer Management';
include __DIR__ . '/includes/header.php';
?>

<div class="w-full">
    <!-- Header -->
    <div class="bg-gradient-to-r from-indigo-600 via-purple-600 to-pink-600 rounded-xl shadow-xl p-4 md:p-6 lg:p-8 mb-4 md:mb-6 text-white">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl md:text-3xl font-bold mb-1 md:mb-2">
                    <i class="fas fa-tools mr-2 md:mr-3"></i>
                    Footer Management Setup
                </h1>
                <p class="text-gray-200 text-sm md:text-lg">Set up the footer content management system</p>
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
            <span class="font-semibold"><?= escape($error) ?></span>
        </div>
    </div>
    <?php endif; ?>

    <div class="bg-white rounded-xl shadow-lg p-6">
        <?php if ($tableExists): ?>
            <div class="text-center py-8">
                <div class="mb-4">
                    <i class="fas fa-check-circle text-green-500 text-6xl"></i>
                </div>
                <h2 class="text-2xl font-bold text-gray-800 mb-2">Footer Management is Already Set Up!</h2>
                <p class="text-gray-600 mb-6">The footer_content table exists and is ready to use.</p>
                <a href="<?= url('admin/footer.php') ?>" class="inline-block bg-indigo-600 text-white px-6 py-3 rounded-lg font-semibold hover:bg-indigo-700 transition-colors">
                    <i class="fas fa-sitemap mr-2"></i>Go to Footer Management
                </a>
            </div>
        <?php else: ?>
            <div class="space-y-6">
                <div>
                    <h2 class="text-xl font-bold mb-4">What This Will Do:</h2>
                    <ul class="list-disc list-inside space-y-2 text-gray-700">
                        <li>Create the <code class="bg-gray-100 px-2 py-1 rounded">footer_content</code> table</li>
                        <li>Insert default footer content (company info, quick links, social media, etc.)</li>
                        <li>Enable footer management from the admin panel</li>
                    </ul>
                </div>

                <div class="bg-yellow-50 border-l-4 border-yellow-400 p-4 rounded">
                    <div class="flex items-start">
                        <i class="fas fa-exclamation-triangle text-yellow-600 mr-3 mt-1"></i>
                        <div>
                            <p class="font-semibold text-yellow-800 mb-1">Before You Proceed:</p>
                            <p class="text-yellow-700 text-sm">Make sure you have a database backup. This will create a new table in your database.</p>
                        </div>
                    </div>
                </div>

                <form method="POST" onsubmit="return confirm('This will create the footer_content table. Continue?')">
                    <input type="hidden" name="setup" value="1">
                    <button type="submit" class="w-full bg-gradient-to-r from-indigo-600 to-purple-600 text-white px-6 py-4 rounded-lg font-semibold text-lg hover:from-indigo-700 hover:to-purple-700 transition-all duration-300 shadow-lg hover:shadow-xl transform hover:scale-105">
                        <i class="fas fa-magic mr-2"></i>Set Up Footer Management
                    </button>
                </form>

                <div class="border-t pt-6">
                    <h3 class="font-bold mb-3">Alternative: Manual Setup</h3>
                    <p class="text-gray-600 mb-3">If you prefer to set up manually, run this SQL file in your database:</p>
                    <code class="block bg-gray-100 p-3 rounded text-sm">database/footer-management.sql</code>
                    <p class="text-sm text-gray-500 mt-2">
                        <i class="fas fa-info-circle mr-1"></i>
                        You can import it via phpMyAdmin, cPanel MySQL, or command line.
                    </p>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>

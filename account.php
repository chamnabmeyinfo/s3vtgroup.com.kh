<?php
require_once __DIR__ . '/bootstrap/app.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['customer_id'])) {
    header('Location: ' . url('login.php'));
    exit;
}

$customer = db()->fetchOne("SELECT * FROM customers WHERE id = :id", ['id' => $_SESSION['customer_id']]);
$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['update_profile'])) {
        $data = [
            'first_name' => trim($_POST['first_name'] ?? ''),
            'last_name' => trim($_POST['last_name'] ?? ''),
            'phone' => trim($_POST['phone'] ?? ''),
            'company' => trim($_POST['company'] ?? ''),
            'address' => trim($_POST['address'] ?? ''),
            'city' => trim($_POST['city'] ?? ''),
            'state' => trim($_POST['state'] ?? ''),
            'zip_code' => trim($_POST['zip_code'] ?? ''),
            'country' => trim($_POST['country'] ?? 'USA'),
        ];
        
        db()->update('customers', $data, ['id' => $_SESSION['customer_id']]);
        $customer = array_merge($customer, $data);
        $success = 'Profile updated successfully!';
    }
    
    if (isset($_POST['change_password'])) {
        $currentPassword = $_POST['current_password'] ?? '';
        $newPassword = $_POST['new_password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';
        
        if (password_verify($currentPassword, $customer['password'])) {
            if ($newPassword === $confirmPassword && strlen($newPassword) >= 6) {
                db()->update('customers', 
                    ['password' => password_hash($newPassword, PASSWORD_BCRYPT)],
                    ['id' => $_SESSION['customer_id']]
                );
                $success = 'Password changed successfully!';
            } else {
                $error = 'New passwords do not match or are too short.';
            }
        } else {
            $error = 'Current password is incorrect.';
        }
    }
}

$pageTitle = 'My Account - ' . get_site_name();
$robotsNoIndex = true;
include __DIR__ . '/includes/header.php';
?>

<main class="py-8">
    <div class="container mx-auto px-4 max-w-4xl">
        <h1 class="text-3xl font-bold mb-6">My Account</h1>
        
        <?php if ($success): ?>
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
                <?= escape($success) ?>
            </div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
                <?= escape($error) ?>
            </div>
        <?php endif; ?>
        
        <div class="grid md:grid-cols-3 gap-6">
            <!-- Sidebar -->
            <div class="bg-white rounded-lg shadow-md p-6">
                <div class="text-center mb-6">
                    <div class="w-20 h-20 bg-blue-500 rounded-full flex items-center justify-center text-white text-2xl font-bold mx-auto mb-3">
                        <?= strtoupper(substr($customer['first_name'] ?? 'U', 0, 1)) ?>
                    </div>
                    <h3 class="font-bold"><?= escape(trim(($customer['first_name'] ?? '') . ' ' . ($customer['last_name'] ?? '')) ?: 'Customer') ?></h3>
                    <p class="text-sm text-gray-600"><?= escape($customer['email']) ?></p>
                </div>
                
                <nav class="space-y-2">
                    <a href="#profile" class="block p-2 rounded hover:bg-blue-50 active-section">Profile</a>
                    <a href="#orders" class="block p-2 rounded hover:bg-blue-50">Orders</a>
                    <a href="<?= url('wishlist.php') ?>" class="block p-2 rounded hover:bg-blue-50">Wishlist</a>
                    <a href="<?= url('logout.php') ?>" class="block p-2 rounded hover:bg-red-50 text-red-600">Logout</a>
                </nav>
            </div>
            
            <!-- Main Content -->
            <div class="md:col-span-2 space-y-6">
                <!-- Profile Section -->
                <div id="profile-section" class="bg-white rounded-lg shadow-md p-6">
                    <h2 class="text-xl font-bold mb-4">Profile Information</h2>
                    <form method="POST" class="space-y-4">
                        <input type="hidden" name="update_profile" value="1">
                        
                        <div class="grid md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium mb-2">First Name</label>
                                <input type="text" name="first_name" value="<?= escape($customer['first_name'] ?? '') ?>"
                                       class="w-full px-4 py-2 border rounded-lg">
                            </div>
                            <div>
                                <label class="block text-sm font-medium mb-2">Last Name</label>
                                <input type="text" name="last_name" value="<?= escape($customer['last_name'] ?? '') ?>"
                                       class="w-full px-4 py-2 border rounded-lg">
                            </div>
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium mb-2">Email</label>
                            <input type="email" value="<?= escape($customer['email']) ?>" disabled
                                   class="w-full px-4 py-2 border rounded-lg bg-gray-100">
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium mb-2">Phone</label>
                            <input type="tel" name="phone" value="<?= escape($customer['phone'] ?? '') ?>"
                                   class="w-full px-4 py-2 border rounded-lg">
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium mb-2">Company</label>
                            <input type="text" name="company" value="<?= escape($customer['company'] ?? '') ?>"
                                   class="w-full px-4 py-2 border rounded-lg">
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium mb-2">Address</label>
                            <textarea name="address" rows="2"
                                      class="w-full px-4 py-2 border rounded-lg"><?= escape($customer['address'] ?? '') ?></textarea>
                        </div>
                        
                        <div class="grid md:grid-cols-3 gap-4">
                            <div>
                                <label class="block text-sm font-medium mb-2">City</label>
                                <input type="text" name="city" value="<?= escape($customer['city'] ?? '') ?>"
                                       class="w-full px-4 py-2 border rounded-lg">
                            </div>
                            <div>
                                <label class="block text-sm font-medium mb-2">State</label>
                                <input type="text" name="state" value="<?= escape($customer['state'] ?? '') ?>"
                                       class="w-full px-4 py-2 border rounded-lg">
                            </div>
                            <div>
                                <label class="block text-sm font-medium mb-2">ZIP Code</label>
                                <input type="text" name="zip_code" value="<?= escape($customer['zip_code'] ?? '') ?>"
                                       class="w-full px-4 py-2 border rounded-lg">
                            </div>
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium mb-2">Country</label>
                            <input type="text" name="country" value="<?= escape($customer['country'] ?? 'USA') ?>"
                                   class="w-full px-4 py-2 border rounded-lg">
                        </div>
                        
                        <button type="submit" class="btn-primary">Update Profile</button>
                    </form>
                </div>
                
                <!-- Change Password -->
                <div class="bg-white rounded-lg shadow-md p-6">
                    <h2 class="text-xl font-bold mb-4">Change Password</h2>
                    <form method="POST" class="space-y-4">
                        <input type="hidden" name="change_password" value="1">
                        
                        <div>
                            <label class="block text-sm font-medium mb-2">Current Password</label>
                            <input type="password" name="current_password" required
                                   class="w-full px-4 py-2 border rounded-lg">
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium mb-2">New Password</label>
                            <input type="password" name="new_password" required
                                   class="w-full px-4 py-2 border rounded-lg">
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium mb-2">Confirm New Password</label>
                            <input type="password" name="confirm_password" required
                                   class="w-full px-4 py-2 border rounded-lg">
                        </div>
                        
                        <button type="submit" class="btn-primary">Change Password</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</main>

<?php include __DIR__ . '/includes/footer.php'; ?>


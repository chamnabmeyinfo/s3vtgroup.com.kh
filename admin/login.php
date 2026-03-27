<?php
require_once __DIR__ . '/../bootstrap/app.php';

// Redirect if already logged in
if (session('admin_logged_in')) {
    header('Location: ' . url('admin/index.php'));
    exit;
}

$error = '';

// Handle login form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    
    if (empty($username) || empty($password)) {
        $error = 'Please enter username and password.';
    } else {
        try {
            // Find user by username (case-insensitive)
            $user = null;
            
            // Try exact match first
            try {
                $user = db()->fetchOne(
                    "SELECT u.*, r.id as role_id, r.slug as role_slug, r.name as role_name 
                     FROM admin_users u 
                     LEFT JOIN roles r ON u.role_id = r.id 
                     WHERE u.username = :username",
                    ['username' => $username]
                );
            } catch (\Exception $e) {
                // If roles table doesn't exist, try without join
                try {
                    $user = db()->fetchOne(
                        "SELECT * FROM admin_users WHERE username = :username",
                        ['username' => $username]
                    );
                } catch (\Exception $e2) {
                    error_log('Login query error: ' . $e2->getMessage());
                }
            }
            
            // If not found with exact match, try case-insensitive
            if (!$user) {
                try {
                    $user = db()->fetchOne(
                        "SELECT u.*, r.id as role_id, r.slug as role_slug, r.name as role_name 
                         FROM admin_users u 
                         LEFT JOIN roles r ON u.role_id = r.id 
                         WHERE LOWER(u.username) = LOWER(:username)",
                        ['username' => $username]
                    );
                } catch (\Exception $e) {
                    // If roles table doesn't exist, try without join
                    try {
                        $user = db()->fetchOne(
                            "SELECT * FROM admin_users WHERE LOWER(username) = LOWER(:username)",
                            ['username' => $username]
                        );
                    } catch (\Exception $e2) {
                        error_log('Login case-insensitive query error: ' . $e2->getMessage());
                    }
                }
            }
            
            // Verify password
            if ($user) {
                if (empty($user['password'])) {
                    $error = 'User account has no password set. Please contact administrator.';
                } elseif (password_verify($password, $user['password'])) {
                    // Password is correct - login successful
                    session('admin_logged_in', true);
                    session('admin_user_id', $user['id']);
                    session('admin_username', $user['username']);
                    
                    // Load role information
                    if (!empty($user['role_id'])) {
                        session('admin_role_id', $user['role_id']);
                        if (!empty($user['role_name'])) {
                            session('admin_role_name', $user['role_name']);
                        }
                        if (!empty($user['role_slug'])) {
                            session('admin_role_slug', $user['role_slug']);
                        }
                    }
                
                    // Update last login
                    try {
                        db()->query(
                            "UPDATE admin_users SET last_login = NOW() WHERE id = :id",
                            ['id' => $user['id']]
                        );
                    } catch (\Exception $e) {
                        // Ignore if update fails (last_login column might not exist)
                    }
                    
                    header('Location: ' . url('admin/index.php'));
                    exit;
                } else {
                    // Password is incorrect
                    $error = 'Invalid username or password.';
                }
            } else {
                // User not found
                $error = 'Invalid username or password.';
            }
        } catch (\Exception $e) {
            $error = 'Database error. Please check your database setup.';
            error_log('Admin login database error: ' . $e->getMessage());
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login - ForkliftPro</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- Font Awesome Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" integrity="sha512-iecdLmaskl7CVkqkXNQ/ZH/XLlvWZOJyj7Yy7tcenmpD1ypASozpmT/E0iPtmFIB46ZmdtAc9eNBvH0H/ZpiBw==" crossorigin="anonymous" referrerpolicy="no-referrer">
</head>
<body class="bg-gray-100">
    <div class="min-h-screen flex items-center justify-center py-12 px-4 sm:px-6 lg:px-8">
        <div class="max-w-md w-full space-y-8">
            <div>
                <h2 class="mt-6 text-center text-3xl font-extrabold text-gray-900">
                    Admin Login
                </h2>
            </div>
            <form class="mt-8 space-y-6 bg-white p-8 rounded-lg shadow-md" method="POST">
                <?php if (!empty($error)): ?>
                    <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded">
                        <?= escape($error) ?>
                    </div>
                <?php endif; ?>
                
                <div>
                    <label for="username" class="block text-sm font-medium text-gray-700">Username</label>
                    <input id="username" name="username" type="text" required
                           class="mt-1 appearance-none relative block w-full px-3 py-2 border border-gray-300 rounded-md placeholder-gray-500 text-gray-900 focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                </div>
                
                <div>
                    <label for="password" class="block text-sm font-medium text-gray-700">Password</label>
                    <input id="password" name="password" type="password" required
                           class="mt-1 appearance-none relative block w-full px-3 py-2 border border-gray-300 rounded-md placeholder-gray-500 text-gray-900 focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                </div>
                
                <div>
                    <button type="submit" class="group relative w-full flex justify-center py-2 px-4 border border-transparent text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                        Sign in
                    </button>
                </div>
                
                <div class="text-center">
                    <a href="<?= url('admin/forgot-password.php') ?>" class="text-sm text-blue-600 hover:text-blue-800">
                        <i class="fas fa-key mr-1"></i> Forgot Password?
                    </a>
                </div>
            </form>
        </div>
    </div>
</body>
</html>

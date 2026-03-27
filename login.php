<?php
require_once __DIR__ . '/bootstrap/app.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Redirect if already logged in
if (isset($_SESSION['customer_id'])) {
    header('Location: ' . url('account.php'));
    exit;
}

$error = '';

// Security: Rate limiting for login attempts
$attempts = session('customer_login_attempts') ?? 0;
$lastAttempt = session('customer_last_attempt') ?? 0;
$lockoutTime = 900; // 15 minutes

if ($attempts >= 5 && (time() - $lastAttempt) < $lockoutTime) {
    $remainingTime = ceil(($lockoutTime - (time() - $lastAttempt)) / 60);
    $error = "Too many failed login attempts. Please try again in {$remainingTime} minute(s).";
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Security: CSRF protection
    require_csrf();
    
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    
    if (empty($email) || empty($password)) {
        $error = 'Please enter email and password.';
    } else {
        try {
            $customer = db()->fetchOne(
                "SELECT * FROM customers WHERE email = :email AND is_active = 1",
                ['email' => $email]
            );
            
            if ($customer && password_verify($password, $customer['password'])) {
                // Security: Clear failed login attempts on success
                session('customer_login_attempts', 0);
                session('customer_last_attempt', 0);
                
                // Security: Regenerate session ID on successful login
                session_regenerate_id(true);
                
                $_SESSION['customer_id'] = $customer['id'];
                $_SESSION['customer_email'] = $customer['email'];
                $_SESSION['customer_name'] = trim(($customer['first_name'] ?? '') . ' ' . ($customer['last_name'] ?? ''));
                
                // Update last login - Fixed: use proper WHERE clause
                db()->update('customers', 
                    ['last_login' => date('Y-m-d H:i:s')], 
                    'id = :id', 
                    ['id' => $customer['id']]
                );
                
                $redirect = $_GET['redirect'] ?? 'account.php';
                header('Location: ' . url($redirect));
                exit;
            } else {
                // Security: Track failed login attempts
                $attempts = (session('customer_login_attempts') ?? 0) + 1;
                session('customer_login_attempts', $attempts);
                session('customer_last_attempt', time());
                
                // Security: Generic error message to prevent email enumeration
                $error = 'Invalid email or password.';
                
                // Log failed login attempt
                error_log(sprintf("Failed customer login attempt for email: %s from IP: %s", 
                    $email, 
                    get_real_ip()
                ));
            }
        } catch (Exception $e) {
            error_log("Login error: " . $e->getMessage());
            $error = 'Error logging in. Please try again.';
        }
    }
}

$pageTitle = 'Customer Login - ' . get_site_name();
$robotsNoIndex = true;
include __DIR__ . '/includes/header.php';
include __DIR__ . '/includes/message.php';
?>

<main class="py-12">
    <div class="container mx-auto px-4 max-w-md">
        <div class="bg-white rounded-lg shadow-lg p-8">
            <h1 class="text-3xl font-bold mb-6 text-center">Customer Login</h1>
            
            <?= displayMessage('', $error) ?>
            
            <form method="POST" class="space-y-4">
                <?= csrf_field() ?>
                <div>
                    <label class="block text-sm font-medium mb-2">Email</label>
                    <input type="email" name="email" required value="<?= escape($_POST['email'] ?? '') ?>"
                           class="w-full px-4 py-2 border rounded-lg">
                </div>
                
                <div>
                    <label class="block text-sm font-medium mb-2">Password</label>
                    <input type="password" name="password" required
                           class="w-full px-4 py-2 border rounded-lg">
                </div>
                
                <button type="submit" class="btn-primary w-full">Login</button>
            </form>
            
            <div class="mt-6 text-center space-y-2">
                <p class="text-gray-600">
                    Don't have an account? 
                    <a href="<?= url('register.php') ?>" class="text-blue-600 hover:underline">Register here</a>
                </p>
                <p class="text-gray-600">
                    <a href="<?= url('admin/login.php') ?>" class="text-blue-600 hover:underline">Admin Login</a>
                </p>
            </div>
        </div>
    </div>
</main>

<?php include __DIR__ . '/includes/footer.php'; ?>


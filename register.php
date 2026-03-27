<?php
require_once __DIR__ . '/bootstrap/app.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Security: CSRF protection
    require_csrf();
    
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';
    $firstName = trim($_POST['first_name'] ?? '');
    $lastName = trim($_POST['last_name'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $company = trim($_POST['company'] ?? '');
    
    if (empty($email) || empty($password)) {
        $error = 'Email and password are required.';
    } elseif ($password !== $confirmPassword) {
        $error = 'Passwords do not match.';
    } else {
        // Security: Validate password strength
        $passwordValidation = validate_password($password);
        if (!$passwordValidation['valid']) {
            $error = implode(' ', $passwordValidation['errors']);
        }
    }
    
    if (empty($error)) {
        try {
            // Check if email exists
            $existing = db()->fetchOne("SELECT id FROM customers WHERE email = :email", ['email' => $email]);
            
            if ($existing) {
                $error = 'Email already registered. Please login instead.';
            } else {
                // Create account
                db()->insert('customers', [
                    'email' => $email,
                    'password' => password_hash($password, PASSWORD_BCRYPT),
                    'first_name' => $firstName,
                    'last_name' => $lastName,
                    'phone' => $phone,
                    'company' => $company,
                    'is_active' => 1
                ]);
                
                $success = 'Account created successfully! Please login.';
                header('refresh:2;url=' . url('login.php'));
            }
        } catch (Exception $e) {
            $error = 'Error creating account. Please try again.';
        }
    }
}

$pageTitle = 'Create Account - ' . get_site_name();
$robotsNoIndex = true;
include __DIR__ . '/includes/header.php';
include __DIR__ . '/includes/message.php';
?>

<main class="py-12">
    <div class="container mx-auto px-4 max-w-md">
        <div class="bg-white rounded-lg shadow-lg p-8">
            <h1 class="text-3xl font-bold mb-6 text-center">Create Account</h1>
            
            <?= displayMessage($success, $error) ?>
            
            <form method="POST" class="space-y-4">
                <?= csrf_field() ?>
                <div class="grid md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium mb-2">First Name</label>
                        <input type="text" name="first_name" value="<?= escape($_POST['first_name'] ?? '') ?>"
                               class="w-full px-4 py-2 border rounded-lg">
                    </div>
                    <div>
                        <label class="block text-sm font-medium mb-2">Last Name</label>
                        <input type="text" name="last_name" value="<?= escape($_POST['last_name'] ?? '') ?>"
                               class="w-full px-4 py-2 border rounded-lg">
                    </div>
                </div>
                
                <div>
                    <label class="block text-sm font-medium mb-2">Email *</label>
                    <input type="email" name="email" required value="<?= escape($_POST['email'] ?? '') ?>"
                           class="w-full px-4 py-2 border rounded-lg">
                </div>
                
                <div>
                    <label class="block text-sm font-medium mb-2">Phone</label>
                    <input type="tel" name="phone" value="<?= escape($_POST['phone'] ?? '') ?>"
                           class="w-full px-4 py-2 border rounded-lg">
                </div>
                
                <div>
                    <label class="block text-sm font-medium mb-2">Company</label>
                    <input type="text" name="company" value="<?= escape($_POST['company'] ?? '') ?>"
                           class="w-full px-4 py-2 border rounded-lg">
                </div>
                
                <div>
                    <label class="block text-sm font-medium mb-2">Password *</label>
                    <input type="password" name="password" required
                           class="w-full px-4 py-2 border rounded-lg">
                </div>
                
                <div>
                    <label class="block text-sm font-medium mb-2">Confirm Password *</label>
                    <input type="password" name="confirm_password" required
                           class="w-full px-4 py-2 border rounded-lg">
                </div>
                
                <button type="submit" class="btn-primary w-full">Create Account</button>
            </form>
            
            <div class="mt-6 text-center">
                <p class="text-gray-600">
                    Already have an account? 
                    <a href="<?= url('login.php') ?>" class="text-blue-600 hover:underline">Login here</a>
                </p>
            </div>
        </div>
    </div>
</main>

<?php include __DIR__ . '/includes/footer.php'; ?>


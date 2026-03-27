<?php
require_once __DIR__ . '/bootstrap/app.php';

use App\Models\Product;

if (empty($_GET['product_id'])) {
    header('Location: ' . url('products.php'));
    exit;
}

$productModel = new Product();
$product = $productModel->getById($_GET['product_id']);

if (!$product) {
    header('Location: ' . url('products.php'));
    exit;
}

$message = '';
$error = '';

// Handle review submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $rating = (int)($_POST['rating'] ?? 0);
    $title = trim($_POST['title'] ?? '');
    $comment = trim($_POST['comment'] ?? '');
    
    if (empty($name) || empty($email) || empty($rating) || empty($comment)) {
        $error = 'Please fill in all required fields.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    } elseif ($rating < 1 || $rating > 5) {
        $error = 'Please select a valid rating.';
    } else {
        try {
            db()->insert('product_reviews', [
                'product_id' => $product['id'],
                'name' => $name,
                'email' => $email,
                'rating' => $rating,
                'title' => $title,
                'comment' => $comment,
                'is_approved' => 0 // Requires admin approval
            ]);
            
            $message = 'Thank you for your review! It will be published after approval.';
            $_POST = [];
        } catch (Exception $e) {
            $error = 'Error submitting review. Please try again.';
        }
    }
}

// Get approved reviews
$reviews = db()->fetchAll(
    "SELECT * FROM product_reviews 
     WHERE product_id = :product_id AND is_approved = 1 
     ORDER BY created_at DESC",
    ['product_id' => $product['id']]
);

// Calculate average rating
$avgRating = 0;
if (!empty($reviews)) {
    $totalRating = array_sum(array_column($reviews, 'rating'));
    $avgRating = round($totalRating / count($reviews), 1);
}

$pageTitle = 'Reviews for ' . escape($product['name']);
include __DIR__ . '/includes/header.php';
?>

<main class="py-8">
    <div class="container mx-auto px-4 max-w-4xl">
        <nav class="text-sm text-gray-600 mb-6">
            <a href="<?= url('product.php?slug=' . escape($product['slug'])) ?>" class="hover:text-blue-600">← Back to Product</a>
        </nav>
        
        <div class="bg-white rounded-lg shadow-md p-8 mb-8">
            <h1 class="text-3xl font-bold mb-4">Reviews for <?= escape($product['name']) ?></h1>
            
            <?php if ($avgRating > 0): ?>
                <div class="flex items-center gap-4 mb-6">
                    <div class="text-4xl font-bold"><?= $avgRating ?></div>
                    <div>
                        <div class="flex items-center mb-2">
                            <?php for ($i = 1; $i <= 5; $i++): ?>
                                <i class="fas fa-star <?= $i <= round($avgRating) ? 'text-yellow-400' : 'text-gray-300' ?>"></i>
                            <?php endfor; ?>
                        </div>
                        <p class="text-sm text-gray-600">Based on <?= count($reviews) ?> review(s)</p>
                    </div>
                </div>
            <?php endif; ?>
            
            <a href="<?= url('product.php?slug=' . escape($product['slug'])) ?>" class="btn-primary inline-block mb-8">
                View Product Details
            </a>
        </div>
        
        <!-- Review Form -->
        <div class="bg-white rounded-lg shadow-md p-8 mb-8">
            <h2 class="text-2xl font-bold mb-6">Write a Review</h2>
            
            <?php if ($message): ?>
                <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-6">
                    <?= escape($message) ?>
                </div>
            <?php endif; ?>
            
            <?php if ($error): ?>
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-6">
                    <?= escape($error) ?>
                </div>
            <?php endif; ?>
            
            <form method="POST" class="space-y-6">
                <div class="grid md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-sm font-medium mb-2">Your Name *</label>
                        <input type="text" name="name" required value="<?= escape($_POST['name'] ?? '') ?>"
                               class="w-full px-4 py-2 border rounded-lg">
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium mb-2">Your Email *</label>
                        <input type="email" name="email" required value="<?= escape($_POST['email'] ?? '') ?>"
                               class="w-full px-4 py-2 border rounded-lg">
                    </div>
                </div>
                
                <div>
                    <label class="block text-sm font-medium mb-2">Rating *</label>
                    <div class="flex gap-2" id="rating-stars">
                        <?php for ($i = 1; $i <= 5; $i++): ?>
                            <i class="fas fa-star text-3xl text-gray-300 cursor-pointer hover:text-yellow-400 rating-star" 
                               data-rating="<?= $i ?>"></i>
                        <?php endfor; ?>
                    </div>
                    <input type="hidden" name="rating" id="rating-input" required>
                </div>
                
                <div>
                    <label class="block text-sm font-medium mb-2">Review Title</label>
                    <input type="text" name="title" value="<?= escape($_POST['title'] ?? '') ?>"
                           class="w-full px-4 py-2 border rounded-lg">
                </div>
                
                <div>
                    <label class="block text-sm font-medium mb-2">Your Review *</label>
                    <textarea name="comment" rows="6" required
                              class="w-full px-4 py-2 border rounded-lg"><?= escape($_POST['comment'] ?? '') ?></textarea>
                </div>
                
                <button type="submit" class="btn-primary">
                    Submit Review
                </button>
            </form>
        </div>
        
        <!-- Reviews List -->
        <div class="space-y-6">
            <h2 class="text-2xl font-bold">Customer Reviews</h2>
            
            <?php if (empty($reviews)): ?>
                <div class="bg-gray-50 rounded-lg p-8 text-center">
                    <p class="text-gray-600">No reviews yet. Be the first to review this product!</p>
                </div>
            <?php else: ?>
                <?php foreach ($reviews as $review): ?>
                <div class="bg-white rounded-lg shadow-md p-6">
                    <div class="flex justify-between items-start mb-4">
                        <div>
                            <h3 class="font-bold text-lg"><?= escape($review['name']) ?></h3>
                            <?php if ($review['title']): ?>
                                <p class="text-gray-600"><?= escape($review['title']) ?></p>
                            <?php endif; ?>
                        </div>
                        <div class="text-right">
                            <div class="flex items-center gap-1 mb-1">
                                <?php for ($i = 1; $i <= 5; $i++): ?>
                                    <i class="fas fa-star text-sm <?= $i <= $review['rating'] ? 'text-yellow-400' : 'text-gray-300' ?>"></i>
                                <?php endfor; ?>
                            </div>
                            <p class="text-xs text-gray-500"><?= date('M d, Y', strtotime($review['created_at'])) ?></p>
                        </div>
                    </div>
                    <p class="text-gray-700"><?= nl2br(escape($review['comment'])) ?></p>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</main>

<script>
document.querySelectorAll('.rating-star').forEach(star => {
    star.addEventListener('click', function() {
        const rating = this.dataset.rating;
        document.getElementById('rating-input').value = rating;
        
        document.querySelectorAll('.rating-star').forEach((s, index) => {
            if (index < rating) {
                s.classList.remove('text-gray-300');
                s.classList.add('text-yellow-400');
            } else {
                s.classList.remove('text-yellow-400');
                s.classList.add('text-gray-300');
            }
        });
    });
    
    star.addEventListener('mouseenter', function() {
        const rating = this.dataset.rating;
        document.querySelectorAll('.rating-star').forEach((s, index) => {
            if (index < rating) {
                s.classList.add('text-yellow-300');
            }
        });
    });
});

document.getElementById('rating-stars').addEventListener('mouseleave', function() {
    const currentRating = document.getElementById('rating-input').value || 0;
    document.querySelectorAll('.rating-star').forEach((s, index) => {
        s.classList.remove('text-yellow-300');
        if (index < currentRating) {
            s.classList.add('text-yellow-400');
        }
    });
});
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>


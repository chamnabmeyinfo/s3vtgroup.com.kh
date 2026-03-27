<?php
/**
 * Advanced Analytics Dashboard
 */
require_once __DIR__ . '/../bootstrap/app.php';
require_once __DIR__ . '/includes/auth.php';

use App\Services\AnalyticsService;

$pageTitle = 'Advanced Analytics';
include __DIR__ . '/includes/header.php';

$analytics = new AnalyticsService();
$period = $_GET['period'] ?? '30days';

// Get analytics data
$salesData = $analytics->getSalesAnalytics($period);
$productPerformance = $analytics->getProductPerformance(10);
$customerInsights = $analytics->getCustomerInsights();
$trafficAnalytics = $analytics->getTrafficAnalytics($period);
$conversionFunnel = $analytics->getConversionFunnel($period);
$topCategories = $analytics->getTopCategories(5);
?>

<div class="p-6">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-2xl font-bold">Advanced Analytics</h1>
        <div class="flex gap-2">
            <select id="period-select" onchange="changePeriod()" class="px-4 py-2 border rounded-lg">
                <option value="7days" <?= $period === '7days' ? 'selected' : '' ?>>Last 7 Days</option>
                <option value="30days" <?= $period === '30days' ? 'selected' : '' ?>>Last 30 Days</option>
                <option value="12months" <?= $period === '12months' ? 'selected' : '' ?>>Last 12 Months</option>
            </select>
            <button onclick="exportAnalytics()" class="btn-secondary">
                <i class="fas fa-download mr-2"></i> Export
            </button>
        </div>
    </div>
    
    <!-- Key Metrics -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
        <div class="bg-white rounded-lg shadow-md p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-gray-600 text-sm">Total Revenue</p>
                    <p class="text-2xl font-bold text-blue-600">$<?= number_format(array_sum(array_column($salesData, 'revenue')), 2) ?></p>
                </div>
                <i class="fas fa-dollar-sign text-3xl text-blue-200"></i>
            </div>
        </div>
        
        <div class="bg-white rounded-lg shadow-md p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-gray-600 text-sm">Total Orders</p>
                    <p class="text-2xl font-bold text-green-600"><?= array_sum(array_column($salesData, 'orders')) ?></p>
                </div>
                <i class="fas fa-shopping-cart text-3xl text-green-200"></i>
            </div>
        </div>
        
        <div class="bg-white rounded-lg shadow-md p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-gray-600 text-sm">Page Views</p>
                    <p class="text-2xl font-bold text-purple-600"><?= number_format($trafficAnalytics['page_views']) ?></p>
                </div>
                <i class="fas fa-eye text-3xl text-purple-200"></i>
            </div>
        </div>
        
        <div class="bg-white rounded-lg shadow-md p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-gray-600 text-sm">Unique Visitors</p>
                    <p class="text-2xl font-bold text-orange-600"><?= number_format($trafficAnalytics['unique_visitors']) ?></p>
                </div>
                <i class="fas fa-users text-3xl text-orange-200"></i>
            </div>
        </div>
    </div>
    
    <div class="grid md:grid-cols-2 gap-6 mb-8">
        <!-- Sales Chart -->
        <div class="bg-white rounded-lg shadow-md p-6">
            <h2 class="text-xl font-bold mb-4">Sales Trend</h2>
            <canvas id="salesChart" height="300"></canvas>
        </div>
        
        <!-- Conversion Funnel -->
        <div class="bg-white rounded-lg shadow-md p-6">
            <h2 class="text-xl font-bold mb-4">Conversion Funnel</h2>
            <div class="space-y-4">
                <div>
                    <div class="flex justify-between mb-2">
                        <span>Views</span>
                        <span class="font-bold"><?= number_format($conversionFunnel['views']) ?></span>
                    </div>
                    <div class="w-full bg-gray-200 rounded-full h-6">
                        <div class="bg-blue-600 h-6 rounded-full" style="width: 100%"></div>
                    </div>
                </div>
                <div>
                    <div class="flex justify-between mb-2">
                        <span>Cart Adds</span>
                        <span class="font-bold"><?= number_format($conversionFunnel['cart_adds']) ?></span>
                    </div>
                    <div class="w-full bg-gray-200 rounded-full h-6">
                        <div class="bg-green-600 h-6 rounded-full" style="width: <?= $conversionFunnel['views'] > 0 ? ($conversionFunnel['cart_adds'] / $conversionFunnel['views'] * 100) : 0 ?>%"></div>
                    </div>
                </div>
                <div>
                    <div class="flex justify-between mb-2">
                        <span>Checkouts</span>
                        <span class="font-bold"><?= number_format($conversionFunnel['checkouts']) ?></span>
                    </div>
                    <div class="w-full bg-gray-200 rounded-full h-6">
                        <div class="bg-purple-600 h-6 rounded-full" style="width: <?= $conversionFunnel['views'] > 0 ? ($conversionFunnel['checkouts'] / $conversionFunnel['views'] * 100) : 0 ?>%"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Top Products -->
    <div class="bg-white rounded-lg shadow-md p-6 mb-8">
        <h2 class="text-xl font-bold mb-4">Top Performing Products</h2>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Product</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Views</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Interactions</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Wishlist</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Reviews</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <?php foreach ($productPerformance as $product): ?>
                    <tr>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm font-medium"><?= escape($product['name']) ?></div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm"><?= number_format($product['view_count']) ?></td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm"><?= number_format($product['interactions']) ?></td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm"><?= number_format($product['wishlist_count']) ?></td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm"><?= number_format($product['review_count']) ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    
    <!-- Top Categories -->
    <div class="bg-white rounded-lg shadow-md p-6">
        <h2 class="text-xl font-bold mb-4">Top Categories</h2>
        <div class="grid md:grid-cols-5 gap-4">
            <?php foreach ($topCategories as $category): ?>
            <div class="text-center p-4 bg-gray-50 rounded-lg">
                <p class="font-bold mb-2"><?= escape($category['name']) ?></p>
                <p class="text-2xl font-bold text-blue-600"><?= number_format($category['views']) ?></p>
                <p class="text-sm text-gray-600"><?= $category['product_count'] ?> products</p>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
// Sales Chart
const salesData = <?= json_encode($salesData) ?>;
const ctx = document.getElementById('salesChart').getContext('2d');
new Chart(ctx, {
    type: 'line',
    data: {
        labels: salesData.map(item => item.date),
        datasets: [{
            label: 'Revenue',
            data: salesData.map(item => item.revenue),
            borderColor: 'rgb(59, 130, 246)',
            backgroundColor: 'rgba(59, 130, 246, 0.1)',
            tension: 0.4
        }, {
            label: 'Orders',
            data: salesData.map(item => item.orders),
            borderColor: 'rgb(34, 197, 94)',
            backgroundColor: 'rgba(34, 197, 94, 0.1)',
            tension: 0.4
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: true,
        scales: {
            y: {
                beginAtZero: true
            }
        }
    }
});

function changePeriod() {
    const period = document.getElementById('period-select').value;
    window.location.href = '?period=' + period;
}

function exportAnalytics() {
    window.location.href = 'analytics-export.php?period=<?= $period ?>';
}
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>


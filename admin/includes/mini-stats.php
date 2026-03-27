<?php
/**
 * Mini Dashboard Statistics Component
 * Reusable stat cards for quick data overview
 */
$stats = $stats ?? [];
$pageContext = $pageContext ?? 'general';
?>

<?php if (!empty($stats)): ?>
<div class="mini-stats-grid grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
    <?php foreach ($stats as $stat): ?>
    <div class="bg-gradient-to-br <?= $stat['color'] ?? 'from-blue-500 to-blue-600' ?> rounded-xl shadow-lg p-4 md:p-6 text-white transform hover:scale-105 transition-all cursor-pointer <?= !empty($stat['link']) ? '' : 'cursor-default' ?>" 
         <?= !empty($stat['link']) ? 'onclick="window.location.href=\'' . escape($stat['link']) . '\'"' : '' ?>>
        <div class="flex items-center justify-between mb-3">
            <div class="bg-white/20 rounded-lg p-2 md:p-3">
                <i class="<?= escape($stat['icon'] ?? 'fas fa-chart-line') ?> text-xl md:text-2xl"></i>
            </div>
            <div class="text-right">
                <div class="text-2xl md:text-3xl font-bold"><?= escape($stat['value'] ?? '0') ?></div>
                <?php if (!empty($stat['subtitle'])): ?>
                    <div class="text-xs md:text-sm opacity-90 mt-1"><?= escape($stat['subtitle']) ?></div>
                <?php endif; ?>
            </div>
        </div>
        <div class="text-sm md:text-base font-medium mb-1"><?= escape($stat['label'] ?? 'Statistic') ?></div>
        <?php if (!empty($stat['description'])): ?>
            <div class="text-xs opacity-80"><?= escape($stat['description']) ?></div>
        <?php endif; ?>
        <?php if (!empty($stat['link'])): ?>
            <div class="mt-3 text-xs opacity-75 flex items-center gap-1">
                <span>View Details</span>
                <i class="fas fa-arrow-right"></i>
            </div>
        <?php endif; ?>
    </div>
    <?php endforeach; ?>
</div>
<?php endif; ?>


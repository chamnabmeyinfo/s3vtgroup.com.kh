<?php
/**
 * Advanced Filters Component
 * Reusable advanced filtering and column visibility system
 * Minimal & Smart Layout Design
 */
$filterId = $filterId ?? 'main-filter';
$defaultColumns = $defaultColumns ?? [];
$availableColumns = $availableColumns ?? [];
$filters = $filters ?? [];
$sortOptions = $sortOptions ?? [];

// Count active filters
$activeFiltersCount = 0;
if (!empty($_GET['search'])) $activeFiltersCount++;
if (!empty($_GET['status']) && ($_GET['status'] ?? '') !== 'all') $activeFiltersCount++;
if (!empty($_GET['featured']) && ($_GET['featured'] ?? '') !== 'all') $activeFiltersCount++;
if (!empty($_GET['category'])) $activeFiltersCount++;
if (!empty($_GET['type']) && ($_GET['type'] ?? '') !== 'all') $activeFiltersCount++;
if (!empty($_GET['size']) && ($_GET['size'] ?? '') !== 'all') $activeFiltersCount++;
if (!empty($_GET['optimization']) && ($_GET['optimization'] ?? '') !== 'all') $activeFiltersCount++;
if (!empty($_GET['date_from']) || !empty($_GET['date_to'])) $activeFiltersCount++;
if (!empty($_GET['price_min']) || !empty($_GET['price_max'])) $activeFiltersCount++;
?>

<div id="<?= $filterId ?>" class="advanced-filters mb-4">
    <!-- Compact Header Bar -->
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden">
        <div class="flex items-center justify-between p-3 md:p-4">
            <button onclick="toggleFilterPanel('<?= $filterId ?>')" 
                    class="flex items-center gap-2 text-gray-700 hover:text-blue-600 transition-colors group">
                <i class="fas fa-filter text-sm md:text-base group-hover:scale-110 transition-transform"></i>
                <span class="font-medium text-sm md:text-base">Filters</span>
                <?php if ($activeFiltersCount > 0): ?>
                    <span class="bg-blue-600 text-white text-xs font-bold rounded-full px-2 py-0.5 min-w-[20px] text-center">
                        <?= $activeFiltersCount ?>
                    </span>
                <?php endif; ?>
                <i class="fas fa-chevron-<?= $hasActiveFilters ? 'up' : 'down' ?> text-xs transition-transform duration-200" id="toggle-icon-<?= $filterId ?>" style="<?= $hasActiveFilters ? 'transform: rotate(180deg);' : '' ?>"></i>
            </button>
            
            <div class="flex items-center gap-2">
                <?php if ($activeFiltersCount > 0): ?>
                    <button onclick="resetFilters('<?= $filterId ?>')" 
                            class="text-xs md:text-sm text-gray-600 hover:text-red-600 transition-colors px-2 py-1 rounded hover:bg-red-50 flex items-center gap-1">
                        <i class="fas fa-times text-xs"></i>
                        <span class="hidden sm:inline">Clear</span>
                    </button>
                <?php endif; ?>
                <?php if (!empty($sortOptions)): ?>
                    <select name="sort" 
                            onchange="applyFilters('<?= $filterId ?>')"
                            form="filter-form-<?= $filterId ?>"
                            class="text-xs md:text-sm px-3 py-1.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none bg-white">
                        <?php foreach ($sortOptions as $value => $label): ?>
                            <option value="<?= escape($value) ?>" <?= ($_GET['sort'] ?? '') === $value ? 'selected' : '' ?>>
                                <?= escape($label) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Filter Content (Collapsible) -->
        <?php 
        // Check if there are active filters - if so, show the panel by default
        $hasActiveFilters = $activeFiltersCount > 0;
        $panelClass = $hasActiveFilters ? '' : 'hidden';
        ?>
        <div id="filter-content-<?= $filterId ?>" class="filter-content <?= $panelClass ?> border-t border-gray-200 bg-gray-50">
            <form method="GET" id="filter-form-<?= $filterId ?>" class="p-4 md:p-6">
                
                <!-- Quick Filters Row -->
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-3 md:gap-4 mb-4">
                    <!-- Search -->
                    <?php if (isset($filters['search'])): ?>
                    <div class="relative">
                        <i class="fas fa-search absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400 text-sm"></i>
                        <input type="text" 
                               name="search" 
                               value="<?= escape($_GET['search'] ?? '') ?>"
                               placeholder="Search..."
                               class="w-full pl-9 pr-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition-all"
                               onkeyup="debounceFilter('<?= $filterId ?>')">
                    </div>
                    <?php endif; ?>
                    
                    <!-- Status -->
                    <?php if (isset($filters['status']) && is_array($filters['status']) && isset($filters['status']['options'])): ?>
                    <div class="relative">
                        <i class="fas fa-toggle-on absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400 text-sm pointer-events-none"></i>
                        <select name="status" 
                                onchange="applyFilters('<?= $filterId ?>')"
                                class="w-full pl-9 pr-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none bg-white appearance-none cursor-pointer">
                            <?php foreach ($filters['status']['options'] as $value => $label): ?>
                                <option value="<?= escape($value) ?>" <?= ($_GET['status'] ?? '') === $value || ($_GET['status'] === '' && $value === 'all') ? 'selected' : '' ?>>
                                    <?= escape($label) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <i class="fas fa-chevron-down absolute right-3 top-1/2 transform -translate-y-1/2 text-gray-400 text-xs pointer-events-none"></i>
                    </div>
                    <?php endif; ?>
                    
                    <!-- Featured -->
                    <?php if (isset($filters['featured']) && is_array($filters['featured']) && isset($filters['featured']['options'])): ?>
                    <div class="relative">
                        <i class="fas fa-star absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400 text-sm pointer-events-none"></i>
                        <select name="featured" 
                                onchange="applyFilters('<?= $filterId ?>')"
                                class="w-full pl-9 pr-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none bg-white appearance-none cursor-pointer">
                            <?php foreach ($filters['featured']['options'] as $value => $label): ?>
                                <option value="<?= escape($value) ?>" <?= ($_GET['featured'] ?? '') === $value || ($_GET['featured'] === '' && $value === 'all') ? 'selected' : '' ?>>
                                    <?= escape($label) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <i class="fas fa-chevron-down absolute right-3 top-1/2 transform -translate-y-1/2 text-gray-400 text-xs pointer-events-none"></i>
                    </div>
                    <?php endif; ?>
                    
                    <!-- Category -->
                    <?php if (isset($filters['category']) && is_array($filters['category']) && isset($filters['category']['options'])): ?>
                    <div class="relative">
                        <i class="fas fa-tags absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400 text-sm pointer-events-none"></i>
                        <select name="category" 
                                onchange="applyFilters('<?= $filterId ?>')"
                                class="w-full pl-9 pr-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none bg-white appearance-none cursor-pointer">
                            <option value="">All Categories</option>
                            <?php foreach ($filters['category']['options'] as $value => $label): ?>
                                <option value="<?= escape($value) ?>" <?= ($_GET['category'] ?? '') === $value ? 'selected' : '' ?>>
                                    <?= escape($label) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <i class="fas fa-chevron-down absolute right-3 top-1/2 transform -translate-y-1/2 text-gray-400 text-xs pointer-events-none"></i>
                    </div>
                    <?php endif; ?>
                    
                    <!-- Type -->
                    <?php if (isset($filters['type']) && is_array($filters['type']) && isset($filters['type']['options'])): ?>
                    <div class="relative">
                        <i class="fas fa-file-alt absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400 text-sm pointer-events-none"></i>
                        <select name="type" 
                                onchange="applyFilters('<?= $filterId ?>')"
                                class="w-full pl-9 pr-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none bg-white appearance-none cursor-pointer">
                            <?php foreach ($filters['type']['options'] as $value => $label): ?>
                                <option value="<?= escape($value) ?>" <?= ($_GET['type'] ?? '') === $value || ($_GET['type'] === '' && $value === 'all') ? 'selected' : '' ?>>
                                    <?= escape($label) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <i class="fas fa-chevron-down absolute right-3 top-1/2 transform -translate-y-1/2 text-gray-400 text-xs pointer-events-none"></i>
                    </div>
                    <?php endif; ?>
                    
                    <!-- Size -->
                    <?php if (isset($filters['size']) && is_array($filters['size']) && isset($filters['size']['options'])): ?>
                    <div class="relative">
                        <i class="fas fa-weight absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400 text-sm pointer-events-none"></i>
                        <select name="size" 
                                onchange="applyFilters('<?= $filterId ?>')"
                                class="w-full pl-9 pr-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none bg-white appearance-none cursor-pointer">
                            <?php foreach ($filters['size']['options'] as $value => $label): ?>
                                <option value="<?= escape($value) ?>" <?= ($_GET['size'] ?? '') === $value || ($_GET['size'] === '' && $value === 'all') ? 'selected' : '' ?>>
                                    <?= escape($label) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <i class="fas fa-chevron-down absolute right-3 top-1/2 transform -translate-y-1/2 text-gray-400 text-xs pointer-events-none"></i>
                    </div>
                    <?php endif; ?>
                </div>
                
                <!-- Date & Price Range Row -->
                <?php if (isset($filters['date_range']) || isset($filters['price_range'])): ?>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 md:gap-4 mb-4">
                    <?php if (isset($filters['date_range'])): ?>
                    <div class="bg-white rounded-lg p-3 border border-gray-200">
                        <label class="block text-xs font-semibold text-gray-600 mb-2 flex items-center gap-1">
                            <i class="fas fa-calendar-alt text-xs"></i>
                            Date Range
                        </label>
                        <div class="grid grid-cols-2 gap-2">
                            <input type="date" 
                                   name="date_from" 
                                   value="<?= escape($_GET['date_from'] ?? '') ?>"
                                   class="w-full px-3 py-1.5 text-xs border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none">
                            <input type="date" 
                                   name="date_to" 
                                   value="<?= escape($_GET['date_to'] ?? '') ?>"
                                   class="w-full px-3 py-1.5 text-xs border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none">
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <?php if (isset($filters['price_range'])): ?>
                    <div class="bg-white rounded-lg p-3 border border-gray-200">
                        <label class="block text-xs font-semibold text-gray-600 mb-2 flex items-center gap-1">
                            <i class="fas fa-dollar-sign text-xs"></i>
                            Price Range
                        </label>
                        <div class="grid grid-cols-2 gap-2">
                            <input type="number" 
                                   name="price_min" 
                                   value="<?= escape($_GET['price_min'] ?? '') ?>"
                                   step="0.01" 
                                   placeholder="Min"
                                   form="filter-form-<?= $filterId ?>"
                                   class="w-full px-3 py-1.5 text-xs border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none">
                            <input type="number" 
                                   name="price_max" 
                                   value="<?= escape($_GET['price_max'] ?? '') ?>"
                                   step="0.01" 
                                   placeholder="Max"
                                   form="filter-form-<?= $filterId ?>"
                                   onchange="applyFilters('<?= $filterId ?>')"
                                   class="w-full px-3 py-1.5 text-xs border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none">
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
                
                <!-- Column Visibility (Collapsible Section) -->
                <?php if (!empty($availableColumns)): ?>
                <div class="border-t border-gray-200 pt-3 mt-3">
                    <button type="button" 
                            onclick="toggleColumnSection('<?= $filterId ?>')"
                            class="flex items-center justify-between w-full text-left text-xs font-semibold text-gray-700 hover:text-blue-600 transition-colors mb-2">
                        <span class="flex items-center gap-1.5">
                            <i class="fas fa-columns text-xs"></i>
                            <span>Column Visibility</span>
                            <span class="text-xs font-normal text-gray-500">(<?= count($availableColumns) ?> columns)</span>
                        </span>
                        <i class="fas fa-chevron-down text-xs transition-transform duration-200" id="column-toggle-icon-<?= $filterId ?>"></i>
                    </button>
                    <div id="column-section-<?= $filterId ?>" class="hidden">
                        <div class="bg-white rounded-lg border border-gray-200 p-3 max-h-64 overflow-y-auto column-section-scroll">
                            <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 xl:grid-cols-6 gap-1.5">
                                <?php foreach ($availableColumns as $column => $label): ?>
                                    <label class="flex items-center gap-1.5 px-2 py-1.5 rounded hover:bg-blue-50 cursor-pointer transition-colors group text-xs">
                                        <input type="checkbox" 
                                               name="columns[]" 
                                               value="<?= escape($column) ?>"
                                               class="column-toggle w-3.5 h-3.5 text-blue-600 border-gray-300 rounded focus:ring-1 focus:ring-blue-500 cursor-pointer flex-shrink-0"
                                               data-column="<?= escape($column) ?>"
                                               <?= in_array($column, $defaultColumns) || empty($_GET['columns']) ? 'checked' : '' ?>
                                               onchange="toggleColumn('<?= escape($column) ?>', this.checked)">
                                        <span class="text-xs text-gray-700 group-hover:text-blue-600 transition-colors truncate" title="<?= escape($label) ?>"><?= escape($label) ?></span>
                                    </label>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        <div class="flex gap-2 mt-2">
                            <button type="button" 
                                    onclick="selectAllColumns('<?= $filterId ?>')" 
                                    class="text-xs text-blue-600 hover:text-blue-700 hover:underline px-2 py-1 rounded hover:bg-blue-50 transition-colors">
                                <i class="fas fa-check-square text-xs mr-1"></i>Select All
                            </button>
                            <button type="button" 
                                    onclick="deselectAllColumns('<?= $filterId ?>')" 
                                    class="text-xs text-gray-600 hover:text-gray-700 hover:underline px-2 py-1 rounded hover:bg-gray-100 transition-colors">
                                <i class="fas fa-square text-xs mr-1"></i>Deselect All
                            </button>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
                
                <!-- Preserve other GET parameters -->
                <?php foreach ($_GET as $key => $value): ?>
                    <?php if (!in_array($key, ['search', 'status', 'category', 'featured', 'type', 'size', 'sort', 'date_from', 'date_to', 'price_min', 'price_max', 'columns', 'page'])): ?>
                        <?php if (is_array($value)): ?>
                            <?php foreach ($value as $v): ?>
                                <input type="hidden" name="<?= escape($key) ?>[]" value="<?= escape($v) ?>">
                            <?php endforeach; ?>
                        <?php else: ?>
                            <input type="hidden" name="<?= escape($key) ?>" value="<?= escape($value) ?>">
                        <?php endif; ?>
                    <?php endif; ?>
                <?php endforeach; ?>
                
                <!-- Action Buttons -->
                <div class="flex gap-2 pt-4 border-t border-gray-200 mt-4">
                    <button type="button" 
                            onclick="applyFilters('<?= $filterId ?>')" 
                            class="flex-1 bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg text-sm font-medium transition-colors flex items-center justify-center gap-2">
                        <i class="fas fa-filter text-xs"></i>
                        Apply Filters
                    </button>
                    <button type="button" 
                            onclick="saveFilterPreset('<?= $filterId ?>')" 
                            class="px-4 py-2 border border-gray-300 rounded-lg text-sm text-gray-700 hover:bg-gray-50 transition-colors flex items-center gap-2">
                        <i class="fas fa-save text-xs"></i>
                        <span class="hidden sm:inline">Save Preset</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function toggleFilterPanel(filterId) {
    const content = document.getElementById('filter-content-' + filterId);
    const icon = document.getElementById('toggle-icon-' + filterId);
    
    const isHidden = content.classList.contains('hidden');
    content.classList.toggle('hidden');
    
    if (content.classList.contains('hidden')) {
        icon.classList.remove('fa-chevron-up');
        icon.classList.add('fa-chevron-down');
        icon.style.transform = 'rotate(0deg)';
        // Save state to localStorage
        localStorage.setItem('filter_panel_open_' + filterId, 'false');
    } else {
        icon.classList.remove('fa-chevron-down');
        icon.classList.add('fa-chevron-up');
        icon.style.transform = 'rotate(180deg)';
        // Save state to localStorage
        localStorage.setItem('filter_panel_open_' + filterId, 'true');
    }
}

function toggleColumnSection(filterId) {
    const section = document.getElementById('column-section-' + filterId);
    const icon = document.getElementById('column-toggle-icon-' + filterId);
    
    section.classList.toggle('hidden');
    if (section.classList.contains('hidden')) {
        icon.style.transform = 'rotate(0deg)';
    } else {
        icon.style.transform = 'rotate(180deg)';
    }
}

function applyFilters(filterId) {
    // Keep panel open by saving state before submit
    const content = document.getElementById('filter-content-' + filterId);
    if (!content.classList.contains('hidden')) {
        localStorage.setItem('filter_panel_open_' + filterId, 'true');
    }
    document.getElementById('filter-form-' + filterId).submit();
}

function resetFilters(filterId) {
    const url = new URL(window.location.href);
    url.search = '';
    window.location.href = url.toString();
}

function toggleColumn(column, visible) {
    const cells = document.querySelectorAll(`[data-column="${column}"]`);
    const headers = document.querySelectorAll(`th[data-column="${column}"]`);
    
    cells.forEach(cell => {
        cell.style.display = visible ? '' : 'none';
    });
    headers.forEach(header => {
        header.style.display = visible ? '' : 'none';
    });
    
    // Save to localStorage
    const visibleColumns = Array.from(document.querySelectorAll('.column-toggle:checked'))
        .map(cb => cb.value);
    localStorage.setItem('visible_columns_' + filterId, JSON.stringify(visibleColumns));
}

function selectAllColumns(filterId) {
    document.querySelectorAll('#filter-form-' + filterId + ' .column-toggle').forEach(cb => {
        cb.checked = true;
        toggleColumn(cb.dataset.column, true);
    });
}

function deselectAllColumns(filterId) {
    document.querySelectorAll('#filter-form-' + filterId + ' .column-toggle').forEach(cb => {
        cb.checked = false;
        toggleColumn(cb.dataset.column, false);
    });
}

// Restore column visibility and filter panel state from localStorage
document.addEventListener('DOMContentLoaded', function() {
    const filterId = '<?= $filterId ?>';
    
    // Restore filter panel state
    const panelState = localStorage.getItem('filter_panel_open_' + filterId);
    const hasActiveFilters = <?= $activeFiltersCount > 0 ? 'true' : 'false' ?>;
    
    // Open panel if there are active filters OR if it was previously open
    if (hasActiveFilters || panelState === 'true') {
        const content = document.getElementById('filter-content-' + filterId);
        const icon = document.getElementById('toggle-icon-' + filterId);
        
        if (content && icon) {
            content.classList.remove('hidden');
            icon.classList.remove('fa-chevron-down');
            icon.classList.add('fa-chevron-up');
            icon.style.transform = 'rotate(180deg)';
        }
    }
    
    // Restore column visibility
    const saved = localStorage.getItem('visible_columns_' + filterId);
    if (saved) {
        const visibleColumns = JSON.parse(saved);
        document.querySelectorAll('.column-toggle').forEach(cb => {
            const shouldBeVisible = visibleColumns.includes(cb.value);
            cb.checked = shouldBeVisible;
            toggleColumn(cb.dataset.column, shouldBeVisible);
        });
    }
});

let filterTimeout;
function debounceFilter(filterId) {
    clearTimeout(filterTimeout);
    filterTimeout = setTimeout(() => {
        applyFilters(filterId);
    }, 500);
}

function saveFilterPreset(filterId) {
    const form = document.getElementById('filter-form-' + filterId);
    const formData = new FormData(form);
    const preset = {};
    
    for (const [key, value] of formData.entries()) {
        if (key === 'columns[]') {
            if (!preset.columns) preset.columns = [];
            preset.columns.push(value);
        } else {
            preset[key] = value;
        }
    }
    
    const presetName = prompt('Enter preset name:');
    if (presetName) {
        const presets = JSON.parse(localStorage.getItem('filter_presets_' + filterId) || '[]');
        presets.push({ name: presetName, filters: preset });
        localStorage.setItem('filter_presets_' + filterId, JSON.stringify(presets));
        alert('Filter preset saved!');
    }
}
</script>

<style>
.advanced-filters .filter-content {
    transition: all 0.3s ease;
}

.advanced-filters select {
    background-image: none;
}

.column-toggle {
    cursor: pointer;
    accent-color: #2563eb;
}

/* Smooth transitions */
.advanced-filters * {
    transition: all 0.2s ease;
}

/* Custom scrollbar for filter content */
.filter-content::-webkit-scrollbar {
    width: 6px;
}

.filter-content::-webkit-scrollbar-track {
    background: #f1f1f1;
    border-radius: 3px;
}

.filter-content::-webkit-scrollbar-thumb {
    background: #cbd5e1;
    border-radius: 3px;
}

.filter-content::-webkit-scrollbar-thumb:hover {
    background: #94a3b8;
}

/* Column visibility scrollbar */
.column-section-scroll::-webkit-scrollbar {
    width: 5px;
    height: 5px;
}

.column-section-scroll::-webkit-scrollbar-track {
    background: #f9fafb;
    border-radius: 3px;
}

.column-section-scroll::-webkit-scrollbar-thumb {
    background: #cbd5e1;
    border-radius: 3px;
}

.column-section-scroll::-webkit-scrollbar-thumb:hover {
    background: #94a3b8;
}
</style>

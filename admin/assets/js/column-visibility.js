// Column Visibility Management
(function() {
    'use strict';
    
    // Initialize column visibility on page load
    document.addEventListener('DOMContentLoaded', function() {
        initializeColumnVisibility();
    });
    
    function initializeColumnVisibility() {
        // Get visible columns from localStorage or URL params
        const urlParams = new URLSearchParams(window.location.search);
        const columnsParam = urlParams.get('columns');
        
        let visibleColumns = [];
        
        if (columnsParam) {
            visibleColumns = columnsParam.split(',');
            // Save to localStorage
            localStorage.setItem('visible_columns_' + window.location.pathname, JSON.stringify(visibleColumns));
        } else {
            // Load from localStorage
            const saved = localStorage.getItem('visible_columns_' + window.location.pathname);
            if (saved) {
                visibleColumns = JSON.parse(saved);
            }
        }
        
        // Apply column visibility
        if (visibleColumns.length > 0) {
            visibleColumns.forEach(column => {
                toggleColumnVisibility(column, true);
            });
            
            // Hide unchecked columns
            document.querySelectorAll('[data-column]').forEach(el => {
                const column = el.getAttribute('data-column');
                if (!visibleColumns.includes(column)) {
                    toggleColumnVisibility(column, false);
                }
            });
        }
    }
    
    // Toggle column visibility
    window.toggleColumnVisibility = function(column, visible) {
        const elements = document.querySelectorAll(`[data-column="${column}"]`);
        elements.forEach(el => {
            el.style.display = visible ? '' : 'none';
        });
    };
    
    // Save column preferences
    window.saveColumnPreferences = function(pagePath) {
        const visibleColumns = Array.from(document.querySelectorAll('.column-toggle:checked'))
            .map(cb => cb.value);
        localStorage.setItem('visible_columns_' + pagePath, JSON.stringify(visibleColumns));
    };
    
    // Load column preferences
    window.loadColumnPreferences = function(pagePath) {
        const saved = localStorage.getItem('visible_columns_' + pagePath);
        if (saved) {
            return JSON.parse(saved);
        }
        return null;
    };
    
})();


// Smart Search with Intelligent Autocomplete
document.addEventListener('DOMContentLoaded', function() {
    const searchInput = document.getElementById('advanced-search');
    if (!searchInput) return;
    
    let autocompleteTimeout;
    let currentSuggestions = [];
    let selectedIndex = -1;
    
    // Create suggestions dropdown
    const suggestionsDiv = document.createElement('div');
    suggestionsDiv.id = 'smart-search-suggestions';
    suggestionsDiv.className = 'hidden absolute top-full left-0 right-0 mt-1 bg-white border rounded-lg shadow-xl z-50 max-h-96 overflow-y-auto';
    searchInput.parentElement.appendChild(suggestionsDiv);
    
    // Search input handler
    searchInput.addEventListener('input', function() {
        const query = this.value.trim();
        
        clearTimeout(autocompleteTimeout);
        
        if (query.length < 2) {
            suggestionsDiv.classList.add('hidden');
            return;
        }
        
        autocompleteTimeout = setTimeout(() => {
            fetchSmartAutocomplete(query);
        }, 300);
    });
    
    // Keyboard navigation
    searchInput.addEventListener('keydown', function(e) {
        if (!suggestionsDiv.classList.contains('hidden') && currentSuggestions.length > 0) {
            if (e.key === 'ArrowDown') {
                e.preventDefault();
                selectedIndex = Math.min(selectedIndex + 1, currentSuggestions.length - 1);
                updateSelection();
            } else if (e.key === 'ArrowUp') {
                e.preventDefault();
                selectedIndex = Math.max(selectedIndex - 1, -1);
                updateSelection();
            } else if (e.key === 'Enter' && selectedIndex >= 0) {
                e.preventDefault();
                const selected = currentSuggestions[selectedIndex];
                if (selected) {
                    window.location.href = selected.url;
                }
            } else if (e.key === 'Escape') {
                suggestionsDiv.classList.add('hidden');
                selectedIndex = -1;
            }
        }
    });
    
    // Close on outside click
    document.addEventListener('click', function(e) {
        if (!searchInput.parentElement.contains(e.target)) {
            suggestionsDiv.classList.add('hidden');
        }
    });
    
    function fetchSmartAutocomplete(query) {
        const smartSearchUrl = window.APP_CONFIG?.urls?.smartSearch || 'api/smart-search.php';
        fetch(`${smartSearchUrl}?action=autocomplete&q=${encodeURIComponent(query)}`)
            .then(response => response.json())
            .then(data => {
                if (data.success && data.suggestions.length > 0) {
                    currentSuggestions = data.suggestions;
                    selectedIndex = -1;
                    displaySuggestions(data.suggestions);
                } else {
                    suggestionsDiv.classList.add('hidden');
                }
            })
            .catch(error => {
                console.error('Autocomplete error:', error);
            });
    }
    
    function displaySuggestions(suggestions) {
        suggestionsDiv.innerHTML = suggestions.map((suggestion, index) => `
            <a href="${suggestion.url}" 
               class="smart-suggestion-item flex items-center px-4 py-3 hover:bg-blue-50 transition-colors ${index === selectedIndex ? 'bg-blue-50' : ''}"
               data-index="${index}">
                <i class="fas ${suggestion.icon} text-gray-400 mr-3 w-5"></i>
                <div class="flex-1">
                    <div class="font-medium">${escapeHtml(suggestion.text)}</div>
                    <div class="text-xs text-gray-500 capitalize">${suggestion.type}</div>
                </div>
                <i class="fas fa-chevron-right text-gray-300"></i>
            </a>
        `).join('');
        
        // Add click handlers
        suggestionsDiv.querySelectorAll('.smart-suggestion-item').forEach(item => {
            item.addEventListener('mouseenter', function() {
                selectedIndex = parseInt(this.dataset.index);
                updateSelection();
            });
        });
        
        suggestionsDiv.classList.remove('hidden');
    }
    
    function updateSelection() {
        suggestionsDiv.querySelectorAll('.smart-suggestion-item').forEach((item, index) => {
            if (index === selectedIndex) {
                item.classList.add('bg-blue-50');
            } else {
                item.classList.remove('bg-blue-50');
            }
        });
    }
    
    function escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
});


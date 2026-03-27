        </main>
    </div>
    
    <script>
    // Global CSRF Token Reset Function
    if (typeof resetCsrfToken === 'undefined') {
        window.resetCsrfToken = function() {
            const errorDiv = document.getElementById('error-message') || document.querySelector('.bg-red-100.text-red-700');
            const forms = document.querySelectorAll('form');
            
            // Show loading state
            if (errorDiv) {
                const originalContent = errorDiv.innerHTML;
                errorDiv.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i> Resetting token...';
            }
            
            // Fetch new token from API
            fetch('<?= url("api/csrf-reset.php") ?>')
                .then(response => response.json())
                .then(data => {
                    if (data.success && data.token) {
                        // Update all CSRF token inputs in all forms
                        forms.forEach(form => {
                            const csrfInput = form.querySelector('input[name="csrf_token"]');
                            if (csrfInput) {
                                csrfInput.value = data.token;
                            }
                        });
                        
                        // Update error message if exists
                        if (errorDiv) {
                            errorDiv.innerHTML = '<i class="fas fa-check-circle text-green-600 mr-2"></i> Token reset successfully! You can now try again.';
                            errorDiv.className = 'bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded';
                            
                            // Auto-hide success message after 5 seconds
                            setTimeout(() => {
                                if (errorDiv) {
                                    errorDiv.style.transition = 'opacity 0.5s';
                                    errorDiv.style.opacity = '0';
                                    setTimeout(() => errorDiv.remove(), 500);
                                }
                            }, 5000);
                        }
                        
                        // Show success notification
                        if (typeof showToast === 'function') {
                            showToast('CSRF token reset successfully!', 'success');
                        }
                    } else {
                        throw new Error('Failed to reset token');
                    }
                })
                .catch(error => {
                    console.error('Error resetting CSRF token:', error);
                    if (errorDiv) {
                        errorDiv.innerHTML = '<i class="fas fa-exclamation-circle mr-2"></i> Failed to reset token. Please refresh the page.';
                        errorDiv.className = 'bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded';
                    }
                    
                    if (typeof showToast === 'function') {
                        showToast('Failed to reset token. Please refresh the page.', 'error');
                    }
                });
        };
    }
    </script>
    
    <style>
    /* Mobile table scrolling improvements */
    @media (max-width: 768px) {
        .overflow-x-auto {
            -webkit-overflow-scrolling: touch;
        }
        .overflow-x-auto table {
            min-width: 600px;
        }
        /* Better touch targets on mobile */
        button, a.btn, input[type="submit"] {
            min-height: 44px;
            min-width: 44px;
        }
        /* Improve text readability on small screens */
        body {
            font-size: 14px;
        }
        /* Better spacing for mobile cards */
        .bg-white.rounded-xl {
            margin-left: -1rem;
            margin-right: -1rem;
            border-radius: 0;
        }
        @media (min-width: 640px) {
            .bg-white.rounded-xl {
                margin-left: 0;
                margin-right: 0;
                border-radius: 0.75rem;
            }
        }
    }
    </style>
<!-- Custom Modal System -->
<div id="customConfirmModal" class="hidden fixed inset-0 bg-black bg-opacity-50 z-[9999] flex items-center justify-center custom-modal-overlay">
    <div class="bg-white rounded-xl shadow-2xl max-w-md w-full mx-4 custom-modal">
        <div class="p-6">
            <div class="flex items-start mb-4">
                <div class="flex-shrink-0">
                    <div class="w-12 h-12 bg-yellow-100 rounded-full flex items-center justify-center">
                        <i class="fas fa-exclamation-triangle text-yellow-600 text-xl"></i>
                    </div>
                </div>
                <div class="ml-4 flex-1">
                    <h3 class="text-lg font-bold text-gray-900 mb-2" id="confirmModalTitle">Confirm Action</h3>
                    <p class="text-gray-600" id="confirmModalMessage">Are you sure you want to proceed?</p>
                </div>
            </div>
            <div class="flex justify-end gap-3 mt-6">
                <button onclick="customConfirmCancel()" id="confirmModalCancel" class="px-6 py-2 bg-gray-200 text-gray-700 rounded-lg font-semibold hover:bg-gray-300 transition-all">
                    Cancel
                </button>
                <button onclick="customConfirmOk()" id="confirmModalOk" class="px-6 py-2 bg-blue-600 text-white rounded-lg font-semibold hover:bg-blue-700 transition-all">
                    Confirm
                </button>
            </div>
        </div>
    </div>
</div>

<div id="customAlertModal" class="hidden fixed inset-0 bg-black bg-opacity-50 z-[9999] flex items-center justify-center custom-modal-overlay">
    <div class="bg-white rounded-xl shadow-2xl max-w-md w-full mx-4 custom-modal">
        <div class="p-6">
            <div class="flex items-start mb-4">
                <div class="flex-shrink-0">
                    <div class="w-12 h-12 bg-blue-100 rounded-full flex items-center justify-center" id="alertModalIcon">
                        <i class="fas fa-info-circle text-blue-600 text-xl"></i>
                    </div>
                </div>
                <div class="ml-4 flex-1">
                    <h3 class="text-lg font-bold text-gray-900 mb-2" id="alertModalTitle">Notification</h3>
                    <p class="text-gray-600" id="alertModalMessage"></p>
                </div>
            </div>
            <div class="flex justify-end mt-6">
                <button onclick="customAlertClose()" class="px-6 py-2 bg-blue-600 text-white rounded-lg font-semibold hover:bg-blue-700 transition-all">
                    OK
                </button>
            </div>
        </div>
    </div>
</div>

<script>
// Custom Confirm Modal - Replaces browser confirm()
let confirmCallback = null;
let confirmResolve = null;

function customConfirm(message, title = 'Confirm Action') {
    return new Promise((resolve) => {
        confirmResolve = resolve;
        document.getElementById('confirmModalTitle').textContent = title;
        document.getElementById('confirmModalMessage').textContent = message;
        document.getElementById('customConfirmModal').classList.remove('hidden');
    });
}

function customConfirmOk() {
    document.getElementById('customConfirmModal').classList.add('hidden');
    if (confirmResolve) {
        confirmResolve(true);
        confirmResolve = null;
    }
}

function customConfirmCancel() {
    document.getElementById('customConfirmModal').classList.add('hidden');
    if (confirmResolve) {
        confirmResolve(false);
        confirmResolve = null;
    }
}

// Custom Alert Modal - Replaces browser alert()
let alertResolve = null;

function customAlert(message, title = 'Notification', type = 'info') {
    return new Promise((resolve) => {
        alertResolve = resolve;
        document.getElementById('alertModalTitle').textContent = title;
        document.getElementById('alertModalMessage').textContent = message;
        
        const iconDiv = document.getElementById('alertModalIcon');
        const icon = iconDiv.querySelector('i');
        
        // Set icon and color based on type
        iconDiv.className = 'w-12 h-12 rounded-full flex items-center justify-center';
        icon.className = 'fas text-xl';
        
        switch(type) {
            case 'success':
                iconDiv.classList.add('bg-green-100');
                icon.classList.add('fa-check-circle', 'text-green-600');
                break;
            case 'error':
            case 'danger':
                iconDiv.classList.add('bg-red-100');
                icon.classList.add('fa-exclamation-circle', 'text-red-600');
                break;
            case 'warning':
                iconDiv.classList.add('bg-yellow-100');
                icon.classList.add('fa-exclamation-triangle', 'text-yellow-600');
                break;
            default:
                iconDiv.classList.add('bg-blue-100');
                icon.classList.add('fa-info-circle', 'text-blue-600');
        }
        
        document.getElementById('customAlertModal').classList.remove('hidden');
    });
}

function customAlertClose() {
    document.getElementById('customAlertModal').classList.add('hidden');
    if (alertResolve) {
        alertResolve();
        alertResolve = null;
    }
}

// Close modals on overlay click
document.getElementById('customConfirmModal').addEventListener('click', function(e) {
    if (e.target === this) {
        customConfirmCancel();
    }
});

document.getElementById('customAlertModal').addEventListener('click', function(e) {
    if (e.target === this) {
        customAlertClose();
    }
});

// Close modals on Escape key
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        if (!document.getElementById('customConfirmModal').classList.contains('hidden')) {
            customConfirmCancel();
        }
        if (!document.getElementById('customAlertModal').classList.contains('hidden')) {
            customAlertClose();
        }
    }
});

// Replace native confirm() and alert() globally
window.originalConfirm = window.confirm;
window.originalAlert = window.alert;

window.confirm = function(message) {
    return customConfirm(message);
};

window.alert = function(message) {
    return customAlert(message);
};
</script>

</body>
</html>


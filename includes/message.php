<?php
/**
 * Beautiful Message Component
 * Usage: include this file and use showMessage() function
 * 
 * Types: success, error, warning, info
 */

// Function to display a beautiful message
function showMessage($message, $type = 'success', $dismissible = true, $autoHide = true) {
    if (empty($message)) return '';
    
    $icons = [
        'success' => 'fa-check-circle',
        'error' => 'fa-exclamation-circle',
        'warning' => 'fa-exclamation-triangle',
        'info' => 'fa-info-circle'
    ];
    
    $colors = [
        'success' => [
            'bg' => 'bg-gradient-to-r from-green-50 to-emerald-50',
            'border' => 'border-l-4 border-green-500',
            'text' => 'text-green-800',
            'icon' => 'text-green-600',
            'button' => 'text-green-600 hover:bg-green-100'
        ],
        'error' => [
            'bg' => 'bg-gradient-to-r from-red-50 to-rose-50',
            'border' => 'border-l-4 border-red-500',
            'text' => 'text-red-800',
            'icon' => 'text-red-600',
            'button' => 'text-red-600 hover:bg-red-100'
        ],
        'warning' => [
            'bg' => 'bg-gradient-to-r from-yellow-50 to-amber-50',
            'border' => 'border-l-4 border-yellow-500',
            'text' => 'text-yellow-800',
            'icon' => 'text-yellow-600',
            'button' => 'text-yellow-600 hover:bg-yellow-100'
        ],
        'info' => [
            'bg' => 'bg-gradient-to-r from-blue-50 to-cyan-50',
            'border' => 'border-l-4 border-blue-500',
            'text' => 'text-blue-800',
            'icon' => 'text-blue-600',
            'button' => 'text-blue-600 hover:bg-blue-100'
        ]
    ];
    
    $colorScheme = $colors[$type] ?? $colors['success'];
    $icon = $icons[$type] ?? $icons['success'];
    $messageId = 'msg-' . uniqid();
    
    $dismissButton = $dismissible ? '
        <button onclick="dismissMessage(\'' . $messageId . '\')" 
                class="' . $colorScheme['button'] . ' p-1 rounded-full transition-all duration-200 hover:scale-110 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-' . $type . '-500">
            <i class="fas fa-times text-sm"></i>
        </button>' : '';
    
    $autoHideAttr = $autoHide ? 'data-auto-hide="true"' : '';
    
    return '
    <div id="' . $messageId . '" 
         class="message-alert ' . $colorScheme['bg'] . ' ' . $colorScheme['border'] . ' rounded-lg shadow-lg p-4 mb-6 transform transition-all duration-500 ease-out opacity-0 translate-y-[-20px]"
         ' . $autoHideAttr . '>
        <div class="flex items-start justify-between gap-4">
            <div class="flex items-start gap-3 flex-1">
                <div class="flex-shrink-0 mt-0.5">
                    <i class="fas ' . $icon . ' text-2xl ' . $colorScheme['icon'] . ' animate-pulse"></i>
                </div>
                <div class="flex-1 ' . $colorScheme['text'] . '">
                    <p class="font-semibold text-base leading-relaxed">' . escape($message) . '</p>
                </div>
            </div>
            ' . $dismissButton . '
        </div>
        <div class="progress-bar mt-3 h-1 bg-white/30 rounded-full overflow-hidden hidden">
            <div class="progress-fill h-full bg-gradient-to-r from-transparent via-white/50 to-transparent animate-progress"></div>
        </div>
    </div>';
}

// Helper function to show message from PHP variables
function displayMessage($message = '', $error = '', $warning = '', $info = '') {
    $output = '';
    
    if (!empty($message)) {
        $output .= showMessage($message, 'success');
    }
    if (!empty($error)) {
        $output .= showMessage($error, 'error');
    }
    if (!empty($warning)) {
        $output .= showMessage($warning, 'warning');
    }
    if (!empty($info)) {
        $output .= showMessage($info, 'info');
    }
    
    return $output;
}
?>

<style>
/* Message Alert Animations */
.message-alert {
    animation: slideInDown 0.5s ease-out forwards;
    position: relative;
    overflow: hidden;
}

.message-alert::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    width: 4px;
    height: 100%;
    background: linear-gradient(180deg, currentColor 0%, transparent 100%);
    animation: shimmer 2s infinite;
}

@keyframes slideInDown {
    from {
        opacity: 0;
        transform: translateY(-20px) scale(0.95);
    }
    to {
        opacity: 1;
        transform: translateY(0) scale(1);
    }
}

@keyframes slideOutUp {
    from {
        opacity: 1;
        transform: translateY(0) scale(1);
    }
    to {
        opacity: 0;
        transform: translateY(-20px) scale(0.95);
    }
}

@keyframes shimmer {
    0%, 100% {
        opacity: 0.5;
    }
    50% {
        opacity: 1;
    }
}

@keyframes progress {
    from {
        width: 100%;
    }
    to {
        width: 0%;
    }
}

.animate-progress {
    animation: progress 5s linear forwards;
}

.message-alert.dismissing {
    animation: slideOutUp 0.4s ease-in forwards;
}

/* Hover Effects */
.message-alert:hover {
    transform: translateY(-2px) scale(1.01);
    box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
}

/* Icon Animation */
.message-alert i.fa-check-circle,
.message-alert i.fa-exclamation-circle,
.message-alert i.fa-exclamation-triangle,
.message-alert i.fa-info-circle {
    animation: iconBounce 0.6s ease-out;
}

@keyframes iconBounce {
    0%, 100% {
        transform: scale(1);
    }
    50% {
        transform: scale(1.2);
    }
}

/* Progress Bar for Auto-hide */
.message-alert[data-auto-hide="true"] .progress-bar {
    display: block;
}

/* Responsive Design */
@media (max-width: 640px) {
    .message-alert {
        padding: 0.875rem;
    }
    
    .message-alert .text-base {
        font-size: 0.875rem;
    }
}
</style>

<script>
// Message Dismissal and Auto-hide Functionality
function dismissMessage(messageId) {
    const message = document.getElementById(messageId);
    if (message) {
        message.classList.add('dismissing');
        setTimeout(() => {
            message.remove();
        }, 400);
    }
}

// Auto-hide messages after 5 seconds
document.addEventListener('DOMContentLoaded', function() {
    const messages = document.querySelectorAll('.message-alert[data-auto-hide="true"]');
    
    messages.forEach((message, index) => {
        // Stagger the animations slightly
        setTimeout(() => {
            message.style.opacity = '1';
            message.style.transform = 'translateY(0)';
        }, index * 100);
        
        // Auto-dismiss after 5 seconds
        setTimeout(() => {
            if (message && message.parentNode) {
                dismissMessage(message.id);
            }
        }, 5000 + (index * 100));
    });
    
    // Show progress bar for auto-hide messages
    messages.forEach(message => {
        const progressBar = message.querySelector('.progress-bar');
        if (progressBar) {
            progressBar.classList.remove('hidden');
        }
    });
});

// Global function to show toast messages (for AJAX/JS usage)
function showToast(message, type = 'success', duration = 5000) {
    const messageId = 'toast-' + Date.now();
    const icons = {
        success: 'fa-check-circle',
        error: 'fa-exclamation-circle',
        warning: 'fa-exclamation-triangle',
        info: 'fa-info-circle'
    };
    
    const colors = {
        success: {
            bg: 'bg-gradient-to-r from-green-50 to-emerald-50',
            border: 'border-l-4 border-green-500',
            text: 'text-green-800',
            icon: 'text-green-600'
        },
        error: {
            bg: 'bg-gradient-to-r from-red-50 to-rose-50',
            border: 'border-l-4 border-red-500',
            text: 'text-red-800',
            icon: 'text-red-600'
        },
        warning: {
            bg: 'bg-gradient-to-r from-yellow-50 to-amber-50',
            border: 'border-l-4 border-yellow-500',
            text: 'text-yellow-800',
            icon: 'text-yellow-600'
        },
        info: {
            bg: 'bg-gradient-to-r from-blue-50 to-cyan-50',
            border: 'border-l-4 border-blue-500',
            text: 'text-blue-800',
            icon: 'text-blue-600'
        }
    };
    
    const colorScheme = colors[type] || colors.success;
    const icon = icons[type] || icons.success;
    
    const toast = document.createElement('div');
    toast.id = messageId;
    toast.className = `message-alert ${colorScheme.bg} ${colorScheme.border} rounded-lg shadow-xl p-4 mb-4 transform transition-all duration-500 ease-out fixed top-4 right-4 z-50 max-w-md`;
    toast.innerHTML = `
        <div class="flex items-start justify-between gap-4">
            <div class="flex items-start gap-3 flex-1">
                <div class="flex-shrink-0 mt-0.5">
                    <i class="fas ${icon} text-2xl ${colorScheme.icon}"></i>
                </div>
                <div class="flex-1 ${colorScheme.text}">
                    <p class="font-semibold text-base leading-relaxed">${message}</p>
                </div>
            </div>
            <button onclick="dismissMessage('${messageId}')" 
                    class="${colorScheme.icon} hover:opacity-70 p-1 rounded-full transition-all duration-200 hover:scale-110 focus:outline-none">
                <i class="fas fa-times text-sm"></i>
            </button>
        </div>
    `;
    
    // Insert at the top of body or a specific container
    const container = document.querySelector('.message-container') || document.body;
    container.insertBefore(toast, container.firstChild);
    
    // Animate in
    setTimeout(() => {
        toast.style.opacity = '1';
        toast.style.transform = 'translateY(0)';
    }, 10);
    
    // Auto-dismiss
    setTimeout(() => {
        if (document.getElementById(messageId)) {
            dismissMessage(messageId);
        }
    }, duration);
}
</script>


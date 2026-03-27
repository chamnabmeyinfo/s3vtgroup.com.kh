<!-- Live Chat Widget -->
<div id="chat-widget" class="fixed bottom-6 right-6 z-40">
    <!-- Chat Toggle Button -->
    <button onclick="toggleChat()" 
            id="chat-toggle"
            class="w-14 h-14 bg-blue-600 text-white rounded-full shadow-lg hover:bg-blue-700 transition-colors flex items-center justify-center">
        <i class="fas fa-comments text-xl"></i>
        <span id="chat-notification" class="hidden absolute -top-1 -right-1 w-4 h-4 bg-red-500 rounded-full"></span>
    </button>
    
    <!-- Chat Window -->
    <div id="chat-window" 
         class="hidden fixed bottom-24 right-6 w-96 bg-white rounded-lg shadow-2xl flex flex-col"
         style="height: 500px;">
        <!-- Chat Header -->
        <div class="bg-blue-600 text-white p-4 rounded-t-lg flex justify-between items-center">
            <div>
                <h3 class="font-bold">Live Chat Support</h3>
                <p class="text-sm text-blue-100">We're here to help!</p>
            </div>
            <button onclick="toggleChat()" class="text-white hover:text-gray-200">
                <i class="fas fa-times"></i>
            </button>
        </div>
        
        <!-- Chat Messages -->
        <div id="chat-messages" class="flex-1 overflow-y-auto p-4 space-y-4">
            <div class="bg-blue-50 rounded-lg p-3">
                <p class="text-sm">Hello! How can we assist you today?</p>
            </div>
            
            <!-- Placeholder for chat messages -->
            <div id="chat-content" class="space-y-4">
                <p class="text-xs text-gray-500 text-center">To enable live chat, integrate with a service like:</p>
                <ul class="text-xs text-gray-600 space-y-1 pl-4">
                    <li>• Intercom</li>
                    <li>• Zendesk Chat</li>
                    <li>• Tawk.to</li>
                    <li>• Crisp</li>
                    <li>• Facebook Messenger</li>
                </ul>
                <div class="bg-gray-100 rounded-lg p-4 text-center">
                    <p class="text-sm font-semibold mb-2">Or contact us directly:</p>
                    <a href="<?= function_exists('url') ? url('contact.php') : 'contact.php' ?>" class="btn-primary-sm inline-block">
                        Contact Form
                    </a>
                </div>
            </div>
        </div>
        
        <!-- Chat Input -->
        <div class="border-t p-4">
            <div class="flex gap-2">
                <input type="text" 
                       id="chat-input" 
                       placeholder="Type your message..."
                       class="flex-1 px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500">
                <button onclick="sendChatMessage()" 
                        class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
                    <i class="fas fa-paper-plane"></i>
                </button>
            </div>
        </div>
    </div>
</div>

<script>
let chatOpen = false;

function toggleChat() {
    chatOpen = !chatOpen;
    const chatWindow = document.getElementById('chat-window');
    
    if (chatOpen) {
        chatWindow.classList.remove('hidden');
    } else {
        chatWindow.classList.add('hidden');
    }
}

function sendChatMessage() {
    const input = document.getElementById('chat-input');
    const message = input.value.trim();
    
    if (!message) return;
    
    // Add user message
    addChatMessage(message, 'user');
    input.value = '';
    
    // Simulate response (replace with real chat integration)
    setTimeout(() => {
        addChatMessage('Thank you for your message! For immediate assistance, please use our contact form or call us.', 'bot');
    }, 1000);
}

function addChatMessage(text, type) {
    const messagesDiv = document.getElementById('chat-messages');
    const messageDiv = document.createElement('div');
    messageDiv.className = `flex ${type === 'user' ? 'justify-end' : 'justify-start'}`;
    messageDiv.innerHTML = `
        <div class="max-w-[80%] rounded-lg p-3 ${type === 'user' ? 'bg-blue-600 text-white' : 'bg-gray-100'}">
            <p class="text-sm">${escapeHtml(text)}</p>
        </div>
    `;
    messagesDiv.appendChild(messageDiv);
    messagesDiv.scrollTop = messagesDiv.scrollHeight;
}

function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

// Auto-open chat on mobile after delay (optional)
if (window.innerWidth <= 768) {
    setTimeout(() => {
        // Could auto-open on mobile - uncomment if desired
        // toggleChat();
    }, 30000); // 30 seconds
}
</script>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="S3VGroup - Website Under Maintenance. We'll be back soon!">
    <title>Website Under Maintenance - S3VGroup</title>
    
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        @keyframes float {
            0%, 100% { transform: translateY(0px); }
            50% { transform: translateY(-20px); }
        }
        .float-animation {
            animation: float 3s ease-in-out infinite;
        }
        @keyframes pulse-glow {
            0%, 100% { box-shadow: 0 0 20px rgba(59, 130, 246, 0.5); }
            50% { box-shadow: 0 0 40px rgba(59, 130, 246, 0.8); }
        }
        .pulse-glow {
            animation: pulse-glow 2s ease-in-out infinite;
        }
    </style>
</head>
<body class="bg-gradient-to-br from-blue-900 via-blue-800 to-blue-900 min-h-screen flex items-center justify-center p-4">
    <div class="max-w-4xl w-full text-center">
        <!-- Logo/Brand -->
        <div class="mb-8 float-animation">
            <div class="inline-block bg-white rounded-full p-6 pulse-glow">
                <i class="fas fa-hard-hat text-blue-600 text-6xl"></i>
            </div>
        </div>
        
        <!-- Main Content -->
        <h1 class="text-5xl md:text-6xl font-bold text-white mb-4">
            Website Under Maintenance
        </h1>
        
        <p class="text-xl md:text-2xl text-blue-100 mb-8">
            We're performing scheduled maintenance to improve your experience
        </p>
        
        <div class="bg-white bg-opacity-10 backdrop-blur-lg rounded-2xl p-8 mb-8 border border-white border-opacity-20">
            <h2 class="text-2xl font-bold text-white mb-4">
                <i class="fas fa-tools mr-2"></i>
                S3VGroup - Forklift & Equipment Solutions
            </h2>
            <p class="text-blue-100 text-lg mb-6">
                We're currently performing maintenance to enhance our services and improve your experience.
                The website will be back online shortly. Thank you for your patience.
            </p>
            
            <!-- Features Preview -->
            <div class="grid md:grid-cols-3 gap-4 mb-6 text-left">
                <div class="bg-white bg-opacity-5 rounded-lg p-4">
                    <i class="fas fa-forklift text-blue-400 text-2xl mb-2"></i>
                    <h3 class="text-white font-semibold mb-1">Wide Product Range</h3>
                    <p class="text-blue-100 text-sm">Extensive selection of forklifts and equipment</p>
                </div>
                <div class="bg-white bg-opacity-5 rounded-lg p-4">
                    <i class="fas fa-headset text-blue-400 text-2xl mb-2"></i>
                    <h3 class="text-white font-semibold mb-1">Expert Support</h3>
                    <p class="text-blue-100 text-sm">Professional consultation and after-sales service</p>
                </div>
                <div class="bg-white bg-opacity-5 rounded-lg p-4">
                    <i class="fas fa-shipping-fast text-blue-400 text-2xl mb-2"></i>
                    <h3 class="text-white font-semibold mb-1">Fast Delivery</h3>
                    <p class="text-blue-100 text-sm">Quick and reliable shipping options</p>
                </div>
            </div>
        </div>
        
        <!-- Contact Form -->
        <div class="bg-white bg-opacity-10 backdrop-blur-lg rounded-2xl p-8 mb-8 border border-white border-opacity-20">
            <h2 class="text-2xl font-bold text-white mb-4">
                <i class="fas fa-envelope mr-2"></i>
                Get Notified When We're Back
            </h2>
            <p class="text-blue-100 mb-6">
                Enter your email address and we'll notify you as soon as maintenance is complete!
            </p>
            
            <form id="notify-form" class="max-w-md mx-auto">
                <div class="flex gap-2 mb-4">
                    <input type="email" 
                           name="email" 
                           id="email"
                           placeholder="Enter your email address" 
                           required
                           class="flex-1 px-4 py-3 rounded-lg bg-white bg-opacity-20 border border-white border-opacity-30 text-white placeholder-blue-200 focus:outline-none focus:ring-2 focus:ring-blue-400">
                    <button type="submit" 
                            class="px-6 py-3 bg-blue-600 hover:bg-blue-700 text-white font-semibold rounded-lg transition-all transform hover:scale-105">
                        <i class="fas fa-paper-plane mr-2"></i>
                        Notify Me
                    </button>
                </div>
                <div id="form-message" class="hidden text-sm font-semibold"></div>
            </form>
        </div>
        
        <!-- Contact Information -->
        <div class="bg-white bg-opacity-10 backdrop-blur-lg rounded-2xl p-6 border border-white border-opacity-20">
            <h3 class="text-xl font-bold text-white mb-4">
                <i class="fas fa-phone-alt mr-2"></i>
                Need to Reach Us Now?
            </h3>
            <div class="flex flex-wrap justify-center gap-6 text-blue-100">
                <a href="mailto:info@s3vgroup.com" class="hover:text-white transition-colors">
                    <i class="fas fa-envelope mr-2"></i>
                    info@s3vgroup.com
                </a>
                <a href="tel:+1234567890" class="hover:text-white transition-colors">
                    <i class="fas fa-phone mr-2"></i>
                    +1 (234) 567-890
                </a>
            </div>
        </div>
        
        <!-- Progress Bar -->
        <div class="mt-8">
            <?php
            $config = file_exists(__DIR__ . '/config/under-construction.php') 
                ? require __DIR__ . '/config/under-construction.php' 
                : [];
            $progress = $config['progress'] ?? 85;
            ?>
            <div class="text-blue-100 mb-2">We're <span id="progress" class="font-bold text-white"><?= escape($progress) ?>%</span> complete</div>
            <div class="w-full bg-blue-900 bg-opacity-50 rounded-full h-3 overflow-hidden">
                <div class="h-full bg-gradient-to-r from-blue-400 to-blue-600 rounded-full transition-all duration-1000" 
                     id="progress-bar" 
                     style="width: <?= escape($progress) ?>%"></div>
            </div>
        </div>
        
        <!-- Footer -->
        <div class="mt-12 text-blue-200 text-sm">
            <p>&copy; <?= date('Y') ?> S3VGroup. All rights reserved.</p>
        </div>
    </div>
    
    <script>
        // Form submission
        document.getElementById('notify-form').addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const form = this;
            const email = document.getElementById('email').value;
            const messageDiv = document.getElementById('form-message');
            const submitBtn = form.querySelector('button[type="submit"]');
            
            // Disable button
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i> Sending...';
            
            try {
                const response = await fetch('<?= url('api/under-construction-notify.php') ?>', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({ email: email })
                });
                
                const data = await response.json();
                
                messageDiv.className = 'text-sm font-semibold ' + (data.success ? 'text-green-300' : 'text-red-300');
                messageDiv.textContent = data.message || (data.success ? 'Thank you! We\'ll notify you when we launch.' : 'Something went wrong. Please try again.');
                messageDiv.classList.remove('hidden');
                
                if (data.success) {
                    form.reset();
                    setTimeout(() => {
                        messageDiv.classList.add('hidden');
                    }, 5000);
                }
            } catch (error) {
                messageDiv.className = 'text-sm font-semibold text-red-300';
                messageDiv.textContent = 'Unable to send request. Please try again later.';
                messageDiv.classList.remove('hidden');
            } finally {
                submitBtn.disabled = false;
                submitBtn.innerHTML = '<i class="fas fa-paper-plane mr-2"></i> Notify Me';
            }
        });
        
        // Animate progress (optional)
        let progress = <?= escape($progress) ?>;
        const progressInterval = setInterval(() => {
            if (progress < 95) {
                progress += 0.1;
                document.getElementById('progress').textContent = Math.round(progress) + '%';
                document.getElementById('progress-bar').style.width = progress + '%';
            } else {
                clearInterval(progressInterval);
            }
        }, 5000);
    </script>
</body>
</html>


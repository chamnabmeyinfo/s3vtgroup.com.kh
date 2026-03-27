<?php
/**
 * Social Media Sharing Component
 */
$currentUrl = urlencode((isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']);
$title = urlencode($pageTitle ?? 'Check this out!');
$description = urlencode($metaDescription ?? '');
?>

<div class="social-share flex items-center gap-2">
    <span class="text-sm text-gray-600 mr-2">Share:</span>
    
    <!-- Facebook -->
    <a href="https://www.facebook.com/sharer/sharer.php?u=<?= $currentUrl ?>" 
       target="_blank"
       class="w-8 h-8 bg-blue-600 text-white rounded-full flex items-center justify-center hover:bg-blue-700 transition-colors"
       title="Share on Facebook">
        <i class="fab fa-facebook-f text-xs"></i>
    </a>
    
    <!-- Twitter -->
    <a href="https://twitter.com/intent/tweet?url=<?= $currentUrl ?>&text=<?= $title ?>" 
       target="_blank"
       class="w-8 h-8 bg-blue-400 text-white rounded-full flex items-center justify-center hover:bg-blue-500 transition-colors"
       title="Share on Twitter">
        <i class="fab fa-twitter text-xs"></i>
    </a>
    
    <!-- LinkedIn -->
    <a href="https://www.linkedin.com/shareArticle?url=<?= $currentUrl ?>&title=<?= $title ?>" 
       target="_blank"
       class="w-8 h-8 bg-blue-700 text-white rounded-full flex items-center justify-center hover:bg-blue-800 transition-colors"
       title="Share on LinkedIn">
        <i class="fab fa-linkedin-in text-xs"></i>
    </a>
    
    <!-- WhatsApp -->
    <a href="https://wa.me/?text=<?= $title ?> <?= $currentUrl ?>" 
       target="_blank"
       class="w-8 h-8 bg-green-500 text-white rounded-full flex items-center justify-center hover:bg-green-600 transition-colors"
       title="Share on WhatsApp">
        <i class="fab fa-whatsapp text-xs"></i>
    </a>
    
    <!-- Email -->
    <a href="mailto:?subject=<?= $title ?>&body=<?= $description ?> <?= $currentUrl ?>" 
       class="w-8 h-8 bg-gray-600 text-white rounded-full flex items-center justify-center hover:bg-gray-700 transition-colors"
       title="Share via Email">
        <i class="fas fa-envelope text-xs"></i>
    </a>
</div>


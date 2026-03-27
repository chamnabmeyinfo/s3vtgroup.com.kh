<?php
/**
 * Language Switcher Component
 * Uses Google Translate widget for automatic translation
 */

// Get available languages from settings
$availableLanguages = [];
try {
    $langSetting = db()->fetchOne("SELECT value FROM settings WHERE `key` = 'available_languages'");
    if ($langSetting && !empty($langSetting['value'])) {
        $availableLanguages = json_decode($langSetting['value'], true) ?: [];
    }
} catch (\Exception $e) {
    // Fallback to default languages
}

// Default languages if not configured
if (empty($availableLanguages)) {
    $availableLanguages = [
        ['code' => 'en', 'name' => 'English', 'flag' => '🇺🇸'],
        ['code' => 'km', 'name' => 'ខ្មែរ', 'flag' => '🇰🇭'],
        ['code' => 'th', 'name' => 'ไทย', 'flag' => '🇹🇭'],
        ['code' => 'vi', 'name' => 'Tiếng Việt', 'flag' => '🇻🇳'],
        ['code' => 'zh', 'name' => '中文', 'flag' => '🇨🇳'],
        ['code' => 'ja', 'name' => '日本語', 'flag' => '🇯🇵'],
    ];
}

// Get current language from session or default to 'en'
$currentLang = $_SESSION['site_language'] ?? 'en';

// Language codes mapping for Google Translate
$langCodes = [
    'en' => 'en',
    'km' => 'km',
    'th' => 'th',
    'vi' => 'vi',
    'zh' => 'zh-CN',
    'ja' => 'ja',
];

// Google Translate language codes (may differ from standard codes)
$googleLangCodes = [
    'en' => 'en',
    'km' => 'km',
    'th' => 'th',
    'vi' => 'vi',
    'zh' => 'zh-CN',
    'ja' => 'ja',
];
?>

<!-- Google Translate Element (Hidden) -->
<div id="google_translate_element" style="display: none;"></div>

<!-- Language Switcher -->
<div class="language-switcher relative group" id="languageSwitcher">
    <button onclick="toggleLanguageMenu()" 
            class="language-switcher-btn flex items-center gap-2 px-3 py-2 rounded-lg bg-white/80 hover:bg-white border border-gray-200 hover:border-blue-500 transition-all duration-300 shadow-sm hover:shadow-md"
            title="Select Language">
        <i class="fas fa-globe text-blue-600"></i>
        <span class="current-lang-flag text-lg"><?= $availableLanguages[array_search($currentLang, array_column($availableLanguages, 'code'))]['flag'] ?? '🌐' ?></span>
        <span class="current-lang-code hidden sm:inline text-sm font-semibold text-gray-700"><?= strtoupper($currentLang) ?></span>
        <i class="fas fa-chevron-down text-xs text-gray-500 transform transition-transform duration-300" id="langChevron"></i>
    </button>
    
    <!-- Language Dropdown -->
    <div id="languageDropdown" 
         class="language-dropdown absolute top-full right-0 mt-2 bg-white rounded-xl shadow-2xl border border-gray-200 overflow-hidden opacity-0 invisible transform translate-y-2 transition-all duration-300 z-50 min-w-[200px]"
         style="display: none;">
        <div class="p-2">
            <?php foreach ($availableLanguages as $lang): 
                $isActive = $lang['code'] === $currentLang;
                $langCode = $langCodes[$lang['code']] ?? $lang['code'];
                $googleLangCode = $googleLangCodes[$lang['code']] ?? $langCode;
            ?>
                <button onclick="switchLanguage('<?= escape($lang['code']) ?>', '<?= escape($langCode) ?>', '<?= escape($googleLangCode) ?>')" 
                        class="language-option w-full flex items-center gap-3 px-4 py-3 rounded-lg hover:bg-blue-50 transition-all duration-200 text-left <?= $isActive ? 'bg-blue-50 border-l-4 border-blue-500' : '' ?>"
                        data-lang="<?= escape($lang['code']) ?>">
                    <span class="text-2xl"><?= escape($lang['flag']) ?></span>
                    <span class="flex-1 font-medium text-gray-800 <?= $isActive ? 'text-blue-600' : '' ?>"><?= escape($lang['name']) ?></span>
                    <?php if ($isActive): ?>
                        <i class="fas fa-check-circle text-blue-600"></i>
                    <?php endif; ?>
                </button>
            <?php endforeach; ?>
        </div>
        <div class="border-t border-gray-200 p-2 bg-gray-50">
            <p class="text-xs text-gray-500 text-center">
                <i class="fas fa-info-circle mr-1"></i>
                Uses Google Translate
            </p>
        </div>
    </div>
</div>

<script>
// Language switcher functionality
let languageMenuOpen = false;

function toggleLanguageMenu() {
    const dropdown = document.getElementById('languageDropdown');
    const chevron = document.getElementById('langChevron');
    
    if (languageMenuOpen) {
        dropdown.style.display = 'none';
        dropdown.classList.remove('opacity-100', 'visible', 'translate-y-0');
        dropdown.classList.add('opacity-0', 'invisible', 'translate-y-2');
        chevron.classList.remove('rotate-180');
        languageMenuOpen = false;
    } else {
        dropdown.style.display = 'block';
        setTimeout(() => {
            dropdown.classList.remove('opacity-0', 'invisible', 'translate-y-2');
            dropdown.classList.add('opacity-100', 'visible', 'translate-y-0');
        }, 10);
        chevron.classList.add('rotate-180');
        languageMenuOpen = true;
    }
}

// Initialize Google Translate
let googleTranslateInitialized = false;
let googleTranslateSelect = null;

function initGoogleTranslate() {
    if (googleTranslateInitialized && googleTranslateSelect) return;
    
    // Add Google Translate script if not already loaded
    if (!document.getElementById('google-translate-script')) {
        const script = document.createElement('script');
        script.id = 'google-translate-script';
        script.src = '//translate.google.com/translate_a/element.js?cb=googleTranslateElementInit';
        script.async = true;
        document.head.appendChild(script);
    }
    
    // Initialize Google Translate Element
    if (!window.googleTranslateElementInit) {
        window.googleTranslateElementInit = function() {
            const element = document.getElementById('google_translate_element');
            if (element && !element.hasChildNodes()) {
                const includedLangs = <?= json_encode(array_map(function($lang) use ($googleLangCodes) { 
                    return $googleLangCodes[$lang['code']] ?? $lang['code']; 
                }, $availableLanguages)) ?>;
                
                new google.translate.TranslateElement({
                    pageLanguage: 'en',
                    includedLanguages: includedLangs.join(','),
                    layout: google.translate.TranslateElement.InlineLayout.SIMPLE,
                    autoDisplay: false
                }, 'google_translate_element');
            }
            
            // Find the select element after initialization
            setTimeout(() => {
                googleTranslateSelect = document.querySelector('.goog-te-combo');
                if (googleTranslateSelect) {
                    googleTranslateInitialized = true;
                }
            }, 500);
        };
    }
    
    // If script already loaded, initialize immediately
    if (window.google && window.google.translate && window.google.translate.TranslateElement) {
        window.googleTranslateElementInit();
    }
}

function switchLanguage(langCode, htmlLangCode, googleLangCode) {
    // Save to session storage
    if (typeof(Storage) !== "undefined") && langCode !== 'en') {
        localStorage.setItem('site_language', langCode);
    } else if (langCode === 'en') {
        localStorage.removeItem('site_language');
    }
    
    // Update HTML lang attribute
    document.documentElement.setAttribute('lang', htmlLangCode);
    
    // Update meta tags
    let metaLang = document.querySelector('meta[http-equiv="content-language"]');
    if (!metaLang) {
        metaLang = document.createElement('meta');
        metaLang.setAttribute('http-equiv', 'content-language');
        document.head.appendChild(metaLang);
    }
    metaLang.setAttribute('content', htmlLangCode);
    
    // Save language preference via AJAX
    fetch('<?= url("api/save-language.php") ?>', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            language: langCode
        })
    }).catch(err => console.log('Language save error:', err));
    
    // Close dropdown
    toggleLanguageMenu();
    
    // Use Google Translate to translate the page
    if (langCode === 'en') {
        // Reset to English - reload page without language parameter
        if (window.location.search.includes('lang=')) {
            window.location.href = window.location.pathname;
        } else {
            window.location.reload();
        }
    } else {
        // Initialize Google Translate if not already done
        initGoogleTranslate();
        
        // Wait for Google Translate to be ready, then trigger translation
        const translatePage = () => {
            const select = document.querySelector('.goog-te-combo') || googleTranslateSelect;
            if (select) {
                // Set the language
                select.value = googleLangCode;
                // Trigger change event to translate
                const event = new Event('change', { bubbles: true });
                select.dispatchEvent(event);
                
                // Also try click event
                select.click();
                
                // Update URL without reload
                const url = new URL(window.location);
                url.searchParams.set('lang', langCode);
                window.history.pushState({}, '', url);
                
                console.log('Translating to:', googleLangCode);
            } else {
                // If select not found, wait a bit and retry
                if (window.google && window.google.translate) {
                    setTimeout(translatePage, 200);
                } else {
                    // Fallback: reload page with language parameter
                    window.location.href = window.location.pathname + '?lang=' + langCode;
                }
            }
        };
        
        // Start translation after a short delay to ensure Google Translate is ready
        setTimeout(translatePage, 300);
    }
}

// Close dropdown when clicking outside
document.addEventListener('click', function(event) {
    const switcher = document.getElementById('languageSwitcher');
    if (switcher && !switcher.contains(event.target) && languageMenuOpen) {
        toggleLanguageMenu();
    }
});

// Load saved language on page load
document.addEventListener('DOMContentLoaded', function() {
    const savedLang = localStorage.getItem('site_language');
    const urlParams = new URLSearchParams(window.location.search);
    const urlLang = urlParams.get('lang');
    
    const langToUse = urlLang || savedLang;
    
    if (langToUse && langToUse !== 'en') {
        const langCodes = <?= json_encode($langCodes) ?>;
        const googleLangCodes = <?= json_encode($googleLangCodes) ?>;
        const htmlLangCode = langCodes[langToUse] || langToUse;
        const googleLangCode = googleLangCodes[langToUse] || htmlLangCode;
        
        document.documentElement.setAttribute('lang', htmlLangCode);
        
        // Update meta tag
        let metaLang = document.querySelector('meta[http-equiv="content-language"]');
        if (!metaLang) {
            metaLang = document.createElement('meta');
            metaLang.setAttribute('http-equiv', 'content-language');
            document.head.appendChild(metaLang);
        }
        metaLang.setAttribute('content', htmlLangCode);
        
        // Initialize and trigger Google Translate
        initGoogleTranslate();
        
        setTimeout(() => {
            const select = document.querySelector('.goog-te-combo');
            if (select) {
                select.value = googleLangCode;
                select.dispatchEvent(new Event('change'));
            }
        }, 1000);
    }
});

// Hide Google Translate default UI
setTimeout(() => {
    const style = document.createElement('style');
    style.textContent = `
        .goog-te-banner-frame { display: none !important; }
        .goog-te-balloon-frame { display: none !important; }
        body { top: 0 !important; }
        #google_translate_element { display: none !important; }
        .skiptranslate { display: none !important; }
    `;
    document.head.appendChild(style);
}, 100);
</script>

<style>
.language-switcher-btn {
    cursor: pointer;
}

.language-dropdown {
    max-height: 400px;
    overflow-y: auto;
}

.language-option {
    cursor: pointer;
}

.language-option:hover {
    transform: translateX(4px);
}

.language-option.active {
    background: linear-gradient(90deg, rgba(59, 130, 246, 0.1), rgba(79, 70, 229, 0.1));
}

/* Smooth transitions */
.language-dropdown {
    backdrop-filter: blur(10px);
    -webkit-backdrop-filter: blur(10px);
}
</style>

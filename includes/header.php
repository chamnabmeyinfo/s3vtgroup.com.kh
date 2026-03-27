<?php
// Get categories for navigation dropdown
use App\Models\Category;
$categoryModel = new Category();
$navCategories = $categoryModel->getAll(true);
?>
<!DOCTYPE html>
<?php
// Get current language from session or default
$currentLanguage = $_SESSION['site_language'] ?? 'en';
$langCodes = [
    'en' => 'en',
    'km' => 'km',
    'th' => 'th',
    'vi' => 'vi',
    'zh' => 'zh-CN',
    'ja' => 'ja',
];
$htmlLang = $langCodes[$currentLanguage] ?? 'en';
?>
<html lang="<?= escape($htmlLang) ?>">
<head>
    <!-- Google tag (gtag.js) — load AW id first per Google Ads install; GA4 + Ads share one library -->
    <script async src="https://www.googletagmanager.com/gtag/js?id=AW-17871315689"></script>
    <script>
      window.dataLayer = window.dataLayer || [];
      function gtag(){dataLayer.push(arguments);}
      gtag('js', new Date());
      gtag('config', 'AW-17871315689');
      gtag('config', 'G-5LY6BHRJCB');
      /* Event snippet for Page view (1) conversion */
      gtag('event', 'conversion', {'send_to': 'AW-17871315689/yTaeCJ2VjJAcEOnF2slC'});
    </script>
    <!-- Google Tag Manager -->
    <script>(function(w,d,s,l,i){w[l]=w[l]||[];w[l].push({'gtm.start':
    new Date().getTime(),event:'gtm.js'});var f=d.getElementsByTagName(s)[0],
    j=d.createElement(s),dl=l!='dataLayer'?'&l='+l:'';j.async=true;j.src=
    'https://www.googletagmanager.com/gtm.js?id='+i+dl;f.parentNode.insertBefore(j,f);
    })(window,document,'script','dataLayer','GTM-5DSZM6R8');</script>
    <!-- End Google Tag Manager -->
    <!-- Microsoft Clarity -->
    <script type="text/javascript">
        (function(c,l,a,r,i,t,y){
            c[a]=c[a]||function(){(c[a].q=c[a].q||[]).push(arguments)};
            t=l.createElement(r);t.async=1;t.src="https://www.clarity.ms/tag/"+i;
            y=l.getElementsByTagName(r)[0];y.parentNode.insertBefore(t,y);
        })(window, document, "clarity", "script", "vck0gjxcsv");
    </script>
    <!-- TikTok Pixel Code Start -->
    <script>
    !function (w, d, t) {
      w.TiktokAnalyticsObject=t;var ttq=w[t]=w[t]||[];ttq.methods=["page","track","identify","instances","debug","on","off","once","ready","alias","group","enableCookie","disableCookie","holdConsent","revokeConsent","grantConsent"],ttq.setAndDefer=function(t,e){t[e]=function(){t.push([e].concat(Array.prototype.slice.call(arguments,0)))}};for(var i=0;i<ttq.methods.length;i++)ttq.setAndDefer(ttq,ttq.methods[i]);ttq.instance=function(t){for(
    var e=ttq._i[t]||[],n=0;n<ttq.methods.length;n++)ttq.setAndDefer(e,ttq.methods[n]);return e},ttq.load=function(e,n){var r="https://analytics.tiktok.com/i18n/pixel/events.js",o=n&&n.partner;ttq._i=ttq._i||{},ttq._i[e]=[],ttq._i[e]._u=r,ttq._t=ttq._t||{},ttq._t[e]=+new Date,ttq._o=ttq._o||{},ttq._o[e]=n||{};n=document.createElement("script")
    ;n.type="text/javascript",n.async=!0,n.src=r+"?sdkid="+e+"&lib="+t;e=document.getElementsByTagName("script")[0];e.parentNode.insertBefore(n,e)};


      ttq.load('D62D9VBC77UC1EV4APJG');
      ttq.page();
    }(window, document, 'ttq');
    </script>
    <!-- TikTok Pixel Code End -->
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="content-language" content="<?= escape($htmlLang) ?>">
    <?php
    $seoDefaults = function_exists('get_seo_defaults') ? get_seo_defaults() : ['meta_title' => 'Forklift & Equipment Pro', 'meta_description' => 'Premium forklifts and industrial equipment for warehouses and factories', 'og_image' => ''];
    $finalTitle = $pageTitle ?? $seoDefaults['meta_title'];
    $finalDescription = $metaDescription ?? $seoDefaults['meta_description'];
    $finalCanonical = $canonicalUrl ?? (function_exists('canonical_url') ? canonical_url() : null);
    if (empty($finalCanonical) && function_exists('url')) {
        $uri = $_SERVER['REQUEST_URI'] ?? '';
        $path = parse_url($uri, PHP_URL_PATH) ?: '/';
        $path = ltrim($path, '/');
        $finalCanonical = $path ? url($path) : rtrim(url(''), '/');
        if ($query = parse_url($uri, PHP_URL_QUERY)) {
            parse_str($query, $params);
            foreach (['utm_source','utm_medium','utm_campaign','utm_term','utm_content','fbclid','gclid','msclkid'] as $k) { unset($params[$k]); }
            if (!empty($params)) $finalCanonical .= '?' . http_build_query($params);
        }
    }
    $finalOgImage = $ogImage ?? $seoDefaults['og_image'];
    $finalSiteName = function_exists('get_site_name') ? get_site_name() : 'Forklift & Equipment Pro';
    ?>
    <meta name="description" content="<?= escape($finalDescription) ?>">
    <title><?= escape($finalTitle) ?></title>
    <?php if (!empty($finalCanonical)): ?>
    <link rel="canonical" href="<?= escape($finalCanonical) ?>">
    <?php endif; ?>
    <meta name="robots" content="<?= !empty($robotsNoIndex) ? 'noindex, nofollow' : 'index, follow' ?>">
    <!-- Open Graph -->
    <meta property="og:type" content="<?= escape($ogType ?? 'website') ?>">
    <meta property="og:title" content="<?= escape($finalTitle) ?>">
    <meta property="og:description" content="<?= escape($finalDescription) ?>">
    <meta property="og:url" content="<?= escape($finalCanonical ?: (function_exists('canonical_url') ? canonical_url() : url(''))) ?>">
    <meta property="og:site_name" content="<?= escape($finalSiteName) ?>">
    <meta property="og:locale" content="<?= escape($htmlLang) ?>">
    <?php if (!empty($finalOgImage)): ?>
    <meta property="og:image" content="<?= escape($finalOgImage) ?>">
    <meta property="og:image:secure_url" content="<?= escape($finalOgImage) ?>">
    <?php endif; ?>
    <!-- Twitter Card -->
    <meta name="twitter:card" content="<?= !empty($finalOgImage) ? 'summary_large_image' : 'summary' ?>">
    <meta name="twitter:title" content="<?= escape($finalTitle) ?>">
    <meta name="twitter:description" content="<?= escape($finalDescription) ?>">
    <?php if (!empty($finalOgImage)): ?>
    <meta name="twitter:image" content="<?= escape($finalOgImage) ?>">
    <?php endif; ?>

    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    
    <!-- Custom Styles -->
    <link rel="stylesheet" href="<?= asset('assets/css/style.css') ?>">
    <link rel="stylesheet" href="<?= asset('assets/css/product-images.css') ?>">
    <link rel="stylesheet" href="<?= asset('assets/css/mobile-bottom-nav.css') ?>">
    <link rel="stylesheet" href="<?= asset('assets/css/category-modern.css') ?>">
    <link rel="stylesheet" href="<?= asset('assets/css/mega-menu.css') ?>">
    <?php if (basename($_SERVER['PHP_SELF']) === 'index.php'): ?>
    <link rel="stylesheet" href="<?= asset('assets/css/hero-slider.css') ?>">
    <link rel="stylesheet" href="<?= asset('assets/css/hero-slider-advanced.css') ?>">
    <?php endif; ?>
    <?php if (basename($_SERVER['PHP_SELF']) === 'products.php'): ?>
    <link rel="stylesheet" href="<?= asset('assets/css/products-responsive.css') ?>">
    <link rel="stylesheet" href="<?= asset('assets/css/app-products.css') ?>">
    <link rel="stylesheet" href="<?= asset('assets/css/advanced-filters.css') ?>">
    <?php endif; ?>
    <?php if (basename($_SERVER['PHP_SELF']) === 'index.php'): ?>
    <link rel="stylesheet" href="<?= asset('assets/css/partners-slider.css') ?>">
    <link rel="stylesheet" href="<?= asset('assets/css/quality-certifications-slider.css') ?>">
    
    <!-- Dynamic Logo Slider Styles - Generated from Admin Settings -->
    <!-- These styles MUST be loaded AFTER external CSS to override them -->
    <?php
    if (isset($logoStyles) && !empty($logoStyles)):
        // Helper function to convert hex color to rgba
        function hexToRgba($hex, $opacity) {
            $hex = str_replace('#', '', $hex);
            if (strlen($hex) != 6) {
                // Handle 3-character hex codes
                if (strlen($hex) == 3) {
                    $hex = $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2];
                } else {
                    return "rgba(0, 0, 0, " . ($opacity / 100) . ")";
                }
            }
            $r = hexdec(substr($hex, 0, 2));
            $g = hexdec(substr($hex, 2, 2));
            $b = hexdec(substr($hex, 4, 2));
            return "rgba($r, $g, $b, " . ($opacity / 100) . ")";
        }
    ?>
    <style id="logo-slider-dynamic-styles">
    /* ===== PARTNERS SECTION STYLES ===== */
    /* Override external CSS to match backend settings exactly */
    section.partners-slider,
    .partners-slider {
        background: linear-gradient(135deg, <?= escape($logoStyles['partners_section_bg_color1'] ?? '#f0f7ff') ?> 0%, <?= escape($logoStyles['partners_section_bg_color2'] ?? '#e0efff') ?> 100%) !important;
        padding: <?= (int)($logoStyles['partners_section_padding'] ?? 80) ?>px 0 !important;
    }
    /* Remove default ::before pseudo-element if needed */
    .partners-slider::before {
        display: none !important;
    }
    .partners-slider-header h2,
    section.partners-slider .partners-slider-header h2,
    section#partners .partners-slider-header h2 {
        background: linear-gradient(135deg, <?= escape($logoStyles['partners_title_color1'] ?? '#1e40af') ?>, <?= escape($logoStyles['partners_title_color2'] ?? '#3b82f6') ?>) !important;
        -webkit-background-clip: text !important;
        -webkit-text-fill-color: transparent !important;
        background-clip: text !important;
        font-size: 2.5rem !important;
        font-weight: 700 !important;
        margin-bottom: 10px !important;
    }
    .partners-slider-header p,
    section.partners-slider .partners-slider-header p {
        color: <?= escape($logoStyles['partners_desc_color'] ?? '#475569') ?> !important;
        font-size: 1.1rem !important;
        font-weight: 500 !important;
    }
    .partners-slider-track,
    section.partners-slider .partners-slider-track {
        gap: <?= (int)($logoStyles['partners_logo_gap'] ?? 40) ?>px !important;
        display: flex !important;
        animation: slide <?= (int)($logoStyles['partners_logo_slide_speed'] ?? 30) ?>s linear infinite !important;
    }
    .partners-slider-item,
    section.partners-slider .partners-slider-item {
        flex-shrink: 0 !important;
        width: <?= (int)($logoStyles['partners_logo_item_width'] ?? 180) ?>px !important;
        height: <?= (int)($logoStyles['partners_logo_item_height'] ?? 100) ?>px !important;
        padding: <?= (int)($logoStyles['partners_logo_padding'] ?? 20) ?>px !important;
        border: <?= (int)($logoStyles['partners_logo_border_width'] ?? 2) ?>px <?= escape($logoStyles['partners_logo_border_style'] ?? 'solid') ?> <?= escape($logoStyles['partners_logo_border_color'] ?? '#3b82f6') ?> !important;
        border-radius: <?= (int)($logoStyles['partners_logo_border_radius'] ?? 12) ?>px !important;
        background-color: <?= escape($logoStyles['partners_logo_bg_color'] ?? '#ffffff') ?> !important;
        box-shadow: <?= (int)($logoStyles['partners_logo_shadow_x'] ?? 0) ?>px <?= (int)($logoStyles['partners_logo_shadow_y'] ?? 2) ?>px <?= (int)($logoStyles['partners_logo_shadow_blur'] ?? 8) ?>px <?= hexToRgba($logoStyles['partners_logo_shadow_color'] ?? '#3b82f6', (int)($logoStyles['partners_logo_shadow_opacity'] ?? 10)) ?> !important;
        transition: all <?= (int)($logoStyles['partners_logo_transition'] ?? 300) ?>ms ease !important;
        display: flex !important;
        align-items: center !important;
        justify-content: center !important;
    }
    .partners-slider-item:hover,
    section.partners-slider .partners-slider-item:hover,
    section#partners .partners-slider-item:hover {
        transform: translateY(<?= (int)($logoStyles['partners_logo_hover_y'] ?? -8) ?>px) scale(<?= escape($logoStyles['partners_logo_hover_scale'] ?? '1.02') ?>) !important;
        border-color: <?= escape($logoStyles['partners_logo_hover_border_color'] ?? '#3b82f6') ?> !important;
        box-shadow: <?= (int)($logoStyles['partners_logo_shadow_x'] ?? 0) ?>px <?= (int)($logoStyles['partners_logo_hover_shadow_y'] ?? 8) ?>px <?= (int)($logoStyles['partners_logo_hover_shadow_blur'] ?? 24) ?>px <?= hexToRgba($logoStyles['partners_logo_shadow_color'] ?? '#3b82f6', (int)($logoStyles['partners_logo_hover_shadow_opacity'] ?? 20)) ?> !important;
    }
    .partners-slider-item img,
    section.partners-slider .partners-slider-item img {
        width: 100% !important;
        height: 100% !important;
        max-width: 100% !important;
        max-height: 100% !important;
        object-fit: <?= escape($logoStyles['partners_logo_object_fit'] ?? 'contain') ?> !important;
        filter: grayscale(<?= (int)($logoStyles['partners_logo_grayscale'] ?? 80) ?>%) opacity(<?= number_format(($logoStyles['partners_logo_image_opacity'] ?? 80) / 100, 2, '.', '') ?>) !important;
        transition: all <?= (int)($logoStyles['partners_logo_transition'] ?? 300) ?>ms ease !important;
    }
    .partners-slider-item:hover img,
    section.partners-slider .partners-slider-item:hover img,
    section#partners .partners-slider-item:hover img {
        filter: grayscale(0%) opacity(1) !important;
        transform: scale(<?= escape($logoStyles['partners_logo_hover_image_scale'] ?? '1.05') ?>) !important;
    }
    
    /* ===== CLIENTS SECTION STYLES ===== */
    /* Override external CSS to match backend settings exactly */
    section.clients-slider,
    .clients-slider {
        background: linear-gradient(135deg, <?= escape($logoStyles['clients_section_bg_color1'] ?? '#f0fdf4') ?> 0%, <?= escape($logoStyles['clients_section_bg_color2'] ?? '#dcfce7') ?> 100%) !important;
        padding: <?= (int)($logoStyles['clients_section_padding'] ?? 80) ?>px 0 !important;
        margin-top: 0 !important;
    }
    /* Remove default ::before pseudo-element if needed */
    .clients-slider::before {
        display: none !important;
    }
    .clients-slider-header h2,
    section.clients-slider .clients-slider-header h2,
    section#clients .clients-slider-header h2 {
        background: linear-gradient(135deg, <?= escape($logoStyles['clients_title_color1'] ?? '#059669') ?>, <?= escape($logoStyles['clients_title_color2'] ?? '#10b981') ?>) !important;
        -webkit-background-clip: text !important;
        -webkit-text-fill-color: transparent !important;
        background-clip: text !important;
        font-size: 2.5rem !important;
        font-weight: 700 !important;
        margin-bottom: 10px !important;
    }
    .clients-slider-header p,
    section.clients-slider .clients-slider-header p {
        color: <?= escape($logoStyles['clients_desc_color'] ?? '#475569') ?> !important;
        font-size: 1.1rem !important;
        font-weight: 500 !important;
    }
    .clients-slider-track,
    section.clients-slider .clients-slider-track {
        gap: <?= (int)($logoStyles['clients_logo_gap'] ?? 40) ?>px !important;
        display: flex !important;
        animation: slide <?= (int)($logoStyles['clients_logo_slide_speed'] ?? 30) ?>s linear infinite !important;
    }
    .clients-slider-item,
    section.clients-slider .clients-slider-item {
        flex-shrink: 0 !important;
        width: <?= (int)($logoStyles['clients_logo_item_width'] ?? 180) ?>px !important;
        height: <?= (int)($logoStyles['clients_logo_item_height'] ?? 100) ?>px !important;
        padding: <?= (int)($logoStyles['clients_logo_padding'] ?? 20) ?>px !important;
        border: <?= (int)($logoStyles['clients_logo_border_width'] ?? 2) ?>px <?= escape($logoStyles['clients_logo_border_style'] ?? 'solid') ?> <?= escape($logoStyles['clients_logo_border_color'] ?? '#10b981') ?> !important;
        border-radius: <?= (int)($logoStyles['clients_logo_border_radius'] ?? 12) ?>px !important;
        background-color: <?= escape($logoStyles['clients_logo_bg_color'] ?? '#ffffff') ?> !important;
        box-shadow: <?= (int)($logoStyles['clients_logo_shadow_x'] ?? 0) ?>px <?= (int)($logoStyles['clients_logo_shadow_y'] ?? 2) ?>px <?= (int)($logoStyles['clients_logo_shadow_blur'] ?? 8) ?>px <?= hexToRgba($logoStyles['clients_logo_shadow_color'] ?? '#10b981', (int)($logoStyles['clients_logo_shadow_opacity'] ?? 10)) ?> !important;
        transition: all <?= (int)($logoStyles['clients_logo_transition'] ?? 300) ?>ms ease !important;
        display: flex !important;
        align-items: center !important;
        justify-content: center !important;
    }
    .clients-slider-item:hover,
    section.clients-slider .clients-slider-item:hover,
    section#clients .clients-slider-item:hover {
        transform: translateY(<?= (int)($logoStyles['clients_logo_hover_y'] ?? -8) ?>px) scale(<?= escape($logoStyles['clients_logo_hover_scale'] ?? '1.02') ?>) !important;
        border-color: <?= escape($logoStyles['clients_logo_hover_border_color'] ?? '#10b981') ?> !important;
        box-shadow: <?= (int)($logoStyles['clients_logo_shadow_x'] ?? 0) ?>px <?= (int)($logoStyles['clients_logo_hover_shadow_y'] ?? 8) ?>px <?= (int)($logoStyles['clients_logo_hover_shadow_blur'] ?? 24) ?>px <?= hexToRgba($logoStyles['clients_logo_shadow_color'] ?? '#10b981', (int)($logoStyles['clients_logo_hover_shadow_opacity'] ?? 20)) ?> !important;
    }
    .clients-slider-item img,
    section.clients-slider .clients-slider-item img {
        width: 100% !important;
        height: 100% !important;
        max-width: 100% !important;
        max-height: 100% !important;
        object-fit: <?= escape($logoStyles['clients_logo_object_fit'] ?? 'contain') ?> !important;
        filter: grayscale(<?= (int)($logoStyles['clients_logo_grayscale'] ?? 80) ?>%) opacity(<?= number_format(($logoStyles['clients_logo_image_opacity'] ?? 80) / 100, 2, '.', '') ?>) !important;
        transition: all <?= (int)($logoStyles['clients_logo_transition'] ?? 300) ?>ms ease !important;
    }
    .clients-slider-item:hover img,
    section.clients-slider .clients-slider-item:hover img,
    section#clients .clients-slider-item:hover img {
        filter: grayscale(0%) opacity(1) !important;
        transform: scale(<?= escape($logoStyles['clients_logo_hover_image_scale'] ?? '1.05') ?>) !important;
    }
    
    /* ===== QUALITY CERTIFICATIONS SECTION STYLES ===== */
    /* Override external CSS to match backend settings exactly */
    section.quality-certifications-slider,
    .quality-certifications-slider {
        background: linear-gradient(to bottom, <?= escape($logoStyles['certs_section_bg_color1'] ?? '#ffffff') ?>, <?= escape($logoStyles['certs_section_bg_color2'] ?? '#f8f9fa') ?>) !important;
        padding: <?= (int)($logoStyles['certs_section_padding'] ?? 60) ?>px 0 !important;
        border-top: none !important;
    }
    .quality-certifications-slider-header h2,
    section.quality-certifications-slider .quality-certifications-slider-header h2 {
        color: <?= escape($logoStyles['certs_title_color'] ?? '#1a1a1a') ?> !important;
        font-size: 2rem !important;
        font-weight: 700 !important;
        margin-bottom: 10px !important;
    }
    .quality-certifications-slider-header p,
    section.quality-certifications-slider .quality-certifications-slider-header p {
        color: <?= escape($logoStyles['certs_desc_color'] ?? '#666666') ?> !important;
        font-size: 1rem !important;
    }
    .quality-certifications-slider-track,
    section.quality-certifications-slider .quality-certifications-slider-track {
        gap: <?= (int)($logoStyles['certs_logo_gap'] ?? 30) ?>px !important;
        display: flex !important;
        animation: slideCertifications <?= (int)($logoStyles['certs_logo_slide_speed'] ?? 25) ?>s linear infinite !important;
    }
    .quality-certifications-slider-item,
    section.quality-certifications-slider .quality-certifications-slider-item {
        flex-shrink: 0 !important;
        width: <?= (int)($logoStyles['certs_logo_item_width'] ?? 160) ?>px !important;
        height: <?= (int)($logoStyles['certs_logo_item_height'] ?? 120) ?>px !important;
        padding: <?= (int)($logoStyles['certs_logo_padding'] ?? 20) ?>px !important;
        border: <?= (int)($logoStyles['certs_logo_border_width'] ?? 1) ?>px <?= escape($logoStyles['certs_logo_border_style'] ?? 'solid') ?> <?= escape($logoStyles['certs_logo_border_color'] ?? '#e5e7eb') ?> !important;
        border-radius: <?= (int)($logoStyles['certs_logo_border_radius'] ?? 12) ?>px !important;
        background-color: <?= escape($logoStyles['certs_logo_bg_color'] ?? '#ffffff') ?> !important;
        box-shadow: <?= (int)($logoStyles['certs_logo_shadow_x'] ?? 0) ?>px <?= (int)($logoStyles['certs_logo_shadow_y'] ?? 2) ?>px <?= (int)($logoStyles['certs_logo_shadow_blur'] ?? 12) ?>px <?= hexToRgba($logoStyles['certs_logo_shadow_color'] ?? '#000000', (int)($logoStyles['certs_logo_shadow_opacity'] ?? 8)) ?> !important;
        transition: all <?= (int)($logoStyles['certs_logo_transition'] ?? 300) ?>ms ease !important;
        display: flex !important;
        flex-direction: column !important;
        align-items: center !important;
        justify-content: center !important;
    }
    .quality-certifications-slider-item:hover,
    section.quality-certifications-slider .quality-certifications-slider-item:hover {
        transform: translateY(<?= (int)($logoStyles['certs_logo_hover_y'] ?? -8) ?>px) scale(<?= escape($logoStyles['certs_logo_hover_scale'] ?? '1.05') ?>) !important;
        border-color: <?= escape($logoStyles['certs_logo_hover_border_color'] ?? '#3b82f6') ?> !important;
        box-shadow: <?= (int)($logoStyles['certs_logo_shadow_x'] ?? 0) ?>px <?= (int)($logoStyles['certs_logo_hover_shadow_y'] ?? 8) ?>px <?= (int)($logoStyles['certs_logo_hover_shadow_blur'] ?? 24) ?>px <?= hexToRgba($logoStyles['certs_logo_shadow_color'] ?? '#000000', (int)($logoStyles['certs_logo_hover_shadow_opacity'] ?? 15)) ?> !important;
    }
    .quality-certifications-slider-item img,
    section.quality-certifications-slider .quality-certifications-slider-item img {
        width: 100% !important;
        max-width: 100% !important;
        max-height: <?= (int)($logoStyles['certs_logo_max_image_height'] ?? 80) ?>px !important;
        object-fit: <?= escape($logoStyles['certs_logo_object_fit'] ?? 'contain') ?> !important;
        transition: all <?= (int)($logoStyles['certs_logo_transition'] ?? 300) ?>ms ease !important;
    }
    .quality-certifications-slider-item:hover img,
    section.quality-certifications-slider .quality-certifications-slider-item:hover img {
        transform: scale(<?= escape($logoStyles['certs_logo_hover_image_scale'] ?? '1.1') ?>) !important;
    }
    .quality-certifications-slider-item .cert-name,
    section.quality-certifications-slider .quality-certifications-slider-item .cert-name {
        color: <?= escape($logoStyles['certs_text_color'] ?? '#6b7280') ?> !important;
        font-size: <?= (int)($logoStyles['certs_text_font_size'] ?? 12) ?>px !important;
        transition: color <?= (int)($logoStyles['certs_logo_transition'] ?? 300) ?>ms ease !important;
        margin-top: 8px !important;
        text-align: center !important;
        font-weight: 500 !important;
    }
    .quality-certifications-slider-item:hover .cert-name,
    section.quality-certifications-slider .quality-certifications-slider-item:hover .cert-name {
        color: <?= escape($logoStyles['certs_text_hover_color'] ?? '#3b82f6') ?> !important;
    }
    
    /* Animation Keyframes - Ensure they're defined */
    @keyframes slide {
        0% {
            transform: translateX(0);
        }
        100% {
            transform: translateX(-50%);
        }
    }
    
    @keyframes slideCertifications {
        0% {
            transform: translateX(0);
        }
        100% {
            transform: translateX(-50%);
        }
    }
    </style>
    <?php endif; ?>
    <?php endif; ?>
    
    <!-- Dynamic Logo Colors -->
    <?php 
    $logoColors = get_logo_colors();
    ?>
    <style>
        :root {
            --logo-primary: <?= escape($logoColors['primary']) ?>;
            --logo-secondary: <?= escape($logoColors['secondary']) ?>;
            --logo-accent: <?= escape($logoColors['accent']) ?>;
            --logo-tertiary: <?= escape($logoColors['tertiary']) ?>;
            --logo-quaternary: <?= escape($logoColors['quaternary']) ?>;
        }
        
        /* Override Tailwind blue colors with logo colors */
        .bg-blue-50 { background-color: color-mix(in srgb, var(--logo-primary) 10%, white) !important; }
        .bg-blue-100 { background-color: color-mix(in srgb, var(--logo-primary) 20%, white) !important; }
        .bg-blue-500 { background-color: var(--logo-accent) !important; }
        .bg-blue-600 { background-color: var(--logo-primary) !important; }
        .bg-blue-700 { background-color: var(--logo-secondary) !important; }
        
        .text-blue-500 { color: var(--logo-accent) !important; }
        .text-blue-600 { color: var(--logo-primary) !important; }
        .text-blue-700 { color: var(--logo-secondary) !important; }
        
        .border-blue-500 { border-color: var(--logo-accent) !important; }
        .border-blue-600 { border-color: var(--logo-primary) !important; }
        
        .hover\:bg-blue-50:hover { background-color: color-mix(in srgb, var(--logo-primary) 10%, white) !important; }
        .hover\:bg-blue-100:hover { background-color: color-mix(in srgb, var(--logo-primary) 20%, white) !important; }
        .hover\:bg-blue-600:hover { background-color: var(--logo-primary) !important; }
        .hover\:bg-blue-700:hover { background-color: var(--logo-secondary) !important; }
        
        .hover\:text-blue-600:hover { color: var(--logo-primary) !important; }
        .hover\:border-blue-500:hover { border-color: var(--logo-accent) !important; }
        .hover\:border-blue-600:hover { border-color: var(--logo-primary) !important; }
        
        /* Gradient overrides */
        .from-blue-50 { --tw-gradient-from: color-mix(in srgb, var(--logo-primary) 10%, white) !important; }
        .from-blue-600 { --tw-gradient-from: var(--logo-primary) !important; }
        .from-blue-700 { --tw-gradient-from: var(--logo-secondary) !important; }
        .via-indigo-600 { --tw-gradient-stops: var(--tw-gradient-from), var(--logo-accent) var(--tw-gradient-via-position), var(--tw-gradient-to) !important; }
        .to-indigo-600 { --tw-gradient-to: var(--logo-accent) !important; }
        .to-indigo-700 { --tw-gradient-to: var(--logo-secondary) !important; }
        .to-purple-600 { --tw-gradient-to: var(--logo-tertiary) !important; }
        .to-purple-700 { --tw-gradient-to: var(--logo-secondary) !important; }
        
        .hover\:from-blue-700:hover { --tw-gradient-from: var(--logo-secondary) !important; }
        .hover\:via-indigo-700:hover { --tw-gradient-stops: var(--tw-gradient-from), var(--logo-secondary) var(--tw-gradient-via-position), var(--tw-gradient-to) !important; }
        .hover\:to-purple-700:hover { --tw-gradient-to: var(--logo-secondary) !important; }
        
        /* Focus states */
        .focus\:ring-blue-500\/20:focus { --tw-ring-color: color-mix(in srgb, var(--logo-primary) 20%, transparent) !important; }
        .focus\:border-blue-500:focus { border-color: var(--logo-accent) !important; }
        .focus\:border-blue-600:focus { border-color: var(--logo-primary) !important; }
        .group-focus-within\:text-blue-600.group:focus-within { color: var(--logo-primary) !important; }
        
        /* Shadow colors */
        .shadow-blue-500\/50 { box-shadow: 0 10px 15px -3px color-mix(in srgb, var(--logo-primary) 50%, transparent), 0 4px 6px -4px color-mix(in srgb, var(--logo-primary) 50%, transparent) !important; }
        
        /* Apply logo colors to key elements */
        .btn-primary,
        .btn-primary-sm {
            background-color: var(--logo-primary) !important;
            color: white !important;
            border-color: var(--logo-primary) !important;
        }
        
        .btn-primary:hover,
        .btn-primary-sm:hover {
            background-color: var(--logo-secondary) !important;
            border-color: var(--logo-secondary) !important;
        }
        
        .gradient-primary {
            background: linear-gradient(135deg, var(--logo-primary) 0%, var(--logo-secondary) 100%);
        }
        
        .text-primary {
            color: var(--logo-primary) !important;
        }
        
        .border-primary {
            border-color: var(--logo-primary) !important;
        }
        
        /* Navigation active state */
        .nav-link-ultra.active,
        .nav-link-ultra:hover {
            color: var(--logo-primary) !important;
        }
        
        /* Ensure menu items stay on one line */
        .nav-link-ultra {
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        
        .nav-action-btn {
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        
        /* Ultra Modern Navigation Link Styles */
        .nav-link-ultra {
            background: rgba(255, 255, 255, 0.5);
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            border: 1px solid rgba(255, 255, 255, 0.3);
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }
        
        .nav-link-ultra:hover {
            background: rgba(255, 255, 255, 0.85);
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
            transform: translateY(-2px);
            box-shadow: 0 8px 24px rgba(59, 130, 246, 0.2);
            border-color: rgba(59, 130, 246, 0.4);
        }
        
        /* Menu Icon Alignment - Ensure all icons align with text */
        .nav-link-ultra i,
        .menu-item i,
        .nav-dropdown i,
        .mega-menu-item i,
        .mega-menu-widget i {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            line-height: 1;
            vertical-align: middle;
        }
        
        /* Ensure flex containers align icons properly */
        .nav-link-ultra.flex.items-center i,
        .menu-item.flex.items-center i,
        a.flex.items-center i,
        button.flex.items-center i {
            flex-shrink: 0;
            line-height: 1;
        }
        
        .nav-link-ultra.active {
            background: linear-gradient(135deg, rgba(59, 130, 246, 0.15), rgba(79, 70, 229, 0.15));
            border-color: rgba(59, 130, 246, 0.5);
            box-shadow: 0 4px 16px rgba(59, 130, 246, 0.25);
        }
        
        .nav-link-ultra .nav-link-indicator {
            background: linear-gradient(90deg, var(--logo-primary, #2563eb), var(--logo-accent, #4f46e5), var(--logo-tertiary, #7c3aed));
            height: 3px;
            border-radius: 2px;
        }
        
        .nav-link-ultra:hover .nav-link-indicator {
            width: 80%;
            opacity: 1;
        }
        
        /* Buttons */
        button.btn-primary,
        a.btn-primary {
            background: linear-gradient(135deg, var(--logo-primary) 0%, var(--logo-accent) 100%);
        }
        
        button.btn-primary:hover,
        a.btn-primary:hover {
            background: linear-gradient(135deg, var(--logo-secondary) 0%, var(--logo-primary) 100%);
        }
        
        /* Logo icon gradient */
        .bg-gradient-to-br.from-blue-600 {
            background: linear-gradient(to bottom right, var(--logo-primary), var(--logo-accent), var(--logo-tertiary)) !important;
        }
        
        /* Site name gradient - Removed as requested */
        
        /* Mobile menu items */
        .mobile-menu-item-ultra:hover {
            background: linear-gradient(to right, color-mix(in srgb, var(--logo-primary) 10%, white), color-mix(in srgb, var(--logo-accent) 10%, white)) !important;
        }
        
        /* Modern Navigation Bar - Premium Design */
        #main-nav {
            background: rgba(255, 255, 255, 0.99);
            backdrop-filter: blur(16px) saturate(200%);
            -webkit-backdrop-filter: blur(16px) saturate(200%);
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.03), 
                        0 2px 4px -1px rgba(0, 0, 0, 0.02),
                        0 0 0 1px rgba(0, 0, 0, 0.02);
            border-bottom: 1px solid rgba(229, 231, 235, 0.4);
        }
        
        /* Navigation container improvements */
        #main-nav .container {
            max-width: 1400px;
        }
        
        /* Smooth scroll behavior */
        @media (prefers-reduced-motion: no-preference) {
            #main-nav {
                transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            }
        }
        
        /* Enhanced Navigation Links - Ultra Modern */
        .nav-link-modern {
            position: relative;
            color: #4b5563;
            font-weight: 500;
            font-size: 0.9375rem;
            padding: 0.75rem 1.25rem;
            border-radius: 0.75rem;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            display: inline-flex;
            align-items: center;
            gap: 0.625rem;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            background: transparent;
            border: 1px solid transparent;
        }
        
        .nav-link-modern::before {
            content: '';
            position: absolute;
            inset: 0;
            border-radius: 0.75rem;
            padding: 1px;
            background: linear-gradient(135deg, var(--logo-primary, #2563eb), var(--logo-accent, #4f46e5));
            -webkit-mask: linear-gradient(#fff 0 0) content-box, linear-gradient(#fff 0 0);
            -webkit-mask-composite: xor;
            mask-composite: exclude;
            opacity: 0;
            transition: opacity 0.3s ease;
        }
        
        .nav-link-modern:hover {
            color: var(--logo-primary, #2563eb);
            background: linear-gradient(135deg, rgba(59, 130, 246, 0.08), rgba(79, 70, 229, 0.08));
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(59, 130, 246, 0.15);
        }
        
        .nav-link-modern:hover::before {
            opacity: 1;
        }
        
        .nav-link-modern.active {
            color: var(--logo-primary, #2563eb);
            background: linear-gradient(135deg, rgba(59, 130, 246, 0.12), rgba(79, 70, 229, 0.12));
            font-weight: 600;
            box-shadow: 0 2px 8px rgba(59, 130, 246, 0.2);
        }
        
        .nav-link-modern.active::before {
            opacity: 1;
        }
        
        .nav-link-modern i {
            transition: transform 0.3s ease;
        }
        
        .nav-link-modern:hover i {
            transform: scale(1.1);
        }
        
        /* Modern Action Buttons - Enhanced */
        .nav-action-btn {
            position: relative;
            padding: 0.75rem 1.25rem;
            border-radius: 0.75rem;
            font-weight: 500;
            font-size: 0.9375rem;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            display: inline-flex;
            align-items: center;
            gap: 0.625rem;
            background: rgba(255, 255, 255, 0.6);
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
            border: 1px solid rgba(229, 231, 235, 0.5);
        }
        
        .nav-action-btn:hover {
            transform: translateY(-2px) scale(1.02);
            box-shadow: 0 6px 20px rgba(0, 0, 0, 0.1);
            background: rgba(255, 255, 255, 0.9);
            border-color: rgba(59, 130, 246, 0.3);
        }
        
        .nav-action-btn i {
            transition: transform 0.3s ease;
        }
        
        .nav-action-btn:hover i {
            transform: scale(1.15) rotate(5deg);
        }
        
        /* Badge styling */
        .nav-badge {
            position: absolute;
            top: -0.25rem;
            right: -0.25rem;
            min-width: 1.25rem;
            height: 1.25rem;
            padding: 0 0.375rem;
            border-radius: 0.625rem;
            font-size: 0.75rem;
            font-weight: 700;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.15);
            border: 2px solid white;
        }
        
        /* Enhanced Dropdown Menus - Ultra Modern */
        .nav-dropdown {
            position: absolute;
            top: calc(100% + 1rem);
            left: 0;
            min-width: 22rem;
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(24px) saturate(180%);
            -webkit-backdrop-filter: blur(24px) saturate(180%);
            border-radius: 1.25rem;
            box-shadow: 0 20px 40px -12px rgba(0, 0, 0, 0.15),
                        0 8px 16px -4px rgba(0, 0, 0, 0.1),
                        0 0 0 1px rgba(255, 255, 255, 0.5);
            border: 1px solid rgba(255, 255, 255, 0.8);
            opacity: 0;
            visibility: hidden;
            transform: translateY(-0.75rem) scale(0.95);
            transition: all 0.4s cubic-bezier(0.34, 1.56, 0.64, 1);
            z-index: 50;
            overflow: hidden;
        }
        
        .nav-dropdown::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 3px;
            background: linear-gradient(90deg, var(--logo-primary, #2563eb), var(--logo-accent, #4f46e5), var(--logo-tertiary, #7c3aed));
            opacity: 0;
            transition: opacity 0.3s ease;
        }
        
        .nav-dropdown-group:hover .nav-dropdown {
            opacity: 1;
            visibility: visible;
            transform: translateY(0) scale(1);
        }
        
        .nav-dropdown-group:hover .nav-dropdown::before {
            opacity: 1;
        }
        
        .nav-dropdown a {
            position: relative;
            overflow: hidden;
        }
        
        .nav-dropdown a::before {
            content: '';
            position: absolute;
            left: 0;
            top: 0;
            bottom: 0;
            width: 0;
            background: linear-gradient(90deg, rgba(59, 130, 246, 0.1), rgba(79, 70, 229, 0.1));
            transition: width 0.3s ease;
        }
        
        .nav-dropdown a:hover::before {
            width: 100%;
        }
        
    </style>
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <?php
    // JSON-LD: page-specific (e.g. Product) or default Organization
    if (!empty($jsonLd) && is_array($jsonLd)) {
        echo '<script type="application/ld+json">' . "\n" . json_encode($jsonLd, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . "\n" . '</script>';
    } elseif (strpos($_SERVER['PHP_SELF'] ?? '', '/admin') === false && function_exists('get_site_name')) {
        $orgUrl = rtrim(config('app.url', ''), '/');
        $orgSchema = [
            '@context' => 'https://schema.org',
            '@type' => 'Organization',
            'name' => get_site_name(),
            'url' => $orgUrl,
        ];
        if (function_exists('get_seo_defaults')) {
            $def = get_seo_defaults();
            if (!empty($def['og_image'])) {
                $orgSchema['logo'] = $def['og_image'];
            }
        }
        echo '<script type="application/ld+json">' . "\n" . json_encode($orgSchema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . "\n" . '</script>';
    }
    ?>

</head>
<body class="bg-white">
    <!-- Google Tag Manager (noscript) -->
    <noscript><iframe src="https://www.googletagmanager.com/ns.html?id=GTM-5DSZM6R8"
    height="0" width="0" style="display:none;visibility:hidden"></iframe></noscript>
    <!-- End Google Tag Manager (noscript) -->

    <!-- Premium Modern Navigation -->
    <nav class="sticky top-0 z-50" id="main-nav">
        <div class="container mx-auto px-4 lg:px-6 xl:px-8">
            <div class="flex items-center justify-between h-20 lg:h-24 gap-2">
                <!-- Logo Section -->
                <?php
                // Get site logo from settings
                $logoSetting = db()->fetchOne("SELECT value FROM settings WHERE `key` = 'site_logo'");
                $siteLogo = $logoSetting ? $logoSetting['value'] : null;
                $siteName = db()->fetchOne("SELECT value FROM settings WHERE `key` = 'site_name'");
                $siteNameText = $siteName ? $siteName['value'] : 'ForkliftPro';
                
                // Get logo size settings
                $logoHeightMobile = db()->fetchOne("SELECT value FROM settings WHERE `key` = 'logo_height_mobile'");
                $logoHeightTablet = db()->fetchOne("SELECT value FROM settings WHERE `key` = 'logo_height_tablet'");
                $logoHeightDesktop = db()->fetchOne("SELECT value FROM settings WHERE `key` = 'logo_height_desktop'");
                $logoMaxWidth = db()->fetchOne("SELECT value FROM settings WHERE `key` = 'logo_max_width'");
                
                $logoHeightMobile = $logoHeightMobile ? (int)$logoHeightMobile['value'] : 40;
                $logoHeightTablet = $logoHeightTablet ? (int)$logoHeightTablet['value'] : 56;
                $logoHeightDesktop = $logoHeightDesktop ? (int)$logoHeightDesktop['value'] : 64;
                $logoMaxWidth = $logoMaxWidth && !empty($logoMaxWidth['value']) ? (int)$logoMaxWidth['value'] : null;
                ?>
                <!-- Logo Section - Enhanced -->
                <a href="<?= url() ?>" class="flex items-center gap-3 group flex-shrink-0 z-10">
                    <?php if ($siteLogo): ?>
                        <style>
                            #site-logo {
                                height: <?= escape($logoHeightMobile) ?>px;
                                <?= $logoMaxWidth ? 'max-width: ' . escape($logoMaxWidth) . 'px;' : '' ?>
                                width: auto;
                                object-fit: contain;
                            }
                            @media (min-width: 768px) {
                                #site-logo {
                                    height: <?= escape($logoHeightTablet) ?>px;
                                }
                            }
                            @media (min-width: 1024px) {
                                #site-logo {
                                    height: <?= escape($logoHeightDesktop) ?>px;
                                }
                            }
                        </style>
                        <img src="<?= escape(image_url($siteLogo)) ?>" 
                             alt="<?= escape($siteNameText) ?>" 
                             id="site-logo"
                             class="object-contain transform group-hover:scale-105 transition-transform duration-300">
                    <?php else: ?>
                        <div class="bg-gradient-to-br from-blue-600 via-indigo-600 to-purple-600 p-2.5 md:p-3 rounded-xl transform group-hover:scale-105 group-hover:rotate-2 transition-all duration-300 shadow-md group-hover:shadow-lg">
                            <i class="fas fa-industry text-white text-lg md:text-xl lg:text-2xl"></i>
                        </div>
                    <?php endif; ?>
                    <!-- Site Name - Visible on Mobile -->
                    <span class="xl:hidden font-bold text-base md:text-lg text-gray-800">
                        <?= escape($siteNameText) ?>
                    </span>
                </a>
                
                
                <!-- Desktop Navigation -->
                <?php
                // Use new menu system
                try {
                    require_once __DIR__ . '/../app/Helpers/MenuHelper.php';
                    $headerMenu = \App\Helpers\get_menu_by_location('header');
                    
                    if ($headerMenu && !empty($headerMenu['id'])):
                        echo \App\Helpers\render_menu($headerMenu['id'], ['location' => 'header']);
                    else:
                        // Fallback to old menu
                ?>
                    <div class="hidden xl:flex items-center space-x-1 ml-auto">
                        <a href="<?= url() ?>" class="nav-link-ultra px-4 py-2.5 rounded-xl transition-all duration-300 group relative" style="white-space: nowrap;">
                            <i class="fas fa-home mr-2"></i>Home
                            <span class="nav-link-indicator"></span>
                        </a>
                        
                        <!-- Products Mega Menu -->
                        <div class="nav-dropdown-group relative group" id="products-dropdown">
                            <button class="nav-link-ultra px-4 py-2.5 rounded-xl transition-all duration-300 group relative flex items-center" style="white-space: nowrap;">
                                <i class="fas fa-box mr-2 flex-shrink-0" style="line-height: 1; vertical-align: middle;"></i>
                                <span style="line-height: 1.5;">Products</span>
                                <i class="fas fa-chevron-down ml-2 text-xs transform group-hover:rotate-180 transition-transform duration-300 flex-shrink-0" style="line-height: 1;"></i>
                                <span class="nav-link-indicator"></span>
                            </button>
                            <div class="nav-dropdown w-[600px] overflow-hidden">
                                <div class="absolute top-0 left-0 right-0 h-1 bg-gradient-to-r from-blue-500 via-indigo-500 to-purple-500"></div>
                                <div class="p-4 bg-gradient-to-r from-blue-50/70 to-indigo-50/70 border-b border-gray-100/50 relative">
                                    <h3 class="text-xs font-bold text-gray-700 uppercase tracking-wider flex items-center gap-2">
                                        <i class="fas fa-th-large text-blue-500"></i>
                                        Browse Categories
                                    </h3>
                                </div>
                                <div class="p-4 max-h-[500px] overflow-y-auto">
                                    <?php
                                    // Check if we should use selected categories
                                    $useSelectedCategories = false;
                                    $selectedCategoryIds = [];
                                    try {
                                        // Check if table exists first
                                        $tableExists = false;
                                        try {
                                            db()->fetchOne("SELECT 1 FROM menu_category_selections LIMIT 1");
                                            $tableExists = true;
                                        } catch (\Exception $e) {
                                            $tableExists = false;
                                        }
                                        
                                        if ($tableExists) {
                                            $setting = db()->fetchOne(
                                                "SELECT value FROM settings WHERE `key` = 'products_menu_use_selected_categories'"
                                            );
                                            $useSelectedCategories = !empty($setting) && $setting['value'] == '1';
                                            
                                            if ($useSelectedCategories) {
                                                $selected = db()->fetchAll(
                                                    "SELECT category_id FROM menu_category_selections 
                                                     WHERE menu_item_id IS NULL AND is_active = 1 
                                                     ORDER BY display_order ASC"
                                                );
                                                $selectedCategoryIds = array_column($selected, 'category_id');
                                                
                                                // Filter categories to only show selected ones
                                                if (!empty($selectedCategoryIds)) {
                                                    $navCategories = array_filter($navCategories, function($cat) use ($selectedCategoryIds) {
                                                        return in_array($cat['id'], $selectedCategoryIds);
                                                    });
                                                    // Reorder by display order
                                                    usort($navCategories, function($a, $b) use ($selectedCategoryIds) {
                                                        $posA = array_search($a['id'], $selectedCategoryIds);
                                                        $posB = array_search($b['id'], $selectedCategoryIds);
                                                        return $posA <=> $posB;
                                                    });
                                                } else {
                                                    $navCategories = []; // No categories selected
                                                }
                                            }
                                        }
                                    } catch (\Exception $e) {
                                        // Fallback to showing all categories
                                        error_log('Menu categories selection error: ' . $e->getMessage());
                                    }
                                    ?>
                                    <?php if (!empty($navCategories)): ?>
                                        <div class="grid grid-cols-2 gap-2">
                                            <?php foreach ($navCategories as $cat): ?>
                                            <a href="<?= url('products.php?category=' . escape($cat['slug'])) ?>" 
                                               class="group/item block px-4 py-3 rounded-xl hover:bg-gradient-to-r hover:from-blue-50/80 hover:via-indigo-50/80 hover:to-purple-50/80 transition-all duration-300 border-l-4 border-transparent hover:border-blue-500 hover:shadow-lg relative overflow-hidden">
                                                <div class="absolute inset-0 bg-gradient-to-r from-blue-500/0 via-indigo-500/0 to-purple-500/0 group-hover/item:from-blue-500/5 group-hover/item:via-indigo-500/5 group-hover/item:to-purple-500/5 transition-all duration-300"></div>
                                                <div class="flex items-center justify-between relative z-10">
                                                    <span class="text-sm font-semibold text-gray-700 group-hover/item:text-blue-600 transition-colors"><?= escape($cat['name']) ?></span>
                                                    <i class="fas fa-arrow-right text-xs text-gray-400 group-hover/item:text-blue-600 transform group-hover/item:translate-x-2 transition-all duration-300"></i>
                                                </div>
                                            </a>
                                            <?php endforeach; ?>
                                        </div>
                                    <?php endif; ?>
                                    <div class="mt-4 pt-4 border-t border-gray-200/50">
                                        <a href="<?= url('products.php') ?>" class="block px-4 py-3 rounded-xl bg-gradient-to-r from-blue-600 via-indigo-600 to-purple-600 hover:from-blue-700 hover:via-indigo-700 hover:to-purple-700 text-white transition-all duration-300 font-semibold text-sm text-center shadow-lg hover:shadow-xl transform hover:scale-105">
                                            <i class="fas fa-th mr-2"></i>View All Products
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Quick Actions -->
                        <a href="<?= url('compare.php') ?>" class="nav-action-btn relative text-gray-600 hover:text-blue-600" style="white-space: nowrap;">
                            <i class="fas fa-balance-scale"></i>
                            <span class="hidden 2xl:inline">Compare</span>
                            <span id="compare-count" class="nav-badge hidden bg-gradient-to-r from-blue-500 to-indigo-500 text-white">0</span>
                        </a>
                        
                        <a href="<?= url('wishlist.php') ?>" class="nav-action-btn relative text-gray-600 hover:text-red-600" style="white-space: nowrap;">
                            <i class="fas fa-heart"></i>
                            <span class="hidden 2xl:inline">Wishlist</span>
                            <span id="wishlist-count" class="nav-badge hidden bg-gradient-to-r from-red-500 to-pink-500 text-white">0</span>
                        </a>
                        
                        <a href="<?= url('cart.php') ?>" class="nav-action-btn relative text-gray-600 hover:text-green-600" style="white-space: nowrap;">
                            <i class="fas fa-shopping-cart"></i>
                            <span class="hidden 2xl:inline">Cart</span>
                            <span id="cart-count" class="nav-badge hidden bg-gradient-to-r from-green-500 to-emerald-500 text-white">0</span>
                        </a>
                        
                        <a href="<?= url('contact.php') ?>" class="nav-link-modern" style="white-space: nowrap;">
                            <i class="fas fa-envelope"></i>
                            <span>Contact</span>
                        </a>
                    </div>
                <?php 
                    endif;
                } catch (\Exception $e) {
                    // Fallback to old menu on error
                ?>
                    <div class="hidden xl:flex items-center gap-0.5">
                        <a href="<?= url() ?>" class="nav-link-modern" style="white-space: nowrap;">
                            <i class="fas fa-home"></i>
                            <span>Home</span>
                        </a>
                        <a href="<?= url('products.php') ?>" class="nav-link-modern" style="white-space: nowrap;">
                            <i class="fas fa-box"></i>
                            <span>Products</span>
                        </a>
                        <a href="<?= url('contact.php') ?>" class="nav-link-modern" style="white-space: nowrap;">
                            <i class="fas fa-envelope"></i>
                            <span>Contact</span>
                        </a>
                    </div>
                <?php } ?>
                
                <!-- Right Section: Language, Hotline & Account -->
                <div class="ml-auto flex items-center gap-2 lg:gap-3">
                    <!-- Language Switcher -->
                    <?php include __DIR__ . '/language-switcher.php'; ?>
                    
                    <!-- Hotline -->
                    <?php
                    // Get hotline from settings - prioritize 'hotline' key, fallback to 'site_phone'
                    $hotlineSetting = db()->fetchOne("SELECT value FROM settings WHERE `key` = 'hotline'");
                    if (!$hotlineSetting || empty($hotlineSetting['value'])) {
                        $hotlineSetting = db()->fetchOne("SELECT value FROM settings WHERE `key` = 'site_phone'");
                    }
                    $hotline = ($hotlineSetting && !empty($hotlineSetting['value'])) ? $hotlineSetting['value'] : '012 345 678';
                    ?>
                    <a href="tel:<?= preg_replace('/[^0-9+]/', '', $hotline) ?>" 
                       class="hidden lg:flex items-center gap-2 px-3 py-2 bg-gradient-to-r from-red-500 to-red-600 hover:from-red-600 hover:to-red-700 text-white rounded-lg shadow-md hover:shadow-lg transition-all duration-200 group"
                       title="Call Hotline: <?= escape($hotline) ?>">
                        <div class="bg-white/20 rounded-full p-1.5 group-hover:bg-white/30 transition-colors">
                            <i class="fas fa-phone-alt text-sm"></i>
                        </div>
                        <div class="flex flex-col">
                            <span class="text-xs font-medium opacity-90">Hot Line</span>
                            <span class="text-sm font-bold leading-tight"><?= escape($hotline) ?></span>
                        </div>
                    </a>
                    
                    <!-- Mobile Hotline Icon -->
                    <a href="tel:<?= preg_replace('/[^0-9+]/', '', $hotline) ?>" 
                       class="lg:hidden p-2.5 bg-red-500 hover:bg-red-600 text-white rounded-lg shadow-md transition-all duration-200"
                       title="Call Hotline: <?= escape($hotline) ?>">
                        <i class="fas fa-phone-alt"></i>
                    </a>
                    
                    <?php if (isset($_SESSION['customer_id'])): ?>
                        <div class="nav-dropdown-group relative" id="account-dropdown">
                            <button class="nav-link-modern" style="white-space: nowrap;">
                                <i class="fas fa-user-circle"></i>
                                <span class="hidden xl:inline">Account</span>
                                <i class="fas fa-chevron-down text-xs transform group-hover:rotate-180 transition-transform duration-300"></i>
                            </button>
                            <div class="nav-dropdown right-0 left-auto w-56">
                                <div class="p-2">
                                    <a href="<?= url('account.php') ?>" class="group/item block px-4 py-2.5 rounded-lg hover:bg-blue-50/50 transition-all duration-200 border-l-2 border-transparent hover:border-blue-500 mb-0.5">
                                        <i class="fas fa-user mr-3 text-blue-600 text-sm"></i><span class="text-sm font-medium text-gray-700 group-hover/item:text-blue-600">My Account</span>
                                    </a>
                                    <a href="<?= url('logout.php') ?>" class="group/item block px-4 py-2.5 rounded-lg hover:bg-red-50/50 transition-all duration-200 border-l-2 border-transparent hover:border-red-500">
                                        <i class="fas fa-sign-out-alt mr-3 text-red-600 text-sm"></i><span class="text-sm font-medium text-red-600">Logout</span>
                                    </a>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </nav>
    
    <!-- Mobile Bottom Navigation - OUTSIDE main nav to position at bottom -->
    <div id="mobile-bottom-nav" class="xl:hidden" style="position: fixed !important; bottom: 0 !important; left: 0 !important; right: 0 !important; top: auto !important; z-index: 9999 !important; width: 100% !important;">
        <nav class="mobile-bottom-nav-container">
            <?php
            // Define menu items with icons
            $menuItems = [
                ['url' => url(), 'icon' => 'fa-home', 'label' => 'Home', 'color' => 'blue'],
                ['url' => url('products.php'), 'icon' => 'fa-box', 'label' => 'Products', 'color' => 'indigo'],
                ['url' => url('cart.php'), 'icon' => 'fa-shopping-cart', 'label' => 'Cart', 'color' => 'green', 'badge' => 'cart-count'],
                ['url' => url('wishlist.php'), 'icon' => 'fa-heart', 'label' => 'Wishlist', 'color' => 'red'],
            ];
            
            // Additional menu items for "More" popup
            $moreMenuItems = [
                ['url' => url('compare.php'), 'icon' => 'fa-balance-scale', 'label' => 'Compare', 'color' => 'blue', 'badge' => 'compare-count'],
                ['url' => url('contact.php'), 'icon' => 'fa-envelope', 'label' => 'Contact', 'color' => 'purple'],
                ['url' => url('quote.php'), 'icon' => 'fa-calculator', 'label' => 'Get Quote', 'color' => 'blue'],
            ];
            
            // Add account items based on login status
            if (isset($_SESSION['customer_id'])) {
                $moreMenuItems[] = ['url' => url('account.php'), 'icon' => 'fa-user', 'label' => 'Account', 'color' => 'indigo'];
                $moreMenuItems[] = ['url' => url('logout.php'), 'icon' => 'fa-sign-out-alt', 'label' => 'Logout', 'color' => 'red'];
            } else {
                $moreMenuItems[] = ['url' => url('login.php'), 'icon' => 'fa-sign-in-alt', 'label' => 'Login', 'color' => 'blue'];
                $moreMenuItems[] = ['url' => url('register.php'), 'icon' => 'fa-user-plus', 'label' => 'Sign Up', 'color' => 'indigo'];
            }
            
            // Show first 4 items in bottom nav
            foreach (array_slice($menuItems, 0, 4) as $item):
                $currentUrl = parse_url($item['url'], PHP_URL_PATH);
                $currentPage = basename($_SERVER['PHP_SELF']);
                $itemPage = basename($currentUrl);
                $isActive = ($currentPage === $itemPage) || 
                            ($item['url'] === url() && $currentPage === 'index.php') ||
                            (strpos($item['url'], 'products.php') !== false && $currentPage === 'products.php');
            ?>
                <a href="<?= $item['url'] ?>" 
                   class="mobile-bottom-nav-item <?= $isActive ? 'active' : '' ?>"
                   data-color="<?= $item['color'] ?>">
                    <div class="mobile-bottom-nav-icon">
                        <i class="fas <?= $item['icon'] ?>"></i>
                        <?php if (isset($item['badge'])): ?>
                            <span class="mobile-bottom-nav-badge" id="<?= $item['badge'] ?>-mobile">0</span>
                        <?php endif; ?>
                    </div>
                    <span class="mobile-bottom-nav-label"><?= $item['label'] ?></span>
                </a>
            <?php endforeach; ?>
            
            <!-- More Button (if there are more items) -->
            <?php if (count($moreMenuItems) > 0): ?>
                <button onclick="toggleMobileMoreMenu()" 
                        class="mobile-bottom-nav-item mobile-bottom-nav-more"
                        id="mobile-more-btn">
                    <div class="mobile-bottom-nav-icon">
                        <i class="fas fa-ellipsis-h"></i>
                    </div>
                    <span class="mobile-bottom-nav-label">More</span>
                </button>
            <?php endif; ?>
        </nav>
    </div>
    
    <!-- Mobile More Menu Popup -->
    <div id="mobile-more-menu" class="mobile-more-menu-overlay hidden" onclick="if(event.target === this) toggleMobileMoreMenu()">
        <div class="mobile-more-menu-backdrop"></div>
        <div class="mobile-more-menu-content" onclick="event.stopPropagation()">
            <div class="mobile-more-menu-header">
                <h3 class="mobile-more-menu-title">Menu</h3>
                <button onclick="toggleMobileMoreMenu()" class="mobile-more-menu-close">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="mobile-more-menu-grid">
                <?php foreach ($moreMenuItems as $item): ?>
                    <a href="<?= $item['url'] ?>" 
                       class="mobile-more-menu-item"
                       data-color="<?= $item['color'] ?>"
                       onclick="toggleMobileMoreMenu()">
                        <div class="mobile-more-menu-icon">
                            <i class="fas <?= $item['icon'] ?>"></i>
                            <?php if (isset($item['badge'])): ?>
                                <span class="mobile-more-menu-badge" id="<?= $item['badge'] ?>-more">0</span>
                            <?php endif; ?>
                        </div>
                        <span class="mobile-more-menu-label"><?= $item['label'] ?></span>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

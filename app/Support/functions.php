<?php

if (!function_exists('config')) {
    function config($key, $default = null)
    {
        static $configs = [];
        $parts = explode('.', $key);
        $file = array_shift($parts);
        
        if (!isset($configs[$file])) {
            $path = __DIR__ . "/../../config/{$file}.php";
            $configs[$file] = file_exists($path) ? require $path : [];
        }
        
        $value = $configs[$file];
        foreach ($parts as $part) {
            $value = $value[$part] ?? null;
        }
        
        return $value ?? $default;
    }
}

if (!function_exists('db')) {
    function db()
    {
        return \App\Database\Connection::getInstance();
    }
}

if (!function_exists('asset')) {
    function asset($path)
    {
        // Use url() function which handles base path correctly
        return url($path);
    }
}

if (!function_exists('url')) {
    function url($path = '')
    {
        // Try to get base URL from config (this should be the project root)
        $baseUrl = config('app.url', null);
        
        // If not in config, auto-detect from current request
        // Use the same logic as detectBasePath() in config
        if (empty($baseUrl)) {
            $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https://' : 'http://';
            $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
            
            // Get the project root directory
            $configFile = __DIR__ . '/../../config/app.php';
            $configDir = dirname($configFile);
            $projectRoot = dirname($configDir);
            
            // Get the document root
            $docRoot = $_SERVER['DOCUMENT_ROOT'] ?? '';
            
            // Normalize paths
            $projectPath = str_replace('\\', '/', $projectRoot);
            $docRootPath = str_replace('\\', '/', $docRoot);
            
            $basePath = '';
            if (!empty($docRoot) && strpos($projectPath, $docRootPath) === 0) {
                $relativePath = substr($projectPath, strlen($docRootPath));
                $basePath = rtrim($relativePath, '/');
            }
            
            if ($basePath === '' || $basePath === '.') {
                $basePath = '';
            }
            
            $baseUrl = $protocol . $host . $basePath;
        }
        
        // If path is empty, return base URL without trailing slash
        if (empty($path)) {
            return rtrim($baseUrl, '/');
        }
        
        return rtrim($baseUrl, '/') . '/' . ltrim($path, '/');
    }
}

if (!function_exists('image_url')) {
    /**
     * Convert image path to full URL
     * Handles relative paths, absolute paths, and full URLs
     */
    function image_url($path)
    {
        if (empty($path)) {
            return '';
        }
        
        // If it's already a full URL (http:// or https://), return as is
        if (preg_match('/^https?:\/\//', $path)) {
            return $path;
        }
        
        // If it starts with /, it's an absolute path from root
        if (strpos($path, '/') === 0) {
            return url(ltrim($path, '/'));
        }
        
        // Otherwise, treat as relative path from root
        return url($path);
    }
}

if (!function_exists('escape')) {
    /**
     * Get logo color palette
     * 
     * @return array Color palette array
     */
    function get_logo_colors()
    {
        static $colors = null;
        
        if ($colors === null) {
            try {
                $paletteJson = db()->fetchOne("SELECT value FROM settings WHERE `key` = 'logo_color_palette'");
                
                if ($paletteJson && !empty($paletteJson['value'])) {
                    $colors = json_decode($paletteJson['value'], true);
                } else {
                    // Fallback to individual color settings
                    $primary = db()->fetchOne("SELECT value FROM settings WHERE `key` = 'logo_primary_color'");
                    if ($primary) {
                        $colors = [
                            'primary' => $primary['value'] ?? '#2563eb',
                            'secondary' => db()->fetchOne("SELECT value FROM settings WHERE `key` = 'logo_secondary_color'")['value'] ?? '#1e40af',
                            'accent' => db()->fetchOne("SELECT value FROM settings WHERE `key` = 'logo_accent_color'")['value'] ?? '#3b82f6',
                            'tertiary' => db()->fetchOne("SELECT value FROM settings WHERE `key` = 'logo_tertiary_color'")['value'] ?? '#60a5fa',
                            'quaternary' => db()->fetchOne("SELECT value FROM settings WHERE `key` = 'logo_quaternary_color'")['value'] ?? '#93c5fd',
                        ];
                    } else {
                        // Default colors
                        $colors = [
                            'primary' => '#2563eb',
                            'secondary' => '#1e40af',
                            'accent' => '#3b82f6',
                            'tertiary' => '#60a5fa',
                            'quaternary' => '#93c5fd',
                        ];
                    }
                }
            } catch (Exception $e) {
                // Default colors on error
                $colors = [
                    'primary' => '#2563eb',
                    'secondary' => '#1e40af',
                    'accent' => '#3b82f6',
                    'tertiary' => '#60a5fa',
                    'quaternary' => '#93c5fd',
                ];
            }
        }
        
        return $colors;
    }
    
    /**
     * Get a specific logo color
     * 
     * @param string $name Color name (primary, secondary, accent, etc.)
     * @return string Hex color code
     */
    function get_logo_color($name = 'primary')
    {
        $colors = get_logo_colors();
        return $colors[$name] ?? $colors['primary'] ?? '#2563eb';
    }
    
    function escape($string)
    {
        if ($string === null) {
            return '';
        }
        if (!is_scalar($string)) {
            return '';
        }
        return htmlspecialchars((string)$string, ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('old')) {
    function old($key, $default = '')
    {
        return $_SESSION['old_input'][$key] ?? $default;
    }
}

if (!function_exists('session')) {
    function session($key = null, $value = null)
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        if ($key === null) {
            return $_SESSION;
        }
        
        if ($value === null) {
            return $_SESSION[$key] ?? null;
        }
        
        $_SESSION[$key] = $value;
        return $value;
    }
}

if (!function_exists('get_real_ip')) {
    /**
     * Get the real client IP address
     * Works correctly when behind Cloudflare proxy
     * 
     * @return string IP address
     */
    function get_real_ip()
    {
        // Cloudflare sends real IP in CF-Connecting-IP header
        if (!empty($_SERVER['HTTP_CF_CONNECTING_IP'])) {
            return $_SERVER['HTTP_CF_CONNECTING_IP'];
        }
        
        // Check for other common proxy headers
        $headers = [
            'HTTP_X_FORWARDED_FOR',
            'HTTP_X_REAL_IP',
            'HTTP_CLIENT_IP',
            'HTTP_X_FORWARDED',
            'HTTP_X_CLUSTER_CLIENT_IP',
            'HTTP_FORWARDED_FOR',
            'HTTP_FORWARDED'
        ];
        
        foreach ($headers as $header) {
            if (!empty($_SERVER[$header])) {
                $ips = explode(',', $_SERVER[$header]);
                $ip = trim($ips[0]);
                // Validate IP address
                if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                    return $ip;
                }
            }
        }
        
        // Fallback to REMOTE_ADDR
        return $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    }
}

// CSRF Protection Functions
if (!function_exists('csrf_token')) {
    /**
     * Generate or retrieve CSRF token
     * 
     * @param bool $regenerate Whether to regenerate the token
     * @return string CSRF token
     */
    function csrf_token($regenerate = false)
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        if ($regenerate || !isset($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        
        return $_SESSION['csrf_token'];
    }
}

if (!function_exists('csrf_reset')) {
    /**
     * Reset/regenerate CSRF token
     * 
     * @return string New CSRF token
     */
    function csrf_reset()
    {
        return csrf_token(true);
    }
}

if (!function_exists('csrf_field')) {
    /**
     * Generate CSRF token hidden input field
     * 
     * @return string HTML input field
     */
    function csrf_field()
    {
        return '<input type="hidden" name="csrf_token" value="' . escape(csrf_token()) . '">';
    }
}

if (!function_exists('csrf_verify')) {
    /**
     * Verify CSRF token
     * 
     * @param string|null $token Token to verify (defaults to POST csrf_token)
     * @return bool True if valid, false otherwise
     */
    function csrf_verify($token = null)
    {
        // CSRF protection is disabled by default
        // Check if CSRF protection is enabled in settings
        try {
            $csrfEnabled = db()->fetchOne("SELECT value FROM settings WHERE `key` = 'csrf_protection_enabled'");
            if ($csrfEnabled && $csrfEnabled['value'] === '1') {
                // CSRF protection is explicitly enabled - verify token
                if (session_status() === PHP_SESSION_NONE) {
                    session_start();
                }
                
                if (!isset($_SESSION['csrf_token'])) {
                    return false;
                }
                
                $token = $token ?? ($_POST['csrf_token'] ?? $_GET['csrf_token'] ?? null);
                
                if (empty($token)) {
                    return false;
                }
                
                return hash_equals($_SESSION['csrf_token'], $token);
            }
        } catch (\Exception $e) {
            // If setting doesn't exist or error, default to disabled
        }
        
        // Default: CSRF protection is disabled - return true
        return true;
    }
}

if (!function_exists('require_csrf')) {
    /**
     * Require valid CSRF token or die with error
     * 
     * @param bool $showResetButton Whether to show reset button in error message
     * @return void
     */
    function require_csrf($showResetButton = false)
    {
        // CSRF protection is disabled by default
        // Check if CSRF protection is enabled in settings
        try {
            $csrfEnabled = db()->fetchOne("SELECT value FROM settings WHERE `key` = 'csrf_protection_enabled'");
            if ($csrfEnabled && $csrfEnabled['value'] === '1') {
                // CSRF protection is explicitly enabled - verify token
                if ($_SERVER['REQUEST_METHOD'] === 'POST' && !csrf_verify()) {
                    http_response_code(403);
                    $errorMsg = 'Invalid security token. Please refresh the page and try again.';
                    if ($showResetButton) {
                        $errorMsg .= ' <button type="button" onclick="resetCsrfToken()" class="ml-2 px-3 py-1 bg-blue-600 text-white rounded hover:bg-blue-700 text-sm">Reset Token</button>';
                    }
                    die($errorMsg);
                }
            }
        } catch (\Exception $e) {
            // If setting doesn't exist or error, default to disabled - do nothing
        }
        
        // Default: CSRF protection is disabled - allow request to proceed
        return;
    }
}

// Password Validation Function
if (!function_exists('validate_password')) {
    /**
     * Validate password strength
     * 
     * @param string $password Password to validate
     * @return array ['valid' => bool, 'errors' => array]
     */
    function validate_password($password)
    {
        $errors = [];
        
        if (strlen($password) < 12) {
            $errors[] = 'Password must be at least 12 characters long.';
        }
        
        if (!preg_match('/[A-Z]/', $password)) {
            $errors[] = 'Password must contain at least one uppercase letter.';
        }
        
        if (!preg_match('/[a-z]/', $password)) {
            $errors[] = 'Password must contain at least one lowercase letter.';
        }
        
        if (!preg_match('/[0-9]/', $password)) {
            $errors[] = 'Password must contain at least one number.';
        }
        
        if (!preg_match('/[^A-Za-z0-9]/', $password)) {
            $errors[] = 'Password must contain at least one special character.';
        }
        
        return [
            'valid' => empty($errors),
            'errors' => $errors
        ];
    }
}

// Image Resize/Crop Function
if (!function_exists('resize_and_save_image')) {
    /**
     * Resize and save an uploaded image to fit within specified dimensions
     * Maintains aspect ratio and preserves transparency for PNG/GIF
     * 
     * @param string $sourcePath Temporary uploaded file path
     * @param string $destinationPath Full path where resized image should be saved
     * @param int $maxWidth Maximum width (default: 800)
     * @param int $maxHeight Maximum height (default: 800)
     * @param int $quality JPEG/WebP quality 0-100 (default: 85)
     * @return bool True on success, false on failure
     */
    function resize_and_save_image($sourcePath, $destinationPath, $maxWidth = 800, $maxHeight = 800, $quality = 85)
    {
        // Check if GD extension is available
        if (!extension_loaded('gd')) {
            // If GD is not available, just copy the file
            return copy($sourcePath, $destinationPath);
        }
        
        // Get image info
        $imageInfo = @getimagesize($sourcePath);
        if (!$imageInfo) {
            return false;
        }
        
        $originalWidth = $imageInfo[0];
        $originalHeight = $imageInfo[1];
        $mimeType = $imageInfo['mime'];
        $extension = strtolower(pathinfo($destinationPath, PATHINFO_EXTENSION));
        
        // Skip SVG files (vector graphics don't need resizing)
        if ($extension === 'svg' || $mimeType === 'image/svg+xml') {
            return copy($sourcePath, $destinationPath);
        }
        
        // Create source image resource
        $source = null;
        switch ($mimeType) {
            case 'image/jpeg':
                $source = @imagecreatefromjpeg($sourcePath);
                break;
            case 'image/png':
                $source = @imagecreatefrompng($sourcePath);
                break;
            case 'image/gif':
                $source = @imagecreatefromgif($sourcePath);
                break;
            case 'image/webp':
                if (function_exists('imagecreatefromwebp')) {
                    $source = @imagecreatefromwebp($sourcePath);
                }
                break;
            default:
                return false;
        }
        
        if (!$source) {
            return false;
        }
        
        // Calculate new dimensions maintaining aspect ratio
        $ratio = min($maxWidth / $originalWidth, $maxHeight / $originalHeight);
        
        // Only resize if image is larger than max dimensions
        if ($ratio >= 1) {
            // Image is smaller than max dimensions, just copy it
            imagedestroy($source);
            return copy($sourcePath, $destinationPath);
        }
        
        $newWidth = (int)($originalWidth * $ratio);
        $newHeight = (int)($originalHeight * $ratio);
        
        // Create destination image
        $destination = imagecreatetruecolor($newWidth, $newHeight);
        
        // Preserve transparency for PNG and GIF
        if ($extension === 'png' || $extension === 'gif') {
            imagealphablending($destination, false);
            imagesavealpha($destination, true);
            $transparent = imagecolorallocatealpha($destination, 255, 255, 255, 127);
            imagefilledrectangle($destination, 0, 0, $newWidth, $newHeight, $transparent);
        }
        
        // Resize image with high quality
        imagecopyresampled($destination, $source, 0, 0, 0, 0, $newWidth, $newHeight, $originalWidth, $originalHeight);
        
        // Save resized image
        $saved = false;
        switch ($extension) {
            case 'jpg':
            case 'jpeg':
                $saved = @imagejpeg($destination, $destinationPath, $quality);
                break;
            case 'png':
                // PNG quality is 0-9 (inverted, 0 = best)
                $pngQuality = (int)(9 - ($quality / 100) * 9);
                $saved = @imagepng($destination, $destinationPath, $pngQuality);
                break;
            case 'gif':
                $saved = @imagegif($destination, $destinationPath);
                break;
            case 'webp':
                if (function_exists('imagewebp')) {
                    $saved = @imagewebp($destination, $destinationPath, $quality);
                }
                break;
        }
        
        // Clean up
        imagedestroy($source);
        imagedestroy($destination);
        
        return $saved;
    }
}

// SEO helpers
if (!function_exists('canonical_url')) {
    /**
     * Build canonical URL for current page (strips tracking params).
     * Pass explicit URL from page script to override (e.g. product, service, page).
     *
     * @param string|null $override Full canonical URL to use, or null to build from request
     * @return string
     */
    function canonical_url($override = null)
    {
        if ($override !== null && $override !== '') {
            return $override;
        }
        $baseUrl = rtrim(config('app.url', ''), '/');
        $uri = $_SERVER['REQUEST_URI'] ?? '';
        $path = parse_url($uri, PHP_URL_PATH);
        $query = parse_url($uri, PHP_URL_QUERY);
        if ($path === null || $path === false) {
            return $baseUrl;
        }
        $path = '/' . ltrim($path, '/');
        if (!empty($query)) {
            parse_str($query, $params);
            $strip = ['utm_source', 'utm_medium', 'utm_campaign', 'utm_term', 'utm_content', 'fbclid', 'gclid', 'msclkid'];
            foreach ($strip as $key) {
                unset($params[$key]);
            }
            $query = http_build_query($params);
            return $baseUrl . $path . ($query !== '' ? '?' . $query : '');
        }
        return $baseUrl . $path;
    }
}

if (!function_exists('get_site_name')) {
    /**
     * Get site name from settings (for titles, OG site_name).
     *
     * @return string
     */
    function get_site_name()
    {
        static $name = null;
        if ($name !== null) {
            return $name;
        }
        try {
            $row = db()->fetchOne("SELECT value FROM settings WHERE `key` = 'site_name'");
            $name = ($row && !empty($row['value'])) ? $row['value'] : 'Forklift & Equipment Pro';
        } catch (\Exception $e) {
            $name = 'Forklift & Equipment Pro';
        }
        return $name;
    }
}

if (!function_exists('get_seo_defaults')) {
    /**
     * Get default SEO values from settings (meta title, meta description, OG image).
     *
     * @return array{meta_title: string, meta_description: string, og_image: string}
     */
    function get_seo_defaults()
    {
        static $defaults = null;
        if ($defaults !== null) {
            return $defaults;
        }
        try {
            $rows = db()->fetchAll("SELECT `key`, value FROM settings WHERE `key` IN ('seo_default_meta_title', 'seo_default_meta_description', 'seo_og_image')");
            $kv = [];
            foreach ($rows as $r) {
                $kv[$r['key']] = $r['value'] ?? '';
            }
            $baseUrl = rtrim(config('app.url', ''), '/');
            $ogImage = !empty($kv['seo_og_image']) ? $kv['seo_og_image'] : '';
            if ($ogImage && strpos($ogImage, 'http') !== 0) {
                $ogImage = $baseUrl . '/' . ltrim($ogImage, '/');
            }
            $defaults = [
                'meta_title' => $kv['seo_default_meta_title'] ?? get_site_name(),
                'meta_description' => $kv['seo_default_meta_description'] ?? 'Premium forklifts and industrial equipment for warehouses and factories',
                'og_image' => $ogImage,
            ];
        } catch (\Exception $e) {
            $defaults = [
                'meta_title' => get_site_name(),
                'meta_description' => 'Premium forklifts and industrial equipment for warehouses and factories',
                'og_image' => '',
            ];
        }
        return $defaults;
    }
}

<?php

/**
 * Google Merchant Center — Content API for Shopping (products.insert / delete).
 *
 * Setup:
 * 1. Google Cloud: create a project, enable "Content API for Shopping".
 * 2. Create a service account, download JSON key → place at credentials_path (default below).
 * 3. Merchant Center: Settings → Content API → link Cloud project; add the service account
 *    email as a user with permission to manage products.
 * 4. Set merchant_id to your Merchant Center ID (numeric).
 *
 * Optional env vars: GOOGLE_MERCHANT_ENABLED, GOOGLE_MERCHANT_MERCHANT_ID,
 * GOOGLE_MERCHANT_CREDENTIALS_PATH, GOOGLE_MERCHANT_CONTENT_LANGUAGE,
 * GOOGLE_MERCHANT_TARGET_COUNTRY, GOOGLE_MERCHANT_CURRENCY, GOOGLE_MERCHANT_DEFAULT_BRAND,
 * GOOGLE_MERCHANT_FEED_ENABLED, GOOGLE_MERCHANT_FEED_TOKEN
 *
 * Reads $_ENV / $_SERVER first (vlucas/phpdotenv createImmutable), then getenv().
 *
 * Fallback: if Composer Dotenv did not run, merge simple KEY=VALUE lines from /.env into $_ENV.
 */
$projectRoot = dirname(__DIR__);
$envFile = $projectRoot . '/.env';
if (is_readable($envFile)) {
    $lines = @file($envFile, FILE_IGNORE_NEW_LINES);
    if (is_array($lines)) {
        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '' || ($line[0] ?? '') === '#') {
                continue;
            }
            if (!str_contains($line, '=')) {
                continue;
            }
            [$name, $value] = array_map('trim', explode('=', $line, 2));
            if ($name === '') {
                continue;
            }
            if (strlen($value) >= 2) {
                $q = $value[0];
                if (($q === '"' || $q === "'") && substr($value, -1) === $q) {
                    $value = substr($value, 1, -1);
                }
            }
            if (!array_key_exists($name, $_ENV)) {
                $_ENV[$name] = $value;
            }
        }
    }
}

$env = static function (string $key, string $default = ''): string {
    if (array_key_exists($key, $_ENV)) {
        return (string) $_ENV[$key];
    }
    if (array_key_exists($key, $_SERVER)) {
        return (string) $_SERVER[$key];
    }
    $v = getenv($key);

    return $v !== false ? (string) $v : $default;
};

$envCred = $env('GOOGLE_MERCHANT_CREDENTIALS_PATH', '');
if ($envCred === '') {
    $credentialsPath = $projectRoot . '/storage/private/google-merchant-credentials.json';
} else {
    $credentialsPath = str_replace('\\', '/', $envCred);
    $isAbsolute = strpos($credentialsPath, '/') === 0
        || preg_match('/^[A-Za-z]:\//', $credentialsPath) === 1;
    if (!$isAbsolute) {
        $credentialsPath = $projectRoot . '/' . ltrim($credentialsPath, '/');
    }
}

$enabledRaw = $env('GOOGLE_MERCHANT_ENABLED', 'false');

return [
    'enabled' => filter_var($enabledRaw, FILTER_VALIDATE_BOOLEAN),
    'merchant_id' => $env('GOOGLE_MERCHANT_MERCHANT_ID', ''),
    'credentials_path' => $credentialsPath,
    'content_language' => $env('GOOGLE_MERCHANT_CONTENT_LANGUAGE', 'en'),
    'target_country' => $env('GOOGLE_MERCHANT_TARGET_COUNTRY', 'KH'),
    'currency' => $env('GOOGLE_MERCHANT_CURRENCY', 'USD'),
    'default_brand' => $env('GOOGLE_MERCHANT_DEFAULT_BRAND', 'S3V Group'),
    'channel' => 'online',
    'feed_enabled' => filter_var($env('GOOGLE_MERCHANT_FEED_ENABLED', 'false'), FILTER_VALIDATE_BOOLEAN),
    'feed_token' => $env('GOOGLE_MERCHANT_FEED_TOKEN', ''),
];

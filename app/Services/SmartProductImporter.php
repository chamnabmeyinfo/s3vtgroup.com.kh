<?php

namespace App\Services;

use DOMDocument;
use DOMXPath;
use Exception;

/**
 * Smart Product Importer
 * Universal product extraction from any website using pattern recognition + AI
 */
class SmartProductImporter
{
    private $userAgent = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36';
    private $aiApiKey = null;
    private $aiProvider = 'openai'; // 'openai' or 'anthropic'
    
    public function __construct()
    {
        // Load AI config if available
        $configFile = __DIR__ . '/../../config/smart-importer.php';
        if (file_exists($configFile)) {
            $config = require $configFile;
            $this->aiApiKey = $config['ai_api_key'] ?? null;
            $this->aiProvider = $config['ai_provider'] ?? 'openai';
        }
    }
    
    /**
     * Extract product data from any URL using hybrid approach
     * Tries pattern recognition first, then AI if needed
     */
    public function extractProductData($url, $options = [])
    {
        $result = [
            'success' => false,
            'method' => 'none',
            'confidence' => 0,
            'data' => [],
            'errors' => []
        ];
        
        try {
            // Fetch HTML
            $html = $this->fetchUrl($url);
            if (empty($html)) {
                throw new Exception('Failed to fetch page content');
            }
            
            // Method 1: Pattern Recognition (Fast, Free)
            $patternData = $this->extractWithPatterns($html, $url);
            $patternConfidence = $this->calculateConfidence($patternData);
            
            // Method 2: AI Extraction (if enabled and pattern recognition is low confidence)
            $aiData = null;
            $aiConfidence = 0;
            $useAI = ($options['force_ai'] ?? false) || ($patternConfidence < 70 && $this->aiApiKey);
            
            if ($useAI && $this->aiApiKey) {
                try {
                    $aiData = $this->extractWithAI($html, $url);
                    $aiConfidence = $this->calculateConfidence($aiData);
                } catch (Exception $e) {
                    $result['errors'][] = 'AI extraction failed: ' . $e->getMessage();
                }
            }
            
            // Combine results intelligently
            $finalData = $this->mergeResults($patternData, $aiData, $patternConfidence, $aiConfidence);
            $finalConfidence = max($patternConfidence, $aiConfidence);
            
            // Determine which method was primary
            if ($aiData && $aiConfidence > $patternConfidence) {
                $result['method'] = 'ai';
            } elseif ($patternData && $patternConfidence > 0) {
                $result['method'] = 'pattern';
            } else {
                $result['method'] = 'hybrid';
            }
            
            $result['success'] = !empty($finalData['name']);
            $result['confidence'] = $finalConfidence;
            $result['data'] = $finalData;
            $result['pattern_confidence'] = $patternConfidence;
            $result['ai_confidence'] = $aiConfidence;
            
        } catch (Exception $e) {
            $result['errors'][] = $e->getMessage();
        }
        
        return $result;
    }
    
    /**
     * Extract product data using pattern recognition
     */
    private function extractWithPatterns($html, $url)
    {
        $data = [
            'name' => '',
            'description' => '',
            'short_description' => '',
            'price' => '',
            'sale_price' => '',
            'images' => [],
            'specifications' => [],
            'features' => [],
            'sku' => '',
            'brand' => '',
            'category' => ''
        ];
        
        // Suppress HTML parsing errors
        libxml_use_internal_errors(true);
        $dom = new DOMDocument();
        @$dom->loadHTML(mb_convert_encoding($html, 'HTML-ENTITIES', 'UTF-8'));
        $xpath = new DOMXPath($dom);
        libxml_clear_errors();
        
        // 1. Schema.org Product Markup (JSON-LD)
        $jsonLdNodes = $xpath->query('//script[@type="application/ld+json"]');
        foreach ($jsonLdNodes as $node) {
            $json = json_decode($node->textContent, true);
            if ($json && isset($json['@type']) && 
                (strtolower($json['@type']) === 'product' || 
                 (isset($json['@type']) && is_array($json['@type']) && in_array('Product', $json['@type'])))) {
                
                if (isset($json['name'])) $data['name'] = $json['name'];
                if (isset($json['description'])) $data['description'] = $json['description'];
                if (isset($json['offers']['price'])) $data['price'] = $json['offers']['price'];
                if (isset($json['offers']['priceCurrency'])) $data['currency'] = $json['offers']['priceCurrency'];
                if (isset($json['image'])) {
                    $images = is_array($json['image']) ? $json['image'] : [$json['image']];
                    foreach ($images as $img) {
                        $imgUrl = is_array($img) ? ($img['url'] ?? $img['@id'] ?? '') : $img;
                        if ($imgUrl) $data['images'][] = $this->normalizeImageUrl($imgUrl, $url);
                    }
                }
                if (isset($json['sku'])) $data['sku'] = $json['sku'];
                if (isset($json['brand']['name'])) $data['brand'] = $json['brand']['name'];
                if (isset($json['category'])) $data['category'] = $json['category'];
                
                // Extract specifications
                if (isset($json['additionalProperty'])) {
                    foreach ($json['additionalProperty'] as $prop) {
                        if (isset($prop['name']) && isset($prop['value'])) {
                            $data['specifications'][$prop['name']] = $prop['value'];
                        }
                    }
                }
            }
        }
        
        // 2. Open Graph Meta Tags
        if (empty($data['name'])) {
            $ogTitle = $xpath->query('//meta[@property="og:title"]/@content')->item(0);
            if ($ogTitle) $data['name'] = $ogTitle->nodeValue;
        }
        
        if (empty($data['description'])) {
            $ogDesc = $xpath->query('//meta[@property="og:description"]/@content')->item(0);
            if ($ogDesc) $data['description'] = $ogDesc->nodeValue;
        }
        
        if (empty($data['images'])) {
            $ogImages = $xpath->query('//meta[@property="og:image"]/@content');
            foreach ($ogImages as $img) {
                $data['images'][] = $this->normalizeImageUrl($img->nodeValue, $url);
            }
        }
        
        // 3. Standard Meta Tags
        if (empty($data['name'])) {
            $titleNode = $xpath->query('//title')->item(0);
            if ($titleNode) $data['name'] = trim($titleNode->textContent);
        }
        
        $metaDesc = $xpath->query('//meta[@name="description"]/@content')->item(0);
        if ($metaDesc && empty($data['description'])) {
            $data['description'] = $metaDesc->nodeValue;
        }
        
        // 4. Common E-commerce Patterns
        // Product title patterns
        if (empty($data['name'])) {
            $titlePatterns = [
                '//h1[contains(@class, "product")]',
                '//h1[contains(@class, "title")]',
                '//h1[contains(@id, "product")]',
                '//*[contains(@class, "product-title")]',
                '//*[contains(@class, "product-name")]',
                '//h1'
            ];
            foreach ($titlePatterns as $pattern) {
                $node = $xpath->query($pattern)->item(0);
                if ($node) {
                    $data['name'] = trim($node->textContent);
                    break;
                }
            }
        }
        
        // Price patterns
        if (empty($data['price'])) {
            $pricePatterns = [
                '//*[contains(@class, "price")]',
                '//*[contains(@class, "product-price")]',
                '//*[contains(@class, "current-price")]',
                '//*[contains(@itemprop, "price")]',
                '//*[contains(@data-price, "")]'
            ];
            foreach ($pricePatterns as $pattern) {
                $nodes = $xpath->query($pattern);
                foreach ($nodes as $node) {
                    $priceText = trim($node->textContent);
                    $price = $this->extractPrice($priceText);
                    if ($price) {
                        $data['price'] = $price;
                        break 2;
                    }
                }
            }
        }
        
        // Description patterns
        if (empty($data['description'])) {
            $descPatterns = [
                '//*[contains(@class, "product-description")]',
                '//*[contains(@class, "description")]',
                '//*[contains(@itemprop, "description")]',
                '//div[contains(@class, "content")]'
            ];
            foreach ($descPatterns as $pattern) {
                $node = $xpath->query($pattern)->item(0);
                if ($node) {
                    $desc = trim($node->textContent);
                    if (strlen($desc) > 50) {
                        $data['description'] = $desc;
                        break;
                    }
                }
            }
        }
        
        // Image patterns
        if (empty($data['images'])) {
            $imgPatterns = [
                '//img[contains(@class, "product")]',
                '//img[contains(@class, "main")]',
                '//img[contains(@id, "product")]',
                '//*[contains(@class, "product-gallery")]//img',
                '//*[contains(@class, "gallery")]//img'
            ];
            foreach ($imgPatterns as $pattern) {
                $imgs = $xpath->query($pattern . '/@src');
                foreach ($imgs as $img) {
                    $imgUrl = $this->normalizeImageUrl($img->nodeValue, $url);
                    if ($imgUrl && !in_array($imgUrl, $data['images'])) {
                        $data['images'][] = $imgUrl;
                    }
                }
            }
        }
        
        // Extract short description (first 200 chars)
        if (!empty($data['description'])) {
            $data['short_description'] = mb_substr(strip_tags($data['description']), 0, 200);
        }
        
        return $data;
    }
    
    /**
     * Extract product data using AI
     */
    private function extractWithAI($html, $url)
    {
        if (!$this->aiApiKey) {
            throw new Exception('AI API key not configured');
        }
        
        // Clean HTML for AI (remove scripts, styles, etc.)
        $cleanHtml = $this->cleanHtmlForAI($html);
        
        // Prepare prompt
        $prompt = $this->buildAIPrompt($cleanHtml, $url);
        
        // Call AI API
        if ($this->aiProvider === 'openai') {
            return $this->callOpenAI($prompt);
        } elseif ($this->aiProvider === 'anthropic') {
            return $this->callAnthropic($prompt);
        } else {
            throw new Exception('Unknown AI provider: ' . $this->aiProvider);
        }
    }
    
    /**
     * Call OpenAI API
     */
    private function callOpenAI($prompt)
    {
        $ch = curl_init('https://api.openai.com/v1/chat/completions');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $this->aiApiKey
        ]);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
            'model' => 'gpt-4o-mini', // Using cheaper model
            'messages' => [
                [
                    'role' => 'system',
                    'content' => 'You are an expert at extracting product information from HTML. Return only valid JSON.'
                ],
                [
                    'role' => 'user',
                    'content' => $prompt
                ]
            ],
            'temperature' => 0.3,
            'max_tokens' => 2000
        ]));
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);
        
        if ($error) {
            throw new Exception("cURL Error: $error");
        }
        
        if ($httpCode !== 200) {
            throw new Exception("OpenAI API Error: HTTP $httpCode - $response");
        }
        
        $data = json_decode($response, true);
        if (!isset($data['choices'][0]['message']['content'])) {
            throw new Exception('Invalid response from OpenAI API');
        }
        
        $content = $data['choices'][0]['message']['content'];
        
        // Extract JSON from response
        $jsonMatch = [];
        if (preg_match('/\{[\s\S]*\}/', $content, $jsonMatch)) {
            $extracted = json_decode($jsonMatch[0], true);
            if ($extracted) {
                return $this->normalizeAIData($extracted);
            }
        }
        
        throw new Exception('Failed to parse AI response');
    }
    
    /**
     * Call Anthropic Claude API
     */
    private function callAnthropic($prompt)
    {
        $ch = curl_init('https://api.anthropic.com/v1/messages');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'x-api-key: ' . $this->aiApiKey,
            'anthropic-version: 2023-06-01'
        ]);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
            'model' => 'claude-3-haiku-20240307', // Fast and cheap
            'max_tokens' => 2000,
            'messages' => [
                [
                    'role' => 'user',
                    'content' => $prompt
                ]
            ]
        ]));
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);
        
        if ($error) {
            throw new Exception("cURL Error: $error");
        }
        
        if ($httpCode !== 200) {
            throw new Exception("Anthropic API Error: HTTP $httpCode - $response");
        }
        
        $data = json_decode($response, true);
        if (!isset($data['content'][0]['text'])) {
            throw new Exception('Invalid response from Anthropic API');
        }
        
        $content = $data['content'][0]['text'];
        
        // Extract JSON from response
        $jsonMatch = [];
        if (preg_match('/\{[\s\S]*\}/', $content, $jsonMatch)) {
            $extracted = json_decode($jsonMatch[0], true);
            if ($extracted) {
                return $this->normalizeAIData($extracted);
            }
        }
        
        throw new Exception('Failed to parse AI response');
    }
    
    /**
     * Build AI prompt
     */
    private function buildAIPrompt($html, $url)
    {
        // Limit HTML size for AI (keep first 10000 chars)
        $htmlSnippet = mb_substr($html, 0, 10000);
        
        return "Extract product information from this HTML page. Return a JSON object with the following structure:

{
  \"name\": \"Product name\",
  \"description\": \"Full product description\",
  \"short_description\": \"Short description (max 200 chars)\",
  \"price\": \"Price as number (e.g., 1299.99)\",
  \"sale_price\": \"Sale price if available\",
  \"currency\": \"Currency code (USD, EUR, etc.)\",
  \"images\": [\"image_url1\", \"image_url2\"],
  \"sku\": \"Product SKU if available\",
  \"brand\": \"Brand name if available\",
  \"category\": \"Product category\",
  \"specifications\": {\"key\": \"value\"},
  \"features\": [\"feature1\", \"feature2\"]
}

URL: $url

HTML Content:
$htmlSnippet

Return ONLY valid JSON, no other text.";
    }
    
    /**
     * Clean HTML for AI processing
     */
    private function cleanHtmlForAI($html)
    {
        // Remove scripts and styles
        $html = preg_replace('/<script\b[^<]*(?:(?!<\/script>)<[^<]*)*<\/script>/mi', '', $html);
        $html = preg_replace('/<style\b[^<]*(?:(?!<\/style>)<[^<]*)*<\/style>/mi', '', $html);
        
        // Remove comments
        $html = preg_replace('/<!--.*?-->/s', '', $html);
        
        // Decode HTML entities
        $html = html_entity_decode($html, ENT_QUOTES, 'UTF-8');
        
        return $html;
    }
    
    /**
     * Normalize AI response data
     */
    private function normalizeAIData($data)
    {
        $normalized = [
            'name' => $data['name'] ?? '',
            'description' => $data['description'] ?? '',
            'short_description' => $data['short_description'] ?? mb_substr(strip_tags($data['description'] ?? ''), 0, 200),
            'price' => $data['price'] ?? '',
            'sale_price' => $data['sale_price'] ?? '',
            'images' => is_array($data['images'] ?? []) ? $data['images'] : [],
            'specifications' => is_array($data['specifications'] ?? []) ? $data['specifications'] : [],
            'features' => is_array($data['features'] ?? []) ? $data['features'] : [],
            'sku' => $data['sku'] ?? '',
            'brand' => $data['brand'] ?? '',
            'category' => $data['category'] ?? ''
        ];
        
        return $normalized;
    }
    
    /**
     * Merge results from pattern recognition and AI
     */
    private function mergeResults($patternData, $aiData, $patternConfidence, $aiConfidence)
    {
        $merged = $patternData;
        
        if ($aiData) {
            // Use AI data if it has higher confidence or fills missing fields
            foreach ($aiData as $key => $value) {
                if (empty($merged[$key]) && !empty($value)) {
                    $merged[$key] = $value;
                } elseif ($aiConfidence > $patternConfidence && !empty($value)) {
                    // AI has higher confidence, prefer AI data
                    if ($key === 'images' && is_array($value)) {
                        // Merge images, AI first
                        $merged[$key] = array_merge($value, $merged[$key] ?? []);
                        $merged[$key] = array_unique($merged[$key]);
                    } else {
                        $merged[$key] = $value;
                    }
                }
            }
        }
        
        return $merged;
    }
    
    /**
     * Calculate confidence score for extracted data
     */
    private function calculateConfidence($data)
    {
        $score = 0;
        $maxScore = 100;
        
        if (!empty($data['name'])) $score += 25;
        if (!empty($data['description'])) $score += 20;
        if (!empty($data['price'])) $score += 20;
        if (!empty($data['images']) && count($data['images']) > 0) $score += 15;
        if (!empty($data['sku'])) $score += 5;
        if (!empty($data['specifications']) && count($data['specifications']) > 0) $score += 10;
        if (!empty($data['features']) && count($data['features']) > 0) $score += 5;
        
        return min($score, $maxScore);
    }
    
    /**
     * Extract price from text
     */
    private function extractPrice($text)
    {
        // Remove currency symbols and extract number
        $text = preg_replace('/[^\d.,]/', '', $text);
        $text = str_replace(',', '', $text);
        $price = floatval($text);
        return $price > 0 ? $price : null;
    }
    
    /**
     * Normalize image URL (make absolute)
     */
    private function normalizeImageUrl($url, $baseUrl)
    {
        if (empty($url)) return '';
        
        // Already absolute
        if (preg_match('/^https?:\/\//', $url)) {
            return $url;
        }
        
        // Relative URL
        $parsed = parse_url($baseUrl);
        $base = $parsed['scheme'] . '://' . $parsed['host'];
        
        if (strpos($url, '/') === 0) {
            // Absolute path
            return $base . $url;
        } else {
            // Relative path
            $path = dirname($parsed['path'] ?? '/');
            return $base . rtrim($path, '/') . '/' . ltrim($url, '/');
        }
    }
    
    /**
     * Fetch URL content
     */
    private function fetchUrl($url)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_USERAGENT, $this->userAgent);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
        curl_setopt($ch, CURLOPT_ENCODING, '');
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
            'Accept-Language: en-US,en;q=0.9',
        ]);
        
        $html = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);
        
        if ($error) {
            throw new Exception("cURL Error: $error");
        }
        
        if ($httpCode !== 200) {
            throw new Exception("HTTP Error: $httpCode");
        }
        
        return $html;
    }
    
    /**
     * Download image to server
     */
    public function downloadImage($imageUrl, $savePath)
    {
        $ch = curl_init($imageUrl);
        $fp = fopen($savePath, 'wb');
        curl_setopt($ch, CURLOPT_FILE, $fp);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_USERAGENT, $this->userAgent);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        
        $success = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        fclose($fp);
        
        if (!$success || $httpCode !== 200) {
            @unlink($savePath);
            return false;
        }
        
        return true;
    }
}


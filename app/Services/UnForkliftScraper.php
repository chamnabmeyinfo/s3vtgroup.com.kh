<?php

namespace App\Services;

use DOMDocument;
use DOMXPath;
use Exception;

class UnForkliftScraper
{
    private $baseUrl = 'https://www.unforklift.com';
    private $userAgent = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36';
    
    /**
     * Get HTML content from a URL with optimized settings
     */
    private function fetchUrl($url)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_USERAGENT, $this->userAgent);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_TIMEOUT, 15); // Reduced timeout
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
        curl_setopt($ch, CURLOPT_ENCODING, ''); // Accept any encoding
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Accept: text/html,application/xhtml+xml',
            'Accept-Language: en-US,en;q=0.9',
            'Connection: keep-alive',
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
     * Parse HTML and return DOMDocument
     */
    private function parseHtml($html)
    {
        libxml_use_internal_errors(true);
        $dom = new DOMDocument();
        $dom->loadHTML('<?xml encoding="UTF-8">' . $html);
        libxml_clear_errors();
        return $dom;
    }
    
    /**
     * Get all product category URLs
     * Tries to extract from website, falls back to known structure
     */
    public function getProductCategories()
    {
        // Try to extract categories from the main product page
        try {
            $html = $this->fetchUrl($this->baseUrl . '/product/');
            $dom = $this->parseHtml($html);
            $xpath = new DOMXPath($dom);
            
            $categories = [];
            
            // Try to find category links
            $categorySelectors = [
                "//a[contains(@href, '/product/') and contains(text(), 'Forklift')]",
                "//a[contains(@href, '/product/')]",
                "//div[contains(@class, 'category')]//a",
            ];
            
            foreach ($categorySelectors as $selector) {
                $links = $xpath->query($selector);
                if ($links && $links->length > 0) {
                    foreach ($links as $link) {
                        $href = $link->getAttribute('href');
                        $text = trim($link->textContent);
                        
                        if ($href && $text && strpos($href, '/product/') !== false) {
                            // Make absolute URL
                            if (strpos($href, 'http') !== 0) {
                                $href = $this->baseUrl . $href;
                            }
                            
                            // Extract slug from URL
                            preg_match('/\/product\/([^\/]+)/', $href, $matches);
                            $slug = $matches[1] ?? '';
                            
                            if (!empty($slug) && !empty($text)) {
                                // Avoid duplicates
                                $exists = false;
                                foreach ($categories as $cat) {
                                    if ($cat['slug'] === $slug || $cat['url'] === $href) {
                                        $exists = true;
                                        break;
                                    }
                                }
                                
                                if (!$exists) {
                                    $categories[] = [
                                        'name' => $text,
                                        'slug' => $slug,
                                        'url' => $href,
                                        'subcategories' => []
                                    ];
                                }
                            }
                        }
                    }
                    
                    if (count($categories) > 0) {
                        break; // Found categories
                    }
                }
            }
            
            // If we found categories, return them
            if (count($categories) > 0) {
                return $categories;
            }
        } catch (Exception $e) {
            // Fall through to default categories
        }
        
        // Fallback to main product page - all products are on one page
        // Based on UN Forklift website structure, products are listed on /product/ page
        $categories = [
            [
                'name' => 'All Products',
                'slug' => 'all-products',
                'url' => $this->baseUrl . '/product/',
                'subcategories' => []
            ],
            [
                'name' => 'IC Forklift',
                'slug' => 'ic-forklift',
                'url' => $this->baseUrl . '/product/',
                'subcategories' => []
            ],
            [
                'name' => 'Electric Forklift',
                'slug' => 'electric-forklift',
                'url' => $this->baseUrl . '/product/',
                'subcategories' => []
            ],
            [
                'name' => 'Warehouse Equipment',
                'slug' => 'warehouse-equipment',
                'url' => $this->baseUrl . '/product/',
                'subcategories' => []
            ],
        ];
        
        return $categories;
    }
    
    /**
     * Extract products from a category page or main product page
     */
    public function extractProductsFromCategory($categoryUrl)
    {
        try {
            $html = $this->fetchUrl($categoryUrl);
            $dom = $this->parseHtml($html);
            $xpath = new DOMXPath($dom);
            
            $products = [];
            $productLinks = [];
            
            // UN Forklift product page structure: products are listed as links
            // Try to find product links - they typically have product names as text
            $allLinks = $xpath->query("//a[@href]");
            
            foreach ($allLinks as $link) {
                $href = $link->getAttribute('href');
                $text = trim($link->textContent);
                
                // Skip if no meaningful text or href
                if (empty($href) || empty($text) || strlen($text) < 10) {
                    continue;
                }
                
                // Make absolute URL
                if (strpos($href, 'http') !== 0) {
                    $href = $this->baseUrl . $href;
                }
                
                // Check if it's a product link
                // Product URLs typically look like: /product/product-name/ or /product/product-name.html
                if (strpos($href, '/product/') !== false && 
                    $href !== $categoryUrl &&
                    $href !== $this->baseUrl . '/product/' &&
                    !preg_match('/\/product\/[^\/]+\/$/', $href) && // Not just /product/category/
                    strpos($href, '#') === false) {
                    
                    // Additional check: product links usually have product names
                    // Skip navigation, category, and other non-product links
                    $skipPatterns = [
                        'home', 'about', 'contact', 'news', 'faq', 'service',
                        'category', 'search', 'language', 'login', 'register'
                    ];
                    
                    $shouldSkip = false;
                    foreach ($skipPatterns as $pattern) {
                        if (stripos($href, $pattern) !== false || stripos($text, $pattern) !== false) {
                            $shouldSkip = true;
                            break;
                        }
                    }
                    
                    if (!$shouldSkip && !in_array($href, $productLinks)) {
                        $productLinks[] = $href;
                    }
                }
            }
            
            // Remove duplicates and limit
            $productLinks = array_unique($productLinks);
            $productLinks = array_slice($productLinks, 0, 20); // Limit to 20 products
            
            if (empty($productLinks)) {
                throw new Exception("No product links found on the page. The website structure may have changed.");
            }
            
            // Extract product details from each link with timeout protection
            $successCount = 0;
            $startTime = time();
            $maxTime = 100; // Maximum 100 seconds for extraction
            
            foreach ($productLinks as $productUrl) {
                // Check timeout
                if ((time() - $startTime) > $maxTime) {
                    break; // Stop if taking too long
                }
                
                try {
                    $product = $this->extractProductDetails($productUrl);
                    if ($product && !empty($product['name'])) {
                        $products[] = $product;
                        $successCount++;
                    }
                    // Reduced delay for faster processing
                    usleep(300000); // 0.3 second delay
                } catch (Exception $e) {
                    // Continue with next product if one fails
                    continue;
                }
            }
            
            if (empty($products)) {
                throw new Exception("Found " . count($productLinks) . " product links but couldn't extract product details. The product page structure may have changed.");
            }
            
            return $products;
            
        } catch (Exception $e) {
            throw new Exception("Error extracting products from category: " . $e->getMessage());
        }
    }
    
    /**
     * Extract detailed product information from a product page
     */
    public function extractProductDetails($productUrl)
    {
        try {
            $html = $this->fetchUrl($productUrl);
            $dom = $this->parseHtml($html);
            $xpath = new DOMXPath($dom);
            
            $product = [
                'url' => $productUrl,
                'name' => '',
                'description' => '',
                'short_description' => '',
                'images' => [],
                'specifications' => [],
                'features' => [],
            ];
            
            // Extract product name (try multiple selectors)
            $nameSelectors = [
                "//h1",
                "//h1[@class='product-title']",
                "//h1[@class='title']",
                "//h2[contains(@class, 'product')]",
                "//div[contains(@class, 'product-title')]//h1",
                "//div[contains(@class, 'product-title')]//h2",
                "//title",
            ];
            
            foreach ($nameSelectors as $selector) {
                $nodes = $xpath->query($selector);
                if ($nodes && $nodes->length > 0) {
                    $name = trim($nodes->item(0)->textContent);
                    // Clean up title if it contains site name
                    $name = preg_replace('/\s*[-|]\s*.*Forklift.*$/i', '', $name);
                    $name = trim($name);
                    if (!empty($name) && strlen($name) > 3) {
                        $product['name'] = $name;
                        break;
                    }
                }
            }
            
            // If still no name, try to extract from URL
            if (empty($product['name'])) {
                preg_match('/\/product\/([^\/]+)/', $productUrl, $matches);
                if (!empty($matches[1])) {
                    $product['name'] = ucwords(str_replace(['-', '_'], ' ', $matches[1]));
                }
            }
            
            // Extract description - ONLY TEXT CONTENT, NO HTML
            $descSelectors = [
                "//div[contains(@class, 'description')]",
                "//div[contains(@class, 'content')]",
                "//div[contains(@class, 'product-description')]",
                "//article//div[contains(@class, 'entry-content')]",
                "//div[contains(@class, 'product-detail')]",
            ];
            
            foreach ($descSelectors as $selector) {
                $nodes = $xpath->query($selector);
                if ($nodes && $nodes->length > 0) {
                    $description = '';
                    foreach ($nodes as $node) {
                        // Extract only text content, no HTML
                        $text = $this->extractTextContent($node);
                        if (!empty($text)) {
                            $description .= $text . "\n\n";
                        }
                    }
                    if (!empty($description)) {
                        $product['description'] = trim($description);
                        // Use first 200 chars as short description
                        $product['short_description'] = mb_substr($product['description'], 0, 200);
                        break;
                    }
                }
            }
            
            // Extract images - improved selectors
            $imgSelectors = [
                "//div[contains(@class, 'product-image')]//img",
                "//div[contains(@class, 'gallery')]//img",
                "//div[contains(@class, 'product-gallery')]//img",
                "//img[contains(@class, 'product')]",
                "//article//img[@src]",
                "//img[contains(@src, 'product')]",
                "//img[contains(@src, 'forklift')]",
            ];
            
            foreach ($imgSelectors as $selector) {
                $imgs = $xpath->query($selector);
                if ($imgs && $imgs->length > 0) {
                    foreach ($imgs as $img) {
                        $src = $img->getAttribute('src');
                        if (empty($src)) {
                            $src = $img->getAttribute('data-src');
                        }
                        if (empty($src)) {
                            $src = $img->getAttribute('data-lazy-src');
                        }
                        if ($src) {
                            // Skip placeholder images
                            if (stripos($src, 'placeholder') !== false || 
                                stripos($src, 'logo') !== false ||
                                stripos($src, 'icon') !== false) {
                                continue;
                            }
                            
                            // Make absolute URL
                            if (strpos($src, 'http') !== 0) {
                                if (strpos($src, '/') === 0) {
                                    $src = $this->baseUrl . $src;
                                } else {
                                    $src = $this->baseUrl . '/' . $src;
                                }
                            }
                            
                            if (!in_array($src, $product['images']) && count($product['images']) < 10) {
                                $product['images'][] = $src;
                            }
                        }
                    }
                    if (count($product['images']) > 0) {
                        break;
                    }
                }
            }
            
            // Extract specifications - OPTIMIZED for text only
            $specSelectors = [
                "//table[contains(@class, 'specification')]",
                "//table[contains(@class, 'spec')]",
                "//div[contains(@class, 'specification')]//table",
                "//table//tr[td]",
            ];
            
            foreach ($specSelectors as $selector) {
                if (strpos($selector, '//tr') !== false) {
                    // Direct row selector
                    $rows = $xpath->query($selector);
                    if ($rows && $rows->length > 0) {
                        foreach ($rows as $row) {
                            $cells = $xpath->query(".//td", $row);
                            if ($cells->length >= 2) {
                                $key = trim($cells->item(0)->textContent);
                                $value = trim($cells->item(1)->textContent);
                                if (!empty($key) && !empty($value) && strlen($key) < 100 && strlen($value) < 500) {
                                    $product['specifications'][$key] = $value;
                                }
                            }
                        }
                        if (count($product['specifications']) > 0) {
                            break;
                        }
                    }
                } else {
                    // Table selector
                    $specs = $xpath->query($selector);
                    if ($specs && $specs->length > 0) {
                        foreach ($specs as $spec) {
                            $rows = $xpath->query(".//tr", $spec);
                            foreach ($rows as $row) {
                                $cells = $xpath->query(".//td", $row);
                                if ($cells->length >= 2) {
                                    $key = trim($cells->item(0)->textContent);
                                    $value = trim($cells->item(1)->textContent);
                                    if (!empty($key) && !empty($value) && strlen($key) < 100 && strlen($value) < 500) {
                                        $product['specifications'][$key] = $value;
                                    }
                                }
                            }
                        }
                        if (count($product['specifications']) > 0) {
                            break;
                        }
                    }
                }
            }
            
            // Extract forklift-specific data from specifications
            $product = $this->extractForkliftFields($product);
            
            // Extract features - TEXT ONLY
            $featureSelectors = [
                "//ul[contains(@class, 'feature')]//li",
                "//div[contains(@class, 'feature')]//li",
                "//ul[contains(@class, 'benefit')]//li",
                "//ul[contains(@class, 'advantage')]//li",
            ];
            
            foreach ($featureSelectors as $selector) {
                $features = $xpath->query($selector);
                if ($features && $features->length > 0) {
                    foreach ($features as $feature) {
                        $text = trim($feature->textContent);
                        // Clean text - remove extra whitespace
                        $text = preg_replace('/\s+/', ' ', $text);
                        if (!empty($text) && strlen($text) > 3 && strlen($text) < 500 && !in_array($text, $product['features'])) {
                            $product['features'][] = $text;
                        }
                    }
                    if (count($product['features']) > 0) {
                        break;
                    }
                }
            }
            
            // Only return if we have at least a name
            if (empty($product['name'])) {
                return null;
            }
            
            return $product;
            
        } catch (Exception $e) {
            throw new Exception("Error extracting product details: " . $e->getMessage());
        }
    }
    
    /**
     * Extract only text content from a DOM node (no HTML)
     */
    private function extractTextContent($node)
    {
        if (!$node) {
            return '';
        }
        
        $text = '';
        $children = $node->childNodes;
        
        foreach ($children as $child) {
            if ($child->nodeType === XML_TEXT_NODE) {
                $text .= $child->textContent . ' ';
            } elseif ($child->nodeType === XML_ELEMENT_NODE) {
                // Skip script, style, and other unwanted tags
                $tagName = strtolower($child->nodeName);
                if (!in_array($tagName, ['script', 'style', 'noscript', 'iframe', 'embed', 'object'])) {
                    $text .= $this->extractTextContent($child) . ' ';
                }
            }
        }
        
        // Clean up whitespace
        $text = preg_replace('/\s+/', ' ', $text);
        $text = trim($text);
        
        return $text;
    }
    
    /**
     * Extract forklift-specific fields from specifications
     */
    private function extractForkliftFields($product)
    {
        if (empty($product['specifications'])) {
            return $product;
        }
        
        $specs = $product['specifications'];
        
        // Common forklift field mappings
        $fieldMappings = [
            'capacity' => ['capacity', 'load capacity', 'lifting capacity', 'rated capacity', 'load'],
            'lifting_height' => ['lifting height', 'max lifting height', 'fork height', 'max height', 'mast height'],
            'mast_type' => ['mast type', 'mast', 'mast configuration'],
            'power_type' => ['power type', 'power source', 'engine type', 'fuel type'],
            'engine_power' => ['engine power', 'power', 'rated power', 'hp', 'horsepower', 'kw'],
            'battery_capacity' => ['battery capacity', 'battery', 'battery voltage', 'battery ah'],
            'fuel_consumption' => ['fuel consumption', 'consumption', 'fuel rate'],
            'max_speed' => ['max speed', 'maximum speed', 'travel speed', 'speed'],
            'turning_radius' => ['turning radius', 'min turning radius', 'turning circle'],
            'overall_length' => ['overall length', 'length', 'total length'],
            'overall_width' => ['overall width', 'width', 'total width'],
            'overall_height' => ['overall height', 'height', 'total height'],
            'wheelbase' => ['wheelbase', 'wheel base'],
            'tire_type' => ['tire type', 'tyre type', 'tire', 'tyre'],
            'manufacturer_model' => ['model', 'model number', 'model no', 'product model'],
            'year_manufactured' => ['year', 'manufacturing year', 'year of manufacture'],
            'warranty_period' => ['warranty', 'warranty period', 'warranty time'],
            'country_of_origin' => ['origin', 'country of origin', 'made in', 'manufacturing country'],
        ];
        
        foreach ($fieldMappings as $field => $keywords) {
            foreach ($keywords as $keyword) {
                foreach ($specs as $key => $value) {
                    if (stripos($key, $keyword) !== false || stripos($value, $keyword) !== false) {
                        // Try to extract numeric value
                        if (in_array($field, ['capacity', 'lifting_height', 'max_speed', 'turning_radius', 
                                              'overall_length', 'overall_width', 'overall_height', 'wheelbase'])) {
                            // Extract number from value
                            if (preg_match('/([\d.]+)/', $value, $matches)) {
                                $product[$field] = (float)$matches[1];
                            }
                        } elseif ($field === 'year_manufactured') {
                            if (preg_match('/(\d{4})/', $value, $matches)) {
                                $year = (int)$matches[1];
                                if ($year >= 1900 && $year <= date('Y') + 1) {
                                    $product[$field] = $year;
                                }
                            }
                        } else {
                            // Text field
                            $product[$field] = trim($value);
                        }
                        break 2; // Break both loops
                    }
                }
            }
        }
        
        return $product;
    }
    
    /**
     * Clean HTML content (legacy method - kept for compatibility)
     */
    private function cleanHtml($html)
    {
        // Return empty string if input is null or empty
        if (empty($html)) {
            return '';
        }
        
        // Remove script and style tags
        $html = preg_replace('/<script\b[^<]*(?:(?!<\/script>)<[^<]*)*<\/script>/is', '', $html);
        $html = preg_replace('/<style\b[^<]*(?:(?!<\/style>)<[^<]*)*<\/style>/is', '', $html);
        
        // Strip all HTML tags - we only want text
        $html = strip_tags($html);
        
        // Clean up whitespace
        $html = preg_replace('/\s+/', ' ', $html);
        $html = trim($html);
        
        return $html;
    }
    
    /**
     * Download image from URL
     */
    public function downloadImage($imageUrl, $savePath)
    {
        try {
            $ch = curl_init($imageUrl);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
            curl_setopt($ch, CURLOPT_USERAGENT, $this->userAgent);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_TIMEOUT, 30);
            
            $imageData = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $error = curl_error($ch);
            curl_close($ch);
            
            if ($error || $httpCode !== 200) {
                return false;
            }
            
            // Create directory if it doesn't exist
            $dir = dirname($savePath);
            if (!is_dir($dir)) {
                mkdir($dir, 0755, true);
            }
            
            // Save image
            file_put_contents($savePath, $imageData);
            
            return true;
        } catch (Exception $e) {
            return false;
        }
    }
}


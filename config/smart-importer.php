<?php
/**
 * Smart Product Importer Configuration
 * Configure AI API keys and settings
 */

return [
    // AI Provider: 'openai' or 'anthropic'
    'ai_provider' => 'openai',
    
    // AI API Key (get from https://platform.openai.com/api-keys or https://console.anthropic.com/)
    // Leave empty to disable AI features (pattern recognition will still work)
    // You can also set this via environment variable AI_API_KEY
    'ai_api_key' => getenv('AI_API_KEY') ?: '',
    
    // Default extraction method: 'auto', 'pattern', or 'ai'
    // 'auto' = try pattern first, use AI if confidence is low
    // 'pattern' = only use pattern recognition
    // 'ai' = force AI extraction
    'default_method' => 'auto',
    
    // Minimum confidence threshold to use AI (0-100)
    // If pattern recognition confidence is below this, AI will be used
    'ai_threshold' => 70,
    
    // Enable/disable AI features
    'ai_enabled' => true,
];


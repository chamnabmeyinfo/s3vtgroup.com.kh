<?php
/**
 * Base API Controller
 * Provides RESTful API functionality
 */
namespace App\Core\Api;

class ApiController {
    protected $db;
    
    public function __construct() {
        $this->db = db();
        $this->setCorsHeaders();
        $this->handlePreflight();
    }
    
    /**
     * Set CORS headers
     */
    protected function setCorsHeaders() {
        header('Access-Control-Allow-Origin: *');
        header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
        header('Access-Control-Allow-Headers: Content-Type, Authorization');
        header('Content-Type: application/json');
    }
    
    /**
     * Handle preflight requests
     */
    protected function handlePreflight() {
        if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
            http_response_code(200);
            exit;
        }
    }
    
    /**
     * Send JSON response
     */
    protected function jsonResponse($data, $statusCode = 200) {
        http_response_code($statusCode);
        echo json_encode($data, JSON_PRETTY_PRINT);
        exit;
    }
    
    /**
     * Send success response
     */
    protected function success($data = null, $message = 'Success', $statusCode = 200) {
        $this->jsonResponse([
            'success' => true,
            'message' => $message,
            'data' => $data,
            'timestamp' => date('c')
        ], $statusCode);
    }
    
    /**
     * Send error response
     */
    protected function error($message = 'Error', $statusCode = 400, $errors = []) {
        $this->jsonResponse([
            'success' => false,
            'message' => $message,
            'errors' => $errors,
            'timestamp' => date('c')
        ], $statusCode);
    }
    
    /**
     * Get request data
     */
    protected function getRequestData() {
        $data = json_decode(file_get_contents('php://input'), true);
        return $data ?? $_POST;
    }
    
    /**
     * Validate request
     */
    protected function validateRequest($rules, $data = null) {
        if ($data === null) {
            $data = $this->getRequestData();
        }
        
        $errors = [];
        foreach ($rules as $field => $rule) {
            $required = strpos($rule, 'required') !== false;
            
            if ($required && empty($data[$field])) {
                $errors[$field] = ucfirst($field) . ' is required';
                continue;
            }
            
            if (!empty($data[$field])) {
                // Email validation
                if (strpos($rule, 'email') !== false && !filter_var($data[$field], FILTER_VALIDATE_EMAIL)) {
                    $errors[$field] = ucfirst($field) . ' must be a valid email';
                }
                
                // Min length
                if (preg_match('/min:(\d+)/', $rule, $matches)) {
                    if (strlen($data[$field]) < $matches[1]) {
                        $errors[$field] = ucfirst($field) . ' must be at least ' . $matches[1] . ' characters';
                    }
                }
            }
        }
        
        return empty($errors) ? true : $errors;
    }
    
    /**
     * Require authentication
     */
    protected function requireAuth() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        if (!isset($_SESSION['admin_id'])) {
            $this->error('Unauthorized', 401);
        }
    }
}


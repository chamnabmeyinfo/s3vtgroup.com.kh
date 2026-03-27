<?php
/**
 * Products API v1
 * RESTful API for products
 */
require_once __DIR__ . '/../../bootstrap/app.php';

use App\Core\Api\ApiController;
use App\Models\Product;

class ProductsApi extends ApiController {
    private $productModel;
    
    public function __construct() {
        parent::__construct();
        $this->productModel = new Product();
    }
    
    public function handle() {
        $method = $_SERVER['REQUEST_METHOD'];
        $id = $_GET['id'] ?? null;
        
        switch ($method) {
            case 'GET':
                if ($id) {
                    $this->getProduct($id);
                } else {
                    $this->listProducts();
                }
                break;
            case 'POST':
                $this->createProduct();
                break;
            case 'PUT':
            case 'PATCH':
                $this->updateProduct($id);
                break;
            case 'DELETE':
                $this->deleteProduct($id);
                break;
            default:
                $this->error('Method not allowed', 405);
        }
    }
    
    private function listProducts() {
        $params = [
            'category_id' => $_GET['category_id'] ?? null,
            'search' => $_GET['search'] ?? null,
            'featured' => isset($_GET['featured']) ? (int)$_GET['featured'] : null,
            'limit' => (int)($_GET['limit'] ?? 20),
            'offset' => (int)($_GET['offset'] ?? 0),
        ];
        
        $products = $this->productModel->getAll($params);
        $total = $this->productModel->count($params);
        
        $this->success([
            'products' => $products,
            'total' => $total,
            'limit' => $params['limit'],
            'offset' => $params['offset']
        ]);
    }
    
    private function getProduct($id) {
        $product = $this->productModel->getById($id);
        
        if (!$product) {
            $this->error('Product not found', 404);
        }
        
        $this->success($product);
    }
    
    private function createProduct() {
        $this->requireAuth();
        
        $data = $this->getRequestData();
        
        $errors = $this->validateRequest([
            'name' => 'required|min:3',
            'price' => 'required',
            'category_id' => 'required'
        ], $data);
        
        if ($errors !== true) {
            $this->error('Validation failed', 400, $errors);
        }
        
        try {
            $id = $this->productModel->create($data);
            $product = $this->productModel->getById($id);
            $this->success($product, 'Product created successfully', 201);
        } catch (Exception $e) {
            $this->error('Failed to create product: ' . $e->getMessage(), 500);
        }
    }
    
    private function updateProduct($id) {
        $this->requireAuth();
        
        if (!$id) {
            $this->error('Product ID required', 400);
        }
        
        $data = $this->getRequestData();
        
        try {
            $this->productModel->update($id, $data);
            $product = $this->productModel->getById($id);
            $this->success($product, 'Product updated successfully');
        } catch (Exception $e) {
            $this->error('Failed to update product: ' . $e->getMessage(), 500);
        }
    }
    
    private function deleteProduct($id) {
        $this->requireAuth();
        
        if (!$id) {
            $this->error('Product ID required', 400);
        }
        
        try {
            $this->productModel->delete($id);
            $this->success(null, 'Product deleted successfully');
        } catch (Exception $e) {
            $this->error('Failed to delete product: ' . $e->getMessage(), 500);
        }
    }
}

$api = new ProductsApi();
$api->handle();


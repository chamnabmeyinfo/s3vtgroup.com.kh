<?php

namespace App\Models;

use App\Database\Connection;

/**
 * Order Model
 */
class Order
{
    private $db;

    public function __construct()
    {
        $this->db = Connection::getInstance();
    }

    /**
     * Get all orders with filters
     */
    public function getAll($filters = [])
    {
        $where = [];
        $params = [];

        // Status filter
        if (!empty($filters['status'])) {
            $where[] = "o.status = :status";
            $params['status'] = $filters['status'];
        }

        // Payment status filter
        if (!empty($filters['payment_status'])) {
            $where[] = "o.payment_status = :payment_status";
            $params['payment_status'] = $filters['payment_status'];
        }

        // Search filter
        if (!empty($filters['search'])) {
            $where[] = "(o.order_number LIKE :search OR 
                         c.first_name LIKE :search OR 
                         c.last_name LIKE :search OR 
                         c.email LIKE :search OR 
                         o.session_id LIKE :search)";
            $params['search'] = '%' . $filters['search'] . '%';
        }

        // Date range filter
        if (!empty($filters['date_from'])) {
            $where[] = "DATE(o.created_at) >= :date_from";
            $params['date_from'] = $filters['date_from'];
        }

        if (!empty($filters['date_to'])) {
            $where[] = "DATE(o.created_at) <= :date_to";
            $params['date_to'] = $filters['date_to'];
        }

        $whereClause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';

        // Sort
        $sort = $filters['sort'] ?? 'date_desc';
        $sortMap = [
            'date_desc' => 'o.created_at DESC',
            'date_asc' => 'o.created_at ASC',
            'total_desc' => 'o.total DESC',
            'total_asc' => 'o.total ASC',
            'number_desc' => 'o.order_number DESC',
            'number_asc' => 'o.order_number ASC',
        ];
        $orderBy = $sortMap[$sort] ?? 'o.created_at DESC';

        // Limit
        $limit = '';
        if (!empty($filters['limit'])) {
            $offset = $filters['offset'] ?? 0;
            $limit = "LIMIT " . (int)$offset . ", " . (int)$filters['limit'];
        }

        $sql = "SELECT o.*, 
                       c.first_name, 
                       c.last_name, 
                       c.email as customer_email,
                       c.phone as customer_phone,
                       COUNT(oi.id) as item_count
                FROM orders o
                LEFT JOIN customers c ON o.customer_id = c.id
                LEFT JOIN order_items oi ON o.id = oi.order_id
                {$whereClause}
                GROUP BY o.id
                ORDER BY {$orderBy}
                {$limit}";

        return $this->db->fetchAll($sql, $params);
    }

    /**
     * Get order by ID
     */
    public function getById($id)
    {
        $order = $this->db->fetchOne(
            "SELECT o.*, 
                    c.first_name, 
                    c.last_name, 
                    c.email as customer_email,
                    c.phone as customer_phone,
                    c.company as customer_company
             FROM orders o
             LEFT JOIN customers c ON o.customer_id = c.id
             WHERE o.id = :id",
            ['id' => $id]
        );

        if ($order) {
            $order['items'] = $this->getOrderItems($id);
        }

        return $order;
    }

    /**
     * Get order by order number
     */
    public function getByOrderNumber($orderNumber)
    {
        return $this->db->fetchOne(
            "SELECT * FROM orders WHERE order_number = :order_number",
            ['order_number' => $orderNumber]
        );
    }

    /**
     * Get order items
     */
    public function getOrderItems($orderId)
    {
        return $this->db->fetchAll(
            "SELECT oi.*, p.name as product_name, p.slug as product_slug, p.image
             FROM order_items oi
             LEFT JOIN products p ON oi.product_id = p.id
             WHERE oi.order_id = :order_id
             ORDER BY oi.id",
            ['order_id' => $orderId]
        );
    }

    /**
     * Update order
     */
    public function update($id, $data)
    {
        return $this->db->update('orders', $data, 'id = :id', ['id' => $id]);
    }

    /**
     * Delete order
     */
    public function delete($id)
    {
        // Delete order items first (due to foreign key)
        $this->db->delete('order_items', 'order_id = :order_id', ['order_id' => $id]);
        // Then delete order
        return $this->db->delete('orders', 'id = :id', ['id' => $id]);
    }

    /**
     * Count orders
     */
    public function count($filters = [])
    {
        $where = [];
        $params = [];

        if (!empty($filters['status'])) {
            $where[] = "status = :status";
            $params['status'] = $filters['status'];
        }

        $whereClause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';
        $result = $this->db->fetchOne(
            "SELECT COUNT(*) as count FROM orders {$whereClause}",
            $params
        );

        return $result['count'] ?? 0;
    }

    /**
     * Get orders by status
     */
    public function getByStatus($status)
    {
        return $this->getAll(['status' => $status]);
    }

    /**
     * Get orders by customer
     */
    public function getByCustomer($customerId)
    {
        return $this->db->fetchAll(
            "SELECT * FROM orders WHERE customer_id = :customer_id ORDER BY created_at DESC",
            ['customer_id' => $customerId]
        );
    }

    /**
     * Generate order number
     */
    public function generateOrderNumber()
    {
        $prefix = 'ORD';
        $date = date('Ymd');
        $random = strtoupper(substr(md5(uniqid(rand(), true)), 0, 6));
        return $prefix . '-' . $date . '-' . $random;
    }
}


<?php
/**
 * PDF Catalog Helper
 * Generates PDF catalogs from products
 */
namespace App\Helpers;

use App\Models\Product;
use App\Models\Category;
use App\Models\Setting;

class PdfCatalogHelper
{
    /**
     * Generate PDF catalog
     * @param array $filters Product filters
     * @param string $format 'pdf' or 'html'
     * @return string File path or HTML content
     */
    public static function generate($filters = [], $format = 'pdf')
    {
        $productModel = new Product();
        $categoryModel = new Category();
        $settingModel = new Setting();
        
        // Get products
        $products = $productModel->getAll($filters);
        
        // Get site settings
        $siteName = $settingModel->get('site_name', 'Forklift & Equipment Pro');
        $siteEmail = $settingModel->get('site_email', 'info@example.com');
        $sitePhone = $settingModel->get('site_phone', '');
        $siteAddress = $settingModel->get('site_address', '');
        
        // Generate HTML catalog
        $html = self::generateHtmlCatalog($products, $siteName, $siteEmail, $sitePhone, $siteAddress);
        
        if ($format === 'html') {
            return $html;
        }
        
        // Generate PDF
        return self::htmlToPdf($html, $siteName);
    }
    
    /**
     * Generate HTML catalog
     */
    private static function generateHtmlCatalog($products, $siteName, $siteEmail, $sitePhone, $siteAddress)
    {
        $date = date('F Y');
        
        $html = '<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Product Catalog - ' . htmlspecialchars($siteName) . '</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: Arial, sans-serif;
            font-size: 12px;
            line-height: 1.6;
            color: #333;
            background: #fff;
        }
        .header {
            background: linear-gradient(135deg, #1e40af 0%, #3b82f6 100%);
            color: white;
            padding: 30px 20px;
            text-align: center;
            margin-bottom: 30px;
        }
        .header h1 {
            font-size: 32px;
            margin-bottom: 10px;
        }
        .header p {
            font-size: 14px;
            opacity: 0.9;
        }
        .company-info {
            background: #f8f9fa;
            padding: 15px 20px;
            margin-bottom: 30px;
            border-left: 4px solid #3b82f6;
        }
        .company-info p {
            margin: 5px 0;
            font-size: 11px;
        }
        .products-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 20px;
            padding: 0 20px;
            margin-bottom: 30px;
        }
        .product-card {
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            overflow: hidden;
            background: white;
            page-break-inside: avoid;
        }
        .product-image {
            width: 100%;
            height: 200px;
            object-fit: cover;
            background: #f3f4f6;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #9ca3af;
            font-size: 14px;
        }
        .product-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        .product-info {
            padding: 15px;
        }
        .product-name {
            font-size: 16px;
            font-weight: bold;
            color: #1f2937;
            margin-bottom: 8px;
        }
        .product-sku {
            font-size: 10px;
            color: #6b7280;
            margin-bottom: 8px;
        }
        .product-description {
            font-size: 11px;
            color: #4b5563;
            margin-bottom: 10px;
            line-height: 1.5;
            max-height: 60px;
            overflow: hidden;
        }
        .product-price {
            font-size: 18px;
            font-weight: bold;
            color: #059669;
            margin-bottom: 5px;
        }
        .product-price .sale-price {
            color: #dc2626;
        }
        .product-price .original-price {
            text-decoration: line-through;
            color: #9ca3af;
            font-size: 14px;
            margin-right: 8px;
        }
        .product-category {
            font-size: 10px;
            color: #6b7280;
            margin-top: 5px;
        }
        .footer {
            text-align: center;
            padding: 20px;
            margin-top: 30px;
            border-top: 2px solid #e5e7eb;
            font-size: 10px;
            color: #6b7280;
        }
        @media print {
            .product-card {
                page-break-inside: avoid;
            }
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>' . htmlspecialchars($siteName) . '</h1>
        <p>Product Catalog - ' . htmlspecialchars($date) . '</p>
    </div>
    
    <div class="company-info">
        <p><strong>Contact Information:</strong></p>';
        
        if ($siteEmail) {
            $html .= '<p>Email: ' . htmlspecialchars($siteEmail) . '</p>';
        }
        if ($sitePhone) {
            $html .= '<p>Phone: ' . htmlspecialchars($sitePhone) . '</p>';
        }
        if ($siteAddress) {
            $html .= '<p>Address: ' . htmlspecialchars($siteAddress) . '</p>';
        }
        
        $html .= '</div>
    
    <div class="products-grid">';
        
        if (empty($products)) {
            $html .= '<div style="grid-column: 1 / -1; text-align: center; padding: 40px; color: #9ca3af;">
                <p>No products available in this catalog.</p>
            </div>';
        } else {
            foreach ($products as $product) {
                $html .= self::generateProductCard($product);
            }
        }
        
        $html .= '</div>
    
    <div class="footer">
        <p>Generated on ' . date('F d, Y \a\t H:i') . '</p>
        <p>For more information, visit our website or contact us directly.</p>
    </div>
</body>
</html>';
        
        return $html;
    }
    
    /**
     * Generate product card HTML
     */
    private static function generateProductCard($product)
    {
        $name = htmlspecialchars($product['name'] ?? 'Unnamed Product');
        $sku = htmlspecialchars($product['sku'] ?? 'N/A');
        $description = htmlspecialchars(strip_tags($product['short_description'] ?? $product['description'] ?? ''));
        if (strlen($description) > 100) {
            $description = substr($description, 0, 100) . '...';
        }
        
        $price = !empty($product['sale_price']) ? (float)$product['sale_price'] : (!empty($product['price']) ? (float)$product['price'] : null);
        $originalPrice = !empty($product['sale_price']) && !empty($product['price']) ? (float)$product['price'] : null;
        
        $categoryName = htmlspecialchars($product['category_name'] ?? 'Uncategorized');
        
        $imageHtml = '<div class="product-image">No Image</div>';
        if (!empty($product['image'])) {
            $imagePath = __DIR__ . '/../../' . $product['image'];
            if (file_exists($imagePath)) {
                $imageData = base64_encode(file_get_contents($imagePath));
                $imageExt = strtolower(pathinfo($product['image'], PATHINFO_EXTENSION));
                $mimeType = 'image/' . ($imageExt === 'jpg' ? 'jpeg' : $imageExt);
                $imageHtml = '<img src="data:' . $mimeType . ';base64,' . $imageData . '" alt="' . htmlspecialchars($name) . '">';
            }
        }
        
        $priceHtml = '';
        if ($price !== null) {
            if ($originalPrice && $originalPrice > $price) {
                $priceHtml = '<div class="product-price">
                    <span class="original-price">$' . number_format($originalPrice, 2) . '</span>
                    <span class="sale-price">$' . number_format($price, 2) . '</span>
                </div>';
            } else {
                $priceHtml = '<div class="product-price">$' . number_format($price, 2) . '</div>';
            }
        } else {
            $priceHtml = '<div class="product-price" style="color: #6b7280;">Contact for Price</div>';
        }
        
        return '<div class="product-card">
            <div class="product-image">' . $imageHtml . '</div>
            <div class="product-info">
                <div class="product-name">' . $name . '</div>
                <div class="product-sku">SKU: ' . $sku . '</div>
                <div class="product-description">' . $description . '</div>
                ' . $priceHtml . '
                <div class="product-category">Category: ' . $categoryName . '</div>
            </div>
        </div>';
    }
    
    /**
     * Convert HTML to PDF
     */
    private static function htmlToPdf($html, $filename)
    {
        // Try to use TCPDF if available
        if (class_exists('TCPDF')) {
            return self::generatePdfWithTcpdf($html, $filename);
        }
        
        // Try to use FPDF/HTML2PDF if available
        if (class_exists('HTML2PDF')) {
            return self::generatePdfWithHtml2pdf($html, $filename);
        }
        
        // Fallback: Save HTML file that can be converted manually
        $storageDir = __DIR__ . '/../../storage/catalogs/';
        if (!is_dir($storageDir)) {
            mkdir($storageDir, 0755, true);
        }
        
        $htmlFile = $storageDir . 'catalog_' . date('Y-m-d_His') . '.html';
        file_put_contents($htmlFile, $html);
        
        // Try to use wkhtmltopdf if available
        $pdfFile = str_replace('.html', '.pdf', $htmlFile);
        if (self::hasWkhtmltopdf()) {
            $command = 'wkhtmltopdf --page-size A4 --orientation Portrait --margin-top 10mm --margin-bottom 10mm --margin-left 10mm --margin-right 10mm "' . $htmlFile . '" "' . $pdfFile . '" 2>&1';
            exec($command, $output, $returnCode);
            
            if ($returnCode === 0 && file_exists($pdfFile)) {
                @unlink($htmlFile);
                return $pdfFile;
            }
        }
        
        // Return HTML file path as fallback
        return $htmlFile;
    }
    
    /**
     * Generate PDF using TCPDF
     */
    private static function generatePdfWithTcpdf($html, $filename)
    {
        $pdf = new \TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
        $pdf->SetCreator('Product Catalog Generator');
        $pdf->SetAuthor('Forklift & Equipment Pro');
        $pdf->SetTitle('Product Catalog');
        $pdf->SetMargins(10, 20, 10);
        $pdf->SetAutoPageBreak(TRUE, 15);
        $pdf->setImageScale(PDF_IMAGE_SCALE_RATIO);
        $pdf->AddPage();
        $pdf->writeHTML($html, true, false, true, false, '');
        
        $storageDir = __DIR__ . '/../../storage/catalogs/';
        if (!is_dir($storageDir)) {
            mkdir($storageDir, 0755, true);
        }
        
        $pdfFile = $storageDir . 'catalog_' . date('Y-m-d_His') . '.pdf';
        $pdf->Output($pdfFile, 'F');
        
        return $pdfFile;
    }
    
    /**
     * Generate PDF using HTML2PDF
     */
    private static function generatePdfWithHtml2pdf($html, $filename)
    {
        try {
            $html2pdf = new \HTML2PDF('P', 'A4', 'en');
            $html2pdf->writeHTML($html);
            
            $storageDir = __DIR__ . '/../../storage/catalogs/';
            if (!is_dir($storageDir)) {
                mkdir($storageDir, 0755, true);
            }
            
            $pdfFile = $storageDir . 'catalog_' . date('Y-m-d_His') . '.pdf';
            $html2pdf->Output($pdfFile, 'F');
            
            return $pdfFile;
        } catch (\Exception $e) {
            return null;
        }
    }
    
    /**
     * Check if wkhtmltopdf is available
     */
    private static function hasWkhtmltopdf()
    {
        $output = [];
        exec('which wkhtmltopdf 2>&1', $output, $returnCode);
        return $returnCode === 0;
    }
    
    /**
     * Send catalog via email
     */
    public static function sendCatalog($toEmail, $customerName = '', $filters = [])
    {
        $settingModel = new Setting();
        $siteName = $settingModel->get('site_name', 'Forklift & Equipment Pro');
        $siteEmail = $settingModel->get('site_email', 'info@example.com');
        
        // Generate PDF
        $pdfFile = self::generate($filters, 'pdf');
        
        // Generate HTML version for email
        $htmlContent = self::generate($filters, 'html');
        
        // Create email body
        $greeting = !empty($customerName) ? "Dear {$customerName}," : "Dear Customer,";
        
        $emailBody = "
        <html>
        <head>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background: linear-gradient(135deg, #1e40af 0%, #3b82f6 100%); color: white; padding: 20px; text-align: center; }
                .content { padding: 20px; background: #f9fafb; }
                .button { display: inline-block; padding: 12px 24px; background: #3b82f6; color: white; text-decoration: none; border-radius: 5px; margin: 20px 0; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h2>Product Catalog</h2>
                </div>
                <div class='content'>
                    <p>{$greeting}</p>
                    <p>Thank you for your interest in our products. Please find attached our complete product catalog.</p>
                    <p>The catalog includes detailed information about all our products, including specifications, pricing, and images.</p>
                    <p>If you have any questions or would like to request a quote for any product, please don't hesitate to contact us.</p>
                    <p>Best regards,<br><strong>{$siteName}</strong></p>
                </div>
            </div>
        </body>
        </html>
        ";
        
        // Send email with attachment
        $subject = "Product Catalog - {$siteName}";
        
        // Try to send directly
        $result = EmailHelper::sendWithAttachment($toEmail, $subject, $emailBody, $pdfFile, 'product-catalog.pdf');
        
        // If direct send fails, queue it
        if (!$result) {
            $result = EmailHelper::queueWithAttachment($toEmail, $subject, $emailBody, $pdfFile);
        }
        
        return $result;
    }
}

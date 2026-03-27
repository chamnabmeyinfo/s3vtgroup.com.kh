<?php
/**
 * Email Helper Class
 * Handles email notifications
 */
namespace App\Helpers;

class EmailHelper {
    /**
     * Queue an email to be sent
     */
    public static function queue($to, $subject, $body) {
        try {
            db()->insert('email_queue', [
                'to_email' => $to,
                'subject' => $subject,
                'body' => $body,
                'status' => 'pending'
            ]);
            return true;
        } catch (Exception $e) {
            return false;
        }
    }
    
    /**
     * Send notification email for new quote request
     */
    public static function sendQuoteNotification($quoteData) {
        $subject = "New Quote Request - " . ($quoteData['product_name'] ?? 'General Inquiry');
        $body = "
        <h2>New Quote Request</h2>
        <p><strong>Name:</strong> {$quoteData['name']}</p>
        <p><strong>Email:</strong> {$quoteData['email']}</p>
        <p><strong>Phone:</strong> " . ($quoteData['phone'] ?? 'N/A') . "</p>
        <p><strong>Company:</strong> " . ($quoteData['company'] ?? 'N/A') . "</p>
        <p><strong>Message:</strong></p>
        <p>" . nl2br($quoteData['message'] ?? '') . "</p>
        ";
        
        $settingModel = new \App\Models\Setting();
        $adminEmail = $settingModel->get('admin_email', 'admin@example.com');
        return self::queue($adminEmail, $subject, $body);
    }
    
    /**
     * Send notification email for new contact message
     */
    public static function sendContactNotification($messageData) {
        $subject = "New Contact Message - " . ($messageData['subject'] ?? 'No Subject');
        $body = "
        <h2>New Contact Message</h2>
        <p><strong>Name:</strong> {$messageData['name']}</p>
        <p><strong>Email:</strong> {$messageData['email']}</p>
        <p><strong>Phone:</strong> " . ($messageData['phone'] ?? 'N/A') . "</p>
        <p><strong>Message:</strong></p>
        <p>" . nl2br($messageData['message'] ?? '') . "</p>
        ";
        
        $settingModel = new \App\Models\Setting();
        $adminEmail = $settingModel->get('admin_email', 'admin@example.com');
        return self::queue($adminEmail, $subject, $body);
    }
    
    /**
     * Send order confirmation email
     */
    public static function sendOrderConfirmation($orderData) {
        $subject = "Order Confirmation - " . ($orderData['order_number'] ?? '');
        $body = "
        <h2>Thank you for your order!</h2>
        <p>Your order #{$orderData['order_number']} has been received.</p>
        <p>We will contact you shortly to confirm your order and arrange payment.</p>
        ";
        
        return self::queue($orderData['email'], $subject, $body);
    }
    
    /**
     * Send password reset email
     */
    public static function sendPasswordReset($to, $body) {
        $subject = "Password Reset Request - " . config('app.name', 'Admin Panel');
        
        // Try to queue email first
        if (self::queue($to, $subject, $body)) {
            return true;
        }
        
        // Fallback: Try to send directly via PHP mail() if queue fails
        try {
            $headers = "MIME-Version: 1.0\r\n";
            $headers .= "Content-type: text/html; charset=UTF-8\r\n";
            $headers .= "From: " . config('app.name', 'Admin Panel') . " <noreply@" . ($_SERVER['HTTP_HOST'] ?? 'localhost') . ">\r\n";
            
            return mail($to, $subject, $body, $headers);
        } catch (Exception $e) {
            return false;
        }
    }
    
    /**
     * Queue email with attachment
     */
    public static function queueWithAttachment($to, $subject, $body, $attachmentPath) {
        try {
            // Check if attachment_path column exists
            $columns = db()->fetchAll("SHOW COLUMNS FROM email_queue LIKE 'attachment_path'");
            $hasAttachmentColumn = !empty($columns);
            
            $attachmentPath = (is_file($attachmentPath) && file_exists($attachmentPath)) ? $attachmentPath : null;
            
            if ($hasAttachmentColumn && $attachmentPath) {
                db()->insert('email_queue', [
                    'to_email' => $to,
                    'subject' => $subject,
                    'body' => $body,
                    'status' => 'pending',
                    'attachment_path' => $attachmentPath
                ]);
            } else {
                // Store without attachment column, attachment will be handled during sending
                db()->insert('email_queue', [
                    'to_email' => $to,
                    'subject' => $subject,
                    'body' => $body . ($attachmentPath ? "\n\n<!-- ATTACHMENT: " . basename($attachmentPath) . " -->" : ''),
                    'status' => 'pending'
                ]);
            }
            return true;
        } catch (Exception $e) {
            // Fallback: try without attachment
            try {
                db()->insert('email_queue', [
                    'to_email' => $to,
                    'subject' => $subject,
                    'body' => $body,
                    'status' => 'pending'
                ]);
                return true;
            } catch (Exception $e2) {
                return false;
            }
        }
    }
    
    /**
     * Send email directly with attachment
     */
    public static function sendWithAttachment($to, $subject, $body, $attachmentPath, $attachmentName = null) {
        $settingModel = new \App\Models\Setting();
        $siteName = $settingModel->get('site_name', 'Forklift & Equipment Pro');
        $siteEmail = $settingModel->get('site_email', 'noreply@example.com');
        
        if (!file_exists($attachmentPath)) {
            return false;
        }
        
        $attachmentName = $attachmentName ?: basename($attachmentPath);
        $fileContent = file_get_contents($attachmentPath);
        $fileEncoded = chunk_split(base64_encode($fileContent));
        $fileSize = filesize($attachmentPath);
        $fileType = mime_content_type($attachmentPath);
        
        // Generate boundary
        $boundary = md5(time());
        
        // Email headers
        $headers = "MIME-Version: 1.0\r\n";
        $headers .= "From: {$siteName} <{$siteEmail}>\r\n";
        $headers .= "Reply-To: {$siteEmail}\r\n";
        $headers .= "Content-Type: multipart/mixed; boundary=\"{$boundary}\"\r\n";
        
        // Email body
        $message = "--{$boundary}\r\n";
        $message .= "Content-Type: text/html; charset=UTF-8\r\n";
        $message .= "Content-Transfer-Encoding: 7bit\r\n\r\n";
        $message .= $body . "\r\n\r\n";
        
        // Attachment
        $message .= "--{$boundary}\r\n";
        $message .= "Content-Type: {$fileType}; name=\"{$attachmentName}\"\r\n";
        $message .= "Content-Disposition: attachment; filename=\"{$attachmentName}\"\r\n";
        $message .= "Content-Transfer-Encoding: base64\r\n\r\n";
        $message .= $fileEncoded . "\r\n";
        $message .= "--{$boundary}--";
        
        return mail($to, $subject, $message, $headers);
    }
}


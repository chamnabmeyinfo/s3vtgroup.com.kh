-- CEO Message Table
-- Stores CEO message content that can be edited through admin panel
CREATE TABLE IF NOT EXISTS ceo_message (
    id INT AUTO_INCREMENT PRIMARY KEY,
    ceo_name VARCHAR(255) NOT NULL DEFAULT 'CEO',
    ceo_title VARCHAR(255) NOT NULL DEFAULT 'Chief Executive Officer',
    ceo_photo VARCHAR(255) NULL,
    greeting VARCHAR(255) NOT NULL DEFAULT 'Dear Valued Customers and Partners,',
    message_content TEXT NOT NULL,
    signature_name VARCHAR(255) NOT NULL DEFAULT 'CEO',
    signature_title VARCHAR(255) NULL,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;
-- Insert default CEO message
INSERT INTO ceo_message (
        ceo_name,
        ceo_title,
        greeting,
        message_content,
        signature_name,
        signature_title,
        is_active
    )
VALUES (
        'CEO',
        'Chief Executive Officer',
        'Dear Valued Customers and Partners,',
        '<p>It is with great pleasure and pride that I welcome you to our company. As the Chief Executive Officer, I am honored to lead a team of dedicated professionals who are committed to delivering excellence in every aspect of our business.</p><p>Our company was founded on the principles of quality, integrity, and customer satisfaction. These core values have guided us through years of growth and have established us as a trusted leader in the forklift and industrial equipment industry.</p><p>We understand that your business success depends on reliable, efficient equipment. That\'s why we go beyond simply selling products – we partner with you to understand your unique needs and provide solutions that drive your operational excellence.</p><p>Our commitment extends to every interaction: from the initial consultation through installation, training, and ongoing support. We believe in building long-term relationships, not just making transactions.</p><p>As we look to the future, we remain dedicated to innovation, continuous improvement, and exceeding your expectations. We invest in our team, our technology, and our processes to ensure we can serve you better every day.</p><p>Thank you for choosing us. We are here to support your success, and we look forward to being your trusted partner for years to come.</p>',
        'CEO',
        'Chief Executive Officer',
        1
    ) ON DUPLICATE KEY
UPDATE id = id;
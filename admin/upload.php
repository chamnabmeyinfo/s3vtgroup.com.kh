<?php
/**
 * Image Upload Handler - Supports Multiple Files
 */
require_once __DIR__ . '/../bootstrap/app.php';
require_once __DIR__ . '/includes/auth.php';

header('Content-Type: application/json');

$response = ['success' => false, 'message' => '', 'files' => []];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $uploadDir = __DIR__ . '/../storage/uploads/';
    
    // Create upload directory if it doesn't exist
    if (!is_dir($uploadDir)) {
        if (!mkdir($uploadDir, 0755, true)) {
            $response['message'] = 'Failed to create upload directory.';
            echo json_encode($response);
            exit;
        }
    }
    
    // Check if directory is writable
    if (!is_writable($uploadDir)) {
        $response['message'] = 'Upload directory is not writable.';
        echo json_encode($response);
        exit;
    }
    
    $uploadedFiles = [];
    $errors = [];
    
    // Handle multiple files (files[]) or single file (file)
    $files = [];
    
    // Check for files[] (multiple files from FormData)
    if (isset($_FILES['files']) && is_array($_FILES['files']['name'])) {
        // Multiple files - files[] format
        for ($i = 0; $i < count($_FILES['files']['name']); $i++) {
            $files[] = [
                'name' => $_FILES['files']['name'][$i],
                'type' => $_FILES['files']['type'][$i],
                'tmp_name' => $_FILES['files']['tmp_name'][$i],
                'error' => $_FILES['files']['error'][$i],
                'size' => $_FILES['files']['size'][$i]
            ];
        }
    } elseif (isset($_FILES['file'])) {
        // Single file (backward compatibility)
        $files[] = $_FILES['file'];
    } else {
        // No files found
        $response['message'] = 'No files were uploaded.';
        echo json_encode($response);
        exit;
    }
    
    foreach ($files as $file) {
        // Check for errors
        if ($file['error'] !== UPLOAD_ERR_OK) {
            $errorMessage = 'Upload error occurred.';
            switch ($file['error']) {
                case UPLOAD_ERR_INI_SIZE:
                case UPLOAD_ERR_FORM_SIZE:
                    $errorMessage = 'File too large. Maximum upload size is ' . ini_get('upload_max_filesize');
                    break;
                case UPLOAD_ERR_PARTIAL:
                    $errorMessage = 'File was only partially uploaded.';
                    break;
                case UPLOAD_ERR_NO_FILE:
                    $errorMessage = 'No file was uploaded.';
                    break;
                case UPLOAD_ERR_NO_TMP_DIR:
                    $errorMessage = 'Missing temporary folder.';
                    break;
                case UPLOAD_ERR_CANT_WRITE:
                    $errorMessage = 'Failed to write file to disk.';
                    break;
                case UPLOAD_ERR_EXTENSION:
                    $errorMessage = 'File upload stopped by extension.';
                    break;
            }
            $errors[] = $file['name'] . ': ' . $errorMessage;
            continue;
        }
        
        // Validate file type
        $allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp', 'image/svg+xml'];
        
        // Check if file exists and is readable
        if (!file_exists($file['tmp_name']) || !is_readable($file['tmp_name'])) {
            $errors[] = $file['name'] . ': File is not readable.';
            continue;
        }
        
        // Get MIME type
        $mimeType = null;
        if (function_exists('finfo_open')) {
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mimeType = finfo_file($finfo, $file['tmp_name']);
            finfo_close($finfo);
        } elseif (function_exists('mime_content_type')) {
            $mimeType = mime_content_type($file['tmp_name']);
        } else {
            // Fallback to file extension
            $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            $extensionMap = [
                'jpg' => 'image/jpeg',
                'jpeg' => 'image/jpeg',
                'png' => 'image/png',
                'gif' => 'image/gif',
                'webp' => 'image/webp',
                'svg' => 'image/svg+xml'
            ];
            $mimeType = $extensionMap[$extension] ?? null;
        }
        
        if (!$mimeType || !in_array($mimeType, $allowedTypes)) {
            $errors[] = $file['name'] . ': Invalid file type. Only images are allowed.';
            continue;
        }
        
        // Security: Sanitize SVG files to prevent XSS
        if ($mimeType === 'image/svg+xml') {
            $svgContent = file_get_contents($file['tmp_name']);
            // Remove potentially dangerous SVG content
            $dangerousPatterns = [
                '/<script\b[^<]*(?:(?!<\/script>)<[^<]*)*<\/script>/is',
                '/javascript:/i',
                '/onerror\s*=/i',
                '/onload\s*=/i',
                '/onclick\s*=/i',
                '/onmouseover\s*=/i',
                '/<iframe/i',
                '/<embed/i',
                '/<object/i',
            ];
            
            foreach ($dangerousPatterns as $pattern) {
                if (preg_match($pattern, $svgContent)) {
                    $errors[] = $file['name'] . ': SVG file contains potentially dangerous content.';
                    continue 2; // Skip to next file
                }
            }
        }
        
        // Security: Validate file extension matches MIME type
        $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg'];
        if (!in_array($extension, $allowedExtensions)) {
            $errors[] = $file['name'] . ': Invalid file extension.';
            continue;
        }
        
        // Security: Additional validation - check file signature (magic bytes)
        $fileSignature = file_get_contents($file['tmp_name'], false, null, 0, 12);
        $validSignatures = [
            'image/jpeg' => ["\xFF\xD8\xFF"],
            'image/png' => ["\x89\x50\x4E\x47"],
            'image/gif' => ["\x47\x49\x46\x38"],
            'image/webp' => ["RIFF"],
        ];
        
        if (isset($validSignatures[$mimeType])) {
            $valid = false;
            foreach ($validSignatures[$mimeType] as $signature) {
                if (strpos($fileSignature, $signature) === 0) {
                    $valid = true;
                    break;
                }
            }
            if (!$valid && $mimeType !== 'image/svg+xml') { // SVG doesn't have fixed signature
                $errors[] = $file['name'] . ': File signature does not match declared type.';
                continue;
            }
        }
        
        // Validate file size (5MB max)
        if ($file['size'] > 5 * 1024 * 1024) {
            $errors[] = $file['name'] . ': File too large. Maximum size is 5MB.';
            continue;
        }
        
        // Generate unique filename (preserve original name if possible, or use unique ID)
        $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $originalName = pathinfo($file['name'], PATHINFO_FILENAME);
        // Sanitize filename
        $originalName = preg_replace('/[^a-zA-Z0-9_-]/', '_', $originalName);
        $filename = $originalName . '_' . uniqid() . '.' . $extension;
        $filepath = $uploadDir . $filename;
        
        // Ensure unique filename
        $counter = 1;
        while (file_exists($filepath)) {
            $filename = $originalName . '_' . uniqid() . '_' . $counter . '.' . $extension;
            $filepath = $uploadDir . $filename;
            $counter++;
        }
        
        // Move uploaded file
        if (move_uploaded_file($file['tmp_name'], $filepath)) {
            $uploadedFiles[] = [
                'filename' => $filename,
                'url' => asset('storage/uploads/' . $filename),
                'size' => $file['size']
            ];
        } else {
            $errors[] = $file['name'] . ': Failed to move uploaded file.';
        }
    }
    
    if (!empty($uploadedFiles)) {
        $response['success'] = true;
        $response['message'] = 'Successfully uploaded ' . count($uploadedFiles) . ' file(s).';
        $response['files'] = $uploadedFiles;
        
        // For backward compatibility with single file uploads
        // If only one file was uploaded, also provide 'file' and 'url' keys
        if (count($uploadedFiles) === 1) {
            $response['file'] = $uploadedFiles[0]['filename'];
            $response['url'] = $uploadedFiles[0]['url'];
        }
        
        if (!empty($errors)) {
            $response['message'] .= ' ' . count($errors) . ' file(s) failed.';
            $response['errors'] = $errors;
        }
    } else {
        $response['message'] = !empty($errors) ? implode(' ', $errors) : 'No files uploaded.';
    }
} else {
    $response['message'] = 'No files uploaded.';
}

echo json_encode($response);


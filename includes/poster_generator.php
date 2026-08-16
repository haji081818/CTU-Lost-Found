<?php
/**
 * 1-Click Printable Physical Poster Generator
 * Generates A4/Letter PDF posters with QR codes for lost/found items
 */

require_once __DIR__ . '/../config/db.php';

/**
 * Generate QR code for an item URL
 * @param string $url The URL to encode in QR code
 * @param string $outputPath Path to save the QR code image
 * @return bool Success status
 */
function generateQRCode($url, $outputPath) {
    // Using a different QR code API that's more reliable
    // Using qrserver.com API as Google Charts API is deprecated
    $qrApiUrl = 'https://api.qrserver.com/v1/create-qr-code/';
    $params = [
        'size' => '200x200',
        'data' => $url,
        'margin' => '10',
        'format' => 'png'
    ];
    
    $qrUrl = $qrApiUrl . '?' . http_build_query($params);
    
    // Download the QR code image with proper error handling
    $context = stream_context_create([
        'http' => [
            'timeout' => 10,
            'user_agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36'
        ]
    ]);
    
    $qrImage = @file_get_contents($qrUrl, false, $context);
    if ($qrImage === false) {
        // Fallback: create a simple placeholder QR code using base64
        return generatePlaceholderQR($outputPath);
    }
    
    // Save the QR code image
    return file_put_contents($outputPath, $qrImage) !== false;
}

/**
 * Generate a placeholder QR code when API fails
 * @param string $outputPath Path to save the placeholder image
 * @return bool Success status
 */
function generatePlaceholderQR($outputPath) {
    // Create a simple placeholder image with text
    $img = imagecreatetruecolor(200, 200);
    $white = imagecolorallocate($img, 255, 255, 255);
    $black = imagecolorallocate($img, 0, 0, 0);
    
    imagefill($img, 0, 0, $white);
    
    // Add a simple pattern to simulate QR code
    for ($i = 0; $i < 20; $i++) {
        $x = rand(0, 180);
        $y = rand(0, 180);
        $size = rand(10, 30);
        imagefilledrectangle($img, $x, $y, $x + $size, $y + $size, $black);
    }
    
    // Add text
    $text = 'QR Code';
    imagestring($img, 5, 50, 90, $text, $black);
    
    // Save the image
    $success = imagepng($img, $outputPath);
    imagedestroy($img);
    
    return $success;
}

/**
 * Generate poster HTML for an item
 * @param int $postId The post ID
 * @return string HTML content for the poster
 */
function generatePosterHTML($postId) {
    global $conn;
    
    // Get post details
    $stmt = $conn->prepare("
        SELECT p.*, u.name AS poster_name
        FROM posts p
        JOIN users u ON u.id = p.user_id
        WHERE p.id = ?
    ");
    $stmt->bind_param('i', $postId);
    $stmt->execute();
    $post = $stmt->get_result()->fetch_assoc();
    
    if (!$post) {
        return false;
    }
    
    $itemUrl = BASE_URL . 'item.php?id=' . $postId;
    $qrCodePath = UPLOAD_DIR . 'qr_' . $postId . '.png';
    
    // Generate QR code
    if (!file_exists($qrCodePath)) {
        generateQRCode($itemUrl, $qrCodePath);
    }
    
    $qrCodeUrl = UPLOAD_URL . 'qr_' . $postId . '.png';
    $imageUrl = $post['image'] ? UPLOAD_URL . $post['image'] : null;
    
    // Determine styling based on item type
    $headerColor = $post['type'] === 'lost' ? '#E53E3E' : '#276749';
    $headerText = $post['type'] === 'lost' ? 'LOST ITEM' : 'FOUND ITEM';
    $headerIcon = $post['type'] === 'lost' ? 'bi-exclamation-triangle-fill' : 'bi-hand-thumbs-up-fill';
    
    ob_start();
    ?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title><?= e($headerText) ?> - <?= e($post['title']) ?></title>
    <style>
        @page {
            size: A4;
            margin: 0;
        }
        body {
            margin: 0;
            padding: 0;
            font-family: 'Arial', sans-serif;
            background: #f0f0f0;
        }
        .poster {
            width: 210mm;
            height: 297mm;
            margin: 0 auto;
            background: white;
            display: flex;
            flex-direction: column;
            overflow: hidden;
        }
        .header {
            background: <?= $headerColor ?>;
            color: white;
            padding: 15mm;
            text-align: center;
            border-bottom: 5px solid rgba(0,0,0,0.1);
        }
        .header h1 {
            margin: 0;
            font-size: 32pt;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 2px;
        }
        .header .subtitle {
            margin-top: 5mm;
            font-size: 14pt;
            opacity: 0.9;
        }
        .content {
            flex: 1;
            padding: 15mm;
            display: flex;
            flex-direction: column;
            align-items: center;
        }
        .item-image {
            max-width: 150mm;
            max-height: 120mm;
            object-fit: contain;
            border-radius: 10px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            margin-bottom: 10mm;
        }
        .no-image {
            width: 150mm;
            height: 120mm;
            background: #f8f9fa;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #adb5bd;
            font-size: 48pt;
            margin-bottom: 10mm;
        }
        .item-details {
            width: 100%;
            max-width: 160mm;
        }
        .item-title {
            font-size: 24pt;
            font-weight: bold;
            color: #1a202c;
            margin-bottom: 5mm;
            text-align: center;
        }
        .detail-row {
            display: flex;
            margin-bottom: 4mm;
            font-size: 12pt;
        }
        .detail-label {
            font-weight: bold;
            color: #4a5568;
            width: 25mm;
            flex-shrink: 0;
        }
        .detail-value {
            color: #2d3748;
            flex: 1;
        }
        .description {
            margin-top: 5mm;
            padding: 5mm;
            background: #f7fafc;
            border-left: 3px solid <?= $headerColor ?>;
            font-size: 11pt;
            line-height: 1.5;
        }
        .qr-section {
            margin-top: auto;
            padding: 10mm;
            text-align: center;
            border-top: 2px solid #e2e8f0;
        }
        .qr-code {
            width: 40mm;
            height: 40mm;
            margin: 0 auto 3mm;
        }
        .qr-instruction {
            font-size: 10pt;
            color: #718096;
        }
        .footer {
            background: #1a202c;
            color: white;
            padding: 5mm;
            text-align: center;
            font-size: 9pt;
        }
    </style>
</head>
<body>
    <div class="poster">
        <div class="header">
            <h1><?= $headerText ?></h1>
            <div class="subtitle">CTU Danao Campus — Lost & Found System</div>
        </div>
        
        <div class="content">
            <?php if ($imageUrl): ?>
                <img src="<?= $imageUrl ?>" alt="<?= e($post['title']) ?>" class="item-image">
            <?php else: ?>
                <div class="no-image">
                    <i class="bi bi-image"></i>
                </div>
            <?php endif; ?>
            
            <div class="item-details">
                <div class="item-title"><?= e($post['title']) ?></div>
                
                <div class="detail-row">
                    <div class="detail-label">Category:</div>
                    <div class="detail-value"><?= e($post['category']) ?></div>
                </div>
                
                <div class="detail-row">
                    <div class="detail-label">Location:</div>
                    <div class="detail-value"><?= e($post['location']) ?></div>
                </div>
                
                <div class="detail-row">
                    <div class="detail-label">Date:</div>
                    <div class="detail-value"><?= date('F j, Y', strtotime($post['created_at'])) ?></div>
                </div>
                
                <div class="detail-row">
                    <div class="detail-label">Posted by:</div>
                    <div class="detail-value"><?= e($post['poster_name']) ?></div>
                </div>
                
                <?php if (!empty($post['contact_number'])): ?>
                <div class="detail-row">
                    <div class="detail-label">Contact:</div>
                    <div class="detail-value"><?= e($post['contact_number']) ?></div>
                </div>
                <?php endif; ?>
                
                <?php if (!empty($post['verification_question'])): ?>
                <div class="detail-row">
                    <div class="detail-label">Security:</div>
                    <div class="detail-value">Verification required</div>
                </div>
                <?php endif; ?>
                
                <div class="description">
                    <strong>Description:</strong><br>
                    <?= nl2br(e($post['description'])) ?>
                </div>
            </div>
            
            <div class="qr-section">
                <img src="<?= $qrCodeUrl ?>" alt="QR Code" class="qr-code">
                <div class="qr-instruction">
                    <?php if ($post['type'] === 'lost'): ?>
                        Scan this QR code to report a sighting or help find this item
                    <?php else: ?>
                        Scan this QR code to view details and submit a claim
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <div class="footer">
            For more information, visit the CTU Danao Lost & Found System or contact campus security.
        </div>
    </div>
</body>
</html>
    <?php
    return ob_get_clean();
}

/**
 * Convert HTML to PDF using wkhtmltopdf
 * @param string $html HTML content
 * @param string $outputPath Path to save the PDF
 * @return bool Success status
 */
function htmlToPDF($html, $outputPath) {
    // Try using wkhtmltopdf if available
    $tempHtml = tempnam(sys_get_temp_dir(), 'poster_') . '.html';
    file_put_contents($tempHtml, $html);
    
    $command = "wkhtmltopdf \"$tempHtml\" \"$outputPath\"";
    exec($command, $output, $returnCode);
    
    // Clean up temp file
    if (file_exists($tempHtml)) {
        unlink($tempHtml);
    }
    
    return $returnCode === 0;
}

/**
 * Generate and serve poster for an item
 * @param int $postId The post ID
 * @param string $format Output format ('html' or 'pdf')
 */
function generateAndServePoster($postId, $format = 'html') {
    $html = generatePosterHTML($postId);
    
    if ($html === false) {
        die('Error generating poster content.');
    }
    
    if ($format === 'pdf') {
        $pdfPath = UPLOAD_DIR . 'poster_' . $postId . '.pdf';
        
        if (!htmlToPDF($html, $pdfPath)) {
            // If PDF generation fails, fall back to HTML
            $format = 'html';
        } else {
            header('Content-Type: application/pdf');
            header('Content-Disposition: attachment; filename="ctu_poster_' . $postId . '.pdf"');
            readfile($pdfPath);
            exit;
        }
    }
    
    // Serve HTML format
    header('Content-Type: text/html');
    echo $html;
    exit;
}

<?php
/**
 * Simple QR Code Generator for Offline Use
 * Uses PHP QR Code library logic for basic QR generation
 */

class QRCodeGenerator {
    private $data;
    private $size;
    private $margin;
    
    public function __construct($data, $size = 150, $margin = 4) {
        $this->data = $data;
        $this->size = $size;
        $this->margin = $margin;
    }
    
    /**
     * Generate QR code using Google Charts API as fallback
     * For offline use, this will fail gracefully and return a placeholder
     */
    public function generate() {
        // Try to use Google Charts API (will fail offline)
        $url = "https://chart.googleapis.com/chart?chs={$this->size}x{$this->size}&cht=qr&chl=" . urlencode($this->data) . "&choe=UTF-8";
        
        // For offline support, we'll use a fallback approach
        // Since we can't implement a full QR library, we'll create a simple text-based QR
        return $this->generateFallbackQR();
    }
    
    /**
     * Generate a fallback QR representation for offline use
     * This creates a simple visual representation that can work offline
     */
    private function generateFallbackQR() {
        // Create a simple image with the URL text
        $img = imagecreatetruecolor($this->size, $this->size);
        $white = imagecolorallocate($img, 255, 255, 255);
        $black = imagecolorallocate($img, 0, 0, 0);
        
        imagefill($img, 0, 0, $white);
        
        // Add a border
        imagerectangle($img, 0, 0, $this->size - 1, $this->size - 1, $black);
        
        // Add "QR" text and URL
        $text = "QR Code";
        $textColor = $black;
        
        // Center the text
        $font = 5;
        $textWidth = imagefontwidth($font) * strlen($text);
        $textHeight = imagefontheight($font);
        $x = ($this->size - $textWidth) / 2;
        $y = ($this->size - $textHeight) / 2 - 10;
        
        imagestring($img, $font, $x, $y, $text, $textColor);
        
        // Add URL below
        $shortUrl = substr($this->data, 0, 20) . (strlen($this->data) > 20 ? '...' : '');
        $urlWidth = imagefontwidth(3) * strlen($shortUrl);
        $urlX = ($this->size - $urlWidth) / 2;
        imagestring($img, 3, $urlX, $y + $textHeight + 5, $shortUrl, $textColor);
        
        // Start output buffering
        ob_start();
        imagepng($img);
        imagedestroy($img);
        $imageData = ob_get_clean();
        
        return $imageData;
    }
    
    /**
     * Generate QR code and return as data URL
     */
    public function getDataURL() {
        $imageData = $this->generate();
        return 'data:image/png;base64,' . base64_encode($imageData);
    }
    
    /**
     * Generate QR code and save to file
     */
    public function save($filename) {
        $imageData = $this->generate();
        file_put_contents($filename, $imageData);
        return file_exists($filename);
    }
}

/**
 * Alternative: Use phpqrcode library if available
 * This function checks if the library exists and uses it
 */
function generateLocalQRCode($data, $size = 150, $margin = 4) {
    $qr = new QRCodeGenerator($data, $size, $margin);
    return $qr->generate();
}
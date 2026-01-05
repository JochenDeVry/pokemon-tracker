<?php

class CardImageOCR {
    private $uploadDir;
    
    public function __construct() {
        $this->uploadDir = __DIR__ . '/../uploads/';
        if (!file_exists($this->uploadDir)) {
            mkdir($this->uploadDir, 0777, true);
        }
    }
    
    /**
     * Process uploaded image and extract card data
     */
    public function processImage($file) {
        try {
            // Validate file
            $validation = $this->validateImage($file);
            if (!$validation['valid']) {
                return ['success' => false, 'error' => $validation['error']];
            }
            
            // Save uploaded file
            $filename = uniqid('card_') . '_' . basename($file['name']);
            $filepath = $this->uploadDir . $filename;
            
            if (!move_uploaded_file($file['tmp_name'], $filepath)) {
                return ['success' => false, 'error' => 'Failed to save uploaded file'];
            }
            
            // Run OCR
            $ocrText = $this->runOCR($filepath);
            
            // Parse card data from OCR text
            $cardData = $this->parseCardData($ocrText);
            
            // Clean up uploaded file
            unlink($filepath);
            
            return [
                'success' => true,
                'data' => $cardData,
                'raw_text' => $ocrText
            ];
            
        } catch (Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
    
    /**
     * Validate uploaded image
     */
    private function validateImage($file) {
        if (!isset($file['error']) || is_array($file['error'])) {
            return ['valid' => false, 'error' => 'Invalid file upload'];
        }
        
        switch ($file['error']) {
            case UPLOAD_ERR_OK:
                break;
            case UPLOAD_ERR_INI_SIZE:
            case UPLOAD_ERR_FORM_SIZE:
                return ['valid' => false, 'error' => 'File is too large'];
            default:
                return ['valid' => false, 'error' => 'Unknown error during upload'];
        }
        
        // Check file size (max 10MB)
        if ($file['size'] > 10 * 1024 * 1024) {
            return ['valid' => false, 'error' => 'File size exceeds 10MB'];
        }
        
        // Check MIME type
        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mimeType = $finfo->file($file['tmp_name']);
        $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        
        if (!in_array($mimeType, $allowedTypes)) {
            return ['valid' => false, 'error' => 'Invalid file type. Only JPG, PNG, GIF, and WebP are allowed'];
        }
        
        return ['valid' => true];
    }
    
    /**
     * Run Tesseract OCR on image
     */
    private function runOCR($filepath) {
        $outputFile = $this->uploadDir . 'ocr_' . uniqid();
        
        // Run tesseract
        $command = sprintf(
            'tesseract %s %s 2>&1',
            escapeshellarg($filepath),
            escapeshellarg($outputFile)
        );
        
        exec($command, $output, $returnCode);
        
        $textFile = $outputFile . '.txt';
        if (!file_exists($textFile)) {
            throw new Exception('OCR failed to generate output');
        }
        
        $text = file_get_contents($textFile);
        unlink($textFile);
        
        return $text;
    }
    
    /**
     * Parse card data from OCR text
     */
    private function parseCardData($text) {
        $data = [
            'card_name' => null,
            'serial_number' => null,
            'set_name' => null,
            'card_number' => null,
            'rarity' => null,
            'hp' => null,
            'card_type' => null
        ];
        
        $lines = explode("\n", $text);
        $text = trim($text);
        
        // Extract HP (usually "HP 120" or "120 HP")
        if (preg_match('/\b(\d{2,3})\s*HP\b/i', $text, $matches)) {
            $data['hp'] = $matches[1];
        } elseif (preg_match('/\bHP\s*(\d{2,3})\b/i', $text, $matches)) {
            $data['hp'] = $matches[1];
        }
        
        // Extract card number (format: XXX/XXX or XXX)
        if (preg_match('/\b(\d{1,4}\/\d{1,4})\b/', $text, $matches)) {
            $data['card_number'] = $matches[1];
        } elseif (preg_match('/\b#(\d{1,4})\b/', $text, $matches)) {
            $data['card_number'] = $matches[1];
        }
        
        // Extract serial number (various formats)
        if (preg_match('/\b([A-Z]{2,4}[-\s]?\d{1,4}[A-Z]?)\b/', $text, $matches)) {
            $data['serial_number'] = str_replace(' ', '', $matches[1]);
        }
        
        // Common Pokemon card types
        $types = ['Grass', 'Fire', 'Water', 'Lightning', 'Psychic', 'Fighting', 'Darkness', 'Metal', 'Fairy', 'Dragon', 'Colorless'];
        foreach ($types as $type) {
            if (stripos($text, $type) !== false) {
                $data['card_type'] = $type;
                break;
            }
        }
        
        // Rarity indicators
        $rarities = [
            'Common' => '/\b(Common|●)\b/i',
            'Uncommon' => '/\b(Uncommon|◆)\b/i',
            'Rare' => '/\b(Rare|★)\b/i',
            'Rare Holo' => '/\b(Rare\s+Holo|Holo\s+Rare)\b/i',
            'Ultra Rare' => '/\b(Ultra\s+Rare|UR)\b/i',
            'Secret Rare' => '/\b(Secret\s+Rare|SR)\b/i',
            'Rare Rainbow' => '/\b(Rainbow\s+Rare|RR)\b/i'
        ];
        
        foreach ($rarities as $rarity => $pattern) {
            if (preg_match($pattern, $text)) {
                $data['rarity'] = $rarity;
                break;
            }
        }
        
        // Try to extract card name (usually first or second line, capitalized)
        $potentialNames = [];
        foreach ($lines as $line) {
            $line = trim($line);
            if (strlen($line) > 2 && strlen($line) < 50) {
                // Look for lines with mostly letters and spaces
                if (preg_match('/^[A-Za-z\s\-\'\.]+$/', $line)) {
                    $potentialNames[] = $line;
                }
            }
        }
        
        if (!empty($potentialNames)) {
            // Use the longest potential name (usually the card name)
            usort($potentialNames, function($a, $b) {
                return strlen($b) - strlen($a);
            });
            $data['card_name'] = $potentialNames[0];
        }
        
        // Common set abbreviations
        $sets = [
            'SV' => 'Scarlet & Violet',
            'PAL' => 'Paldea Evolved',
            'OBF' => 'Obsidian Flames',
            'MEW' => '151',
            'PAR' => 'Paradox Rift',
            'SSH' => 'Sword & Shield',
            'BST' => 'Battle Styles',
            'CRE' => 'Chilling Reign',
            'EVS' => 'Evolving Skies',
            'FST' => 'Fusion Strike',
            'BRS' => 'Brilliant Stars',
            'ASR' => 'Astral Radiance',
            'LOR' => 'Lost Origin',
            'SIT' => 'Silver Tempest'
        ];
        
        foreach ($sets as $abbr => $fullName) {
            if (stripos($text, $abbr) !== false) {
                $data['set_name'] = $fullName;
                break;
            }
        }
        
        return $data;
    }
}

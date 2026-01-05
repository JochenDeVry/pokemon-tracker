<?php
class CardmarketScraper {
    private $userAgent = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36';

    /**
     * Scrape card information from Cardmarket URL
     */
    public function scrapeCard($url) {
        try {
            $html = $this->fetchUrl($url);
            if (!$html) {
                return null;
            }

            $dom = new DOMDocument();
            @$dom->loadHTML($html);
            $xpath = new DOMXPath($dom);

            $cardInfo = [
                'card_name' => $this->extractCardName($xpath),
                'set_name' => $this->extractSetName($xpath),
                'card_number' => $this->extractCardNumber($xpath),
                'rarity' => $this->extractRarity($xpath),
                'current_price' => $this->extractPrice($xpath),
                'image_url' => $this->extractImage($xpath),
                'cardmarket_url' => $url
            ];

            return $cardInfo;

        } catch (Exception $e) {
            error_log("Scrape error: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Get only the price from a Cardmarket URL
     */
    public function getPrice($url) {
        try {
            $html = $this->fetchUrl($url);
            if (!$html) {
                return null;
            }

            $dom = new DOMDocument();
            @$dom->loadHTML($html);
            $xpath = new DOMXPath($dom);

            return $this->extractPrice($xpath);

        } catch (Exception $e) {
            error_log("Price fetch error: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Fetch URL content with cURL
     */
    private function fetchUrl($url) {
        $ch = curl_init();
        
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_USERAGENT, $this->userAgent);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_ENCODING, ''); // Enable automatic decompression
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,*/*;q=0.8',
            'Accept-Language: en-US,en;q=0.9,nl;q=0.8',
            'Accept-Encoding: gzip, deflate, br',
            'Connection: keep-alive',
            'Upgrade-Insecure-Requests: 1',
            'Sec-Fetch-Dest: document',
            'Sec-Fetch-Mode: navigate',
            'Sec-Fetch-Site: none',
            'Cache-Control: max-age=0',
            'DNT: 1'
        ]);
        
        // Add cookies to appear more like a real browser
        curl_setopt($ch, CURLOPT_COOKIEJAR, '/tmp/cardmarket_cookies.txt');
        curl_setopt($ch, CURLOPT_COOKIEFILE, '/tmp/cardmarket_cookies.txt');

        $html = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        
        curl_close($ch);

        if ($httpCode === 403) {
            error_log("Cardmarket blocked request (403). Probeer handmatig gegevens invoeren.");
            return null;
        }
        
        if ($httpCode !== 200) {
            error_log("HTTP Error: " . $httpCode . " voor URL: " . $url);
            return null;
        }

        return $html;
    }

    /**
     * Extract card name from page
     */
    private function extractCardName($xpath) {
        // Try multiple selectors for card name
        $selectors = [
            "//h1[contains(@class, 'product-name')]",
            "//h1[@class='title']",
            "//div[contains(@class, 'product-title')]//h1",
            "//h1"
        ];

        foreach ($selectors as $selector) {
            $nodes = $xpath->query($selector);
            if ($nodes->length > 0) {
                $name = trim($nodes->item(0)->textContent);
                // Clean up the name
                $name = preg_replace('/\s+/', ' ', $name);
                return $name;
            }
        }

        return '';
    }

    /**
     * Extract set name from page
     */
    private function extractSetName($xpath) {
        $selectors = [
            "//a[contains(@href, '/Expansions/')]/span",
            "//div[contains(@class, 'expansion')]//a",
            "//span[contains(@class, 'expansion-name')]"
        ];

        foreach ($selectors as $selector) {
            $nodes = $xpath->query($selector);
            if ($nodes->length > 0) {
                return trim($nodes->item(0)->textContent);
            }
        }

        return '';
    }

    /**
     * Extract card number from page
     */
    private function extractCardNumber($xpath) {
        $selectors = [
            "//span[contains(text(), '#')]",
            "//dt[contains(text(), 'Number')]/following-sibling::dd[1]",
            "//div[contains(@class, 'card-number')]"
        ];

        foreach ($selectors as $selector) {
            $nodes = $xpath->query($selector);
            if ($nodes->length > 0) {
                $text = trim($nodes->item(0)->textContent);
                // Extract number pattern like "001/202"
                if (preg_match('/[\d]+[\/\-][\d]+/', $text, $matches)) {
                    return $matches[0];
                }
            }
        }

        return '';
    }

    /**
     * Extract rarity from page
     */
    private function extractRarity($xpath) {
        $selectors = [
            "//dt[contains(text(), 'Rarity')]/following-sibling::dd[1]",
            "//span[contains(@class, 'rarity')]",
            "//div[contains(@class, 'rarity')]"
        ];

        foreach ($selectors as $selector) {
            $nodes = $xpath->query($selector);
            if ($nodes->length > 0) {
                return trim($nodes->item(0)->textContent);
            }
        }

        return '';
    }

    /**
     * Extract price from page
     * Looks for the "from" price or average price
     */
    private function extractPrice($xpath) {
        // Look for price patterns in the page
        $selectors = [
            "//div[contains(@class, 'price-container')]//span[contains(@class, 'price')]",
            "//dd[contains(@class, 'price')]",
            "//span[contains(text(), '€')]",
            "//*[contains(@class, 'price-from')]",
            "//div[@class='info-list-item']//dd[1]"
        ];

        foreach ($selectors as $selector) {
            $nodes = $xpath->query($selector);
            if ($nodes->length > 0) {
                $priceText = trim($nodes->item(0)->textContent);
                
                // Extract numeric price (handles formats like "€1.23", "1,23 €", "1.23")
                $priceText = str_replace(['€', ' ', ','], ['', '', '.'], $priceText);
                if (preg_match('/[\d]+\.?[\d]*/', $priceText, $matches)) {
                    return floatval($matches[0]);
                }
            }
        }

        // If no price found in structured data, search in page text
        $bodyNodes = $xpath->query('//body');
        if ($bodyNodes->length > 0) {
            $bodyText = $bodyNodes->item(0)->textContent;
            // Look for "from €X.XX" pattern
            if (preg_match('/from\s*€?\s*([\d]+[.,][\d]+)/i', $bodyText, $matches)) {
                return floatval(str_replace(',', '.', $matches[1]));
            }
        }

        return null;
    }

    /**
     * Extract card image URL from page
     */
    private function extractImage($xpath) {
        $selectors = [
            "//img[contains(@class, 'product-image')]/@src",
            "//div[contains(@class, 'product-image')]//img/@src",
            "//img[contains(@alt, 'Pokemon')]/@src",
            "//meta[@property='og:image']/@content"
        ];

        foreach ($selectors as $selector) {
            $nodes = $xpath->query($selector);
            if ($nodes->length > 0) {
                $imageUrl = trim($nodes->item(0)->textContent);
                
                // Make sure it's a full URL
                if (!empty($imageUrl) && strpos($imageUrl, 'http') !== 0) {
                    $imageUrl = 'https://www.cardmarket.com' . $imageUrl;
                }
                
                return $imageUrl;
            }
        }

        return '';
    }

    /**
     * Search Cardmarket for a card by name
     * Returns array of potential matches with URLs
     */
    public function searchCard($cardName) {
        try {
            $searchUrl = 'https://www.cardmarket.com/en/Pokemon/Products/Search?searchString=' . urlencode($cardName);
            $html = $this->fetchUrl($searchUrl);
            
            if (!$html) {
                return [];
            }

            $dom = new DOMDocument();
            @$dom->loadHTML($html);
            $xpath = new DOMXPath($dom);

            $results = [];
            
            // Extract search results
            $productNodes = $xpath->query("//div[contains(@class, 'product-row')]");
            
            foreach ($productNodes as $node) {
                $linkNodes = $xpath->query(".//a[contains(@href, '/Pokemon/Products/')]", $node);
                if ($linkNodes->length > 0) {
                    $url = $linkNodes->item(0)->getAttribute('href');
                    if (strpos($url, 'http') !== 0) {
                        $url = 'https://www.cardmarket.com' . $url;
                    }
                    
                    $results[] = [
                        'name' => trim($linkNodes->item(0)->textContent),
                        'url' => $url
                    ];
                }
            }

            return array_slice($results, 0, 10); // Return max 10 results

        } catch (Exception $e) {
            error_log("Search error: " . $e->getMessage());
            return [];
        }
    }
}

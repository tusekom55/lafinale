<?php
// Simple Yahoo Finance API test

function testYahooAPI() {
    $symbols = ['AAPL', 'MSFT', 'GOOGL'];
    $symbolString = implode(',', $symbols);
    $url = "https://query1.finance.yahoo.com/v7/finance/quote?symbols={$symbolString}";
    
    echo "Testing URL: $url\n\n";
    
    $context = stream_context_create([
        'http' => [
            'timeout' => 15,
            'user_agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36'
        ]
    ]);
    
    $response = file_get_contents($url, false, $context);
    
    if ($response === false) {
        echo "❌ API request failed\n";
        return false;
    }
    
    $data = json_decode($response, true);
    
    if (!isset($data['quoteResponse']['result'])) {
        echo "❌ Invalid response format\n";
        echo "Response: " . substr($response, 0, 500) . "...\n";
        return false;
    }
    
    echo "✅ API request successful!\n";
    echo "Found " . count($data['quoteResponse']['result']) . " results\n\n";
    
    foreach ($data['quoteResponse']['result'] as $quote) {
        $symbol = $quote['symbol'] ?? 'N/A';
        $name = $quote['longName'] ?? $quote['shortName'] ?? 'N/A';
        $price = $quote['regularMarketPrice'] ?? 'N/A';
        $change = $quote['regularMarketChangePercent'] ?? 'N/A';
        
        echo "Symbol: $symbol\n";
        echo "Name: $name\n";
        echo "Price: $price\n";
        echo "Change: $change%\n";
        echo "------------------------\n";
    }
    
    return true;
}

testYahooAPI();
?>

<?php
require_once 'includes/functions.php';

// Test Yahoo Finance API integration
echo "<h2>Yahoo Finance API Test</h2>";

// Test each category
$categories = getFinancialCategories();

foreach ($categories as $category_key => $category_name) {
    echo "<h3>Testing: {$category_name} ({$category_key})</h3>";
    
    $symbols = getCategorySymbols($category_key);
    echo "<p>Symbols: " . implode(', ', array_slice($symbols, 0, 5)) . "...</p>";
    
    // Test first 3 symbols to avoid timeout
    $test_symbols = array_slice($symbols, 0, 3);
    $data = fetchFinancialData($test_symbols, $category_key);
    
    if ($data) {
        echo "<div style='background: #d4edda; padding: 10px; margin: 10px 0; border-radius: 5px;'>";
        echo "<strong>✅ SUCCESS:</strong> API yanıt verdi<br>";
        echo "Dönen veri sayısı: " . count($data) . "<br>";
        
        foreach ($data as $index => $item) {
            $symbol = $item['symbol'] ?? 'N/A';
            $price = $item['regularMarketPrice'] ?? $item['price'] ?? 'N/A';
            $name = $item['longName'] ?? $item['shortName'] ?? 'N/A';
            
            echo "• {$symbol}: {$name} - Price: {$price}<br>";
        }
        echo "</div>";
        
        // Test database update
        echo "<p>Testing database update...</p>";
        if (updateFinancialData($category_key)) {
            echo "<div style='background: #d4edda; padding: 10px; margin: 10px 0; border-radius: 5px;'>";
            echo "✅ Database update successful for {$category_name}";
            echo "</div>";
        } else {
            echo "<div style='background: #f8d7da; padding: 10px; margin: 10px 0; border-radius: 5px;'>";
            echo "❌ Database update failed for {$category_name}";
            echo "</div>";
        }
        
    } else {
        echo "<div style='background: #f8d7da; padding: 10px; margin: 10px 0; border-radius: 5px;'>";
        echo "<strong>❌ FAILED:</strong> API yanıt vermedi veya hata oluştu";
        echo "</div>";
    }
    
    echo "<hr>";
    
    // Add delay to avoid rate limiting
    sleep(1);
}

echo "<h3>Database Check</h3>";
$database = new Database();
$db = $database->getConnection();

foreach ($categories as $category_key => $category_name) {
    $query = "SELECT COUNT(*) as count FROM markets WHERE category = ?";
    $stmt = $db->prepare($query);
    $stmt->execute([$category_key]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    echo "<p>{$category_name}: {$result['count']} records in database</p>";
}
?>

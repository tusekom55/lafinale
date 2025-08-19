<?php
// Simple FMP API test
ini_set('display_errors', 1);
error_reporting(E_ALL);

echo "<h2>Basit FMP API Test</h2>";

try {
    // Load functions
    require_once 'includes/functions.php';
    echo "✅ Functions yüklendi<br>";
    
    // Check FMP constants
    echo "FMP API Key: " . (defined('FMP_API_KEY') ? 'Tanımlı' : 'Tanımsız') . "<br>";
    echo "FMP API URL: " . (defined('FMP_API_URL') ? FMP_API_URL : 'Tanımsız') . "<br>";
    
    // Test function existence
    if (function_exists('makeFMPRequest')) {
        echo "✅ makeFMPRequest fonksiyonu mevcut<br>";
    } else {
        echo "❌ makeFMPRequest fonksiyonu bulunamadı<br>";
    }
    
    if (function_exists('convertSymbolToFMP')) {
        echo "✅ convertSymbolToFMP fonksiyonu mevcut<br>";
    } else {
        echo "❌ convertSymbolToFMP fonksiyonu bulunamadı<br>";
    }
    
    // Test symbol conversion
    echo "<h3>Sembol Dönüşüm Testi:</h3>";
    $test_symbol = convertSymbolToFMP('AAPL', 'us_stocks');
    echo "AAPL → $test_symbol ✅<br>";
    
    $test_symbol2 = convertSymbolToFMP('EURUSD=X', 'forex_major');
    echo "EURUSD=X → $test_symbol2 ✅<br>";
    
    // Simple API test - just one stock
    echo "<h3>Basit API İsteği:</h3>";
    $result = makeFMPRequest('/quote/AAPL');
    
    if ($result['success']) {
        echo "✅ API isteği başarılı<br>";
        echo "Dönen veri sayısı: " . (is_array($result['data']) ? count($result['data']) : '1') . "<br>";
        
        if (!empty($result['data'])) {
            $data = is_array($result['data']) ? $result['data'][0] : $result['data'];
            echo "Apple fiyatı: " . ($data['price'] ?? 'N/A') . "<br>";
        }
    } else {
        echo "❌ API isteği başarısız: " . $result['error'] . "<br>";
    }
    
} catch (Exception $e) {
    echo "❌ HATA: " . $e->getMessage() . "<br>";
    echo "Satır: " . $e->getLine() . "<br>";
    echo "Dosya: " . $e->getFile() . "<br>";
}

echo "<hr>";
echo "<p><a href='fmp-api-test.php'>Tam Test Sayfasına Git</a></p>";
echo "<p><a href='index.php'>Ana Sayfaya Dön</a></p>";
?>

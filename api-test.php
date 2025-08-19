<?php
require_once 'includes/functions.php';

echo "<!DOCTYPE html><html><head><title>Financial API Test</title></head><body>";
echo "<h1>🧪 Financial API Test</h1>";

// Test financial categories
echo "<h2>📊 Finansal Kategoriler:</h2>";
$categories = getFinancialCategories();
foreach ($categories as $key => $name) {
    echo "<p>✅ <strong>$key</strong>: $name</p>";
}

// Test symbol retrieval
echo "<h2>📈 ABD Hisse Senetleri Sembolleri:</h2>";
$usStocks = getCategorySymbols('us_stocks');
echo "<p>Toplam " . count($usStocks) . " sembol:</p>";
echo "<div style='display: grid; grid-template-columns: repeat(5, 1fr); gap: 10px; margin: 10px 0;'>";
foreach (array_slice($usStocks, 0, 20) as $symbol) {
    echo "<span style='background: #f0f8ff; padding: 5px; border-radius: 5px; text-align: center;'>$symbol</span>";
}
echo "</div>";

// Test Forex symbols
echo "<h2>💱 Forex Majör Çiftleri:</h2>";
$forexMajor = getCategorySymbols('forex_major');
echo "<div style='display: grid; grid-template-columns: repeat(4, 1fr); gap: 10px; margin: 10px 0;'>";
foreach ($forexMajor as $symbol) {
    echo "<span style='background: #fff5ee; padding: 5px; border-radius: 5px; text-align: center;'>$symbol</span>";
}
echo "</div>";

// Test Crypto symbols
echo "<h2>💰 Kripto Para:</h2>";
$crypto = getCategorySymbols('crypto');
echo "<div style='display: grid; grid-template-columns: repeat(5, 1fr); gap: 10px; margin: 10px 0;'>";
foreach ($crypto as $symbol) {
    echo "<span style='background: #f0fff0; padding: 5px; border-radius: 5px; text-align: center;'>$symbol</span>";
}
echo "</div>";

// Test database structure
echo "<h2>🗄️ Database Test:</h2>";
try {
    $database = new Database();
    $db = $database->getConnection();
    
    // Check if markets table exists
    $query = "SHOW TABLES LIKE 'markets'";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $result = $stmt->fetch();
    
    if ($result) {
        echo "<p>✅ Markets tablosu mevcut</p>";
        
        // Check table structure
        $query = "DESCRIBE markets";
        $stmt = $db->prepare($query);
        $stmt->execute();
        $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "<h3>Tablo Yapısı:</h3>";
        echo "<table border='1' style='border-collapse: collapse; margin: 10px 0;'>";
        echo "<tr><th>Sütun</th><th>Tip</th><th>Null</th><th>Key</th><th>Default</th></tr>";
        foreach ($columns as $column) {
            echo "<tr>";
            echo "<td>{$column['Field']}</td>";
            echo "<td>{$column['Type']}</td>";
            echo "<td>{$column['Null']}</td>";
            echo "<td>{$column['Key']}</td>";
            echo "<td>{$column['Default']}</td>";
            echo "</tr>";
        }
        echo "</table>";
        
        // Count existing records
        $query = "SELECT category, COUNT(*) as count FROM markets GROUP BY category";
        $stmt = $db->prepare($query);
        $stmt->execute();
        $counts = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "<h3>Mevcut Veriler:</h3>";
        if ($counts) {
            foreach ($counts as $count) {
                echo "<p>📊 {$count['category']}: {$count['count']} kayıt</p>";
            }
        } else {
            echo "<p>⚠️ Henüz veri yok</p>";
        }
        
    } else {
        echo "<p>❌ Markets tablosu bulunamadı</p>";
    }
    
} catch (Exception $e) {
    echo "<p>❌ Database hatası: " . $e->getMessage() . "</p>";
}

// API Test with demo key
echo "<h2>🔗 API Test (Demo Mode):</h2>";
echo "<p><strong>Twelve Data API URL:</strong> " . TWELVE_DATA_API_URL . "</p>";
echo "<p><strong>API Key:</strong> " . TWELVE_DATA_API_KEY . "</p>";

if (TWELVE_DATA_API_KEY === 'demo') {
    echo "<p style='background: #fff3cd; padding: 10px; border-radius: 5px;'>";
    echo "⚠️ <strong>Demo Mode:</strong> Gerçek veri almak için Twelve Data API key'i gerekli<br>";
    echo "🔗 <a href='https://twelvedata.com/' target='_blank'>Twelve Data'dan ücretsiz API key alın</a>";
    echo "</p>";
} else {
    echo "<p style='background: #d4edda; padding: 10px; border-radius: 5px;'>";
    echo "✅ <strong>API Key Aktif:</strong> Gerçek veri çekme hazır";
    echo "</p>";
}

echo "<h2>🚀 Sonraki Adımlar:</h2>";
echo "<ol>";
echo "<li>Twelve Data'dan ücretsiz API key alın</li>";
echo "<li>config/api_keys.php dosyasında TWELVE_DATA_API_KEY'i güncelleyin</li>";
echo "<li>Financial data güncelleme işlemini test edin</li>";
echo "<li>Landing page'de yeni kategorileri görüntüleyin</li>";
echo "</ol>";

echo "</body></html>";
?>

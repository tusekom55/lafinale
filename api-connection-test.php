<?php
require_once 'includes/functions.php';

echo "<!DOCTYPE html><html><head><title>API Connection Test</title>";
echo "<style>
body { font-family: Arial, sans-serif; max-width: 1000px; margin: 0 auto; padding: 20px; }
.success { color: #28a745; background: #d4edda; padding: 10px; border-radius: 5px; margin: 10px 0; }
.error { color: #dc3545; background: #f8d7da; padding: 10px; border-radius: 5px; margin: 10px 0; }
.warning { color: #856404; background: #fff3cd; padding: 10px; border-radius: 5px; margin: 10px 0; }
.info { color: #0c5460; background: #d1ecf1; padding: 10px; border-radius: 5px; margin: 10px 0; }
pre { background: #f8f9fa; padding: 15px; border-radius: 5px; overflow-x: auto; }
</style></head><body>";

echo "<h1>🔌 API Bağlantı Testi</h1>";

// Test 1: API Keys kontrolü
echo "<h2>1️⃣ API Anahtarları Kontrolü</h2>";
echo "<div class='info'>";
echo "<p><strong>Twelve Data API Key:</strong> " . TWELVE_DATA_API_KEY . "</p>";
echo "<p><strong>Alpha Vantage API Key:</strong> " . ALPHA_VANTAGE_API_KEY . "</p>";
echo "</div>";

if (TWELVE_DATA_API_KEY === 'demo') {
    echo "<div class='warning'>";
    echo "<p>⚠️ <strong>Demo Mode:</strong> Gerçek API key yok, sadece test verileri</p>";
    echo "</div>";
} else {
    echo "<div class='success'>";
    echo "<p>✅ <strong>Live Mode:</strong> Gerçek API key mevcut</p>";
    echo "</div>";
}

// Test 2: Basit API isteği
echo "<h2>2️⃣ Basit API İsteği Testi</h2>";

$testSymbol = 'AAPL';
$testUrl = TWELVE_DATA_API_URL . "/quote?symbol={$testSymbol}&apikey=" . TWELVE_DATA_API_KEY;

echo "<p><strong>Test URL:</strong> " . htmlspecialchars($testUrl) . "</p>";

$context = stream_context_create([
    'http' => [
        'timeout' => 10,
        'user_agent' => 'GlobalBorsa/2.0'
    ]
]);

echo "<p>🔄 API'ye istek gönderiliyor...</p>";

$response = @file_get_contents($testUrl, false, $context);

if ($response === false) {
    echo "<div class='error'>";
    echo "<p>❌ <strong>API Hatası:</strong> ��stek gönderilemedi</p>";
    echo "<p>Olası nedenler:</p>";
    echo "<ul>";
    echo "<li>İnternet bağlantısı sorunu</li>";
    echo "<li>Twelve Data API erişilemez</li>";
    echo "<li>Hosting provider API isteklerini engelliyor</li>";
    echo "</ul>";
    echo "</div>";
} else {
    $data = json_decode($response, true);
    
    if (json_last_error() !== JSON_ERROR_NONE) {
        echo "<div class='error'>";
        echo "<p>❌ <strong>JSON Hatası:</strong> " . json_last_error_msg() . "</p>";
        echo "<p><strong>Ham response:</strong></p>";
        echo "<pre>" . htmlspecialchars(substr($response, 0, 500)) . "</pre>";
        echo "</div>";
    } else {
        if (isset($data['code']) && $data['code'] === 429) {
            echo "<div class='warning'>";
            echo "<p>⚠️ <strong>API Limit:</strong> Günlük quota dolmuş</p>";
            echo "<p>Mesaj: " . ($data['message'] ?? 'Bilinmiyor') . "</p>";
            echo "</div>";
        } elseif (isset($data['code']) && $data['code'] === 401) {
            echo "<div class='error'>";
            echo "<p>❌ <strong>Yetkilendirme Hatası:</strong> Geçersiz API key</p>";
            echo "<p>Mesaj: " . ($data['message'] ?? 'Bilinmiyor') . "</p>";
            echo "</div>";
        } elseif (isset($data['symbol'])) {
            echo "<div class='success'>";
            echo "<p>✅ <strong>API Başarılı!</strong> Veri alındı:</p>";
            echo "<ul>";
            echo "<li><strong>Sembol:</strong> " . ($data['symbol'] ?? 'N/A') . "</li>";
            echo "<li><strong>Fiyat:</strong> $" . ($data['close'] ?? $data['price'] ?? 'N/A') . "</li>";
            echo "<li><strong>Değişim:</strong> " . ($data['percent_change'] ?? 'N/A') . "%</li>";
            echo "</ul>";
            echo "</div>";
        } else {
            echo "<div class='warning'>";
            echo "<p>⚠️ <strong>Beklenmeyen response:</strong></p>";
            echo "<pre>" . htmlspecialchars(json_encode($data, JSON_PRETTY_PRINT)) . "</pre>";
            echo "</div>";
        }
    }
}

// Test 3: Kategorilere göre sembolleri test et
echo "<h2>3️⃣ Kategori Sembolleri Testi</h2>";

$categories = getFinancialCategories();
echo "<p>Toplam " . count($categories) . " kategori test edilecek:</p>";

foreach ($categories as $category => $name) {
    echo "<h3>📊 $name ($category)</h3>";
    
    $symbols = getCategorySymbols($category);
    
    if (empty($symbols)) {
        echo "<div class='warning'><p>⚠️ Bu kategori için sembol tanımlı değil</p></div>";
        continue;
    }
    
    echo "<p>🔢 Toplam " . count($symbols) . " sembol:</p>";
    echo "<div style='display: grid; grid-template-columns: repeat(5, 1fr); gap: 5px; margin: 10px 0;'>";
    foreach (array_slice($symbols, 0, 10) as $symbol) {
        echo "<span style='background: #e9ecef; padding: 5px; border-radius: 3px; text-align: center; font-size: 0.9rem;'>$symbol</span>";
    }
    echo "</div>";
    
    if (count($symbols) > 10) {
        echo "<p><em>... ve " . (count($symbols) - 10) . " sembol daha</em></p>";
    }
}

// Test 4: fetchFinancialData fonksiyonu test
echo "<h2>4️⃣ fetchFinancialData Fonksiyonu Testi</h2>";

if (TWELVE_DATA_API_KEY !== 'demo') {
    echo "<p>🔄 ABD hisse senetleri verisi çekiliyor...</p>";
    
    $testSymbols = ['AAPL', 'MSFT', 'GOOGL'];
    $result = fetchFinancialData($testSymbols, 'us_stocks');
    
    if ($result === false) {
        echo "<div class='error'>";
        echo "<p>❌ <strong>fetchFinancialData başarısız</strong></p>";
        echo "</div>";
    } else {
        echo "<div class='success'>";
        echo "<p>✅ <strong>fetchFinancialData başarılı!</strong></p>";
        echo "<p>Dönen veri sayısı: " . count($result) . "</p>";
        echo "<p>İlk sembol verisi:</p>";
        echo "<pre>" . htmlspecialchars(json_encode($result[0] ?? [], JSON_PRETTY_PRINT)) . "</pre>";
        echo "</div>";
    }
} else {
    echo "<div class='info'>";
    echo "<p>ℹ️ Demo mode'da olduğu için API testi yapılmadı</p>";
    echo "</div>";
}

// Test 5: Database bağlantısı
echo "<h2>5️⃣ Database Bağlantı Testi</h2>";

try {
    $database = new Database();
    $db = $database->getConnection();
    
    echo "<div class='success'>";
    echo "<p>✅ <strong>Database bağlantısı başarılı</strong></p>";
    echo "</div>";
    
    // Mevcut verileri kontrol et
    $query = "SELECT category, COUNT(*) as count FROM markets GROUP BY category";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $counts = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($counts)) {
        echo "<div class='warning'>";
        echo "<p>⚠️ <strong>Database'de veri yok</strong></p>";
        echo "<p>Demo veri eklemek için: <a href='demo-data-insert.php' target='_blank'>Demo Data Insert</a></p>";
        echo "</div>";
    } else {
        echo "<div class='info'>";
        echo "<p>📊 <strong>Mevcut veriler:</strong></p>";
        echo "<ul>";
        foreach ($counts as $count) {
            echo "<li>{$count['category']}: {$count['count']} enstrüman</li>";
        }
        echo "</ul>";
        echo "</div>";
    }
    
} catch (Exception $e) {
    echo "<div class='error'>";
    echo "<p>❌ <strong>Database hatası:</strong> " . $e->getMessage() . "</p>";
    echo "</div>";
}

// Test sonuçları ve öneriler
echo "<h2>🎯 Test Sonuçları ve Öneriler</h2>";

echo "<div class='info'>";
echo "<h3>📋 Yapılacaklar:</h3>";
echo "<ol>";

if (TWELVE_DATA_API_KEY === 'demo') {
    echo "<li><strong>Gerçek API Key Alın:</strong> <a href='https://twelvedata.com/' target='_blank'>Twelve Data</a> sitesinden ücretsiz API key alın (800 request/gün)</li>";
    echo "<li><strong>API Key'i Güncelleyin:</strong> config/api_keys.php dosyasında TWELVE_DATA_API_KEY'i değiştirin</li>";
}

echo "<li><strong>Demo Veri Ekleyin:</strong> <a href='demo-data-insert.php' target='_blank'>Demo veri dosyasını</a> çalıştırın</li>";
echo "<li><strong>Ana Sayfayı Test Edin:</strong> <a href='index.php' target='_blank'>Piyasalar sayfasını</a> kontrol edin</li>";
echo "<li><strong>Landing Sayfasını Test Edin:</strong> <a href='landing-new.php' target='_blank'>Landing sayfasını</a> kontrol edin</li>";
echo "</ol>";
echo "</div>";

$currentTime = date('Y-m-d H:i:s');
echo "<p style='text-align: center; color: #666; font-size: 0.9rem;'>Test tamamlandı: $currentTime</p>";

echo "</body></html>";
?>

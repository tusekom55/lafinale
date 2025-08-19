<?php
require_once 'config/database.php';
require_once 'includes/functions.php';

echo "<h2>Parametrik Trading Sistemi Kurulumu</h2>";

try {
    // Create tables
    echo "<h3>1. Veritabanı Tablolarını Oluşturma...</h3>";
    createTables();
    echo "<div style='color: green;'>✓ Tablolar başarıyla oluşturuldu!</div><br>";
    
    // Test exchange rate API
    echo "<h3>2. Kur API Testi...</h3>";
    $rate = fetchUSDTRYRate();
    echo "Mevcut USD/TRY Kuru: " . formatNumber($rate, 4) . "<br>";
    echo "<div style='color: green;'>✓ Kur API çalışıyor!</div><br>";
    
    // Check system parameters
    echo "<h3>3. Sistem Parametreleri Kontrolü...</h3>";
    $trading_currency = getSystemParameter('trading_currency', '1');
    $current_rate = getSystemParameter('usdtry_rate', '27.45');
    echo "Trading Currency: " . ($trading_currency == 1 ? 'TL' : 'USD') . "<br>";
    echo "Cached USD/TRY Rate: " . $current_rate . "<br>";
    echo "<div style='color: green;'>✓ Sistem parametreleri hazır!</div><br>";
    
    // Create test user with balances
    echo "<h3>4. Test Kullanıcısı Oluşturma...</h3>";
    $database = new Database();
    $db = $database->getConnection();
    
    // Check if test user exists
    $query = "SELECT id FROM users WHERE username = 'testuser'";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$user) {
        $query = "INSERT INTO users (username, email, password, balance_tl, balance_usd, balance_btc, balance_eth) 
                  VALUES ('testuser', 'test@example.com', ?, 50000.00, 2000.00, 0.1, 2.0)";
        $stmt = $db->prepare($query);
        $stmt->execute([password_hash('test123', PASSWORD_DEFAULT)]);
        echo "Test kullanıcısı oluşturuldu: <br>";
        echo "Kullanıcı adı: <strong>testuser</strong><br>";
        echo "Şifre: <strong>test123</strong><br>";
    } else {
        echo "Test kullanıcısı zaten mevcut.<br>";
    }
    echo "<div style='color: green;'>✓ Test kullanıcısı hazır!</div><br>";
    
    // Add sample market data
    echo "<h3>5. Örnek Market Verileri Ekleme...</h3>";
    
    // BTC sample data
    $query = "INSERT INTO markets (symbol, name, price, change_24h, volume_24h, high_24h, low_24h, market_cap, category, logo_url) 
              VALUES ('BTC', 'Bitcoin', 43250.00, 2.5, 25000000000, 44000.00, 42500.00, 850000000000, 'us_stocks', 'https://logo.clearbit.com/bitcoin.org')
              ON DUPLICATE KEY UPDATE 
              price = VALUES(price), 
              change_24h = VALUES(change_24h), 
              volume_24h = VALUES(volume_24h),
              updated_at = CURRENT_TIMESTAMP";
    $stmt = $db->prepare($query);
    $stmt->execute();
    
    // ETH sample data
    $query = "INSERT INTO markets (symbol, name, price, change_24h, volume_24h, high_24h, low_24h, market_cap, category, logo_url) 
              VALUES ('ETH', 'Ethereum', 2450.00, 1.8, 15000000000, 2500.00, 2400.00, 295000000000, 'us_stocks', 'https://logo.clearbit.com/ethereum.org')
              ON DUPLICATE KEY UPDATE 
              price = VALUES(price), 
              change_24h = VALUES(change_24h), 
              volume_24h = VALUES(volume_24h),
              updated_at = CURRENT_TIMESTAMP";
    $stmt = $db->prepare($query);
    $stmt->execute();
    
    echo "BTC ve ETH market verileri eklendi.<br>";
    echo "<div style='color: green;'>✓ Market verileri hazır!</div><br>";
    
    echo "<h3>6. Sistem Test Bağlantıları</h3>";
    echo "<p><a href='trading.php?pair=BTC' target='_blank'>🔸 BTC Trading Testi</a></p>";
    echo "<p><a href='trading.php?pair=ETH' target='_blank'>🔸 ETH Trading Testi</a></p>";
    echo "<p><a href='login.php' target='_blank'>🔸 Login Sayfası</a></p>";
    
    echo "<br><div style='background: #d4edda; padding: 15px; border-radius: 5px; color: #155724;'>";
    echo "<h4>✅ Parametrik Trading Sistemi Başarıyla Kuruldu!</h4>";
    echo "<p><strong>Özellikler:</strong></p>";
    echo "<ul>";
    echo "<li>🌍 Gerçek zamanlı USD/TRY kur entegrasyonu</li>";
    echo "<li>💱 Parametrik currency sistemi (TL/USD)</li>";
    echo "<li>📊 Dinamik fiyat gösterimi</li>";
    echo "<li>💰 Akıllı bakiye yönetimi</li>";
    echo "<li>⚡ Real-time JavaScript hesaplamaları</li>";
    echo "</ul>";
    echo "<p><strong>Test için:</strong> testuser / test123 ile giriş yapın</p>";
    echo "</div>";
    
} catch (Exception $e) {
    echo "<div style='color: red;'>❌ Hata: " . $e->getMessage() . "</div>";
}
?>

<style>
body { font-family: Arial, sans-serif; padding: 20px; background: #f8f9fa; }
h2, h3 { color: #333; }
a { color: #007bff; text-decoration: none; }
a:hover { text-decoration: underline; }
</style>

<?php
// Test dosyası - landing-new.php'nin çalışıp çalışmadığını test etmek için
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<!DOCTYPE html><html><head><title>Landing Test</title></head><body>";
echo "<h1>🧪 Landing Test</h1>";

echo "<h2>📝 Test 1: Basic PHP</h2>";
echo "<p>✅ PHP çalışıyor - Zaman: " . date('Y-m-d H:i:s') . "</p>";

echo "<h2>📁 Test 2: Include Files</h2>";

// Test includes/functions.php
if (file_exists('includes/functions.php')) {
    echo "<p>✅ includes/functions.php mevcut</p>";
    try {
        require_once 'includes/functions.php';
        echo "<p>✅ functions.php başarıyla yüklendi</p>";
        
        // Test specific functions
        if (function_exists('getCurrentLang')) {
            echo "<p>✅ getCurrentLang() fonksiyonu mevcut: " . getCurrentLang() . "</p>";
        } else {
            echo "<p>❌ getCurrentLang() fonksiyonu yok</p>";
        }
        
        if (function_exists('t')) {
            echo "<p>✅ t() fonksiyonu mevcut: " . t('login') . "</p>";
        } else {
            echo "<p>❌ t() fonksiyonu yok</p>";
        }
        
    } catch (Exception $e) {
        echo "<p>❌ functions.php yüklenirken hata: " . $e->getMessage() . "</p>";
    }
} else {
    echo "<p>❌ includes/functions.php bulunamadı</p>";
}

echo "<h2>🔧 Test 3: Database Connection</h2>";
try {
    $database = new Database();
    $db = $database->getConnection();
    echo "<p>✅ Database bağlantısı başarılı</p>";
} catch (Exception $e) {
    echo "<p>❌ Database bağlantı hatası: " . $e->getMessage() . "</p>";
}

echo "<h2>💰 Test 4: Market Data</h2>";
try {
    $markets = getMarketData('crypto_tl', 3);
    if ($markets && count($markets) > 0) {
        echo "<p>✅ Market data alındı: " . count($markets) . " market</p>";
        echo "<ul>";
        foreach (array_slice($markets, 0, 3) as $market) {
            echo "<li>" . $market['name'] . " (" . $market['symbol'] . "): " . $market['price'] . "</li>";
        }
        echo "</ul>";
    } else {
        echo "<p>⚠️ Market data boş</p>";
    }
} catch (Exception $e) {
    echo "<p>❌ Market data hatası: " . $e->getMessage() . "</p>";
}

echo "<h2>🌐 Test 5: Header Include</h2>";
try {
    ob_start();
    include 'includes/header.php';
    $header_output = ob_get_clean();
    
    if (strlen($header_output) > 100) {
        echo "<p>✅ Header başarıyla include edildi (" . strlen($header_output) . " karakter)</p>";
    } else {
        echo "<p>❌ Header çok kısa: " . strlen($header_output) . " karakter</p>";
        echo "<pre>" . htmlspecialchars($header_output) . "</pre>";
    }
} catch (Exception $e) {
    echo "<p>❌ Header include hatası: " . $e->getMessage() . "</p>";
}

echo "<h2>🔗 Test 6: Session</h2>";
echo "<p>Session ID: " . session_id() . "</p>";
echo "<p>Session vars: " . print_r($_SESSION, true) . "</p>";

echo "<h2>🚀 Test 7: landing-new.php Direct Test</h2>";
echo "<p><a href='landing-new.php' target='_blank'>landing-new.php'yi aç</a></p>";

echo "</body></html>";
?>

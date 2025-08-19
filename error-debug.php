<?php
// Enable error reporting
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

echo "<h2>GlobalBorsa Hata Ayıklama</h2>";
echo "<p>Timestamp: " . date('Y-m-d H:i:s') . "</p>";

// Test 1: Basic PHP
echo "<h3>1. PHP Çalışıyor ✅</h3>";

// Test 2: Include files one by one
echo "<h3>2. Dosya Testleri:</h3>";

try {
    echo "Database config test: ";
    require_once 'config/database.php';
    echo "✅ OK<br>";
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "<br>";
}

try {
    echo "API keys config test: ";
    require_once 'config/api_keys.php';
    echo "✅ OK<br>";
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "<br>";
}

try {
    echo "Languages config test: ";
    require_once 'config/languages.php';
    echo "✅ OK<br>";
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "<br>";
}

try {
    echo "Functions include test: ";
    require_once 'includes/functions.php';
    echo "✅ OK<br>";
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "<br>";
}

// Test 3: Database connection
echo "<h3>3. Veritabanı Bağlantısı:</h3>";
try {
    $database = new Database();
    $db = $database->getConnection();
    echo "✅ Veritabanı bağlantısı başarılı<br>";
    
    // Test basic query
    $query = "SELECT COUNT(*) as count FROM markets";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "Markets tablosunda " . $result['count'] . " kayıt var<br>";
    
} catch (Exception $e) {
    echo "❌ Veritabanı hatası: " . $e->getMessage() . "<br>";
}

// Test 4: FMP API connection
echo "<h3>4. FMP API Testi:</h3>";
try {
    echo "FMP API Key: " . (defined('FMP_API_KEY') ? (FMP_API_KEY === 'demo' ? 'Demo' : 'Set') : 'Not defined') . "<br>";
    echo "FMP API URL: " . (defined('FMP_API_URL') ? FMP_API_URL : 'Not defined') . "<br>";
    
    if (function_exists('makeFMPRequest')) {
        echo "makeFMPRequest function: ✅ Defined<br>";
    } else {
        echo "makeFMPRequest function: ❌ Not defined<br>";
    }
    
} catch (Exception $e) {
    echo "❌ FMP API test error: " . $e->getMessage() . "<br>";
}

// Test 5: Check for common issues
echo "<h3>5. Genel Kontroller:</h3>";

// Check if session is working
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
echo "Session status: " . session_status() . " ✅<br>";

// Check PHP version
echo "PHP Version: " . phpversion() . "<br>";

// Check memory limit
echo "Memory Limit: " . ini_get('memory_limit') . "<br>";

// Check execution time
echo "Max Execution Time: " . ini_get('max_execution_time') . "<br>";

echo "<h3>6. Son Error Log:</h3>";
echo "<pre>";
$error_log = error_get_last();
if ($error_log) {
    print_r($error_log);
} else {
    echo "No recent errors found.";
}
echo "</pre>";

?>

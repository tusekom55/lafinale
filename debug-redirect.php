<?php
// Redirect debug - hangi dosya nerede yönlendirme yapıyor tespit et
echo "<!DOCTYPE html><html><head><title>Redirect Debug</title></head><body>";
echo "<h1>🔍 Redirect Debug</h1>";

// Session start olmadan önce debug
echo "<h2>🟢 Başlangıç - Session Öncesi</h2>";
echo "<p>✅ PHP çalışıyor</p>";

// Headers kontrol
echo "<h2>📋 Headers (Sent öncesi):</h2>";
echo "<pre>";
print_r(headers_list());
echo "</pre>";

// Includes test et - teker teker
echo "<h2>🔧 Includes Test:</h2>";

try {
    echo "<p>📂 config/database.php include ediliyor...</p>";
    require_once 'config/database.php';
    echo "<p>✅ config/database.php başarılı</p>";
} catch (Exception $e) {
    echo "<p>❌ config/database.php hatası: " . $e->getMessage() . "</p>";
}

try {
    echo "<p>📂 config/api_keys.php include ediliyor...</p>";
    require_once 'config/api_keys.php';
    echo "<p>✅ config/api_keys.php başarılı</p>";
} catch (Exception $e) {
    echo "<p>❌ config/api_keys.php hatası: " . $e->getMessage() . "</p>";
}

try {
    echo "<p>📂 config/languages.php include ediliyor...</p>";
    require_once 'config/languages.php';
    echo "<p>✅ config/languages.php başarılı</p>";
} catch (Exception $e) {
    echo "<p>❌ config/languages.php hatası: " . $e->getMessage() . "</p>";
}

// Headers tekrar kontrol
echo "<h2>📋 Headers (config sonrası):</h2>";
echo "<pre>";
print_r(headers_list());
echo "</pre>";

// Session debug
echo "<h2>🔗 Session Debug:</h2>";
echo "<p>Session durumu: " . session_status() . "</p>";
echo "<p>Session ID: " . (session_id() ?: 'YOK') . "</p>";

try {
    if (session_status() == PHP_SESSION_NONE) {
        session_start();
        echo "<p>✅ Session başlatıldı</p>";
    } else {
        echo "<p>ℹ️ Session zaten aktif</p>";
    }
} catch (Exception $e) {
    echo "<p>❌ Session hatası: " . $e->getMessage() . "</p>";
}

// Headers son kontrol
echo "<h2>📋 Headers (session sonrası):</h2>";
echo "<pre>";
print_r(headers_list());
echo "</pre>";

// Functions.php include etmeye çalış AMA dikkatli
echo "<h2>⚠️ Critical Test - functions.php:</h2>";
echo "<p>includes/functions.php include edilecek - redirect burada olabilir!</p>";

// Output buffer başlat ki yönlendirme durduralım
ob_start();

try {
    require_once 'includes/functions.php';
    $output = ob_get_contents();
    ob_end_clean();
    
    echo "<p>✅ includes/functions.php include edildi</p>";
    echo "<p>📤 Output: " . strlen($output) . " karakter</p>";
    
    if (!empty($output)) {
        echo "<h3>🔍 functions.php Output:</h3>";
        echo "<pre>" . htmlspecialchars($output) . "</pre>";
    }
    
} catch (Exception $e) {
    ob_end_clean();
    echo "<p>❌ includes/functions.php hatası: " . $e->getMessage() . "</p>";
}

// Headers final kontrol
echo "<h2>📋 Final Headers:</h2>";
echo "<pre>";
print_r(headers_list());
echo "</pre>";

// Location header özel kontrol
$headers = headers_list();
foreach ($headers as $header) {
    if (stripos($header, 'location') !== false) {
        echo "<h2>🚨 REDIRECT BULUNDU!</h2>";
        echo "<p style='color: red; font-weight: bold;'>$header</p>";
    }
}

// $_SERVER debug
echo "<h2>🌐 Server Vars:</h2>";
echo "<pre>";
echo "REQUEST_URI: " . ($_SERVER['REQUEST_URI'] ?? 'YOK') . "\n";
echo "SCRIPT_NAME: " . ($_SERVER['SCRIPT_NAME'] ?? 'YOK') . "\n";
echo "QUERY_STRING: " . ($_SERVER['QUERY_STRING'] ?? 'YOK') . "\n";
echo "HTTP_HOST: " . ($_SERVER['HTTP_HOST'] ?? 'YOK') . "\n";
echo "REQUEST_METHOD: " . ($_SERVER['REQUEST_METHOD'] ?? 'YOK') . "\n";
echo "</pre>";

// Function tests
echo "<h2>🔧 Function Tests:</h2>";
if (function_exists('isLoggedIn')) {
    echo "<p>✅ isLoggedIn() mevcut</p>";
    
    try {
        $loggedIn = isLoggedIn();
        echo "<p>📊 isLoggedIn() result: " . ($loggedIn ? 'true' : 'false') . "</p>";
    } catch (Exception $e) {
        echo "<p>❌ isLoggedIn() hatası: " . $e->getMessage() . "</p>";
    }
} else {
    echo "<p>❌ isLoggedIn() fonksiyonu yok</p>";
}

if (function_exists('requireLogin')) {
    echo "<p>⚠️ requireLogin() mevcut - BU REDIRECT YAPABILIR!</p>";
} else {
    echo "<p>✅ requireLogin() yok</p>";
}

// Final test
echo "<h2>🎯 Final Test:</h2>";
echo "<p>Eğer bu mesajı görüyorsanız, redirect includes/functions.php'nin sonrasında oluyor.</p>";

echo "</body></html>";
?>

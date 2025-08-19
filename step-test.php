<?php
// Step by step test - hangi include'da problem oluyor tespit et
?>
<!DOCTYPE html>
<html>
<head>
    <title>Step Test</title>
</head>
<body>
    <h1>🔍 Step by Step Test</h1>
    
    <h2>Step 1: Basic PHP ✅</h2>
    <p>PHP çalışıyor: <?php echo PHP_VERSION; ?></p>
    
    <h2>Step 2: Config Files Test</h2>
    <?php
    echo "<p>📂 config/database.php test...</p>";
    try {
        require_once 'config/database.php';
        echo "<p>✅ config/database.php başarılı</p>";
    } catch (Exception $e) {
        echo "<p>❌ config/database.php hatası: " . $e->getMessage() . "</p>";
    }
    
    echo "<p>📂 config/api_keys.php test...</p>";
    try {
        require_once 'config/api_keys.php';
        echo "<p>✅ config/api_keys.php başarılı</p>";
    } catch (Exception $e) {
        echo "<p>❌ config/api_keys.php hatası: " . $e->getMessage() . "</p>";
    }
    
    echo "<p>📂 config/languages.php test...</p>";
    try {
        require_once 'config/languages.php';
        echo "<p>✅ config/languages.php başarılı</p>";
    } catch (Exception $e) {
        echo "<p>❌ config/languages.php hatası: " . $e->getMessage() . "</p>";
    }
    ?>
    
    <h2>Step 3: Critical Test - functions.php</h2>
    <p>⚠️ Bu kısım sorunlu olabilir:</p>
    <?php
    echo "<p>📂 includes/functions.php test...</p>";
    
    // Debug headers before
    echo "<p>Headers before functions.php:</p>";
    echo "<pre>" . print_r(headers_list(), true) . "</pre>";
    
    try {
        require_once 'includes/functions.php';
        echo "<p>✅ includes/functions.php başarılı</p>";
        
        // Debug headers after
        echo "<p>Headers after functions.php:</p>";
        echo "<pre>" . print_r(headers_list(), true) . "</pre>";
        
    } catch (Exception $e) {
        echo "<p>❌ includes/functions.php hatası: " . $e->getMessage() . "</p>";
    }
    ?>
    
    <h2>Step 4: Header Test</h2>
    <p>🔍 includes/header.php dahil edilecek:</p>
    <?php
    try {
        // Output buffer kullan
        ob_start();
        include 'includes/header.php';
        $header_output = ob_get_contents();
        ob_end_clean();
        
        echo "<p>✅ includes/header.php dahil edildi</p>";
        echo "<p>Output uzunluğu: " . strlen($header_output) . " karakter</p>";
        
        // İlk 500 karakteri göster
        if (!empty($header_output)) {
            echo "<h3>Header Output Preview:</h3>";
            echo "<pre>" . htmlspecialchars(substr($header_output, 0, 500)) . "</pre>";
        }
        
    } catch (Exception $e) {
        echo "<p>❌ includes/header.php hatası: " . $e->getMessage() . "</p>";
    }
    ?>
    
    <h2>Final Headers</h2>
    <pre><?php print_r(headers_list()); ?></pre>
    
    <script>
        console.log('🔍 Step test completed');
    </script>
</body>
</html>

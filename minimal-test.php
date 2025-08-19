<?php
// En minimal test - hiçbir include yok
?>
<!DOCTYPE html>
<html>
<head>
    <title>Minimal Test</title>
</head>
<body>
    <h1>🟢 Minimal Test</h1>
    <p><strong>Bu sayfa görülüyorsa:</strong> PHP çalışıyor</p>
    <p><strong>Bu sayfa görülmüyorsa:</strong> Hosting/server seviyesi problem</p>
    
    <h2>📊 PHP Info:</h2>
    <p>PHP Version: <?php echo PHP_VERSION; ?></p>
    <p>Server: <?php echo $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown'; ?></p>
    <p>Document Root: <?php echo $_SERVER['DOCUMENT_ROOT'] ?? 'Unknown'; ?></p>
    
    <h2>🔍 Auto-prepend/append Test:</h2>
    <p>auto_prepend_file: <?php echo ini_get('auto_prepend_file') ?: 'None'; ?></p>
    <p>auto_append_file: <?php echo ini_get('auto_append_file') ?: 'None'; ?></p>
    
    <h2>📋 Headers Before Output:</h2>
    <pre><?php print_r(headers_list()); ?></pre>
    
    <script>
        console.log('🟢 Minimal test page loaded successfully');
        console.log('📊 User Agent:', navigator.userAgent);
        console.log('🌐 Current URL:', window.location.href);
    </script>
</body>
</html>

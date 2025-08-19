<?php
echo "<!DOCTYPE html><html><head><title>File Check</title></head><body>";
echo "<h1>🔍 Dosya Kontrol</h1>";

$filesToCheck = [
    'login.html',
    'register.html', 
    'admin-login.html',
    'landing.html',
    'index.htm'
];

foreach($filesToCheck as $file) {
    if(file_exists($file)) {
        echo "<p>❌ <strong>$file</strong> dosyası MEVCUT!</p>";
        echo "<pre>İçerik (ilk 500 karakter):\n" . htmlspecialchars(substr(file_get_contents($file), 0, 500)) . "</pre>";
        echo "<hr>";
    } else {
        echo "<p>✅ <strong>$file</strong> dosyası yok</p>";
    }
}

// Hidden files check
echo "<h2>🔍 Gizli Dosyalar:</h2>";
$allFiles = glob('.*');
foreach($allFiles as $file) {
    if($file != '.' && $file != '..' && $file != '.git') {
        echo "<p>📁 $file</p>";
    }
}

// Check if there's a default index
echo "<h2>📄 Index Dosyaları:</h2>";
$indexFiles = ['index.html', 'index.htm', 'index.php', 'default.html', 'home.html'];
foreach($indexFiles as $idx) {
    if(file_exists($idx)) {
        echo "<p>📄 <strong>$idx</strong> mevcut</p>";
        if($idx == 'index.html') {
            echo "<pre>" . htmlspecialchars(file_get_contents($idx)) . "</pre>";
        }
    }
}

// Check server cache headers
echo "<h2>🗄️ Cache Headers:</h2>";
foreach(headers_list() as $header) {
    echo "<p>$header</p>";
}

echo "</body></html>";
?>

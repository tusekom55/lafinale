<?php
// Debug dosyası - Hangi dosyanın çalıştığını görmek için
echo "<!DOCTYPE html><html><head><title>Debug - GlobalBorsa</title></head><body>";
echo "<h1>🔍 Debug Bilgileri</h1>";

echo "<h2>📁 Dosya Bilgileri:</h2>";
echo "<p><strong>Çalışan dosya:</strong> " . __FILE__ . "</p>";
echo "<p><strong>Çalışan dizin:</strong> " . __DIR__ . "</p>";
echo "<p><strong>Document Root:</strong> " . $_SERVER['DOCUMENT_ROOT'] . "</p>";
echo "<p><strong>Request URI:</strong> " . $_SERVER['REQUEST_URI'] . "</p>";

echo "<h2>🌐 Sunucu Bilgileri:</h2>";
echo "<p><strong>Server Name:</strong> " . $_SERVER['SERVER_NAME'] . "</p>";
echo "<p><strong>HTTP Host:</strong> " . $_SERVER['HTTP_HOST'] . "</p>";
echo "<p><strong>Script Name:</strong> " . $_SERVER['SCRIPT_NAME'] . "</p>";

echo "<h2>📂 Mevcut Dosyalar:</h2>";
$files = scandir(__DIR__);
echo "<ul>";
foreach($files as $file) {
    if($file != '.' && $file != '..') {
        echo "<li>$file</li>";
    }
}
echo "</ul>";

echo "<h2>⚙️ .htaccess Kontrol:</h2>";
if(file_exists('.htaccess')) {
    echo "<p>✅ .htaccess dosyası mevcut</p>";
    echo "<pre>" . htmlspecialchars(file_get_contents('.htaccess')) . "</pre>";
} else {
    echo "<p>❌ .htaccess dosyası bulunamadı</p>";
}

echo "<h2>🔗 Redirect Test:</h2>";
echo "<p><a href='landing-new.php'>landing-new.php'ye git</a></p>";
echo "<p><a href='landing-ornek.html'>landing-ornek.html'e git</a></p>";
echo "<p><a href='index.html'>index.html'e git</a></p>";
echo "<p><a href='index.php'>index.php'ye git</a></p>";

echo "<h2>🚨 Header Kontrol:</h2>";
$headers = getallheaders();
foreach($headers as $name => $value) {
    echo "<p><strong>$name:</strong> $value</p>";
}

echo "</body></html>";
?>

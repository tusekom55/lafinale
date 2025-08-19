<?php
// Basit test - hiçbir include yok
?>
<!DOCTYPE html>
<html>
<head>
    <title>Redirect Test</title>
</head>
<body>
    <h1>🧪 Redirect Test</h1>
    <p><strong>Bu sayfa açıldıysa:</strong> PHP çalışıyor ve yönlendirme problemi yok</p>
    <p><strong>Eğer login.html'e gidiyorsa:</strong> Hosting panel'de manual redirect var</p>
    
    <h2>📊 Server Bilgileri:</h2>
    <pre>
Server: <?php echo $_SERVER['SERVER_SOFTWARE'] ?? 'N/A'; ?>
Request URI: <?php echo $_SERVER['REQUEST_URI'] ?? 'N/A'; ?>
Script Name: <?php echo $_SERVER['SCRIPT_NAME'] ?? 'N/A'; ?>
Query String: <?php echo $_SERVER['QUERY_STRING'] ?? 'N/A'; ?>
Host: <?php echo $_SERVER['HTTP_HOST'] ?? 'N/A'; ?>
    </pre>
    
    <h2>🔧 Çözüm:</h2>
    <ol>
        <li>Hostinger hPanel'e giriş yapın</li>
        <li>"Website" > "Redirects" bölümüne gidin</li>
        <li>login.html ile ilgili redirect varsa silin</li>
        <li>Alternatif: hosting desteğe yazın</li>
    </ol>
    
    <script>
        console.log('🟢 redirect-test.php başarıyla yüklendi');
    </script>
</body>
</html>

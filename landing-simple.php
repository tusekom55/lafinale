<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>GlobalBorsa - Test</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 50px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            text-align: center;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }
        
        .hero {
            max-width: 800px;
            margin: 0 auto;
        }
        
        .hero h1 {
            font-size: 3rem;
            margin-bottom: 1rem;
        }
        
        .hero p {
            font-size: 1.2rem;
            margin-bottom: 2rem;
            opacity: 0.9;
        }
        
        .btn {
            display: inline-block;
            background: #ffd700;
            color: #333;
            padding: 1rem 2rem;
            text-decoration: none;
            border-radius: 50px;
            font-weight: bold;
            transition: transform 0.3s ease;
        }
        
        .btn:hover {
            transform: translateY(-3px);
        }
    </style>
</head>
<body>
    <div class="hero">
        <h1>GlobalBorsa</h1>
        <p>Türkiye'nin En Güvenilir Kripto Borsası</p>
        <p>Bu basit test sayfasıdır. Hiçbir external kaynak veya include yok.</p>
        <a href="register.php" class="btn">Hemen Başla</a>
        <br><br>
        <a href="login.php" style="color: white;">Giriş Yap</a>
    </div>
    
    <script>
        console.log('Simple landing page loaded');
        console.log('No external scripts loaded');
    </script>
</body>
</html>

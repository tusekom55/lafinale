<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CSS Test - GlobalBorsa</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <!-- Custom Landing CSS -->
    <link href="assets/css/landing.css" rel="stylesheet">
    
    <style>
        /* CSS Test Styles */
        body {
            margin: 0;
            padding: 20px;
            font-family: Arial, sans-serif;
            background: #f0f0f0;
        }
        
        .test-hero {
            background: linear-gradient(135deg, #1a365d 0%, #2b5ce6 50%, #3182ce 100%);
            color: white;
            padding: 3rem;
            text-align: center;
            border-radius: 10px;
            margin-bottom: 2rem;
        }
        
        .test-cards {
            display: flex;
            gap: 1rem;
            flex-wrap: wrap;
            margin-bottom: 2rem;
        }
        
        .test-card {
            background: white;
            padding: 2rem;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            flex: 1;
            min-width: 250px;
            transition: transform 0.3s ease;
        }
        
        .test-card:hover {
            transform: translateY(-5px);
        }
        
        .crypto-test-card {
            background: rgba(43, 92, 230, 0.1);
            border: 1px solid rgba(43, 92, 230, 0.3);
            border-radius: 12px;
            padding: 1rem;
            text-align: center;
            margin: 1rem;
            min-width: 150px;
        }
        
        .btn-test {
            background: #2b5ce6;
            color: white;
            padding: 12px 24px;
            border: none;
            border-radius: 8px;
            text-decoration: none;
            display: inline-block;
            margin: 0.5rem;
            transition: all 0.3s ease;
        }
        
        .btn-test:hover {
            background: #3182ce;
            transform: translateY(-2px);
            color: white;
            text-decoration: none;
        }
        
        .status-indicator {
            display: inline-block;
            width: 20px;
            height: 20px;
            border-radius: 50%;
            margin-right: 10px;
        }
        
        .status-success { background: #28a745; }
        .status-warning { background: #ffc107; }
        .status-error { background: #dc3545; }
        
        .debug-info {
            background: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 5px;
            padding: 1rem;
            margin: 1rem 0;
            font-family: monospace;
            font-size: 0.9rem;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🧪 CSS Test Sayfası - GlobalBorsa</h1>
        <p>Bu sayfa CSS dosyalarının doğru yüklenip yüklenmediğini test eder.</p>
        
        <!-- Hero Test -->
        <div class="test-hero">
            <h2>Hero Section Test</h2>
            <p>Bu alan mavi gradient background'a sahip olmalı</p>
            <a href="#" class="btn-test">Test Butonu</a>
        </div>
        
        <!-- Card Test -->
        <h3>📦 Kart Testleri</h3>
        <div class="test-cards">
            <div class="test-card">
                <h4>Bootstrap Kartı</h4>
                <p>Bu kart Bootstrap stilleriyle oluşturuldu.</p>
                <div class="status-indicator status-success"></div>
                <span>Bootstrap CSS çalışıyor</span>
            </div>
            
            <div class="test-card">
                <h4>Custom CSS Kartı</h4>
                <p>Bu kart custom CSS ile oluşturuldu.</p>
                <div class="status-indicator status-success"></div>
                <span>Custom CSS çalışıyor</span>
            </div>
        </div>
        
        <!-- Crypto Card Test -->
        <h3>💰 Kripto Kart Testi</h3>
        <div style="display: flex; justify-content: center; flex-wrap: wrap;">
            <div class="crypto-test-card">
                <i class="fab fa-bitcoin" style="font-size: 2rem; color: #f7931a; margin-bottom: 0.5rem;"></i>
                <div style="font-weight: bold;">BTC/TL</div>
                <div style="opacity: 0.8;">Bitcoin</div>
                <div style="color: #28a745;">+2.45%</div>
            </div>
            
            <div class="crypto-test-card">
                <i class="fab fa-ethereum" style="font-size: 2rem; color: #627eea; margin-bottom: 0.5rem;"></i>
                <div style="font-weight: bold;">ETH/TL</div>
                <div style="opacity: 0.8;">Ethereum</div>
                <div style="color: #dc3545;">-1.23%</div>
            </div>
        </div>
        
        <!-- Font Awesome Test -->
        <h3>🎨 Font Awesome Test</h3>
        <div class="debug-info">
            <div><i class="fas fa-check-circle"></i> Chart Line Icon: <i class="fas fa-chart-line"></i></div>
            <div><i class="fas fa-check-circle"></i> Bitcoin Icon: <i class="fab fa-bitcoin"></i></div>
            <div><i class="fas fa-check-circle"></i> Ethereum Icon: <i class="fab fa-ethereum"></i></div>
            <div><i class="fas fa-check-circle"></i> Mobile Icon: <i class="fas fa-mobile-alt"></i></div>
        </div>
        
        <!-- CSS Yükleme Testi -->
        <h3>📊 CSS Yükleme Durumu</h3>
        <div class="debug-info">
            <div id="bootstrap-test">
                <div class="status-indicator status-warning"></div>
                Bootstrap CSS test ediliyor...
            </div>
            <div id="fontawesome-test">
                <div class="status-indicator status-warning"></div>
                Font Awesome test ediliyor...
            </div>
            <div id="custom-test">
                <div class="status-indicator status-warning"></div>
                Custom CSS test ediliyor...
            </div>
        </div>
        
        <!-- Hover Test -->
        <h3>🖱️ Hover Efekt Testi</h3>
        <div class="test-cards">
            <div class="test-card">
                <h4>Hover Testi</h4>
                <p>Bu kartın üzerine mouse ile gelin</p>
                <a href="#" class="btn-test">Hover Test Butonu</a>
            </div>
        </div>
        
        <!-- Responsive Test -->
        <h3>📱 Responsive Test</h3>
        <div class="debug-info">
            <div>Ekran Genişliği: <span id="screen-width"></span>px</div>
            <div>Tarayıcı: <span id="browser-info"></span></div>
            <div>CSS Grid Desteği: <span id="css-grid"></span></div>
            <div>CSS Flexbox Desteği: <span id="css-flexbox"></span></div>
        </div>
        
        <div style="margin-top: 2rem; text-align: center;">
            <a href="landing-test.php" class="btn-test">🚀 Landing Test Sayfasına Dön</a>
            <a href="index.php" class="btn-test">🏠 Ana Sayfaya Git</a>
        </div>
    </div>
    
    <script>
        // CSS Test Scripts
        document.addEventListener('DOMContentLoaded', function() {
            // Screen info
            document.getElementById('screen-width').textContent = window.innerWidth;
            document.getElementById('browser-info').textContent = navigator.userAgent.split(' ')[0];
            
            // CSS support tests
            document.getElementById('css-grid').textContent = 
                CSS.supports('display', 'grid') ? '✅ Destekleniyor' : '❌ Desteklenmiyor';
            document.getElementById('css-flexbox').textContent = 
                CSS.supports('display', 'flex') ? '✅ Destekleniyor' : '❌ Desteklenmiyor';
            
            // Bootstrap test
            setTimeout(() => {
                const bootstrapTest = document.getElementById('bootstrap-test');
                const hasBootstrap = window.getComputedStyle(document.body).getPropertyValue('--bs-blue');
                updateStatus(bootstrapTest, hasBootstrap ? true : false, 'Bootstrap CSS');
            }, 500);
            
            // Font Awesome test
            setTimeout(() => {
                const faTest = document.getElementById('fontawesome-test');
                const testIcon = document.createElement('i');
                testIcon.className = 'fas fa-test';
                testIcon.style.position = 'absolute';
                testIcon.style.visibility = 'hidden';
                document.body.appendChild(testIcon);
                
                const hasFA = window.getComputedStyle(testIcon, ':before').content !== 'none';
                updateStatus(faTest, hasFA, 'Font Awesome');
                document.body.removeChild(testIcon);
            }, 1000);
            
            // Custom CSS test
            setTimeout(() => {
                const customTest = document.getElementById('custom-test');
                const heroElement = document.querySelector('.test-hero');
                const bgColor = window.getComputedStyle(heroElement).background;
                const hasGradient = bgColor.includes('gradient') || bgColor.includes('rgb');
                updateStatus(customTest, hasGradient, 'Custom CSS');
            }, 1500);
        });
        
        function updateStatus(element, success, name) {
            const indicator = element.querySelector('.status-indicator');
            const text = element.childNodes[2];
            
            if (success) {
                indicator.className = 'status-indicator status-success';
                text.textContent = ` ${name} ✅ Başarıyla yüklendi`;
            } else {
                indicator.className = 'status-indicator status-error';
                text.textContent = ` ${name} ❌ Yüklenemedi`;
            }
        }
        
        // Resize listener
        window.addEventListener('resize', function() {
            document.getElementById('screen-width').textContent = window.innerWidth;
        });
        
        console.log('🧪 CSS Test sayfası yüklendi');
        console.log('📊 Stylesheet sayısı:', document.styleSheets.length);
        console.log('🎨 CSS kuralları kontrol ediliyor...');
        
        // List all loaded stylesheets
        for (let i = 0; i < document.styleSheets.length; i++) {
            try {
                console.log(`📄 Stylesheet ${i + 1}:`, document.styleSheets[i].href || 'Inline styles');
            } catch (e) {
                console.log(`📄 Stylesheet ${i + 1}: CORS hatası (muhtemelen CDN)`);
            }
        }
    </script>
</body>
</html>

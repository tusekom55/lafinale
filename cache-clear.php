<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cache Temizleme - GlobalBorsa</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 20px;
            background: #f5f5f5;
        }
        .container {
            max-width: 800px;
            margin: 0 auto;
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .btn {
            background: #007bff;
            color: white;
            border: none;
            padding: 12px 24px;
            border-radius: 5px;
            cursor: pointer;
            margin: 10px 5px;
            font-size: 16px;
        }
        .btn:hover {
            background: #0056b3;
        }
        .success {
            color: #28a745;
            font-weight: bold;
        }
        .warning {
            color: #ffc107;
            font-weight: bold;
        }
        .error {
            color: #dc3545;
            font-weight: bold;
        }
        .network-logs {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 5px;
            margin: 15px 0;
            max-height: 300px;
            overflow-y: auto;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🧹 Cache Temizleme & Debug</h1>
        
        <h2>🔍 Dashboard.html Problemi</h2>
        <p>Console'da <code>dashboard.html</code> hatası görülüyorsa aşağıdaki adımları uygulayın:</p>
        
        <h3>1. Browser Cache Temizleme:</h3>
        <button class="btn" onclick="clearBrowserCache()">
            🗑️ Browser Cache'i Temizle
        </button>
        
        <h3>2. Hard Refresh:</h3>
        <ul>
            <li><strong>Chrome/Edge:</strong> Ctrl + Shift + R</li>
            <li><strong>Firefox:</strong> Ctrl + F5</li>
            <li><strong>Safari:</strong> Cmd + Shift + R</li>
        </ul>
        
        <h3>3. Network İstekleri Debug:</h3>
        <button class="btn" onclick="startNetworkDebug()">
            📊 Network Debug Başlat
        </button>
        
        <div id="networkLogs" class="network-logs" style="display: none;">
            <h4>📋 Network İstekleri:</h4>
            <div id="logContent"></div>
        </div>
        
        <h3>4. Global Trader Kalıntıları Temizleme:</h3>
        <button class="btn" onclick="clearLocalStorage()">
            🧽 LocalStorage Temizle
        </button>
        
        <h3>5. Service Worker Temizleme:</h3>
        <button class="btn" onclick="clearServiceWorkers()">
            ⚙️ Service Workers Temizle
        </button>
        
        <h3>6. Manuel Test:</h3>
        <div style="margin: 20px 0;">
            <p>Bu linkler çalışıyor mu test edin:</p>
            <a href="assets/css/style.css" target="_blank" class="btn">📄 CSS Test</a>
            <a href="assets/js/main.js" target="_blank" class="btn">📄 JS Test</a>
            <a href="landing-new.php" target="_blank" class="btn">🏠 Landing Test</a>
        </div>
        
        <div id="results"></div>
        
        <h3>7. Tarayıcı Geliştirici Araçları:</h3>
        <ol>
            <li>F12'ye basın</li>
            <li><strong>Network</strong> sekmesine gidin</li>
            <li><strong>Disable cache</strong> kutusunu işaretleyin</li>
            <li>Sayfayı yenileyin (F5)</li>
            <li>Kırmızı/hatalı istekleri kontrol edin</li>
        </ol>
        
        <div style="background: #fff3cd; padding: 15px; border-radius: 5px; margin: 20px 0;">
            <h4>⚠️ Önemli Not:</h4>
            <p>Eğer hala dashboard.html hatası görüyorsanız:</p>
            <ul>
                <li>Tarayıcıyı tamamen kapatıp açın</li>
                <li>Incognito/Private mode deneyin</li>
                <li>Farklı tarayıcı deneyin</li>
            </ul>
        </div>
    </div>

    <script>
        function clearBrowserCache() {
            // Meta refresh ile cache bypass
            const timestamp = new Date().getTime();
            const currentUrl = window.location.href.split('?')[0];
            window.location.href = currentUrl + '?cache_clear=' + timestamp;
        }
        
        function clearLocalStorage() {
            try {
                localStorage.clear();
                sessionStorage.clear();
                showResult('✅ LocalStorage ve SessionStorage temizlendi!', 'success');
            } catch (e) {
                showResult('❌ LocalStorage temizlenemedi: ' + e.message, 'error');
            }
        }
        
        function clearServiceWorkers() {
            if ('serviceWorker' in navigator) {
                navigator.serviceWorker.getRegistrations().then(function(registrations) {
                    let count = 0;
                    for(let registration of registrations) {
                        registration.unregister();
                        count++;
                    }
                    showResult(`✅ ${count} Service Worker temizlendi!`, 'success');
                });
            } else {
                showResult('ℹ️ Service Worker desteklenmiyor', 'warning');
            }
        }
        
        function startNetworkDebug() {
            const logDiv = document.getElementById('networkLogs');
            const logContent = document.getElementById('logContent');
            logDiv.style.display = 'block';
            
            // Performance API ile network isteklerini izle
            const observer = new PerformanceObserver((list) => {
                const entries = list.getEntries();
                entries.forEach((entry) => {
                    const status = entry.responseStatus || 'unknown';
                    const color = status >= 400 ? 'red' : (status >= 300 ? 'orange' : 'green');
                    
                    logContent.innerHTML += `
                        <div style="color: ${color}; margin: 5px 0;">
                            <strong>${entry.name}</strong> - Status: ${status} - ${entry.duration.toFixed(2)}ms
                        </div>
                    `;
                });
            });
            
            observer.observe({entryTypes: ['resource']});
            
            // Console'daki hataları yakala
            const originalError = console.error;
            console.error = function(...args) {
                logContent.innerHTML += `
                    <div style="color: red; margin: 5px 0; background: #ffebee; padding: 5px;">
                        <strong>Console Error:</strong> ${args.join(' ')}
                    </div>
                `;
                originalError.apply(console, args);
            };
            
            showResult('📊 Network debug başlatıldı. Console ve Network istekleri izleniyor...', 'success');
        }
        
        function showResult(message, type) {
            const resultsDiv = document.getElementById('results');
            resultsDiv.innerHTML = `<div class="${type}" style="margin: 10px 0; padding: 10px; border-radius: 5px; background: #f8f9fa;">${message}</div>`;
        }
        
        // Sayfa yüklendiğinde otomatik kontroller
        window.addEventListener('load', function() {
            console.log('🧹 Cache temizleme sayfası yüklendi');
            
            // CSS dosyası erişim testi
            fetch('assets/css/style.css')
                .then(response => {
                    if (response.ok) {
                        showResult('✅ CSS dosyasına erişim başarılı', 'success');
                    } else {
                        showResult('❌ CSS dosyası erişim hatası: ' + response.status, 'error');
                    }
                })
                .catch(error => {
                    showResult('❌ CSS fetch hatası: ' + error.message, 'error');
                });
        });
        
        // URL parametresi kontrol et
        const urlParams = new URLSearchParams(window.location.search);
        if (urlParams.get('cache_clear')) {
            showResult('🔄 Cache bypass aktif - Sayfa cache olmadan yüklendi', 'success');
        }
    </script>
</body>
</html>

<?php
// Emergency debug - çok basit test
echo "BAŞLANGIÇ: PHP çalışıyor<br>";

// Headers debug
echo "HEADERS ÖNCESİ:<br>";
print_r(headers_list());
echo "<br><br>";

// Server variables
echo "SERVER INFO:<br>";
echo "REQUEST_URI: " . ($_SERVER['REQUEST_URI'] ?? 'YOK') . "<br>";
echo "HTTP_HOST: " . ($_SERVER['HTTP_HOST'] ?? 'YOK') . "<br>";
echo "SCRIPT_NAME: " . ($_SERVER['SCRIPT_NAME'] ?? 'YOK') . "<br>";

// Auto-prepend check
echo "<br>AUTO-PREPEND: " . ini_get('auto_prepend_file') . "<br>";
echo "AUTO-APPEND: " . ini_get('auto_append_file') . "<br>";

echo "<br>SON: Test tamamlandı";
?>

<script>
console.log('🔍 Emergency debug loaded');
console.log('📍 Current URL:', window.location.href);

// Network isteklerini izle
const observer = new PerformanceObserver((list) => {
    const entries = list.getEntries();
    entries.forEach((entry) => {
        if (entry.name.includes('dashboard')) {
            console.error('🚨 DASHBOARD BULUNDU:', entry.name);
            console.error('🔍 Type:', entry.entryType);
            console.error('📊 Details:', entry);
        }
    });
});

observer.observe({entryTypes: ['resource', 'navigation']});

// Fetch isteklerini yakala
const originalFetch = window.fetch;
window.fetch = function(...args) {
    if (args[0] && args[0].includes('dashboard')) {
        console.error('🚨 FETCH DASHBOARD:', args[0]);
        console.error('🔍 Stack:', new Error().stack);
    }
    return originalFetch.apply(this, args);
};

// XMLHttpRequest isteklerini yakala
const originalXHR = window.XMLHttpRequest.prototype.open;
window.XMLHttpRequest.prototype.open = function(method, url, ...args) {
    if (url && url.includes('dashboard')) {
        console.error('🚨 XHR DASHBOARD:', url);
        console.error('🔍 Stack:', new Error().stack);
    }
    return originalXHR.apply(this, [method, url, ...args]);
};

setTimeout(() => {
    console.log('✅ 5 saniye geçti, dashboard isteği yoksa temiz');
}, 5000);
</script>

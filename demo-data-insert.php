<?php
require_once 'includes/functions.php';

echo "<h1>📊 Demo Veri Ekleme</h1>";

// Demo data for each category
$demoData = [
    'us_stocks' => [
        ['AAPL', 'Apple Inc.', 175.84, 2.43, 45000000, 177.20, 174.50],
        ['MSFT', 'Microsoft Corp.', 342.68, 1.82, 28000000, 344.15, 340.22],
        ['GOOGL', 'Alphabet Inc.', 138.84, -0.91, 32000000, 140.50, 137.80],
        ['AMZN', 'Amazon.com Inc.', 142.81, 3.21, 38000000, 145.20, 141.30],
        ['TSLA', 'Tesla Inc.', 248.50, 5.67, 55000000, 252.80, 245.10],
        ['META', 'Meta Platforms', 298.58, 2.18, 22000000, 301.40, 296.20]
    ],
    'eu_stocks' => [
        ['SAP', 'SAP SE', 128.45, 1.56, 1200000, 129.80, 127.20],
        ['ASML', 'ASML Holding', 672.30, 2.84, 800000, 685.50, 668.90],
        ['LVMH', 'LVMH Group', 785.60, 0.92, 600000, 792.40, 782.10],
        ['NESN', 'Nestle SA', 108.75, -0.45, 900000, 109.80, 107.90],
        ['ROG', 'Roche Holding', 267.80, 1.23, 400000, 270.50, 265.30]
    ],
    'commodities' => [
        ['XAUUSD', 'Gold Spot', 2024.50, 1.85, 125000, 2035.80, 2018.20],
        ['XAGUSD', 'Silver Spot', 24.68, 2.34, 89000, 25.15, 24.20],
        ['USOIL', 'Crude Oil WTI', 82.45, -1.23, 156000, 84.20, 81.80],
        ['UKOIL', 'Brent Oil', 87.12, -0.89, 98000, 88.50, 86.30],
        ['NATGAS', 'Natural Gas', 2.845, 3.45, 145000, 2.980, 2.780]
    ],
    'forex_major' => [
        ['EUR/USD', 'Euro Dollar', 1.0892, 0.15, 2800000, 1.0925, 1.0875],
        ['GBP/USD', 'Pound Dollar', 1.2745, -0.08, 1950000, 1.2780, 1.2710],
        ['USD/JPY', 'Dollar Yen', 149.85, 0.35, 2100000, 150.20, 149.40],
        ['USD/CHF', 'Dollar Swiss', 0.8756, 0.12, 1200000, 0.8785, 0.8740],
        ['AUD/USD', 'Aussie Dollar', 0.6589, -0.22, 1650000, 0.6615, 0.6565]
    ],
    'indices' => [
        ['SPX', 'S&P 500', 4515.87, 0.78, 0, 4525.20, 4502.30],
        ['IXIC', 'NASDAQ', 14240.52, 1.24, 0, 14285.60, 14195.80],
        ['DJI', 'Dow Jones', 34890.24, 0.45, 0, 34925.80, 34845.10],
        ['DAX', 'DAX Index', 15680.45, 0.92, 0, 15720.80, 15645.20],
        ['FTSE', 'FTSE 100', 7545.68, -0.15, 0, 7568.40, 7532.90]
    ]
];

try {
    $database = new Database();
    $db = $database->getConnection();
    
    echo "<h2>🗑️ Eski Verileri Temizleme...</h2>";
    $db->exec("DELETE FROM markets");
    echo "<p>✅ Eski veriler temizlendi</p>";
    
    echo "<h2>📥 Demo Verileri Ekleme...</h2>";
    
    foreach ($demoData as $category => $stocks) {
        echo "<h3>📊 $category kategorisi ekleniyor...</h3>";
        
        foreach ($stocks as $stock) {
            $symbol = $stock[0];
            $name = $stock[1];
            $price = $stock[2];
            $change = $stock[3];
            $volume = $stock[4];
            $high = $stock[5];
            $low = $stock[6];
            $market_cap = $price * 1000000; // Fake market cap
            
            $query = "INSERT INTO markets (symbol, name, price, change_24h, volume_24h, high_24h, low_24h, market_cap, category, logo_url, created_at, updated_at) 
                      VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, '', NOW(), NOW())";
            
            $stmt = $db->prepare($query);
            $success = $stmt->execute([$symbol, $name, $price, $change, $volume, $high, $low, $market_cap, $category]);
            
            if ($success) {
                echo "<p style='color: green;'>✅ $symbol ($name) eklendi</p>";
            } else {
                echo "<p style='color: red;'>❌ $symbol eklenemedi: " . implode(', ', $stmt->errorInfo()) . "</p>";
            }
        }
        echo "<p><strong>$category kategorisi tamamlandı!</strong></p><br>";
    }
    
    echo "<h2>📊 Toplam Veri Kontrolü:</h2>";
    $query = "SELECT category, COUNT(*) as count FROM markets GROUP BY category";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $counts = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($counts as $count) {
        echo "<p>📈 <strong>{$count['category']}</strong>: {$count['count']} enstrüman</p>";
    }
    
    $totalQuery = "SELECT COUNT(*) as total FROM markets";
    $totalStmt = $db->prepare($totalQuery);
    $totalStmt->execute();
    $total = $totalStmt->fetch(PDO::FETCH_ASSOC);
    
    echo "<p><strong>🎯 Toplam: {$total['total']} enstrüman eklendi!</strong></p>";
    
    echo "<div style='background: #d4edda; padding: 20px; border-radius: 10px; margin: 20px 0;'>";
    echo "<h3>✅ Demo Veri Ekleme Başarılı!</h3>";
    echo "<p>Şimdi bu sayfaları test edebilirsiniz:</p>";
    echo "<ul>";
    echo "<li><a href='index.php' target='_blank'>Ana Piyasalar Sayfası</a></li>";
    echo "<li><a href='landing-new.php' target='_blank'>Landing Sayfası</a></li>";
    echo "<li><a href='api-test.php' target='_blank'>API Test Sayfası</a></li>";
    echo "</ul>";
    echo "</div>";
    
} catch (Exception $e) {
    echo "<div style='background: #f8d7da; padding: 20px; border-radius: 10px; margin: 20px 0;'>";
    echo "<h3>❌ Hata Oluştu!</h3>";
    echo "<p><strong>Hata:</strong> " . $e->getMessage() . "</p>";
    echo "<p><strong>Dosya:</strong> " . $e->getFile() . "</p>";
    echo "<p><strong>Satır:</strong> " . $e->getLine() . "</p>";
    echo "</div>";
}
?>

<style>
body {
    font-family: Arial, sans-serif;
    max-width: 1000px;
    margin: 0 auto;
    padding: 20px;
    line-height: 1.6;
}
h1, h2, h3 {
    color: #333;
}
p {
    margin: 5px 0;
}
</style>

<?php
require_once 'config/database.php';

// Portföy manipülasyon sistemi için gerekli tabloları oluştur
function createPortfolioTables() {
    $database = new Database();
    $db = $database->getConnection();
    
    try {
        // Price manipulations table - Admin fiyat manipülasyonlarını takip eder
        $query = "CREATE TABLE IF NOT EXISTS price_manipulations (
            id INT AUTO_INCREMENT PRIMARY KEY,
            admin_id INT NOT NULL,
            symbol VARCHAR(20) NOT NULL,
            old_price DECIMAL(15,8) NOT NULL,
            new_price DECIMAL(15,8) NOT NULL,
            change_percent DECIMAL(8,4) NOT NULL,
            reason TEXT,
            affected_users INT DEFAULT 0,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (admin_id) REFERENCES users(id)
        )";
        $db->exec($query);
        echo "✅ price_manipulations tablosu oluşturuldu<br>";
        
        // Admin actions table - Tüm admin işlemlerini loglar
        $query = "CREATE TABLE IF NOT EXISTS admin_actions (
            id INT AUTO_INCREMENT PRIMARY KEY,
            admin_id INT NOT NULL,
            action_type ENUM('price_change', 'portfolio_view', 'user_manipulation', 'bulk_operation') NOT NULL,
            target_user_id INT,
            symbol VARCHAR(20),
            details JSON,
            ip_address VARCHAR(45),
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (admin_id) REFERENCES users(id),
            FOREIGN KEY (target_user_id) REFERENCES users(id)
        )";
        $db->exec($query);
        echo "✅ admin_actions tablosu oluşturuldu<br>";
        
        // Settings table - Admin ayarları için (eğer yoksa)
        $query = "CREATE TABLE IF NOT EXISTS settings (
            id INT AUTO_INCREMENT PRIMARY KEY,
            setting_key VARCHAR(100) NOT NULL UNIQUE,
            setting_value TEXT NOT NULL,
            description TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        )";
        $db->exec($query);
        echo "✅ settings tablosu oluşturuldu<br>";
        
        // Current prices table - Güncel fiyatları saklar (eğer markets tablosu yetersizse)
        $query = "CREATE TABLE IF NOT EXISTS current_prices (
            id INT AUTO_INCREMENT PRIMARY KEY,
            symbol VARCHAR(20) NOT NULL UNIQUE,
            price DECIMAL(15,8) NOT NULL,
            original_price DECIMAL(15,8) NOT NULL,
            is_manipulated TINYINT(1) DEFAULT 0,
            manipulation_count INT DEFAULT 0,
            last_updated TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        )";
        $db->exec($query);
        echo "✅ current_prices tablosu oluşturuldu<br>";
        
        // Markets tablosunda eksik sütunları ekle
        $query = "SHOW COLUMNS FROM markets LIKE 'current_price'";
        $stmt = $db->prepare($query);
        $stmt->execute();
        $column_exists = $stmt->fetch();
        
        if (!$column_exists) {
            $query = "ALTER TABLE markets ADD COLUMN current_price DECIMAL(15,8) NOT NULL DEFAULT 0 AFTER price";
            $db->exec($query);
            
            $query = "ALTER TABLE markets ADD COLUMN is_active TINYINT(1) DEFAULT 1";
            $db->exec($query);
            
            $query = "ALTER TABLE markets ADD COLUMN change_percent DECIMAL(8,4) DEFAULT 0";
            $db->exec($query);
            
            echo "✅ markets tablosuna eksik sütunlar eklendi<br>";
        }
        
        // User portfolio tablosunda eksik sütunları ekle
        $query = "SHOW COLUMNS FROM user_portfolio LIKE 'current_value'";
        $stmt = $db->prepare($query);
        $stmt->execute();
        $column_exists = $stmt->fetch();
        
        if (!$column_exists) {
            $query = "ALTER TABLE user_portfolio ADD COLUMN current_value DECIMAL(15,2) DEFAULT 0 AFTER total_invested";
            $db->exec($query);
            
            $query = "ALTER TABLE user_portfolio ADD COLUMN profit_loss DECIMAL(15,2) DEFAULT 0";
            $db->exec($query);
            
            $query = "ALTER TABLE user_portfolio ADD COLUMN profit_loss_percent DECIMAL(8,4) DEFAULT 0";
            $db->exec($query);
            
            echo "✅ user_portfolio tablosuna eksik sütunlar eklendi<br>";
        }
        
        // Sample data ekle
        insertSampleData($db);
        
        echo "<br><strong>🎉 Portföy manipülasyon sistemi veritabanı hazır!</strong>";
        
    } catch(Exception $e) {
        echo "❌ Hata: " . $e->getMessage();
    }
}

function insertSampleData($db) {
    // Sample markets data
    $query = "INSERT IGNORE INTO markets (symbol, name, price, current_price, change_24h, volume_24h, high_24h, low_24h, is_active) VALUES 
              ('BTCUSD', 'Bitcoin', 45000.00, 45000.00, 2.5, 1000000, 46000, 44000, 1),
              ('ETHUSD', 'Ethereum', 3200.00, 3200.00, 1.8, 500000, 3300, 3100, 1),
              ('ADAUSD', 'Cardano', 0.52, 0.52, -0.5, 100000, 0.55, 0.50, 1),
              ('SOLUSD', 'Solana', 125.00, 125.00, 3.2, 200000, 130, 120, 1),
              ('DOGUSD', 'Dogecoin', 0.08, 0.08, 5.5, 50000, 0.085, 0.075, 1)";
    $db->exec($query);
    echo "✅ Sample market data eklendi<br>";
    
    // Sample user portfolio data (kullanıcı varsa)
    $query = "SELECT id FROM users WHERE is_admin = 0 LIMIT 3";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (count($users) > 0) {
        foreach ($users as $user) {
            $user_id = $user['id'];
            
            // Random portfolio for each user
            $portfolios = [
                ['BTCUSD', 0.5, 42000.00, 21000.00],
                ['ETHUSD', 2.3, 2800.00, 6440.00],
                ['ADAUSD', 1000, 0.45, 450.00],
                ['SOLUSD', 5, 110.00, 550.00],
                ['DOGUSD', 10000, 0.06, 600.00]
            ];
            
            // Her kullanıcıya rastgele 2-3 pozisyon ver
            $user_portfolios = array_slice($portfolios, 0, rand(2, 3));
            
            foreach ($user_portfolios as $portfolio) {
                $query = "INSERT IGNORE INTO user_portfolio (user_id, symbol, quantity, avg_price, total_invested) 
                         VALUES (?, ?, ?, ?, ?)";
                $stmt = $db->prepare($query);
                $stmt->execute([$user_id, $portfolio[0], $portfolio[1], $portfolio[2], $portfolio[3]]);
            }
        }
        echo "✅ Sample portfolio data eklendi<br>";
    }
    
    // Current prices tablosunu doldur
    $query = "INSERT IGNORE INTO current_prices (symbol, price, original_price) 
              SELECT symbol, price, price FROM markets WHERE is_active = 1";
    $db->exec($query);
    echo "✅ Current prices data eklendi<br>";
}

// Fonksiyonu çalıştır
createPortfolioTables();
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Portföy Sistemi Kurulumu</title>
    <style>
        body { font-family: Arial, sans-serif; padding: 20px; background: #f5f5f5; }
        .container { max-width: 800px; margin: 0 auto; background: white; padding: 30px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        h1 { color: #333; text-align: center; }
        .success { color: #27ae60; }
        .error { color: #e74c3c; }
        .info { background: #ecf0f1; padding: 15px; border-radius: 5px; margin: 20px 0; }
        .btn { display: inline-block; padding: 10px 20px; background: #3498db; color: white; text-decoration: none; border-radius: 5px; margin: 10px 5px; }
        .btn:hover { background: #2980b9; }
    </style>
</head>
<body>
    <div class="container">
        <h1>🚀 Admin Portföy Manipülasyon Sistemi</h1>
        
        <div class="info">
            <h3>✅ Kurulum Tamamlandı!</h3>
            <p>Aşağıdaki özellikler artık kullanılabilir:</p>
            <ul>
                <li>👥 Kullanıcı portföy görüntüleme</li>
                <li>📈 Fiyat manipülasyonu</li>
                <li>💰 Kar/zarar hesaplama</li>
                <li>📊 Admin işlem logları</li>
                <li>🎯 Hedefli kullanıcı manipülasyonu</li>
            </ul>
        </div>
        
        <div style="text-align: center;">
            <a href="admin.php" class="btn">🏠 Admin Paneli</a>
            <a href="admin_portfolio.php" class="btn">📊 Portföy Yönetimi</a>
        </div>
    </div>
</body>
</html>

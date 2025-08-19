<?php
require_once 'includes/functions.php';

// Check if user is logged in at all
if (!isLoggedIn()) {
    echo "<div style='padding:20px; font-family:Arial;'>";
    echo "<h3>⚠️ Giriş Gerekli</h3>";
    echo "<p>Bu sayfaya erişmek için giriş yapmanız gerekiyor.</p>";
    echo "<p><a href='login.php'>Giriş Yap</a> | <a href='index.php'>Ana Sayfa</a></p>";
    echo "</div>";
    exit();
}

// Check if user is admin
if (!isAdmin()) {
    echo "<div style='padding:20px; font-family:Arial;'>";
    echo "<h3>⚠️ Yetki Gerekli</h3>";
    echo "<p>Bu sayfaya erişmek için admin yetkisine sahip olmanız gerekiyor.</p>";
    echo "<p>Mevcut kullanıcı: " . ($_SESSION['username'] ?? 'Bilinmiyor') . "</p>";
    echo "<p>Admin durumu: " . (isset($_SESSION['is_admin']) ? ($_SESSION['is_admin'] ? 'Aktif' : 'Pasif') : 'Tanımsız') . "</p>";
    echo "<p><a href='admin.php'>Admin Panel</a> | <a href='index.php'>Ana Sayfa</a></p>";
    echo "</div>";
    exit();
}

$page_title = 'Kaldıraç Sistemi - Veritabanı Kurulumu';

// Create leverage database tables
function createLeverageTables() {
    $database = new Database();
    $db = $database->getConnection();
    
    $results = [];
    
    try {
        // 1. Create admin_settings table if not exists
        $admin_settings_sql = "
        CREATE TABLE IF NOT EXISTS admin_settings (
            id INT AUTO_INCREMENT PRIMARY KEY,
            setting_key VARCHAR(100) NOT NULL UNIQUE,
            setting_value TEXT NOT NULL,
            description TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_setting_key (setting_key)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ";
        
        $db->exec($admin_settings_sql);
        $results[] = "✓ admin_settings tablosu oluşturuldu";
        
        // 2. Create leverage_positions table
        $leverage_positions_sql = "
        CREATE TABLE IF NOT EXISTS leverage_positions (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            symbol VARCHAR(20) NOT NULL,
            collateral DECIMAL(20,8) NOT NULL,
            leverage_ratio INT NOT NULL,
            position_size DECIMAL(20,8) NOT NULL,
            entry_price DECIMAL(20,8) NOT NULL,
            liquidation_price DECIMAL(20,8) NOT NULL,
            trade_type ENUM('LONG', 'SHORT') NOT NULL,
            status ENUM('OPEN', 'CLOSED', 'LIQUIDATED') DEFAULT 'OPEN',
            unrealized_pnl DECIMAL(20,8) DEFAULT 0,
            realized_pnl DECIMAL(20,8) DEFAULT 0,
            trading_fee DECIMAL(20,8) NOT NULL,
            margin_used DECIMAL(20,8) NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            closed_at TIMESTAMP NULL,
            INDEX idx_user_id (user_id),
            INDEX idx_symbol (symbol),
            INDEX idx_status (status),
            INDEX idx_created_at (created_at),
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ";
        
        $db->exec($leverage_positions_sql);
        $results[] = "✓ leverage_positions tablosu oluşturuldu";
        
        // 2. Create leverage_transactions table
        $leverage_transactions_sql = "
        CREATE TABLE IF NOT EXISTS leverage_transactions (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            position_id INT NOT NULL,
            type ENUM('OPEN', 'CLOSE', 'LIQUIDATION', 'PARTIAL_CLOSE') NOT NULL,
            symbol VARCHAR(20) NOT NULL,
            amount DECIMAL(20,8) NOT NULL,
            price DECIMAL(20,8) NOT NULL,
            fee DECIMAL(20,8) NOT NULL,
            pnl DECIMAL(20,8) DEFAULT 0,
            leverage_ratio INT NOT NULL,
            trade_type ENUM('LONG', 'SHORT') NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_user_id (user_id),
            INDEX idx_position_id (position_id),
            INDEX idx_symbol (symbol),
            INDEX idx_type (type),
            INDEX idx_created_at (created_at),
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
            FOREIGN KEY (position_id) REFERENCES leverage_positions(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ";
        
        $db->exec($leverage_transactions_sql);
        $results[] = "✓ leverage_transactions tablosu oluşturuldu";
        
        // 3. Create leverage system settings
        $settings = [
            ['max_leverage', '100', 'Maksimum kaldıraç oranı'],
            ['leverage_fee_rate', '0.001', 'Kaldıraç işlem ücreti oranı (0.1%)'],
            ['liquidation_threshold', '0.05', 'Likidasyon eşik değeri (5%)'],
            ['margin_call_threshold', '0.20', 'Margin call eşik değeri (20%)'],
            ['leverage_trading_enabled', '1', 'Kaldıraç sistemi aktif mi']
        ];
        
        foreach ($settings as $setting) {
            $query = "INSERT INTO admin_settings (setting_key, setting_value, description, created_at) 
                     VALUES (?, ?, ?, NOW()) 
                     ON DUPLICATE KEY UPDATE 
                     setting_value = VALUES(setting_value), 
                     description = VALUES(description),
                     updated_at = NOW()";
            $stmt = $db->prepare($query);
            $stmt->execute($setting);
        }
        $results[] = "✓ Kaldıraç sistemi ayarları kaydedildi";
        
        // 4. Add leverage-related columns to users table if not exists
        try {
            $columns_to_add = [
                "ALTER TABLE users ADD COLUMN IF NOT EXISTS leverage_limit DECIMAL(20,8) DEFAULT 1000.00",
                "ALTER TABLE users ADD COLUMN IF NOT EXISTS max_leverage_ratio INT DEFAULT 10",
                "ALTER TABLE users ADD COLUMN IF NOT EXISTS leverage_trading_enabled BOOLEAN DEFAULT TRUE"
            ];
            
            foreach ($columns_to_add as $sql) {
                $db->exec($sql);
            }
            $results[] = "✓ Kullanıcı tablosuna kaldıraç kolonları eklendi";
        } catch (Exception $e) {
            $results[] = "⚠️ Kullanıcı tablosu güncellenemedi: " . $e->getMessage();
        }
        
        return ['success' => true, 'results' => $results];
        
    } catch (Exception $e) {
        return ['success' => false, 'error' => $e->getMessage()];
    }
}

// Clean leverage system function
function cleanLeverageSystem() {
    $database = new Database();
    $db = $database->getConnection();
    
    $results = [];
    
    try {
        // 1. Drop leverage tables
        $tables_to_drop = ['leverage_transactions', 'leverage_positions'];
        
        foreach ($tables_to_drop as $table) {
            try {
                $query = "DROP TABLE IF EXISTS $table";
                $db->exec($query);
                $results[] = "✓ $table tablosu silindi";
            } catch (Exception $e) {
                $results[] = "⚠️ $table tablosu silinemedi: " . $e->getMessage();
            }
        }
        
        // 2. Remove leverage settings from admin_settings
        try {
            $leverage_settings = [
                'max_leverage',
                'leverage_fee_rate', 
                'liquidation_threshold',
                'margin_call_threshold',
                'leverage_trading_enabled'
            ];
            
            foreach ($leverage_settings as $setting) {
                $query = "DELETE FROM admin_settings WHERE setting_key = ?";
                $stmt = $db->prepare($query);
                $stmt->execute([$setting]);
            }
            $results[] = "✓ Kaldıraç ayarları admin_settings'den silindi";
        } catch (Exception $e) {
            $results[] = "⚠️ Admin settings temizlenemedi: " . $e->getMessage();
        }
        
        return ['success' => true, 'results' => $results];
        
    } catch (Exception $e) {
        return ['success' => false, 'error' => $e->getMessage()];
    }
}

// Form processing
$operation_result = null;
if ($_POST) {
    if (isset($_POST['create_tables'])) {
        $operation_result = createLeverageTables();
    } elseif (isset($_POST['clean_leverage'])) {
        $operation_result = cleanLeverageSystem();
    }
}

include 'includes/header.php';
?>

<div class="container mt-4">
    <div class="row">
        <div class="col-md-10 mx-auto">
            <div class="card">
                <div class="card-header bg-warning text-dark">
                    <h4 class="mb-0">
                        <i class="fas fa-bolt me-2"></i>Kaldıraç Sistemi - Veritabanı Yönetimi
                    </h4>
                </div>
                <div class="card-body">
                    
                    <?php if ($operation_result): ?>
                    <div class="alert <?php echo $operation_result['success'] ? 'alert-success' : 'alert-danger'; ?>" role="alert">
                        <?php if ($operation_result['success']): ?>
                            <h5><i class="fas fa-check-circle me-2"></i>İşlem Tamamlandı!</h5>
                            <ul class="mb-0">
                                <?php foreach ($operation_result['results'] as $result): ?>
                                <li><?php echo $result; ?></li>
                                <?php endforeach; ?>
                            </ul>
                        <?php else: ?>
                            <h5><i class="fas fa-exclamation-triangle me-2"></i>Hata Oluştu!</h5>
                            <p class="mb-0"><?php echo $operation_result['error']; ?></p>
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="card h-100">
                                <div class="card-header bg-success text-white">
                                    <h5 class="mb-0"><i class="fas fa-plus me-2"></i>Kaldıraç Sistemini Kur</h5>
                                </div>
                                <div class="card-body">
                                    <p>Bu işlem aşağıdaki tabloları oluşturacak:</p>
                                    <ul>
                                        <li><strong>leverage_positions</strong> - Kaldıraç pozisyonları</li>
                                        <li><strong>leverage_transactions</strong> - Kaldıraç işlem geçmişi</li>
                                        <li><strong>admin_settings</strong> - Sistem ayarları</li>
                                    </ul>
                                    
                                    <form method="POST">
                                        <button type="submit" name="create_tables" class="btn btn-success">
                                            <i class="fas fa-database me-2"></i>Tabloları Oluştur
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="card h-100">
                                <div class="card-header bg-danger text-white">
                                    <h5 class="mb-0"><i class="fas fa-trash me-2"></i>Sistemi Temizle</h5>
                                </div>
                                <div class="card-body">
                                    <p>Bu işlem aşağıdaki verileri silecek:</p>
                                    <ul>
                                        <li>Tüm kaldıraç pozisyonları</li>
                                        <li>Tüm kaldıraç işlem geçmişi</li>
                                        <li>Kaldıraç sistem ayarları</li>
                                    </ul>
                                    <div class="alert alert-warning">
                                        <small><strong>Dikkat:</strong> Bu işlem geri alınamaz!</small>
                                    </div>
                                    
                                    <form method="POST" onsubmit="return confirm('Tüm kaldıraç verilerini silmek istediğinizden emin misiniz? Bu işlem geri alınamaz!');">
                                        <button type="submit" name="clean_leverage" class="btn btn-danger">
                                            <i class="fas fa-trash-alt me-2"></i>Sistemi Temizle
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mt-4">
                        <a href="admin.php" class="btn btn-secondary">
                            <i class="fas fa-arrow-left me-2"></i>Admin Paneline Dön
                        </a>
                        <a href="markets.php" class="btn btn-primary ms-2">
                            <i class="fas fa-chart-line me-2"></i>Piyasaları Test Et
                        </a>
                    </div>
                </div>
            </div>
            
            <!-- Current System Status -->
            <div class="card mt-4">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-database me-2"></i>Mevcut Sistem Durumu
                    </h5>
                </div>
                <div class="card-body">
                    <?php
                    $database = new Database();
                    $db = $database->getConnection();
                    
                    echo "<div class='row'>";
                    
                    // Check table existence
                    echo "<div class='col-md-6'>";
                    echo "<h6>Tablolar:</h6>";
                    $tables = ['leverage_positions', 'leverage_transactions'];
                    foreach ($tables as $table) {
                        try {
                            $query = "SHOW TABLES LIKE '$table'";
                            $stmt = $db->prepare($query);
                            $stmt->execute();
                            $exists = $stmt->rowCount() > 0;
                            
                            if ($exists) {
                                // Count records
                                $count_query = "SELECT COUNT(*) FROM $table";
                                $count_stmt = $db->prepare($count_query);
                                $count_stmt->execute();
                                $count = $count_stmt->fetchColumn();
                                
                                echo "<div class='mb-2'><span class='badge bg-success me-2'>$table</span> $count kayıt</div>";
                            } else {
                                echo "<div class='mb-2'><span class='badge bg-secondary me-2'>$table</span> Tablo yok</div>";
                            }
                        } catch (Exception $e) {
                            echo "<div class='mb-2'><span class='badge bg-danger me-2'>$table</span> Hata: " . $e->getMessage() . "</div>";
                        }
                    }
                    echo "</div>";
                    
                    // Check admin settings
                    echo "<div class='col-md-6'>";
                    echo "<h6>Sistem Ayarları:</h6>";
                    try {
                        $leverage_settings = [
                            'max_leverage' => 'Maksimum Kaldıraç',
                            'leverage_fee_rate' => 'İşlem Ücreti Oranı', 
                            'liquidation_threshold' => 'Likidasyon Eşiği',
                            'margin_call_threshold' => 'Margin Call Eşiği',
                            'leverage_trading_enabled' => 'Sistem Aktif'
                        ];
                        
                        foreach ($leverage_settings as $key => $label) {
                            $query = "SELECT setting_value FROM admin_settings WHERE setting_key = ?";
                            $stmt = $db->prepare($query);
                            $stmt->execute([$key]);
                            $value = $stmt->fetchColumn();
                            
                            if ($value !== false) {
                                $display_value = $value;
                                if ($key === 'leverage_trading_enabled') {
                                    $display_value = $value ? 'Aktif' : 'Pasif';
                                }
                                echo "<div class='mb-2'><span class='badge bg-info me-2'>$label</span> $display_value</div>";
                            } else {
                                echo "<div class='mb-2'><span class='badge bg-secondary me-2'>$label</span> Ayar yok</div>";
                            }
                        }
                        
                    } catch (Exception $e) {
                        echo "<div class='mb-2'><span class='badge bg-danger'>Ayarlar kontrol edilemedi</span></div>";
                    }
                    echo "</div>";
                    echo "</div>";
                    ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>

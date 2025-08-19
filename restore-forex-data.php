<?php
require_once 'includes/functions.php';

// Set execution time
set_time_limit(120);
ini_set('display_errors', 1);
error_reporting(E_ALL);

$page_title = 'Forex Data Restore';

// Handle restore action
$action = $_GET['action'] ?? '';
$results = [];

if ($action === 'restore') {
    $database = new Database();
    $db = $database->getConnection();
    
    $results = [
        'forex_minor' => 0,
        'forex_exotic' => 0,
        'total' => 0
    ];
    
    // Restore forex_minor category
    $minor_symbols = getCategorySymbols('forex_minor');
    foreach($minor_symbols as $symbol) {
        // Generate realistic demo data
        $basePrice = getBasePriceForSymbol($symbol, 'forex_minor');
        $change = (rand(-200, 200) / 100); // -2% to +2%
        $price = $basePrice + ($basePrice * $change / 100);
        
        $name = getCompanyName($symbol, 'forex_minor');
        $logo_url = getLogoUrl($symbol, 'forex_minor');
        
        $query = "UPDATE markets SET 
                  price = ?, 
                  change_24h = ?, 
                  volume_24h = ?, 
                  high_24h = ?, 
                  low_24h = ?, 
                  market_cap = 0,
                  logo_url = ?,
                  updated_at = CURRENT_TIMESTAMP 
                  WHERE symbol = ?";
        
        $stmt = $db->prepare($query);
        $success = $stmt->execute([
            round($price, 6),
            round($change, 2),
            rand(100000, 1000000),
            round($price * 1.01, 6),
            round($price * 0.99, 6),
            $logo_url,
            $symbol
        ]);
        
        if ($success) {
            $results['forex_minor']++;
            $results['total']++;
        }
    }
    
    // Restore forex_exotic category  
    $exotic_symbols = getCategorySymbols('forex_exotic');
    foreach($exotic_symbols as $symbol) {
        // Skip USD/TRY as it's updated by cron
        if ($symbol === 'USDTRY=X') continue;
        
        // Generate realistic demo data
        $basePrice = getBasePriceForSymbol($symbol, 'forex_exotic');
        $change = (rand(-300, 300) / 100); // -3% to +3% (more volatile)
        $price = $basePrice + ($basePrice * $change / 100);
        
        $name = getCompanyName($symbol, 'forex_exotic');
        $logo_url = getLogoUrl($symbol, 'forex_exotic');
        
        $query = "UPDATE markets SET 
                  price = ?, 
                  change_24h = ?, 
                  volume_24h = ?, 
                  high_24h = ?, 
                  low_24h = ?, 
                  market_cap = 0,
                  logo_url = ?,
                  updated_at = CURRENT_TIMESTAMP 
                  WHERE symbol = ?";
        
        $stmt = $db->prepare($query);
        $success = $stmt->execute([
            round($price, 6),
            round($change, 2),
            rand(50000, 500000),
            round($price * 1.02, 6),
            round($price * 0.98, 6),
            $logo_url,
            $symbol
        ]);
        
        if ($success) {
            $results['forex_exotic']++;
            $results['total']++;
        }
    }
}

include 'includes/header.php';
?>

<div class="container">
    <div class="row">
        <div class="col-12">
            <!-- Header -->
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-body">
                    <h1 class="h3 mb-3">
                        <i class="fas fa-undo me-2 text-warning"></i>
                        Forex Veri Geri Yükleme
                    </h1>
                    <p class="text-muted mb-0">
                        Forex Minor ve Exotic kategorilerindeki sıfırlanan verileri demo veri ile geri yükleyin.
                    </p>
                </div>
            </div>

            <!-- Current Status -->
            <div class="row mb-4">
                <?php
                $database = new Database();
                $db = $database->getConnection();
                
                // Check forex data
                $forex_minor_query = "SELECT COUNT(*) as total, 
                                    COUNT(CASE WHEN price > 0 THEN 1 END) as has_price,
                                    COUNT(CASE WHEN price = 0 OR price IS NULL THEN 1 END) as zero_price
                                    FROM markets WHERE category = 'forex_minor'";
                $minor_stats = $db->query($forex_minor_query)->fetch(PDO::FETCH_ASSOC);
                
                $forex_exotic_query = "SELECT COUNT(*) as total, 
                                     COUNT(CASE WHEN price > 0 THEN 1 END) as has_price,
                                     COUNT(CASE WHEN price = 0 OR price IS NULL THEN 1 END) as zero_price
                                     FROM markets WHERE category = 'forex_exotic'";
                $exotic_stats = $db->query($forex_exotic_query)->fetch(PDO::FETCH_ASSOC);
                ?>
                
                <div class="col-md-6">
                    <div class="card border-0 bg-light">
                        <div class="card-body">
                            <h6 class="text-primary mb-2">Forex Minor</h6>
                            <div class="row text-center">
                                <div class="col-4">
                                    <small class="text-muted">Toplam</small>
                                    <h5 class="mb-0"><?php echo $minor_stats['total']; ?></h5>
                                </div>
                                <div class="col-4">
                                    <small class="text-muted">Veri Var</small>
                                    <h5 class="mb-0 text-success"><?php echo $minor_stats['has_price']; ?></h5>
                                </div>
                                <div class="col-4">
                                    <small class="text-muted">Sıfır</small>
                                    <h5 class="mb-0 text-danger"><?php echo $minor_stats['zero_price']; ?></h5>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-6">
                    <div class="card border-0 bg-light">
                        <div class="card-body">
                            <h6 class="text-primary mb-2">Forex Exotic</h6>
                            <div class="row text-center">
                                <div class="col-4">
                                    <small class="text-muted">Toplam</small>
                                    <h5 class="mb-0"><?php echo $exotic_stats['total']; ?></h5>
                                </div>
                                <div class="col-4">
                                    <small class="text-muted">Veri Var</small>
                                    <h5 class="mb-0 text-success"><?php echo $exotic_stats['has_price']; ?></h5>
                                </div>
                                <div class="col-4">
                                    <small class="text-muted">Sıfır</small>
                                    <h5 class="mb-0 text-danger"><?php echo $exotic_stats['zero_price']; ?></h5>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Results Display -->
            <?php if (!empty($results) && $action === 'restore'): ?>
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-success text-white">
                    <h5 class="mb-0">
                        <i class="fas fa-check-circle me-2"></i>
                        Geri Yükleme Tamamlandı
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-4 text-center">
                            <h3 class="text-primary"><?php echo $results['forex_minor']; ?></h3>
                            <p class="text-muted mb-0">Forex Minor<br>Güncellendi</p>
                        </div>
                        <div class="col-md-4 text-center">
                            <h3 class="text-success"><?php echo $results['forex_exotic']; ?></h3>
                            <p class="text-muted mb-0">Forex Exotic<br>Güncellendi</p>
                        </div>
                        <div class="col-md-4 text-center">
                            <h3 class="text-info"><?php echo $results['total']; ?></h3>
                            <p class="text-muted mb-0">Toplam<br>Güncellendi</p>
                        </div>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <!-- Action Card -->
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-warning text-dark">
                    <h5 class="mb-0">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        Durum ve İşlemler
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-8">
                            <h6>❗ Sorun Açıklaması</h6>
                            <p class="text-muted">
                                Cron job sadece USD/TRY'yi güncelliyor, diğer forex çiftleri sıfırlandı. 
                                Bu normal bir durum çünkü API tasarrufu için sadece kritik semboller güncelleniyor.
                            </p>
                            
                            <h6>🔧 Çözüm</h6>
                            <p class="text-muted">
                                Forex Minor ve Exotic kategorileri için gerçekçi demo veri ile geri yükleme yapacağız.
                                Bu veriler trade amacıyla değil, gösterim amacıyla kullanılacak.
                            </p>
                            
                            <h6>📊 Veri Özellikleri</h6>
                            <ul class="text-muted">
                                <li>Gerçekçi piyasa fiyatları</li>
                                <li>-2% ile +3% arasında değişim</li>
                                <li>Uygun volume değerleri</li>
                                <li>High/low fiyat aralıkları</li>
                            </ul>
                        </div>
                        
                        <div class="col-md-4">
                            <div class="d-grid gap-2">
                                <?php if ($action !== 'restore'): ?>
                                <a href="?action=restore" class="btn btn-warning btn-lg">
                                    <i class="fas fa-undo me-2"></i>
                                    Forex Verilerini Geri Yükle
                                </a>
                                <?php else: ?>
                                <a href="?" class="btn btn-secondary">
                                    <i class="fas fa-redo me-2"></i>
                                    Sayfayı Yenile
                                </a>
                                <?php endif; ?>
                                
                                <a href="batch-update-manager.php" class="btn btn-primary">
                                    <i class="fas fa-cogs me-2"></i>
                                    Batch Manager'a Dön
                                </a>
                                
                                <a href="index.php" class="btn btn-outline-primary">
                                    <i class="fas fa-home me-2"></i>
                                    Ana Sayfaya Git
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Forex Data Preview -->
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-info text-white">
                    <h5 class="mb-0">
                        <i class="fas fa-chart-line me-2"></i>
                        Forex Kategorilerinde Mevcut Veriler
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <h6>Forex Minor</h6>
                            <?php
                            $minor_data = $db->query("SELECT symbol, price, change_24h, updated_at 
                                                     FROM markets 
                                                     WHERE category = 'forex_minor' 
                                                     ORDER BY symbol LIMIT 5")->fetchAll(PDO::FETCH_ASSOC);
                            ?>
                            <div class="table-responsive">
                                <table class="table table-sm">
                                    <thead>
                                        <tr>
                                            <th>Sembol</th>
                                            <th>Fiyat</th>
                                            <th>Değişim</th>
                                            <th>Güncelleme</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach($minor_data as $row): ?>
                                        <tr>
                                            <td><code><?php echo $row['symbol']; ?></code></td>
                                            <td><?php echo $row['price'] > 0 ? formatPrice($row['price']) : '<span class="text-danger">0.0000</span>'; ?></td>
                                            <td><?php echo formatChange($row['change_24h']); ?></td>
                                            <td><small class="text-muted"><?php echo date('H:i', strtotime($row['updated_at'])); ?></small></td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <h6>Forex Exotic</h6>
                            <?php
                            $exotic_data = $db->query("SELECT symbol, price, change_24h, updated_at 
                                                      FROM markets 
                                                      WHERE category = 'forex_exotic' 
                                                      ORDER BY symbol LIMIT 5")->fetchAll(PDO::FETCH_ASSOC);
                            ?>
                            <div class="table-responsive">
                                <table class="table table-sm">
                                    <thead>
                                        <tr>
                                            <th>Sembol</th>
                                            <th>Fiyat</th>
                                            <th>Değişim</th>
                                            <th>Güncelleme</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach($exotic_data as $row): ?>
                                        <tr>
                                            <td><code><?php echo $row['symbol']; ?></code></td>
                                            <td><?php echo $row['price'] > 0 ? formatPrice($row['price']) : '<span class="text-danger">0.0000</span>'; ?></td>
                                            <td><?php echo formatChange($row['change_24h']); ?></td>
                                            <td><small class="text-muted"><?php echo date('H:i', strtotime($row['updated_at'])); ?></small></td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>

<style>
.badge { font-size: 0.85em; }
.card-header { font-weight: 600; }
</style>

<?php include 'includes/footer.php'; ?>

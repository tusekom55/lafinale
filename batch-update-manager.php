<?php
require_once 'includes/functions.php';

// Set execution time for batch operations
set_time_limit(300);
ini_set('display_errors', 1);
error_reporting(E_ALL);

$page_title = 'Batch FMP Update Manager';

// Handle actions
$action = $_GET['action'] ?? '';
$results = null;

if ($action === 'populate_fmp_symbols') {
    $updated = populateFMPSymbols();
    $results = [
        'type' => 'populate',
        'message' => "FMP sembolleri güncellendi: $updated kayıt",
        'updated' => $updated
    ];
}

if ($action === 'batch_update') {
    $results = updateAllMarketsWithBatchFMP();
    $results['type'] = 'batch_update';
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
                        <i class="fas fa-sync-alt me-2 text-primary"></i>
                        Batch FMP Update Manager
                    </h1>
                    <p class="text-muted mb-0">
                        Ultra-verimli batch API güncelleme sistemi - Günde sadece ~5-6 istek!
                    </p>
                </div>
            </div>

            <!-- Database Status -->
            <div class="row mb-4">
                <?php
                $database = new Database();
                $db = $database->getConnection();
                
                // Check fmp_symbol column exists
                $check_column = $db->query("SHOW COLUMNS FROM markets LIKE 'fmp_symbol'")->fetch();
                $column_exists = !empty($check_column);
                
                // Get statistics
                $stats_query = "SELECT 
                    COUNT(*) as total_markets,
                    COUNT(fmp_symbol) as has_fmp_symbol,
                    COUNT(CASE WHEN fmp_symbol IS NULL OR fmp_symbol = '' THEN 1 END) as missing_fmp_symbol
                    FROM markets";
                $stats = $db->query($stats_query)->fetch(PDO::FETCH_ASSOC);
                
                $categories_query = "SELECT category, COUNT(*) as count, COUNT(fmp_symbol) as fmp_count 
                                   FROM markets GROUP BY category ORDER BY category";
                $categories = $db->query($categories_query)->fetchAll(PDO::FETCH_ASSOC);
                ?>
                
                <div class="col-md-3">
                    <div class="card border-0 bg-light">
                        <div class="card-body text-center">
                            <h5 class="text-primary mb-1">
                                <?php echo $column_exists ? '✅' : '❌'; ?>
                            </h5>
                            <small class="text-muted">FMP Column</small>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card border-0 bg-light">
                        <div class="card-body text-center">
                            <h5 class="text-info mb-1"><?php echo $stats['total_markets']; ?></h5>
                            <small class="text-muted">Toplam Market</small>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card border-0 bg-light">
                        <div class="card-body text-center">
                            <h5 class="text-success mb-1"><?php echo $stats['has_fmp_symbol']; ?></h5>
                            <small class="text-muted">FMP Sembol Var</small>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card border-0 bg-light">
                        <div class="card-body text-center">
                            <h5 class="text-warning mb-1"><?php echo $stats['missing_fmp_symbol']; ?></h5>
                            <small class="text-muted">FMP Sembol Eksik</small>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Results Display -->
            <?php if ($results): ?>
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-info text-white">
                    <h5 class="mb-0">
                        <i class="fas fa-chart-bar me-2"></i>
                        İşlem Sonucu
                    </h5>
                </div>
                <div class="card-body">
                    <?php if ($results['type'] === 'populate'): ?>
                        <div class="alert alert-success">
                            <i class="fas fa-check-circle me-2"></i>
                            <?php echo $results['message']; ?>
                        </div>
                    <?php elseif ($results['type'] === 'batch_update'): ?>
                        <div class="row">
                            <div class="col-md-6">
                                <h6>📊 Batch Güncelleme Özeti</h6>
                                <ul class="list-unstyled">
                                    <li><strong>Toplam API İsteği:</strong> <span class="badge bg-info"><?php echo $results['total_requests']; ?></span></li>
                                    <li><strong>Güncellenen Sembol:</strong> <span class="badge bg-success"><?php echo $results['updated_symbols']; ?></span></li>
                                    <li><strong>İşlenen Kategori:</strong> <span class="badge bg-primary"><?php echo count($results['categories']); ?></span></li>
                                    <li><strong>Hata Sayısı:</strong> <span class="badge bg-danger"><?php echo count($results['errors']); ?></span></li>
                                </ul>
                            </div>
                            <div class="col-md-6">
                                <h6>🎯 Verimlilik</h6>
                                <ul class="list-unstyled">
                                    <li><strong>API Kullanımı:</strong> <?php echo $results['total_requests']; ?>/100 günlük limit</li>
                                    <li><strong>Kalan İstek:</strong> <?php echo 100 - $results['total_requests']; ?></li>
                                    <li><strong>Verimlilik:</strong> 
                                        <?php echo $results['updated_symbols'] > 0 ? round($results['updated_symbols'] / $results['total_requests'], 1) : 0; ?> 
                                        sembol/istek
                                    </li>
                                </ul>
                            </div>
                        </div>
                        
                        <?php if (!empty($results['categories'])): ?>
                        <h6 class="mt-3">📋 İşlenen Kategoriler</h6>
                        <div class="d-flex flex-wrap gap-2">
                            <?php foreach($results['categories'] as $cat): ?>
                            <span class="badge bg-secondary"><?php echo $cat; ?></span>
                            <?php endforeach; ?>
                        </div>
                        <?php endif; ?>
                        
                        <?php if (!empty($results['errors'])): ?>
                        <h6 class="mt-3 text-danger">⚠️ Hatalar</h6>
                        <ul class="list-unstyled">
                            <?php foreach($results['errors'] as $error): ?>
                            <li class="text-danger">• <?php echo htmlspecialchars($error); ?></li>
                            <?php endforeach; ?>
                        </ul>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
            </div>
            <?php endif; ?>

            <!-- Action Buttons -->
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">
                        <i class="fas fa-cogs me-2"></i>
                        Yönetim İşlemleri
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <h6>🔧 Hazırlık İşlemleri</h6>
                            <p class="text-muted small">
                                Önce FMP symbol kolonunu oluşturun ve sembolleri doldurun.
                            </p>
                            
                            <?php if (!$column_exists): ?>
                            <div class="alert alert-warning">
                                <strong>⚠️ DİKKAT:</strong> Önce <code>add-fmp-symbol-column.sql</code> dosyasını çalıştırın!
                            </div>
                            <?php endif; ?>
                            
                            <div class="d-grid gap-2">
                                <a href="?action=populate_fmp_symbols" class="btn btn-warning">
                                    <i class="fas fa-database me-2"></i>
                                    FMP Sembollerini Doldur
                                    <?php if ($stats['missing_fmp_symbol'] > 0): ?>
                                    <span class="badge bg-light text-dark"><?php echo $stats['missing_fmp_symbol']; ?></span>
                                    <?php endif; ?>
                                </a>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <h6>🚀 Batch Güncelleme</h6>
                            <p class="text-muted small">
                                Tüm kategorileri minimal API isteği ile güncelleyin.
                            </p>
                            
                            <div class="d-grid gap-2">
                                <a href="?action=batch_update" class="btn btn-success">
                                    <i class="fas fa-sync-alt me-2"></i>
                                    Batch Güncelleme Başlat
                                    <small>(~5-6 API isteği)</small>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Category Breakdown -->
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-dark text-white">
                    <h5 class="mb-0">
                        <i class="fas fa-list me-2"></i>
                        Kategori Detayları
                    </h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>Kategori</th>
                                    <th>Toplam</th>
                                    <th>FMP Sembol</th>
                                    <th>Eksik</th>
                                    <th>Durum</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach($categories as $cat): ?>
                                <?php 
                                $missing = $cat['count'] - $cat['fmp_count'];
                                $percentage = $cat['count'] > 0 ? round(($cat['fmp_count'] / $cat['count']) * 100) : 0;
                                ?>
                                <tr>
                                    <td>
                                        <strong><?php echo getFinancialCategories()[$cat['category']] ?? $cat['category']; ?></strong>
                                        <br><small class="text-muted"><?php echo $cat['category']; ?></small>
                                    </td>
                                    <td><span class="badge bg-secondary"><?php echo $cat['count']; ?></span></td>
                                    <td><span class="badge bg-success"><?php echo $cat['fmp_count']; ?></span></td>
                                    <td><span class="badge bg-warning"><?php echo $missing; ?></span></td>
                                    <td>
                                        <?php if ($percentage >= 100): ?>
                                        <span class="badge bg-success">✅ Hazır</span>
                                        <?php elseif ($percentage >= 50): ?>
                                        <span class="badge bg-warning">⚠️ Eksik</span>
                                        <?php else: ?>
                                        <span class="badge bg-danger">❌ Boş</span>
                                        <?php endif; ?>
                                        <small class="text-muted">(<?php echo $percentage; ?>%)</small>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Instructions -->
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-info text-white">
                    <h5 class="mb-0">
                        <i class="fas fa-info-circle me-2"></i>
                        Kullanım Talimatları
                    </h5>
                </div>
                <div class="card-body">
                    <h6>🔄 Kurulum Adımları:</h6>
                    <ol>
                        <li><strong>SQL Çalıştır:</strong> <code>add-fmp-symbol-column.sql</code> dosyasını veritabanında çalıştırın</li>
                        <li><strong>Sembol Doldur:</strong> "FMP Sembollerini Doldur" butonuna tıklayın</li>
                        <li><strong>Test Et:</strong> "Batch Güncelleme Başlat" ile sistemini test edin</li>
                        <li><strong>Otomatik:</strong> Cronjob ile günlük otomatik güncelleme ayarlayın</li>
                    </ol>
                    
                    <h6 class="mt-3">⚡ Batch Sistem Avantajları:</h6>
                    <ul>
                        <li><strong>Ultra Verimli:</strong> 80+ sembollü sadece ~5-6 API isteği</li>
                        <li><strong>Rate Limit Safe:</strong> Günlük 100 istek limitini korur</li>
                        <li><strong>Otomatik Fallback:</strong> API hatalarında demo veri</li>
                        <li><strong>Kategori Gruplu:</strong> Stocks/Forex/Commodities ayrı batch</li>
                    </ul>
                </div>
            </div>

        </div>
    </div>
</div>

<style>
.badge { font-size: 0.85em; }
.card-header { font-weight: 600; }
.list-unstyled li { margin-bottom: 0.25rem; }
</style>

<?php include 'includes/footer.php'; ?>

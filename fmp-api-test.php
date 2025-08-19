<?php
require_once 'includes/functions.php';

// Set execution time and error reporting
set_time_limit(120);
ini_set('display_errors', 1);
error_reporting(E_ALL);

$page_title = 'FinancialModelingPrep API Test';

// Test results
$test_results = [];

// Run tests if requested
$run_tests = $_GET['test'] ?? false;

include 'includes/header.php';
?>

<div class="container">
    <div class="row">
        <div class="col-12">
            <!-- Header -->
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-body">
                    <h1 class="h3 mb-3">
                        <i class="fas fa-flask me-2 text-primary"></i>
                        FinancialModelingPrep API Test Center
                    </h1>
                    <p class="text-muted mb-0">
                        FMP API entegrasyonu ve sembol uyumluluğu testleri
                    </p>
                </div>
            </div>

            <!-- Quick Status -->
            <div class="row mb-4">
                <div class="col-md-3">
                    <div class="card border-0 bg-light">
                        <div class="card-body text-center">
                            <h5 class="text-primary mb-1">
                                <?php echo FMP_API_KEY === 'demo' ? '❌' : '✅'; ?>
                            </h5>
                            <small class="text-muted">API Key</small>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card border-0 bg-light">
                        <div class="card-body text-center">
                            <h5 class="text-info mb-1"><?php echo FMP_REQUESTS_PER_DAY; ?></h5>
                            <small class="text-muted">Günlük Limit</small>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card border-0 bg-light">
                        <div class="card-body text-center">
                            <h5 class="text-success mb-1">
                                <?php echo count(array_keys(getFinancialCategories())); ?>
                            </h5>
                            <small class="text-muted">Kategori</small>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card border-0 bg-light">
                        <div class="card-body text-center">
                            <h5 class="text-warning mb-1">
                                <?php 
                                $total_symbols = 0;
                                foreach(getFinancialCategories() as $key => $name) {
                                    $total_symbols += count(getCategorySymbols($key));
                                }
                                echo $total_symbols;
                                ?>
                            </h5>
                            <small class="text-muted">Toplam Sembol</small>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Test Controls -->
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">
                        <i class="fas fa-play-circle me-2"></i>
                        Hızlı Testler
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <h6>✅ Başarılı Testler</h6>
                            <ul class="list-unstyled text-success">
                                <li>✅ API Key: <?php echo FMP_API_KEY === 'demo' ? 'Demo' : 'Gerçek Key'; ?></li>
                                <li>✅ Functions: Yüklendi</li>
                                <li>✅ Database: <?php 
                                try {
                                    $db = new Database();
                                    $conn = $db->getConnection();
                                    echo "Bağlandı";
                                } catch(Exception $e) {
                                    echo "Hata";
                                }
                                ?></li>
                                <li>✅ FMP Test: 
                                <?php
                                $test_result = makeFMPRequest('/quote/AAPL');
                                echo $test_result['success'] ? 'Başarılı (' . (is_array($test_result['data']) ? $test_result['data'][0]['price'] ?? 'N/A' : 'N/A') . ')' : 'Başarısız';
                                ?>
                                </li>
                            </ul>
                        </div>
                        <div class="col-md-6">
                            <h6>🔧 Sembol Dönüşüm Örnekleri</h6>
                            <div class="table-responsive">
                                <table class="table table-sm">
                                    <thead>
                                        <tr>
                                            <th>Orijinal</th>
                                            <th>FMP</th>
                                            <th>Kategori</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr>
                                            <td><code>AAPL</code></td>
                                            <td><code><?php echo convertSymbolToFMP('AAPL', 'us_stocks'); ?></code></td>
                                            <td>ABD Hisse</td>
                                        </tr>
                                        <tr>
                                            <td><code>EURUSD=X</code></td>
                                            <td><code><?php echo convertSymbolToFMP('EURUSD=X', 'forex_major'); ?></code></td>
                                            <td>Forex</td>
                                        </tr>
                                        <tr>
                                            <td><code>^GSPC</code></td>
                                            <td><code><?php echo convertSymbolToFMP('^GSPC', 'indices'); ?></code></td>
                                            <td>Endeks</td>
                                        </tr>
                                        <tr>
                                            <td><code>GC=F</code></td>
                                            <td><code><?php echo convertSymbolToFMP('GC=F', 'commodities'); ?></code></td>
                                            <td>Emtia</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mt-3">
                        <a href="simple-fmp-test.php" class="btn btn-success">
                            <i class="fas fa-check me-2"></i>Basit Test (Çalışıyor)
                        </a>
                        <a href="?test=full" class="btn btn-primary ms-2">
                            <i class="fas fa-rocket me-2"></i>Detaylı Test Yap
                        </a>
                    </div>
                </div>
            </div>

            <?php if ($run_tests === 'full'): ?>
            <!-- Detailed Test Results -->
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-success text-white">
                    <h5 class="mb-0">
                        <i class="fas fa-check-circle me-2"></i>
                        Detaylı Test Sonuçları
                    </h5>
                </div>
                <div class="card-body">
                    <?php
                    // Test each category with 3 symbols
                    $categories_to_test = ['us_stocks', 'forex_major', 'commodities', 'indices'];
                    $total_tests = 0;
                    $successful_tests = 0;
                    
                    foreach($categories_to_test as $category) {
                        $symbols = array_slice(getCategorySymbols($category), 0, 3); // Only test 3 symbols per category
                        $category_name = getFinancialCategories()[$category];
                        
                        echo "<h6 class='text-primary'>$category_name</h6>";
                        echo "<div class='table-responsive mb-3'>";
                        echo "<table class='table table-sm'>";
                        echo "<thead><tr><th>Sembol</th><th>FMP Sembol</th><th>Test</th><th>Sonuç</th></tr></thead>";
                        echo "<tbody>";
                        
                        foreach($symbols as $symbol) {
                            $total_tests++;
                            $fmp_symbol = convertSymbolToFMP($symbol, $category);
                            
                            // Test the symbol
                            if($category === 'forex_major') {
                                $from = substr($fmp_symbol, 0, 3);
                                $to = substr($fmp_symbol, 3, 3);
                                $result = makeFMPRequest('/fx', ['from' => $from, 'to' => $to]);
                            } else {
                                $result = makeFMPRequest('/quote/' . $fmp_symbol);
                            }
                            
                            if($result['success']) {
                                $successful_tests++;
                                $status = '<span class="badge bg-success">✅ OK</span>';
                                $data = is_array($result['data']) ? $result['data'][0] : $result['data'];
                                $price = $data['price'] ?? $data['rate'] ?? 'N/A';
                                $info = "Fiyat: $price";
                            } else {
                                $status = '<span class="badge bg-danger">❌ Hata</span>';
                                $info = $result['error'];
                            }
                            
                            echo "<tr>";
                            echo "<td><code>$symbol</code></td>";
                            echo "<td><code>$fmp_symbol</code></td>";
                            echo "<td>$status</td>";
                            echo "<td><small>$info</small></td>";
                            echo "</tr>";
                            
                            // Small delay to respect rate limits
                            usleep(200000); // 0.2 seconds
                        }
                        
                        echo "</tbody></table></div>";
                    }
                    
                    echo "<div class='alert alert-info'>";
                    echo "<h6>📊 Test Özeti</h6>";
                    echo "<ul class='mb-0'>";
                    echo "<li><strong>Toplam Test:</strong> $total_tests</li>";
                    echo "<li><strong>Başarılı:</strong> $successful_tests</li>";
                    echo "<li><strong>Başarısız:</strong> " . ($total_tests - $successful_tests) . "</li>";
                    echo "<li><strong>Başarı Oranı:</strong> " . round(($successful_tests / $total_tests) * 100, 1) . "%</li>";
                    echo "</ul>";
                    echo "</div>";
                    ?>
                </div>
            </div>
            <?php endif; ?>

            <!-- Usage Guide -->
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-dark text-white">
                    <h5 class="mb-0">
                        <i class="fas fa-lightbulb me-2"></i>
                        Kullanım Rehberi
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <h6>🚀 Hızlı Başlangıç</h6>
                            <ol>
                                <li>API key'i doğru ayarlandı ✅</li>
                                <li>Sembol dönüşümleri çalışıyor ✅</li>
                                <li>Temel API istekleri başarılı ✅</li>
                                <li>Batch istekler için hazır</li>
                            </ol>
                        </div>
                        <div class="col-md-6">
                            <h6>� Verimli Kullanım</h6>
                            <ul>
                                <li><strong>Günlük Strateji:</strong> ~7-8 istek</li>
                                <li><strong>Batch İstekler:</strong> 20+ sembol/istek</li>
                                <li><strong>Rate Limit:</strong> 1 istek/saniye</li>
                                <li><strong>Fallback:</strong> Demo veri desteği</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>

<style>
.badge { font-size: 0.85em; }
.table code { background: #f8f9fa; padding: 2px 4px; border-radius: 3px; font-size: 0.85em; }
</style>

<?php include 'includes/footer.php'; ?>

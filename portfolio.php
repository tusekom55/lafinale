<?php
require_once 'includes/functions.php';

// Require login for portfolio
requireLogin();

$page_title = t('portfolio') ?: 'Portfolio';
$error = '';
$success = '';

$user_id = $_SESSION['user_id'];

// Handle sell from portfolio
if ($_POST && isset($_POST['sell_from_portfolio'])) {
    $symbol = $_POST['symbol'] ?? '';
    $sell_quantity = (float)($_POST['sell_quantity'] ?? 0);
    
    $holding = getPortfolioHolding($user_id, $symbol);
    $market = getSingleMarket($symbol);
    
    if (!$holding) {
        $error = 'Bu varlığa sahip değilsiniz.';
    } elseif (!$market) {
        $error = 'Piyasa verisi bulunamadı.';
    } elseif ($sell_quantity <= 0 || $sell_quantity > $holding['quantity']) {
        $error = 'Geçersiz satış miktarı.';
    } else {
        // Calculate USD amount based on quantity and current price
        $usd_amount = $sell_quantity * $market['price'];
        
        // Execute the sell trade
        if (executeSimpleTrade($user_id, $symbol, 'sell', $usd_amount, $market['price'])) {
            $success = formatTurkishNumber($sell_quantity, 6) . ' ' . $symbol . ' başarıyla satıldı!';
        } else {
            $error = 'Satış işlemi başarısız oldu.';
        }
    }
}

// Get user portfolio
$portfolio = getUserPortfolio($user_id);
$portfolio_stats = getPortfolioValue($user_id);

// Get balances
$trading_currency = getTradingCurrency();
$currency_field = getCurrencyField($trading_currency);
$currency_symbol = getCurrencySymbol($trading_currency);
$balance = getUserBalance($user_id, $currency_field);

// Get recent transactions for portfolio
$recent_transactions = getUserTransactions($user_id, 20);

include 'includes/header.php';
?>

<!-- Modern Portfolio Styles -->
<style>
.portfolio-header {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    border-radius: 16px;
    padding: 2.5rem 2rem;
    margin-bottom: 2rem;
    position: relative;
    overflow: hidden;
}

.portfolio-header::before {
    content: '';
    position: absolute;
    top: 0;
    right: 0;
    width: 100px;
    height: 100px;
    background: rgba(255,255,255,0.1);
    border-radius: 50%;
    transform: translate(30px, -30px);
}

.portfolio-card {
    background: white;
    border-radius: 16px;
    padding: 1.5rem;
    box-shadow: 0 4px 20px rgba(0,0,0,0.08);
    border: 1px solid rgba(0,0,0,0.05);
    transition: all 0.3s ease;
    height: 100%;
}

.portfolio-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 8px 30px rgba(0,0,0,0.12);
}

.metric-icon {
    width: 48px;
    height: 48px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    margin-bottom: 1rem;
    font-size: 1.5rem;
}

.metric-value {
    font-size: 1.8rem;
    font-weight: 700;
    margin: 0.5rem 0;
    line-height: 1.2;
}

.metric-label {
    color: #6c757d;
    font-size: 0.875rem;
    font-weight: 500;
    margin-bottom: 0.5rem;
}

.metric-subtitle {
    color: #adb5bd;
    font-size: 0.75rem;
}

.holdings-table {
    background: white;
    border-radius: 16px;
    overflow: hidden;
    box-shadow: 0 4px 20px rgba(0,0,0,0.08);
    border: 1px solid rgba(0,0,0,0.05);
}

.table-header {
    background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
    border-bottom: 1px solid #dee2e6;
}

.asset-logo {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    object-fit: cover;
    border: 2px solid #f8f9fa;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
}

.asset-info h6 {
    font-weight: 700;
    color: #212529;
    margin-bottom: 0.25rem;
}

.asset-info small {
    color: #6c757d;
    font-weight: 500;
}

.performance-positive {
    color: #10b981 !important;
    font-weight: 600;
}

.performance-negative {
    color: #ef4444 !important;
    font-weight: 600;
}

.action-buttons .btn {
    border-radius: 8px;
    font-weight: 500;
    transition: all 0.2s ease;
}

.action-buttons .btn:hover {
    transform: translateY(-1px);
}

.distribution-item {
    padding: 1rem;
    border-radius: 12px;
    background: #f8f9fa;
    transition: all 0.2s ease;
    border: 1px solid rgba(0,0,0,0.05);
}

.distribution-item:hover {
    background: #e9ecef;
    transform: translateY(-1px);
}

.progress-modern {
    height: 6px;
    border-radius: 3px;
    background: #e9ecef;
}

.progress-modern .progress-bar {
    background: linear-gradient(90deg, #667eea 0%, #764ba2 100%);
    border-radius: 3px;
}

@media (max-width: 768px) {
    .portfolio-header {
        padding: 2rem 1.5rem;
        text-align: center;
    }
    
    .metric-value {
        font-size: 1.5rem;
    }
}
</style>

<div class="container" style="padding-top: 2rem;">
    <!-- Professional Portfolio Header -->
    <div class="portfolio-header">
        <div class="row align-items-center">
            <div class="col-md-8 text-center text-md-start">
                <h1 class="h2 mb-2 fw-bold">Portföy Özeti</h1>
                <p class="mb-0 opacity-90">Yatırımlarınızı izleyin ve performansını gerçek zamanlı takip edin</p>
            </div>
            <div class="col-md-4 text-center mt-3 mt-md-0">
                <div class="d-inline-flex align-items-center bg-white bg-opacity-20 rounded-pill px-3 py-2">
                    <div class="text-center">
                        <div class="h5 mb-0 fw-bold"><?php echo count($portfolio); ?></div>
                        <small class="opacity-75">Varlık</small>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Professional Stats Cards -->
    <div class="row mb-4 g-3">
        <div class="col-lg-4 col-md-6 col-12">
            <div class="portfolio-card">
                <div class="metric-icon bg-primary bg-opacity-10">
                    <i class="fas fa-wallet text-primary"></i>
                </div>
                <div class="metric-label">Toplam Portföy Değeri</div>
                <div class="metric-value text-dark">
                    <?php 
                    if ($trading_currency == 1) {
                        echo formatTurkishNumber(convertUSDToTL($portfolio_stats['current_value']), 2) . ' TL';
                    } else {
                        echo formatTurkishNumber($portfolio_stats['current_value'], 2) . ' USD';
                    }
                    ?>
                </div>
                <div class="metric-subtitle">
                    Toplam Yatırım: <?php 
                    $invested_display = $trading_currency == 1 ? convertUSDToTL($portfolio_stats['total_invested']) : $portfolio_stats['total_invested'];
                    echo formatTurkishNumber($invested_display, 2) . ' ' . $currency_symbol;
                    ?>
                </div>
            </div>
        </div>
        
        <div class="col-lg-4 col-md-6 col-12">
            <div class="portfolio-card">
                <div class="metric-icon <?php echo $portfolio_stats['profit_loss'] >= 0 ? 'bg-success' : 'bg-danger'; ?> bg-opacity-10">
                    <i class="fas <?php echo $portfolio_stats['profit_loss'] >= 0 ? 'fa-trending-up text-success' : 'fa-trending-down text-danger'; ?>"></i>
                </div>
                <div class="metric-label">Toplam Kar/Zarar</div>
                <div class="metric-value <?php echo $portfolio_stats['profit_loss'] >= 0 ? 'performance-positive' : 'performance-negative'; ?>">
                    <?php 
                    $profit_loss_display = $trading_currency == 1 ? convertUSDToTL($portfolio_stats['profit_loss']) : $portfolio_stats['profit_loss'];
                    echo ($portfolio_stats['profit_loss'] >= 0 ? '+' : '') . formatTurkishNumber($profit_loss_display, 2) . ' ' . $currency_symbol;
                    ?>
                </div>
                <div class="metric-subtitle <?php echo $portfolio_stats['profit_loss_percentage'] >= 0 ? 'performance-positive' : 'performance-negative'; ?>">
                    <?php echo ($portfolio_stats['profit_loss_percentage'] >= 0 ? '+' : '') . formatTurkishNumber($portfolio_stats['profit_loss_percentage'], 2); ?>% Getiri
                </div>
            </div>
        </div>
        
        <div class="col-lg-4 col-12">
            <div class="portfolio-card">
                <div class="metric-icon bg-info bg-opacity-10">
                    <i class="fas fa-chart-line text-info"></i>
                </div>
                <div class="metric-label">Kullanılabilir Bakiye</div>
                <div class="metric-value text-dark">
                    <?php echo formatTurkishNumber($balance, 2) . ' ' . $currency_symbol; ?>
                </div>
                <div class="metric-subtitle">
                    Yatırıma hazır
                </div>
            </div>
        </div>
    </div>

    <?php if ($error): ?>
    <div class="alert alert-danger" role="alert">
        <i class="fas fa-exclamation-triangle me-2"></i><?php echo $error; ?>
    </div>
    <?php endif; ?>
    
    <?php if ($success): ?>
    <div class="alert alert-success" role="alert">
        <i class="fas fa-check-circle me-2"></i><?php echo $success; ?>
    </div>
    <?php endif; ?>

    <!-- Professional Holdings Table -->
    <div class="row">
        <div class="col-12">
            <div class="holdings-table">
                <div class="table-header p-3">
                    <h5 class="mb-0 fw-bold text-dark">
                        <i class="fas fa-briefcase me-2"></i>Portföy Varlıkları
                    </h5>
                </div>
                <div class="p-0">
                    <?php if (empty($portfolio)): ?>
                    <div class="text-center py-5">
                        <div class="mb-4">
                            <div class="d-inline-flex align-items-center justify-content-center bg-light rounded-circle" 
                                 style="width: 80px; height: 80px;">
                                <i class="fas fa-chart-pie fa-2x text-muted"></i>
                            </div>
                        </div>
                        <h5 class="text-dark mb-2">Yatırım Yolculuğunuzu Başlatın</h5>
                        <p class="text-muted mb-4">
                            Hisse senetleri, kripto para ve diğer varlıklara yatırım yaparak portföyünüzü oluşturun
                        </p>
                        <a href="markets.php" class="btn btn-primary btn-lg rounded-pill px-4">
                            <i class="fas fa-plus me-2"></i>Piyasaları Keşfet
                        </a>
                    </div>
                    <?php else: ?>
                    <div class="table-responsive">
                        <table class="table mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th class="border-0 ps-4 py-3 fw-semibold text-uppercase d-none d-md-table-cell" style="font-size: 0.75rem; letter-spacing: 0.5px;">Varlık</th>
                                    <th class="border-0 ps-4 py-3 fw-semibold text-uppercase d-md-none" style="font-size: 0.75rem; letter-spacing: 0.5px;">V.</th>
                                    <th class="border-0 text-end py-3 fw-semibold text-uppercase d-none d-lg-table-cell" style="font-size: 0.75rem; letter-spacing: 0.5px;">Miktar</th>
                                    <th class="border-0 text-end py-3 fw-semibold text-uppercase d-none d-xl-table-cell" style="font-size: 0.75rem; letter-spacing: 0.5px;">Ort. Fiyat</th>
                                    <th class="border-0 text-end py-3 fw-semibold text-uppercase d-none d-lg-table-cell" style="font-size: 0.75rem; letter-spacing: 0.5px;">Güncel Fiyat</th>
                                    <th class="border-0 text-end py-3 fw-semibold text-uppercase" style="font-size: 0.75rem; letter-spacing: 0.5px;">Değer</th>
                                    <th class="border-0 text-end py-3 fw-semibold text-uppercase" style="font-size: 0.75rem; letter-spacing: 0.5px;">K/Z</th>
                                    <th class="border-0 text-center pe-4 py-3 fw-semibold text-uppercase" style="font-size: 0.75rem; letter-spacing: 0.5px;">İşlem</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($portfolio as $holding): ?>
                                <?php 
                                $current_value = $holding['quantity'] * $holding['current_price'];
                                $profit_loss = $current_value - $holding['total_invested'];
                                $profit_loss_percent = $holding['total_invested'] > 0 ? ($profit_loss / $holding['total_invested']) * 100 : 0;
                                ?>
                                <tr class="border-bottom" style="border-color: rgba(0,0,0,0.05) !important;">
                                    <td class="ps-4 py-4">
                                        <div class="d-flex align-items-center">
                                            <?php if ($holding['logo_url']): ?>
                                            <img src="<?php echo $holding['logo_url']; ?>" 
                                                 alt="<?php echo $holding['name']; ?>" 
                                                 class="asset-logo me-3"
                                                 onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                                            <div class="bg-gradient text-white rounded-circle d-none align-items-center justify-content-center me-3" 
                                                 style="width: 40px; height: 40px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
                                                <i class="fas fa-coins"></i>
                                            </div>
                                            <?php else: ?>
                                            <div class="bg-gradient text-white rounded-circle d-flex align-items-center justify-content-center me-3" 
                                                 style="width: 40px; height: 40px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
                                                <i class="fas fa-coins"></i>
                                            </div>
                                            <?php endif; ?>
                                            <div class="asset-info">
                                                <h6 class="mb-1"><?php echo $holding['symbol']; ?></h6>
                                                <small><?php echo substr($holding['name'], 0, 30) . (strlen($holding['name']) > 30 ? '...' : ''); ?></small>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="text-end py-4 d-none d-lg-table-cell">
                                        <div class="fw-bold text-dark"><?php echo formatTurkishNumber($holding['quantity'], 6); ?></div>
                                        <small class="text-muted">adet</small>
                                    </td>
                                    <td class="text-end py-4 d-none d-xl-table-cell">
                                        <div class="fw-semibold"><?php echo formatPrice($holding['avg_price']); ?></div>
                                        <small class="text-muted">USD</small>
                                    </td>
                                    <td class="text-end py-4 d-none d-lg-table-cell">
                                        <div class="fw-semibold"><?php echo formatPrice($holding['current_price']); ?></div>
                                        <small class="text-muted">USD</small>
                                    </td>
                                    <td class="text-end py-4">
                                        <div class="fw-bold text-dark">
                                            <?php 
                                            if ($trading_currency == 1) {
                                                echo formatTurkishNumber(convertUSDToTL($current_value), 2) . ' TL';
                                            } else {
                                                echo formatTurkishNumber($current_value, 2) . ' USD';
                                            }
                                            ?>
                                        </div>
                                        <small class="text-muted d-none d-md-block">
                                            Maliyet: <?php 
                                            $invested_display = $trading_currency == 1 ? convertUSDToTL($holding['total_invested']) : $holding['total_invested'];
                                            echo formatTurkishNumber($invested_display, 2) . ' ' . $currency_symbol;
                                            ?>
                                        </small>
                                    </td>
                                    <td class="text-end py-4">
                                        <div class="fw-bold <?php echo $profit_loss >= 0 ? 'performance-positive' : 'performance-negative'; ?>">
                                            <?php 
                                            $profit_loss_display = $trading_currency == 1 ? convertUSDToTL($profit_loss) : $profit_loss;
                                            echo ($profit_loss >= 0 ? '+' : '') . formatTurkishNumber($profit_loss_display, 2) . ' ' . $currency_symbol;
                                            ?>
                                        </div>
                                        <small class="<?php echo $profit_loss_percent >= 0 ? 'performance-positive' : 'performance-negative'; ?>">
                                            <?php echo ($profit_loss_percent >= 0 ? '+' : '') . formatTurkishNumber($profit_loss_percent, 2); ?>%
                                        </small>
                                    </td>
                                    <td class="text-center py-4 pe-4">
                                        <div class="action-buttons d-flex flex-column flex-md-row gap-1">
                                            <button type="button" class="btn btn-sm btn-outline-danger" 
                                                    onclick="showSellModal('<?php echo $holding['symbol']; ?>', '<?php echo $holding['name']; ?>', <?php echo $holding['quantity']; ?>, <?php echo $holding['current_price']; ?>)">
                                                <i class="fas fa-arrow-down me-1"></i><span class="d-none d-md-inline">Sat</span>
                                            </button>
                                            <a href="markets.php?search=<?php echo urlencode($holding['symbol']); ?>" 
                                               class="btn btn-sm btn-outline-primary">
                                                <i class="fas fa-chart-line me-1"></i><span class="d-none d-md-inline">Grafik</span>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Leverage Positions Section -->
    <div class="row mt-4">
        <div class="col-12">
            <div class="holdings-table">
                <div class="table-header p-3" style="background: linear-gradient(135deg, #ff6b35 0%, #f7931e 100%);">
                    <h5 class="mb-0 fw-bold text-white">
                        <i class="fas fa-bolt me-2"></i>Kaldıraçlı İşlemler
                    </h5>
                </div>
                <div class="p-0">
                    <?php
                    // Get user's leverage positions
                    $leverage_positions = getUserLeveragePositions($user_id);
                    ?>
                    
                    <?php if (empty($leverage_positions)): ?>
                    <div class="text-center py-5">
                        <div class="mb-4">
                            <div class="d-inline-flex align-items-center justify-content-center" 
                                 style="width: 80px; height: 80px; background: linear-gradient(135deg, #ff6b35 0%, #f7931e 100%); border-radius: 50%;">
                                <i class="fas fa-bolt fa-2x text-white"></i>
                            </div>
                        </div>
                        <h5 class="text-dark mb-2">Henüz Kaldıraçlı Pozisyon Yok</h5>
                        <p class="text-muted mb-4">
                            Yüksek getiri potansiyeli için kaldıraçlı pozisyonlar açabilirsiniz
                        </p>
                        <a href="markets.php" class="btn btn-warning btn-lg rounded-pill px-4">
                            <i class="fas fa-bolt me-2"></i>Kaldıraçlı İşlem Yap
                        </a>
                    </div>
                    <?php else: ?>
                    <div class="table-responsive">
                        <table class="table mb-0">
                            <thead class="table-warning">
                                <tr>
                                    <th class="border-0 ps-4 py-3 fw-semibold text-uppercase" style="font-size: 0.75rem; letter-spacing: 0.5px;">Varlık</th>
                                    <th class="border-0 text-end py-3 fw-semibold text-uppercase d-none d-lg-table-cell" style="font-size: 0.75rem; letter-spacing: 0.5px;">Teminat</th>
                                    <th class="border-0 text-end py-3 fw-semibold text-uppercase" style="font-size: 0.75rem; letter-spacing: 0.5px;">Kaldıraç</th>
                                    <th class="border-0 text-center py-3 fw-semibold text-uppercase" style="font-size: 0.75rem; letter-spacing: 0.5px;">Tür</th>
                                    <th class="border-0 text-end py-3 fw-semibold text-uppercase d-none d-xl-table-cell" style="font-size: 0.75rem; letter-spacing: 0.5px;">Giriş</th>
                                    <th class="border-0 text-end py-3 fw-semibold text-uppercase d-none d-lg-table-cell" style="font-size: 0.75rem; letter-spacing: 0.5px;">Likidasyon</th>
                                    <th class="border-0 text-end py-3 fw-semibold text-uppercase" style="font-size: 0.75rem; letter-spacing: 0.5px;">PnL</th>
                                    <th class="border-0 text-center py-3 fw-semibold text-uppercase" style="font-size: 0.75rem; letter-spacing: 0.5px;">Durum</th>
                                    <th class="border-0 text-center pe-4 py-3 fw-semibold text-uppercase" style="font-size: 0.75rem; letter-spacing: 0.5px;">İşlem</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($leverage_positions as $position): ?>
                                <?php 
                                // Get current market price for PnL calculation
                                $current_market = getSingleMarket($position['symbol']);
                                $current_price = $current_market ? $current_market['price'] : $position['entry_price'];
                                
                                // Calculate unrealized PnL
                                $price_change = $current_price - $position['entry_price'];
                                if ($position['trade_type'] === 'SHORT') {
                                    $price_change = -$price_change; // Invert for short positions
                                }
                                $unrealized_pnl = ($price_change / $position['entry_price']) * $position['position_size'];
                                
                                // Update the database with current PnL
                                updateLeveragePositionPnL($position['id'], $unrealized_pnl);
                                
                                // Check liquidation risk
                                $liquidation_risk = false;
                                if ($position['trade_type'] === 'LONG' && $current_price <= $position['liquidation_price']) {
                                    $liquidation_risk = true;
                                } elseif ($position['trade_type'] === 'SHORT' && $current_price >= $position['liquidation_price']) {
                                    $liquidation_risk = true;
                                }
                                ?>
                                <tr class="border-bottom <?php echo $liquidation_risk ? 'bg-danger bg-opacity-10' : ''; ?>" style="border-color: rgba(0,0,0,0.05) !important;">
                                    <td class="ps-4 py-4">
                                        <div class="d-flex align-items-center">
                                            <?php if ($current_market && $current_market['logo_url']): ?>
                                            <img src="<?php echo $current_market['logo_url']; ?>" 
                                                 alt="<?php echo $position['symbol']; ?>" 
                                                 class="asset-logo me-3"
                                                 onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                                            <div class="bg-gradient text-white rounded-circle d-none align-items-center justify-content-center me-3" 
                                                 style="width: 40px; height: 40px; background: linear-gradient(135deg, #ff6b35 0%, #f7931e 100%);">
                                                <i class="fas fa-bolt"></i>
                                            </div>
                                            <?php else: ?>
                                            <div class="bg-gradient text-white rounded-circle d-flex align-items-center justify-content-center me-3" 
                                                 style="width: 40px; height: 40px; background: linear-gradient(135deg, #ff6b35 0%, #f7931e 100%);">
                                                <i class="fas fa-bolt"></i>
                                            </div>
                                            <?php endif; ?>
                                            <div class="asset-info">
                                                <h6 class="mb-1"><?php echo $position['symbol']; ?></h6>
                                                <small><?php echo $current_market ? substr($current_market['name'], 0, 30) . (strlen($current_market['name']) > 30 ? '...' : '') : $position['symbol']; ?></small>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="text-end py-4 d-none d-lg-table-cell">
                                        <div class="fw-bold text-dark"><?php echo formatTurkishNumber($position['collateral'], 2); ?></div>
                                        <small class="text-muted">USD</small>
                                    </td>
                                    <td class="text-end py-4">
                                        <div class="fw-bold text-warning"><?php echo $position['leverage_ratio']; ?>x</div>
                                        <small class="text-muted">Pozisyon: $<?php echo formatTurkishNumber($position['position_size'], 0); ?></small>
                                    </td>
                                    <td class="text-center py-4">
                                        <?php if ($position['trade_type'] === 'LONG'): ?>
                                        <span class="badge bg-success fs-6 px-3 py-2">
                                            <i class="fas fa-trending-up me-1"></i>LONG
                                        </span>
                                        <?php else: ?>
                                        <span class="badge bg-danger fs-6 px-3 py-2">
                                            <i class="fas fa-trending-down me-1"></i>SHORT
                                        </span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-end py-4 d-none d-xl-table-cell">
                                        <div class="fw-semibold"><?php echo formatPrice($position['entry_price']); ?></div>
                                        <small class="text-muted">USD</small>
                                    </td>
                                    <td class="text-end py-4 d-none d-lg-table-cell">
                                        <div class="fw-semibold text-danger"><?php echo formatPrice($position['liquidation_price']); ?></div>
                                        <small class="text-muted">Risk</small>
                                    </td>
                                    <td class="text-end py-4">
                                        <div class="fw-bold <?php echo $unrealized_pnl >= 0 ? 'performance-positive' : 'performance-negative'; ?>">
                                            <?php 
                                            echo ($unrealized_pnl >= 0 ? '+' : '') . formatTurkishNumber($unrealized_pnl, 2) . ' USD';
                                            ?>
                                        </div>
                                        <small class="<?php echo $unrealized_pnl >= 0 ? 'performance-positive' : 'performance-negative'; ?>">
                                            <?php 
                                            $pnl_percent = $position['collateral'] > 0 ? ($unrealized_pnl / $position['collateral']) * 100 : 0;
                                            echo ($pnl_percent >= 0 ? '+' : '') . formatTurkishNumber($pnl_percent, 1); 
                                            ?>%
                                        </small>
                                    </td>
                                    <td class="text-center py-4">
                                        <?php if ($position['status'] === 'OPEN'): ?>
                                            <?php if ($liquidation_risk): ?>
                                            <span class="badge bg-danger">
                                                <i class="fas fa-exclamation-triangle me-1"></i>RİSK
                                            </span>
                                            <?php else: ?>
                                            <span class="badge bg-success">
                                                <i class="fas fa-chart-line me-1"></i>AÇIK
                                            </span>
                                            <?php endif; ?>
                                        <?php elseif ($position['status'] === 'CLOSED'): ?>
                                        <span class="badge bg-secondary">
                                            <i class="fas fa-check me-1"></i>KAPALI
                                        </span>
                                        <?php else: ?>
                                        <span class="badge bg-danger">
                                            <i class="fas fa-skull me-1"></i>LİKİDE
                                        </span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-center py-4 pe-4">
                                        <?php if ($position['status'] === 'OPEN'): ?>
                                        <div class="action-buttons d-flex flex-column flex-md-row gap-1">
                                            <button type="button" class="btn btn-sm btn-outline-danger" 
                                                    onclick="closeLeveragePosition(<?php echo $position['id']; ?>, '<?php echo $position['symbol']; ?>', <?php echo $current_price; ?>)">
                                                <i class="fas fa-times me-1"></i><span class="d-none d-md-inline">Kapat</span>
                                            </button>
                                        </div>
                                        <?php else: ?>
                                        <small class="text-muted">-</small>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Portfolio Performance Chart -->
    <?php if (!empty($portfolio)): ?>
    <div class="row mt-4">
        <div class="col-12">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white">
                    <h5 class="mb-0">📈 Portföy Dağılımı</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <?php foreach ($portfolio as $holding): ?>
                        <?php 
                        $current_value = $holding['quantity'] * $holding['current_price'];
                        $percentage = $portfolio_stats['current_value'] > 0 ? ($current_value / $portfolio_stats['current_value']) * 100 : 0;
                        ?>
                        <div class="col-md-4 col-sm-6 mb-3">
                            <div class="d-flex align-items-center">
                                <div class="me-3">
                                    <?php if ($holding['logo_url']): ?>
                                    <img src="<?php echo $holding['logo_url']; ?>" 
                                         alt="<?php echo $holding['name']; ?>" 
                                         class="rounded-circle" 
                                         width="24" height="24">
                                    <?php else: ?>
                                    <div class="bg-primary rounded-circle d-flex align-items-center justify-content-center" 
                                         style="width: 24px; height: 24px;">
                                        <i class="fas fa-coins text-white" style="font-size: 10px;"></i>
                                    </div>
                                    <?php endif; ?>
                                </div>
                                <div class="flex-grow-1">
                                    <div class="d-flex justify-content-between">
                                        <span class="fw-bold"><?php echo $holding['symbol']; ?></span>
                                        <span class="text-muted"><?php echo formatTurkishNumber($percentage, 1); ?>%</span>
                                    </div>
                                    <div class="progress" style="height: 4px;">
                                        <div class="progress-bar" role="progressbar" 
                                             style="width: <?php echo $percentage; ?>%" 
                                             aria-valuenow="<?php echo $percentage; ?>" 
                                             aria-valuemin="0" aria-valuemax="100"></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Transaction History -->
    <div class="row mt-4">
        <div class="col-12">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white">
                    <h5 class="mb-0">📋 İşlem Geçmişi</h5>
                </div>
                <div class="card-body p-0">
                    <?php if (empty($recent_transactions)): ?>
                    <div class="text-center py-5">
                        <i class="fas fa-history fa-3x text-muted mb-3"></i>
                        <h5 class="text-muted">Henüz işlem geçmişi yok</h5>
                        <p class="text-muted">
                            İlk işleminizi yapmak için <a href="markets.php" class="text-decoration-none">piyasalar</a> sayfasını ziyaret edin.
                        </p>
                    </div>
                    <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="bg-light">
                                <tr>
                                    <th class="border-0 ps-4">Tarih</th>
                                    <th class="border-0">Varlık</th>
                                    <th class="border-0 text-center">İşlem</th>
                                    <th class="border-0 text-end">Miktar</th>
                                    <th class="border-0 text-end">Fiyat</th>
                                    <th class="border-0 text-end pe-4">Toplam</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recent_transactions as $transaction): ?>
                                <tr>
                                    <td class="ps-4 py-3">
                                        <div class="fw-bold"><?php echo date('d.m.Y', strtotime($transaction['created_at'])); ?></div>
                                        <small class="text-muted"><?php echo date('H:i', strtotime($transaction['created_at'])); ?></small>
                                    </td>
                                    <td class="py-3">
                                        <div class="d-flex align-items-center">
                                            <?php 
                                            // Get logo from markets table for this symbol
                                            $market_data = getSingleMarket($transaction['symbol']);
                                            $logo_url = $market_data['logo_url'] ?? '';
                                            ?>
                                            <?php if ($logo_url): ?>
                                            <img src="<?php echo $logo_url; ?>" 
                                                 alt="<?php echo $transaction['symbol']; ?>" 
                                                 class="me-2 rounded-circle" 
                                                 width="24" height="24"
                                                 onerror="this.outerHTML='<div class=&quot;bg-primary rounded-circle d-flex align-items-center justify-content-center me-2&quot; style=&quot;width: 24px; height: 24px;&quot;><i class=&quot;fas fa-coins text-white&quot; style=&quot;font-size: 10px;&quot;></i></div>';">
                                            <?php else: ?>
                                            <div class="bg-primary rounded-circle d-flex align-items-center justify-content-center me-2" 
                                                 style="width: 24px; height: 24px;">
                                                <i class="fas fa-coins text-white" style="font-size: 10px;"></i>
                                            </div>
                                            <?php endif; ?>
                                            <div>
                                                <div class="fw-bold"><?php echo $transaction['symbol']; ?></div>
                                                <small class="text-muted"><?php echo $transaction['market_name'] ?: $transaction['symbol']; ?></small>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="text-center py-3">
                                        <?php 
                                        $transaction_type = $transaction['type'];
                                        
                                        // DEBUG: Log transaction type to see what we're actually getting
                                        error_log("PORTFOLIO DEBUG: Transaction type = '" . $transaction_type . "' (NULL: " . (is_null($transaction_type) ? "yes" : "no") . ")");
                                        
                                        // Handle NULL/empty transaction types first
                                        if (empty($transaction_type) || is_null($transaction_type)) {
                                            $display_text = 'İŞLEM';
                                            $badge_class = 'bg-info';
                                            $icon = 'fas fa-exchange-alt';
                                        } else {
                                            // More accurate transaction type display that distinguishes leverage operations
                                            $display_text = '';
                                            $badge_class = 'bg-info';
                                            $icon = '';
                                            
                                            switch (strtoupper($transaction_type)) {
                                                case 'BUY':
                                                    $display_text = 'ALIM';
                                                    $badge_class = 'bg-success';
                                                    $icon = 'fas fa-arrow-up';
                                                    break;
                                                case 'SELL':
                                                    $display_text = 'SATIM';
                                                    $badge_class = 'bg-danger';
                                                    $icon = 'fas fa-arrow-down';
                                                    break;
                                                case 'LEVERAGE_LONG':
                                                    $display_text = 'LONG AÇMA';
                                                    $badge_class = 'bg-warning';
                                                    $icon = 'fas fa-bolt';
                                                    break;
                                                case 'LEVERAGE_SHORT':
                                                    $display_text = 'SHORT AÇMA';
                                                    $badge_class = 'bg-warning';
                                                    $icon = 'fas fa-bolt';
                                                    break;
                                                case 'CLOSE_LONG':
                                                    $display_text = 'LONG KAPAMA';
                                                    $badge_class = 'bg-secondary';
                                                    $icon = 'fas fa-times';
                                                    break;
                                                case 'CLOSE_SHORT':
                                                    $display_text = 'SHORT KAPAMA';
                                                    $badge_class = 'bg-secondary';
                                                    $icon = 'fas fa-times';
                                                    break;
                                                default:
                                                    // Handle numeric transaction types and other unrecognized formats
                                                    $transaction_type_lower = strtolower($transaction_type);
                                                    
                                                    // Check if it's a numeric type (legacy system might use numbers)
                                                    if (is_numeric($transaction_type)) {
                                                        // Numeric transaction types - try to map them logically
                                                        switch ($transaction_type) {
                                                            case '1':
                                                            case '2':
                                                                $display_text = 'ALIM';
                                                                $badge_class = 'bg-success';
                                                                $icon = 'fas fa-arrow-up';
                                                                break;
                                                            case '3':
                                                            case '4':
                                                                $display_text = 'SATIM';
                                                                $badge_class = 'bg-danger';
                                                                $icon = 'fas fa-arrow-down';
                                                                break;
                                                            case '5':
                                                            case '6':
                                                            case '7':
                                                            case '8':
                                                            case '9':
                                                                $display_text = 'KALDIRAÇ';
                                                                $badge_class = 'bg-warning';
                                                                $icon = 'fas fa-bolt';
                                                                break;
                                                            default:
                                                                $display_text = 'İŞLEM';
                                                                $badge_class = 'bg-info';
                                                                $icon = 'fas fa-exchange-alt';
                                                                break;
                                                        }
                                                    }
                                                    // Check if it contains leverage-related words
                                                    elseif (strpos($transaction_type_lower, 'leverage') !== false || 
                                                        strpos($transaction_type_lower, 'long') !== false || 
                                                        strpos($transaction_type_lower, 'short') !== false) {
                                                        $display_text = 'KALDIRAÇ';
                                                        $badge_class = 'bg-warning';
                                                        $icon = 'fas fa-bolt';
                                                    }
                                                    // Check if it contains buy/sell related words
                                                    elseif (strpos($transaction_type_lower, 'buy') !== false || 
                                                        strpos($transaction_type_lower, 'alim') !== false) {
                                                        $display_text = 'ALIM';
                                                        $badge_class = 'bg-success';
                                                        $icon = 'fas fa-arrow-up';
                                                    }
                                                    elseif (strpos($transaction_type_lower, 'sell') !== false || 
                                                        strpos($transaction_type_lower, 'satim') !== false) {
                                                        $display_text = 'SATIM';
                                                        $badge_class = 'bg-danger';
                                                        $icon = 'fas fa-arrow-down';
                                                    }
                                                    else {
                                                        // Show the actual type for debugging, but make it look clean
                                                        $display_text = 'İŞLEM (' . strtoupper($transaction_type) . ')';
                                                        $badge_class = 'bg-secondary';
                                                        $icon = 'fas fa-exchange-alt';
                                                    }
                                                    break;
                                            }
                                        }
                                        ?>
                                        <span class="badge <?php echo $badge_class; ?>" style="font-size: 0.75rem;">
                                            <i class="<?php echo $icon; ?> me-1"></i><?php echo $display_text; ?>
                                        </span>
                                    </td>
                                    <td class="text-end py-3">
                                        <div class="fw-bold"><?php echo formatTurkishNumber($transaction['amount'], 6); ?></div>
                                        <small class="text-muted">adet</small>
                                    </td>
                                    <td class="text-end py-3">
                                        <div><?php echo formatPrice($transaction['price']); ?></div>
                                        <small class="text-muted">USD</small>
                                    </td>
                                    <td class="text-end py-3 pe-4">
                                        <div class="fw-bold">
                                            <?php 
                                            // Transaction total is always in USD now
                                            if ($trading_currency == 1) {
                                                // Convert USD to TL for display
                                                $tl_total = $transaction['total'] * getUSDTRYRate();
                                                echo formatTurkishNumber($tl_total, 2) . ' TL';
                                            } else {
                                                // Show USD directly
                                                echo formatTurkishNumber($transaction['total'], 2) . ' USD';
                                            }
                                            ?>
                                        </div>
                                        <?php if ($transaction['fee'] > 0): ?>
                                        <small class="text-muted">Fee: <?php echo formatTurkishNumber($transaction['fee'], 2); ?></small>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    
                    <!-- Show All Transactions Link -->
                    <div class="card-footer bg-white text-center">
                        <a href="trading.php" class="btn btn-outline-primary btn-sm">
                            <i class="fas fa-list me-2"></i>Tüm İşlem Geçmişini Gör
                        </a>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Sell Modal -->
<div class="modal fade" id="sellModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Varlık Sat</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="">
                <input type="hidden" name="sell_from_portfolio" value="1">
                <input type="hidden" name="symbol" id="sellSymbol">
                
                <div class="modal-body">
                    <div class="text-center mb-3">
                        <div class="mb-2">
                            <img id="sellAssetLogo" src="" alt="" class="rounded-circle" width="48" height="48" style="display: block;">
                        </div>
                        <h6 id="sellAssetName">Apple Inc.</h6>
                        <p class="text-muted mb-0">Güncel Fiyat: $<span id="sellCurrentPrice">175.50</span></p>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Satış Miktarı</label>
                        <input type="number" class="form-control" name="sell_quantity" id="sellQuantity" 
                               step="any" min="0" max="" required 
                               oninput="calculateSellTotal()">
                        <small class="text-muted">
                            Mevcut: <span id="availableQuantity">0.000000</span> adet
                        </small>
                    </div>
                    
                    <div class="mb-3">
                        <div class="d-flex justify-content-between">
                            <button type="button" class="btn btn-outline-secondary btn-sm" onclick="setSellPercentage(25)">%25</button>
                            <button type="button" class="btn btn-outline-secondary btn-sm" onclick="setSellPercentage(50)">%50</button>
                            <button type="button" class="btn btn-outline-secondary btn-sm" onclick="setSellPercentage(75)">%75</button>
                            <button type="button" class="btn btn-outline-secondary btn-sm" onclick="setSellPercentage(100)">Tümü</button>
                        </div>
                    </div>
                    
                    <div class="card bg-light">
                        <div class="card-body p-3">
                            <div class="d-flex justify-content-between mb-2">
                                <span class="text-muted">Satış Tutarı:</span>
                                <span class="fw-bold" id="sellTotalUSD">$0.00</span>
                            </div>
                            <div class="d-flex justify-content-between mb-2" style="display: none !important;">
                                <span class="text-muted">İşlem Ücreti (0.1%):</span>
                                <span id="sellFee">$0.00</span>
                            </div>
                            <hr class="my-2">
                            <div class="d-flex justify-content-between">
                                <span class="text-muted">Alacağınız Tutar:</span>
                                <span class="fw-bold text-success" id="sellNetAmount">
                                    <?php echo $currency_symbol; ?> 0.00
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">İptal</button>
                    <button type="submit" class="btn btn-danger">
                        <i class="fas fa-hand-holding-usd me-2"></i>Sat
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
const TRADING_CURRENCY = <?php echo $trading_currency; ?>;
const CURRENCY_SYMBOL = '<?php echo $currency_symbol; ?>';
const USD_TRY_RATE = <?php echo getUSDTRYRate(); ?>;

let currentSellPrice = 0;
let maxQuantity = 0;

function showSellModal(symbol, name, quantity, price) {
    document.getElementById('sellSymbol').value = symbol;
    document.getElementById('sellAssetName').textContent = name;
    document.getElementById('sellCurrentPrice').textContent = formatTurkishNumber(price, 4);
    document.getElementById('availableQuantity').textContent = formatTurkishNumber(quantity, 6);
    document.getElementById('sellQuantity').max = quantity;
    
    currentSellPrice = price;
    maxQuantity = quantity;
    
    // Set logo for modal - SADECE ORJİNAL LOGO
    const logoImg = document.getElementById('sellAssetLogo');
    
    // Portfolio tablosundan logo URL'ini al
    const portfolioRows = document.querySelectorAll('table tbody tr');
    let logoUrl = '';
    
    portfolioRows.forEach(row => {
        const symbolCell = row.querySelector('.fw-bold');
        if (symbolCell && symbolCell.textContent.trim() === symbol) {
            const logoElement = row.querySelector('img');
            if (logoElement && logoElement.src && !logoElement.src.includes('outerHTML')) {
                logoUrl = logoElement.src;
            }
        }
    });
    
    // Sadece orjinal logo varsa göster
    if (logoUrl && logoUrl !== '') {
        logoImg.src = logoUrl;
        logoImg.alt = name;
        logoImg.style.display = 'block';
        
        // Logo yüklenemezse gizle (fallback yok)
        logoImg.onerror = function() {
            logoImg.style.display = 'none';
        };
    } else {
        // Logo bulunamazsa gizle
        logoImg.style.display = 'none';
    }
    
    // Reset form
    document.getElementById('sellQuantity').value = '';
    calculateSellTotal();
    
    const modal = new bootstrap.Modal(document.getElementById('sellModal'));
    modal.show();
}

function setSellPercentage(percentage) {
    if (percentage === 100) {
        // "Tümü" butonunda tam değeri kullan (precision safe)
        document.getElementById('sellQuantity').value = maxQuantity.toString();
    } else {
        // Diğer yüzdelerde normal rounded değer
        const quantity = maxQuantity * (percentage / 100);
        document.getElementById('sellQuantity').value = quantity.toFixed(6);
    }
    calculateSellTotal();
}

function calculateSellTotal() {
    const quantity = parseFloat(document.getElementById('sellQuantity').value) || 0;
    
    if (quantity <= 0) {
        document.getElementById('sellTotalUSD').textContent = '$0.00';
        document.getElementById('sellFee').textContent = '$0.00';
        document.getElementById('sellNetAmount').textContent = CURRENCY_SYMBOL + ' 0.00';
        return;
    }
    
    const totalUSD = quantity * currentSellPrice;
    const feeUSD = 0; // No fee
    const netUSD = totalUSD - feeUSD;
    
    document.getElementById('sellTotalUSD').textContent = '$' + formatTurkishNumber(totalUSD, 2);
    document.getElementById('sellFee').textContent = '$' + formatTurkishNumber(feeUSD, 2);
    
    if (TRADING_CURRENCY === 1) { // TL mode
        const netTL = netUSD * USD_TRY_RATE;
        document.getElementById('sellNetAmount').textContent = formatTurkishNumber(netTL, 2) + ' TL';
    } else { // USD mode
        document.getElementById('sellNetAmount').textContent = '$' + formatTurkishNumber(netUSD, 2);
    }
}

function formatTurkishNumber(number, decimals = 2) {
    return number.toLocaleString('tr-TR', {
        minimumFractionDigits: decimals,
        maximumFractionDigits: decimals
    });
}

// Close leverage position function
function closeLeveragePosition(positionId, symbol, currentPrice) {
    if (!confirm('Bu kaldıraç pozisyonunu kapatmak istediğinizden emin misiniz?\n\nGüncel fiyat: $' + formatTurkishNumber(currentPrice, 4) + '\nSymbol: ' + symbol)) {
        return;
    }
    
    // Show loading state
    const button = event.target.closest('button');
    const originalContent = button.innerHTML;
    button.disabled = true;
    button.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>Kapatılıyor...';
    
    // Create form and submit
    const form = document.createElement('form');
    form.method = 'POST';
    form.action = 'close_leverage_position.php';
    
    const positionIdInput = document.createElement('input');
    positionIdInput.type = 'hidden';
    positionIdInput.name = 'position_id';
    positionIdInput.value = positionId;
    
    const currentPriceInput = document.createElement('input');
    currentPriceInput.type = 'hidden';
    currentPriceInput.name = 'close_price';
    currentPriceInput.value = currentPrice;
    
    form.appendChild(positionIdInput);
    form.appendChild(currentPriceInput);
    document.body.appendChild(form);
    
    form.submit();
}
</script>

<?php include 'includes/footer.php'; ?>

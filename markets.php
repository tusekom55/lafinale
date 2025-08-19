<?php
require_once 'includes/functions.php';

$page_title = 'GlobalBorsa - Financial Markets';

// Get current category
$category = $_GET['group'] ?? 'us_stocks';
$valid_categories = array_keys(getFinancialCategories());
if (!in_array($category, $valid_categories)) {
    $category = 'us_stocks';
}

// Get market data
$markets = getMarketData($category, 50);

// Get trading currency settings for modals
$trading_currency = getTradingCurrency();
$currency_field = getCurrencyField($trading_currency);
$currency_symbol = getCurrencySymbol($trading_currency);
$usd_try_rate = getUSDTRYRate();

// Get user balances if logged in
$user_balances = [];
if (isLoggedIn()) {
    $user_id = $_SESSION['user_id'];
    $user_balances = [
        'primary' => getUserBalance($user_id, $currency_field),
        'tl' => getUserBalance($user_id, 'tl'),
        'usd' => getUserBalance($user_id, 'usd'),
        'btc' => getUserBalance($user_id, 'btc'),
        'eth' => getUserBalance($user_id, 'eth')
    ];
}

// Simple Trading Form Handler - SADECE SPOT TRADING
if ($_POST && isset($_POST['trade_action']) && isLoggedIn()) {
    $trade_action = $_POST['trade_action']; // 'buy', 'sell'
    $symbol = $_POST['symbol'] ?? '';
    $usd_amount = (float)($_POST['usd_amount'] ?? 0);
    
    // Get market data for this symbol
    $current_market = null;
    foreach ($markets as $market) {
        if ($market['symbol'] === $symbol) {
            $current_market = $market;
            break;
        }
    }
    
    if ($current_market && $usd_amount > 0) {
        $usd_price = (float)$current_market['price'];
        
        // Execute simple trade (existing logic)
        $trade_result = executeSimpleTrade($_SESSION['user_id'], $symbol, $trade_action, $usd_amount, $usd_price);
        
        if ($trade_result) {
            unset($_SESSION['trade_error']);
            
            $action_text = $trade_action == 'buy' ? 'ALINDI' : 'SATILDI';
            $detailed_message = "$usd_amount USD $symbol $action_text";
            
            $_SESSION['trade_success'] = $detailed_message;
            header('Location: markets.php?group=' . $category);
            exit();
        } else {
            unset($_SESSION['trade_success']);
            $_SESSION['trade_error'] = 'İşlem başarısız oldu. Lütfen bakiyenizi kontrol edin.';
            header('Location: markets.php?group=' . $category);
            exit();
        }
    } else {
        $_SESSION['trade_error'] = getCurrentLang() == 'tr' ? 'Geçersiz işlem parametreleri.' : 'Invalid trade parameters.';
        header('Location: markets.php?group=' . $category);
        exit();
    }
}

// Update market data if it's been more than 10 minutes (to save API quota)
$database = new Database();
$db = $database->getConnection();

$query = "SELECT updated_at FROM markets WHERE category = ? ORDER BY updated_at DESC LIMIT 1";
$stmt = $db->prepare($query);
$stmt->execute([$category]);
$last_update = $stmt->fetchColumn();

// OTOMATIK GÜNCELLEME KAPATILDI - Sadece manuel güncelleme
// Auto update disabled - Manual update only via admin panel

// Search functionality
$search = $_GET['search'] ?? '';
if ($search) {
    $markets = array_filter($markets, function($market) use ($search) {
        return stripos($market['name'], $search) !== false || 
               stripos($market['symbol'], $search) !== false;
    });
}

include 'includes/header.php';

// Check for session messages
$success_message = '';
$error_message = '';

if (isset($_SESSION['trade_success'])) {
    $success_message = $_SESSION['trade_success'];
    unset($_SESSION['trade_success']);
}

if (isset($_SESSION['trade_error'])) {
    $error_message = $_SESSION['trade_error'];
    unset($_SESSION['trade_error']);
}

// Check for leverage messages
if (isset($_SESSION['leverage_success'])) {
    $success_message = $_SESSION['leverage_success'];
    unset($_SESSION['leverage_success']);
}

if (isset($_SESSION['leverage_error'])) {
    $error_message = $_SESSION['leverage_error'];
    unset($_SESSION['leverage_error']);
}

// AGGRESSIVE SESSION CLEANING - Tüm muhtemel error mesajlarını temizle
$all_message_keys = [
    'error', 'success', 'message', 'alert', 'notification', 'trade_message', 'status_message',
    'trade_error', 'trade_success', 'balance_error', 'insufficient_balance', 'transaction_error',
    'system_error', 'warning', 'info', 'flash_message', 'user_message', 'temp_message',
    'modal_error', 'form_error', 'validation_error', 'payment_error', 'wallet_error'
];

foreach($all_message_keys as $key) {
    if (isset($_SESSION[$key])) {
        unset($_SESSION[$key]);
    }
}

// Clear any session key that contains 'error', 'message', or 'alert'
foreach($_SESSION as $session_key => $session_value) {
    if (strpos(strtolower($session_key), 'error') !== false || 
        strpos(strtolower($session_key), 'message') !== false || 
        strpos(strtolower($session_key), 'alert') !== false) {
        unset($_SESSION[$session_key]);
    }
}
?>

<div class="container">
    
    <!-- Page Header -->
    <div class="row mb-4">
        <div class="col-md-8">
            <h1 class="h3 mb-0"><?php echo getFinancialCategories()[$category] ?? 'Financial Markets'; ?></h1>
            <p class="text-muted">
                <?php echo getCurrentLang() == 'tr' ? 'Canlı finansal piyasa verileri' : 'Live financial market data'; ?>
            </p>
        </div>
        <div class="col-md-4">
            <div class="input-group">
                <span class="input-group-text"><i class="fas fa-search"></i></span>
                <input type="text" class="form-control" placeholder="<?php echo getCurrentLang() == 'tr' ? 'Enstrüman ara...' : 'Search instruments...'; ?>" 
                       value="<?php echo htmlspecialchars($search); ?>" id="marketSearch">
            </div>
        </div>
    </div>
    
    <!-- Desktop: Financial Categories Grid -->
    <div class="row mb-4 desktop-categories">
        <div class="col-12">
            <h5 class="mb-3 text-secondary">
                <i class="fas fa-layer-group me-2"></i>Piyasa Kategorileri
            </h5>
            <div class="row g-3">
                <?php 
                $categories = getFinancialCategories();
                $icons = getCategoryIcons();
                $descriptions = getCategoryDescriptions();
                foreach ($categories as $cat_key => $cat_name): 
                ?>
                <div class="col-lg-3 col-md-4 col-sm-6">
                    <a href="?group=<?php echo $cat_key; ?>" class="text-decoration-none">
                        <div class="category-card card h-100 border-0 shadow-sm <?php echo $category == $cat_key ? 'category-active' : ''; ?>">
                            <div class="card-body p-3 text-center">
                                <div class="category-icon mb-2">
                                    <i class="<?php echo $icons[$cat_key] ?? 'fas fa-chart-line'; ?> fa-2x"></i>
                                </div>
                                <h6 class="card-title mb-2 fw-bold"><?php echo $cat_name; ?></h6>
                                <p class="card-text text-muted small mb-0">
                                    <?php echo $descriptions[$cat_key] ?? ''; ?>
                                </p>
                                <?php if ($category == $cat_key): ?>
                                <div class="mt-2">
                                    <span class="badge bg-primary">
                                        <i class="fas fa-check me-1"></i>Aktif
                                    </span>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </a>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <!-- Mobile: Horizontal Scroll Category Tabs -->
    <div class="mobile-category-tabs sticky-top" style="display: none;">
        <div class="category-tabs-container">
            <?php foreach ($categories as $cat_key => $cat_name): ?>
            <a href="?group=<?php echo $cat_key; ?>" class="category-tab <?php echo $category == $cat_key ? 'active' : ''; ?>">
                <i class="<?php echo $icons[$cat_key] ?? 'fas fa-chart-line'; ?>"></i>
                <span><?php echo $cat_name; ?></span>
            </a>
            <?php endforeach; ?>
        </div>
    </div>
    
    <!-- Market Table -->
    <div class="card border-0 shadow-sm">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover market-table mb-0">
                    <thead class="bg-light">
                        <tr>
                            <th class="border-0 ps-4"><?php echo t('market_name'); ?></th>
                            <th class="border-0 text-end"><?php echo t('last_price'); ?></th>
                            <th class="border-0 text-end"><?php echo t('change'); ?></th>
                            <th class="border-0 text-end"><?php echo t('low_24h'); ?></th>
                            <th class="border-0 text-end"><?php echo t('high_24h'); ?></th>
                            <th class="border-0 text-end"><?php echo t('volume_24h'); ?></th>
                            <th class="border-0 text-center pe-4">İşlem</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($markets)): ?>
                        <tr>
                            <td colspan="7" class="text-center py-5">
                                <i class="fas fa-chart-line fa-3x text-muted mb-3"></i>
                                <p class="text-muted">
                                    <?php echo getCurrentLang() == 'tr' ? 'Henüz piyasa verisi yok' : 'No market data available'; ?>
                                </p>
                            </td>
                        </tr>
                        <?php else: ?>
                        <?php foreach ($markets as $market): ?>
                        <tr class="market-row" data-symbol="<?php echo $market['symbol']; ?>">
                            <td class="ps-4 py-3">
                                <div class="d-flex align-items-center">
                                    <?php if ($market['logo_url']): ?>
                                    <img src="<?php echo $market['logo_url']; ?>" 
                                         alt="<?php echo $market['name']; ?>" 
                                         class="me-3 rounded-circle" 
                                         width="32" height="32"
                                         onerror="this.outerHTML='<div class=&quot;bg-primary rounded-circle d-flex align-items-center justify-content-center me-3&quot; style=&quot;width: 32px; height: 32px;&quot;><i class=&quot;fas fa-coins text-white&quot;></i></div>';">
                                    <?php else: ?>
                                    <div class="bg-primary rounded-circle d-flex align-items-center justify-content-center me-3" 
                                         style="width: 32px; height: 32px;">
                                        <i class="fas fa-coins text-white"></i>
                                    </div>
                                    <?php endif; ?>
                                    <div>
                                        <div class="fw-bold"><?php echo $market['symbol']; ?></div>
                                        <small class="text-muted"><?php echo $market['name']; ?></small>
                                    </div>
                                </div>
                            </td>
                            <td class="text-end py-3">
                                <div class="fw-bold price-cell" data-price="<?php echo $market['price']; ?>">
                                    <?php echo formatPrice($market['price']); ?>
                                    <small class="text-muted ms-1">
                                        <?php echo $category == 'crypto_tl' ? 'TL' : ($category == 'crypto_usd' ? 'USDT' : 'USD'); ?>
                                    </small>
                                </div>
                            </td>
                            <td class="text-end py-3">
                                <?php echo formatChange($market['change_24h']); ?>
                            </td>
                            <td class="text-end py-3">
                                <span class="text-muted"><?php echo formatPrice($market['low_24h']); ?></span>
                                <small class="text-muted ms-1">
                                    <?php echo $category == 'crypto_tl' ? 'TL' : ($category == 'crypto_usd' ? 'USDT' : 'USD'); ?>
                                </small>
                            </td>
                            <td class="text-end py-3">
                                <span class="text-muted"><?php echo formatPrice($market['high_24h']); ?></span>
                                <small class="text-muted ms-1">
                                    <?php echo $category == 'crypto_tl' ? 'TL' : ($category == 'crypto_usd' ? 'USDT' : 'USD'); ?>
                                </small>
                            </td>
                            <td class="text-end py-3">
                                <span class="text-muted"><?php echo formatVolume($market['volume_24h']); ?></span>
                                <small class="text-muted ms-1">
                                    <?php 
                                    $symbol_parts = explode('_', $market['symbol']);
                                    echo $symbol_parts[0];
                                    ?>
                                </small>
                            </td>
                            <td class="text-center py-3 pe-4">
                                <div class="btn-group" role="group">
                                    <button type="button" class="btn btn-success btn-sm trade-btn" 
                                            data-symbol="<?php echo $market['symbol']; ?>" 
                                            data-name="<?php echo $market['name']; ?>" 
                                            data-price="<?php echo $market['price']; ?>" 
                                            data-action="buy"
                                            onclick="event.stopPropagation(); openTradeModal(this);">
                                        <i class="fas fa-shopping-cart me-1"></i>AL
                                    </button>
                                    <button type="button" class="btn btn-danger btn-sm trade-btn" 
                                            data-symbol="<?php echo $market['symbol']; ?>" 
                                            data-name="<?php echo $market['name']; ?>" 
                                            data-price="<?php echo $market['price']; ?>" 
                                            data-action="sell"
                                            onclick="event.stopPropagation(); openSellModal(this);">
                                        <i class="fas fa-hand-holding-usd me-1"></i>SAT
                                    </button>
                                    <button type="button" class="btn btn-warning btn-sm trade-btn" 
                                            data-symbol="<?php echo $market['symbol']; ?>" 
                                            data-name="<?php echo $market['name']; ?>" 
                                            data-price="<?php echo $market['price']; ?>" 
                                            data-action="leverage"
                                            onclick="event.stopPropagation(); openLeverageModal(this);">
                                        <i class="fas fa-bolt me-1"></i>KALDIRAÇ
                                    </button>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    
    <!-- Mobile Market Cards (Hidden on Desktop) -->
    <div class="mobile-market-cards" style="display: none;">
        <?php if (empty($markets)): ?>
        <div class="text-center py-5">
            <i class="fas fa-chart-line fa-3x text-muted mb-3"></i>
            <p class="text-muted">
                <?php echo getCurrentLang() == 'tr' ? 'Henüz piyasa verisi yok' : 'No market data available'; ?>
            </p>
        </div>
        <?php else: ?>
        <?php foreach ($markets as $market): ?>
        <div class="mobile-market-card" data-symbol="<?php echo $market['symbol']; ?>">
            <!-- Market Header -->
            <div class="mobile-market-header">
                <?php if ($market['logo_url']): ?>
                <img src="<?php echo $market['logo_url']; ?>" 
                     alt="<?php echo $market['name']; ?>" 
                     class="mobile-market-logo"
                     onerror="this.outerHTML='<div class=&quot;mobile-market-logo bg-primary d-flex align-items-center justify-content-center&quot;><i class=&quot;fas fa-coins text-white&quot;></i></div>';">
                <?php else: ?>
                <div class="mobile-market-logo bg-primary d-flex align-items-center justify-content-center">
                    <i class="fas fa-coins text-white"></i>
                </div>
                <?php endif; ?>
                
                <div class="mobile-market-info">
                    <h6><?php echo $market['symbol']; ?></h6>
                    <small><?php echo $market['name']; ?></small>
                </div>
                
                <div class="mobile-market-price">
                    <div class="price" data-price="<?php echo $market['price']; ?>">
                        <?php echo formatPrice($market['price']); ?>
                        <small class="text-muted">
                            <?php echo $category == 'crypto_tl' ? 'TL' : ($category == 'crypto_usd' ? 'USDT' : 'USD'); ?>
                        </small>
                    </div>
                    <div class="change">
                        <?php echo formatChange($market['change_24h']); ?>
                    </div>
                </div>
            </div>
            
            <!-- Market Stats Grid -->
            <div class="mobile-market-stats">
                <div class="mobile-stat">
                    <div class="mobile-stat-label">Düşük</div>
                    <div class="mobile-stat-value"><?php echo formatPrice($market['low_24h']); ?></div>
                </div>
                <div class="mobile-stat">
                    <div class="mobile-stat-label">Yüksek</div>
                    <div class="mobile-stat-value"><?php echo formatPrice($market['high_24h']); ?></div>
                </div>
                <div class="mobile-stat">
                    <div class="mobile-stat-label">Hacim</div>
                    <div class="mobile-stat-value"><?php echo formatVolume($market['volume_24h']); ?></div>
                </div>
                <div class="mobile-stat">
                    <div class="mobile-stat-label">Piyasa Değeri</div>
                    <div class="mobile-stat-value"><?php echo formatVolume($market['market_cap']); ?></div>
                </div>
            </div>
            
            <!-- Mobile Trading Buttons -->
            <div class="mobile-trade-buttons">
                <button type="button" class="btn btn-success trade-btn" 
                        data-symbol="<?php echo $market['symbol']; ?>" 
                        data-name="<?php echo $market['name']; ?>" 
                        data-price="<?php echo $market['price']; ?>" 
                        data-action="buy"
                        onclick="openTradeModal(this);">
                    <i class="fas fa-shopping-cart me-1"></i>AL
                </button>
                <button type="button" class="btn btn-danger trade-btn" 
                        data-symbol="<?php echo $market['symbol']; ?>" 
                        data-name="<?php echo $market['name']; ?>" 
                        data-price="<?php echo $market['price']; ?>" 
                        data-action="sell"
                        onclick="openTradeModal(this);">
                    <i class="fas fa-hand-holding-usd me-1"></i>SAT
                </button>
                <button type="button" class="btn btn-warning trade-btn" 
                        data-symbol="<?php echo $market['symbol']; ?>" 
                        data-name="<?php echo $market['name']; ?>" 
                        data-price="<?php echo $market['price']; ?>" 
                        data-action="leverage"
                        onclick="openLeverageModal(this);">
                    <i class="fas fa-bolt me-1"></i>KALDIRAÇ
                </button>
            </div>
        </div>
        <?php endforeach; ?>
        <?php endif; ?>
    </div>
    
    <!-- Market Stats -->
    <div class="row mt-4 market-stats-mobile">
        <div class="col-md-3">
            <div class="card border-0 bg-light">
                <div class="card-body text-center">
                    <h5 class="text-success mb-1"><?php echo count($markets); ?></h5>
                    <small class="text-muted">
                        <?php echo getCurrentLang() == 'tr' ? 'Toplam Piyasa' : 'Total Markets'; ?>
                    </small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 bg-light">
                <div class="card-body text-center">
                    <h5 class="text-primary mb-1">
                        <?php 
                        $gainers = array_filter($markets, function($m) { return $m['change_24h'] > 0; });
                        echo count($gainers);
                        ?>
                    </h5>
                    <small class="text-muted">
                        <?php echo getCurrentLang() == 'tr' ? 'Yükselenler' : 'Gainers'; ?>
                    </small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 bg-light">
                <div class="card-body text-center">
                    <h5 class="text-danger mb-1">
                        <?php 
                        $losers = array_filter($markets, function($m) { return $m['change_24h'] < 0; });
                        echo count($losers);
                        ?>
                    </h5>
                    <small class="text-muted">
                        <?php echo getCurrentLang() == 'tr' ? 'Düşenler' : 'Losers'; ?>
                    </small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 bg-light">
                <div class="card-body text-center">
                    <h5 class="text-info mb-1">
                        <?php 
                        $total_volume = array_sum(array_column($markets, 'volume_24h'));
                        echo formatVolume($total_volume);
                        ?>
                    </h5>
                    <small class="text-muted">
                        <?php echo getCurrentLang() == 'tr' ? '24S Hacim' : '24h Volume'; ?>
                    </small>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Success Popup CSS -->
<style>
.success-popup {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    z-index: 999999;
    opacity: 0;
    visibility: hidden;
    transition: all 0.3s ease;
}

.success-popup.show {
    opacity: 1;
    visibility: visible;
}

.success-popup.closing {
    opacity: 0;
    transform: scale(0.9);
}

.success-overlay {
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0, 0, 0, 0.5);
    backdrop-filter: blur(4px);
}

.success-content {
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    background: white;
    border-radius: 16px;
    padding: 2rem;
    text-align: center;
    box-shadow: 0 20px 40px rgba(0, 0, 0, 0.15);
    max-width: 400px;
    width: 90%;
    animation: successSlideIn 0.3s ease-out;
}

@keyframes successSlideIn {
    from {
        transform: translate(-50%, -60%);
        opacity: 0;
    }
    to {
        transform: translate(-50%, -50%);
        opacity: 1;
    }
}

.success-icon {
    margin-bottom: 1rem;
}

.success-icon i {
    font-size: 4rem;
    color: #28a745;
    animation: successPulse 0.6s ease-out;
}

@keyframes successPulse {
    0% {
        transform: scale(0);
    }
    50% {
        transform: scale(1.1);
    }
    100% {
        transform: scale(1);
    }
}

.success-content h3 {
    color: #28a745;
    margin-bottom: 0.5rem;
    font-weight: 600;
}

/* Mobile optimizations */
@media (max-width: 768px) {
    /* Hide desktop categories, show mobile tabs */
    .desktop-categories {
        display: none !important;
    }
    
    .mobile-category-tabs {
        display: block !important;
        background: white;
        z-index: 10001 !important;
        padding: 1rem 0;
        border-bottom: 1px solid #e9ecef;
        margin-bottom: 1rem;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
    }
    
    .category-tabs-container {
        display: flex;
        overflow-x: auto;
        scroll-behavior: smooth;
        -webkit-overflow-scrolling: touch;
        gap: 0.5rem;
        padding: 0 1rem 0.5rem 1rem;
        scrollbar-width: thin;
        scrollbar-color: #dee2e6 transparent;
    }
    
    .category-tab {
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        min-width: 70px;
        height: 70px;
        padding: 0.5rem 0.25rem;
        border-radius: 8px;
        text-decoration: none;
        color: #6c757d;
        background: white;
        border: 1px solid #e9ecef;
        text-align: center;
        font-size: 0.7rem;
        font-weight: 500;
        transition: all 0.15s ease;
        flex-shrink: 0;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
    }
    
    .category-tab.active {
        color: white;
        background: #007bff;
        border-color: #007bff;
        box-shadow: 0 2px 6px rgba(0, 123, 255, 0.4);
    }
    
    /* Hide desktop table, show mobile cards */
    .market-table {
        display: none !important;
    }
    
    .mobile-market-cards {
        display: block !important;
    }
    
    .mobile-market-card {
        background: white;
        border-radius: 12px;
        padding: 1rem;
        margin-bottom: 0.75rem;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        border: 1px solid #e9ecef;
    }
    
    .mobile-market-header {
        display: flex;
        align-items: center;
        margin-bottom: 0.75rem;
    }
    
    .mobile-market-logo {
        width: 40px;
        height: 40px;
        border-radius: 50%;
        margin-right: 0.75rem;
        flex-shrink: 0;
    }
    
    .mobile-market-stats {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 0.5rem;
        margin-bottom: 0.75rem;
        padding: 0.75rem;
        background: #f8f9fa;
        border-radius: 8px;
    }
    
    .mobile-trade-buttons {
        display: flex;
        gap: 0.25rem;
        justify-content: center;
    }
    
    .mobile-trade-buttons .btn {
        flex: 1;
        font-size: 0.75rem;
        padding: 0.5rem 0.25rem;
        min-height: 44px;
    }
}

/* Desktop-only styles */
@media (min-width: 769px) {
    .mobile-market-cards {
        display: none !important;
    }
}

/* Modal CSS moved to external file: assets/css/modal-overrides.css */
</style>

<script>
// Parametric system constants
const TRADING_CURRENCY = <?php echo $trading_currency; ?>; // 1=TL, 2=USD
const CURRENCY_SYMBOL = '<?php echo $currency_symbol; ?>';
const USD_TRY_RATE = <?php echo $usd_try_rate; ?>;

// Enhanced Search functionality for both desktop and mobile
document.getElementById('marketSearch').addEventListener('input', function(e) {
    const searchTerm = e.target.value.toLowerCase();
    
    // Search desktop table rows
    const rows = document.querySelectorAll('.market-row');
    rows.forEach(row => {
        const symbol = row.querySelector('.fw-bold').textContent.toLowerCase();
        const name = row.querySelector('.text-muted').textContent.toLowerCase();
        
        if (symbol.includes(searchTerm) || name.includes(searchTerm)) {
            row.style.display = '';
        } else {
            row.style.display = 'none';
        }
    });
    
    // Search mobile cards
    const mobileCards = document.querySelectorAll('.mobile-market-card');
    mobileCards.forEach(card => {
        const symbol = card.querySelector('.mobile-market-info h6').textContent.toLowerCase();
        const name = card.querySelector('.mobile-market-info small').textContent.toLowerCase();
        
        if (symbol.includes(searchTerm) || name.includes(searchTerm)) {
            card.style.display = '';
        } else {
            card.style.display = 'none';
        }
    });
});

// Turkish number formatting function
function formatTurkishNumber(number, decimals = 2) {
    return new Intl.NumberFormat('tr-TR', {
        minimumFractionDigits: decimals,
        maximumFractionDigits: decimals
    }).format(number);
}

// SAT butonu özel fonksiyonu - Sahiplik kontrolü ile
function openSellModal(button) {
    const symbol = button.dataset.symbol;
    const name = button.dataset.name;
    const price = parseFloat(button.dataset.price);
    
    <?php if (isLoggedIn()): ?>
    // Kullanıcı giriş yapmış - sahiplik kontrolü yap
    fetch('api/get_portfolio_holding.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            symbol: symbol
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success && data.holding) {
            // Sahip - portföye yönlendir
            window.location.href = `portfolio.php?symbol=${symbol}`;
        } else {
            // Sahip değil - uyarı göster
            showNotOwnerAlert(symbol, name);
        }
    })
    .catch(error => {
        console.error('Error checking portfolio holding:', error);
        showNotOwnerAlert(symbol, name);
    });
    <?php else: ?>
    // Giriş yapmamış - login sayfasına yönlendir
    window.location.href = 'login.php';
    <?php endif; ?>
}

// Trading modal functions - SADECE BASIT MODAL
function openTradeModal(button) {
    const symbol = button.dataset.symbol;
    const name = button.dataset.name;
    const price = parseFloat(button.dataset.price);
    const action = button.dataset.action;
    
    // Update modal content
    document.getElementById('modalSymbol').textContent = symbol;
    document.getElementById('modalName').textContent = name;
    document.getElementById('modalPrice').textContent = formatPrice(price);
    
    // Set hidden fields for forms
    document.getElementById('buySymbol').value = symbol;
    
    // Show modal
    const modal = new bootstrap.Modal(document.getElementById('tradeModal'));
    modal.show();
}

// KALDIRAÇ butonu - Gerçek leverage modal
function openLeverageModal(button) {
    const symbol = button.dataset.symbol;
    const name = button.dataset.name;
    const price = parseFloat(button.dataset.price);
    
    // Update leverage modal content
    document.getElementById('leverageModalSymbol').textContent = symbol;
    document.getElementById('leverageModalName').textContent = name;
    document.getElementById('leverageModalPrice').textContent = formatPrice(price);
    
    // Set hidden fields for forms
    document.getElementById('leverageSymbol').value = symbol;
    document.getElementById('leverageEntryPrice').value = price;
    
    // Reset form
    document.getElementById('collateral_amount').value = '';
    document.getElementById('leverage_ratio').value = '1';
    
    // Calculate initial values
    calculateLeveragePosition();
    
    // Show modal
    const modal = new bootstrap.Modal(document.getElementById('leverageModal'));
    modal.show();
}

// Kaldıraç pozisyon hesaplamaları
function calculateLeveragePosition() {
    const collateral = parseFloat(document.getElementById('collateral_amount').value) || 0;
    const leverage = parseFloat(document.getElementById('leverage_ratio').value) || 1;
    const entryPrice = parseFloat(document.getElementById('leverageEntryPrice').value);
    
    // Reset displays if no collateral
    if (collateral <= 0) {
        document.getElementById('positionSize').textContent = '$0.00';
        document.getElementById('tradingFee').textContent = '$0.00';
        document.getElementById('liquidationPriceLong').textContent = '$0.00';
        document.getElementById('liquidationPriceShort').textContent = '$0.00';
        document.getElementById('unrealizedPnl').textContent = '$0.00';
        document.getElementById('requiredCollateral').textContent = '$0.00';
        document.getElementById('leverageBalance').textContent = '$0.00';
        
        // Reset buttons
        const longBtn = document.querySelector('#leverageForm button[name="trade_type"][value="long"]');
        const shortBtn = document.querySelector('#leverageForm button[name="trade_type"][value="short"]');
        longBtn.disabled = false;
        shortBtn.disabled = false;
        longBtn.className = 'btn btn-success flex-fill';
        shortBtn.className = 'btn btn-danger flex-fill';
        longBtn.innerHTML = '<i class="fas fa-trending-up me-2"></i>AL (LONG)';
        shortBtn.innerHTML = '<i class="fas fa-trending-down me-2"></i>SAT (SHORT)';
        return;
    }
    
    // Calculate position size
    const positionSize = collateral * leverage;
    
    // Calculate trading fee (0.1% of position size)
    const tradingFee = positionSize * 0.001;
    
    // Calculate liquidation prices
    const liquidationLong = entryPrice * (1 - (1 / leverage));
    const liquidationShort = entryPrice * (1 + (1 / leverage));
    
    // Total required amount (collateral + fee)
    const totalRequired = collateral + tradingFee;
    
    // Get current balance
    let currentBalance;
    if (TRADING_CURRENCY === 1) { // TL Mode
        currentBalance = <?php echo isLoggedIn() ? getUserBalance($_SESSION['user_id'], 'usd') : 1000; ?>;
        const remainingBalance = currentBalance - totalRequired;
        
        document.getElementById('leverageBalance').textContent = formatTurkishNumber(remainingBalance, 2) + ' USD';
    } else { // USD Mode
        currentBalance = <?php echo isLoggedIn() ? getUserBalance($_SESSION['user_id'], 'usd') : 1000; ?>;
        const remainingBalance = currentBalance - totalRequired;
        
        document.getElementById('leverageBalance').textContent = formatTurkishNumber(remainingBalance, 2) + ' USD';
    }
    
    // Update displays
    document.getElementById('positionSize').textContent = '$' + formatTurkishNumber(positionSize, 2);
    document.getElementById('tradingFee').textContent = '$' + formatTurkishNumber(tradingFee, 2);
    document.getElementById('liquidationPriceLong').textContent = '$' + formatTurkishNumber(liquidationLong, 4);
    document.getElementById('liquidationPriceShort').textContent = '$' + formatTurkishNumber(liquidationShort, 4);
    document.getElementById('unrealizedPnl').textContent = '$0.00'; // Initially zero
    document.getElementById('requiredCollateral').textContent = '$' + formatTurkishNumber(totalRequired, 2);
    
    // SMART BUTTON CONTROL - Balance check
    const longBtn = document.querySelector('#leverageForm button[name="trade_type"][value="long"]');
    const shortBtn = document.querySelector('#leverageForm button[name="trade_type"][value="short"]');
    
    if (totalRequired > currentBalance) {
        // Insufficient balance - Red buttons
        longBtn.disabled = true;
        shortBtn.disabled = true;
        longBtn.className = 'btn btn-danger flex-fill';
        shortBtn.className = 'btn btn-danger flex-fill';
        longBtn.innerHTML = '<i class="fas fa-exclamation-triangle me-2"></i>YETERSİZ BAKİYE';
        shortBtn.innerHTML = '<i class="fas fa-exclamation-triangle me-2"></i>YETERSİZ BAKİYE';
    } else {
        // Sufficient balance - Normal buttons
        longBtn.disabled = false;
        shortBtn.disabled = false;
        longBtn.className = 'btn btn-success flex-fill';
        shortBtn.className = 'btn btn-danger flex-fill';
        longBtn.innerHTML = '<i class="fas fa-trending-up me-2"></i>AL (LONG)';
        shortBtn.innerHTML = '<i class="fas fa-trending-down me-2"></i>SAT (SHORT)';
    }
}

// Sahip değilsiniz uyarısı
function showNotOwnerAlert(symbol, name) {
    const alertPopup = document.createElement('div');
    alertPopup.className = 'success-popup show';
    alertPopup.innerHTML = `
        <div class="success-overlay" onclick="closeNotOwnerAlert()"></div>
        <div class="success-content">
            <div class="success-icon">
                <i class="fas fa-exclamation-triangle" style="color: #ffc107;"></i>
            </div>
            <h3 style="color: #ffc107;">Bu Varlığa Sahip Değilsiniz</h3>
            <p class="mb-4">
                <strong>${symbol}</strong> (${name}) satabilmek için önce portföyünüzde bulunması gerekiyor.
            </p>
            <div class="d-grid gap-2">
                <button onclick="buyInstead('${symbol}')" class="btn btn-success">
                    <i class="fas fa-shopping-cart me-2"></i>Önce Satın Al
                </button>
                <button onclick="window.open('portfolio.php', '_blank')" class="btn btn-outline-secondary">
                    <i class="fas fa-chart-pie me-2"></i>Portföyümü Gör
                </button>
                <button onclick="closeNotOwnerAlert()" class="btn btn-outline-dark">
                    <i class="fas fa-times me-2"></i>Kapat
                </button>
            </div>
        </div>
    `;
    
    document.body.appendChild(alertPopup);
}

// Uyarı kapat
function closeNotOwnerAlert() {
    const popup = document.querySelector('.success-popup');
    if (popup) {
        popup.classList.add('closing');
        setTimeout(() => {
            popup.remove();
        }, 300);
    }
}

// Önce satın al - AL modalını aç
function buyInstead(symbol) {
    closeNotOwnerAlert();
    closeLeverageAlert();
    // AL modalını bul ve aç
    const buyButton = document.querySelector(`[data-symbol="${symbol}"][data-action="buy"]`);
    if (buyButton) {
        openTradeModal(buyButton);
    }
}

function formatPrice(price) {
    if (price >= 1000) {
        return formatTurkishNumber(price, 2);
    } else if (price >= 1) {
        return formatTurkishNumber(price, 4);
    } else {
        return formatTurkishNumber(price, 8);
    }
}

// Simple trading calculation
function calculateSimpleTrade() {
    const usdAmount = parseFloat(document.getElementById('usd_amount').value) || 0;
    const priceUSD = parseFloat(document.getElementById('modalPrice').textContent.replace(',', '.'));
    const submitBtn = document.querySelector('#buyForm button[type="submit"]');
    
    if (usdAmount <= 0) {
        // Reset displays if no amount
        document.getElementById('totalValue').textContent = '$0.00';
        document.getElementById('requiredAmount').textContent = '$0.00';
        document.getElementById('remainingBalance').textContent = '$0.00';
        
        // Reset button
        submitBtn.disabled = false;
        submitBtn.className = 'btn btn-success w-100';
        submitBtn.innerHTML = '<i class="fas fa-shopping-cart me-2"></i>SATIN AL';
        return;
    }
    
    const fee = 0; // No fee for simple trading
    let currentBalance, totalWithFee, remainingBalance;
    
    if (TRADING_CURRENCY === 1) { // TL Mode
        // Convert USD to TL 
        const totalTL = usdAmount * USD_TRY_RATE;
        const feeTL = fee * USD_TRY_RATE;
        totalWithFee = totalTL + feeTL;
        
        // Get current balance
        currentBalance = <?php echo isLoggedIn() ? getUserBalance($_SESSION['user_id'], 'tl') : 10000; ?>;
        remainingBalance = currentBalance - totalWithFee;
        
        // Update display
        document.getElementById('totalValue').textContent = formatTurkishNumber(totalTL, 2) + ' TL';
        document.getElementById('requiredAmount').textContent = formatTurkishNumber(totalWithFee, 2) + ' TL';
        document.getElementById('remainingBalance').textContent = formatTurkishNumber(remainingBalance, 2) + ' TL';
        
    } else { // USD Mode
        totalWithFee = usdAmount + fee;
        
        // Get current balance  
        currentBalance = <?php echo isLoggedIn() ? getUserBalance($_SESSION['user_id'], 'usd') : 1000; ?>;
        remainingBalance = currentBalance - totalWithFee;
        
        // Update display
        document.getElementById('totalValue').textContent = formatTurkishNumber(usdAmount, 2) + ' USD';
        document.getElementById('requiredAmount').textContent = formatTurkishNumber(totalWithFee, 2) + ' USD';
        document.getElementById('remainingBalance').textContent = formatTurkishNumber(remainingBalance, 2) + ' USD';
    }
    
    // SMART BUTTON CONTROL - Anlık Bakiye Kontrolü
    if (totalWithFee > currentBalance) {
        // Yetersiz Bakiye - Kırmızı Buton
        submitBtn.disabled = true;
        submitBtn.className = 'btn btn-danger w-100';
        submitBtn.innerHTML = '<i class="fas fa-exclamation-triangle me-2"></i>YETERSİZ BAKİYE';
    } else {
        // Yeterli Bakiye - Yeşil Buton
        submitBtn.disabled = false;
        submitBtn.className = 'btn btn-success w-100';
        submitBtn.innerHTML = '<i class="fas fa-shopping-cart me-2"></i>SATIN AL';
    }
    
    // Calculate lot equivalent for display
    const lotAmount = usdAmount / priceUSD;
    document.getElementById('lotAmount').textContent = formatTurkishNumber(lotAmount, 4) + ' Lot';
}

// Success Popup System
function showSuccessPopup(message) {
    // Remove any existing popup
    const existingPopup = document.getElementById('successPopup');
    if (existingPopup) {
        existingPopup.remove();
    }
    
    // Parse the message to extract details
    const parts = message.split(' ');
    const amount = parts[0];
    const currency = parts[1];
    const symbol = parts[2];
    const action = parts[3];
    
    // Create popup HTML
    const popup = document.createElement('div');
    popup.id = 'successPopup';
    popup.className = 'success-popup';
    popup.innerHTML = `
        <div class="success-overlay"></div>
        <div class="success-content">
            <div class="success-icon">
                <i class="fas fa-check-circle"></i>
            </div>
            <h3>🎉 İşlem Başarılı!</h3>
            <p><strong>${amount} ${currency} ${symbol} ${action}</strong></p>
            <button onclick="closeSuccessPopup()" class="btn btn-success">
                <i class="fas fa-check me-2"></i>Tamam
            </button>
        </div>
    `;
    
    // Add to body
    document.body.appendChild(popup);
    
    // Show with animation
    setTimeout(() => {
        popup.classList.add('show');
    }, 10);
    
    // Auto close after 3 seconds
    setTimeout(() => {
        closeSuccessPopup();
    }, 3000);
}

function closeSuccessPopup() {
    const popup = document.getElementById('successPopup');
    if (popup) {
        popup.classList.add('closing');
        setTimeout(() => {
            popup.remove();
        }, 300);
    }
}

// Error Popup System
function showErrorPopup(message) {
    const popup = document.createElement('div');
    popup.id = 'errorPopup';
    popup.className = 'success-popup';
    popup.innerHTML = `
        <div class="success-overlay"></div>
        <div class="success-content">
            <div class="success-icon">
                <i class="fas fa-exclamation-triangle" style="color: #dc3545;"></i>
            </div>
            <h3 style="color: #dc3545;">İşlem Başarısız!</h3>
            <p>${message}</p>
            <button onclick="closeErrorPopup()" class="btn btn-danger">
                <i class="fas fa-times me-2"></i>Tamam
            </button>
        </div>
    `;
    
    document.body.appendChild(popup);
    setTimeout(() => popup.classList.add('show'), 10);
    setTimeout(() => closeErrorPopup(), 5000);
}

function closeErrorPopup() {
    const popup = document.getElementById('errorPopup');
    if (popup) {
        popup.classList.add('closing');
        setTimeout(() => popup.remove(), 300);
    }
}

// Mobile trading calculation (separate from desktop)
function calculateSimpleTradeModal() {
    const usdAmount = parseFloat(document.getElementById('usd_amount_mobile').value) || 0;
    const priceUSD = parseFloat(document.getElementById('modalPrice').textContent.replace(',', '.'));
    const submitBtn = document.getElementById('buyButtonMobile');
    
    if (usdAmount <= 0) {
        // Reset displays if no amount
        document.getElementById('totalValueMobile').textContent = '$0.00';
        document.getElementById('requiredAmountMobile').textContent = '$0.00';
        document.getElementById('remainingBalanceMobile').textContent = '$0.00';
        
        // Reset button
        submitBtn.disabled = false;
        submitBtn.className = 'btn btn-success w-100';
        submitBtn.innerHTML = '<i class="fas fa-shopping-cart me-2"></i>SATIN AL';
        return;
    }
    
    const fee = 0; // No fee for simple trading
    let currentBalance, totalWithFee, remainingBalance;
    
    if (TRADING_CURRENCY === 1) { // TL Mode
        // Convert USD to TL 
        const totalTL = usdAmount * USD_TRY_RATE;
        const feeTL = fee * USD_TRY_RATE;
        totalWithFee = totalTL + feeTL;
        
        // Get current balance
        currentBalance = <?php echo isLoggedIn() ? getUserBalance($_SESSION['user_id'], 'tl') : 10000; ?>;
        remainingBalance = currentBalance - totalWithFee;
        
        // Update display
        document.getElementById('totalValueMobile').textContent = formatTurkishNumber(totalTL, 2) + ' TL';
        document.getElementById('requiredAmountMobile').textContent = formatTurkishNumber(totalWithFee, 2) + ' TL';
        document.getElementById('remainingBalanceMobile').textContent = formatTurkishNumber(remainingBalance, 2) + ' TL';
        
    } else { // USD Mode
        totalWithFee = usdAmount + fee;
        
        // Get current balance  
        currentBalance = <?php echo isLoggedIn() ? getUserBalance($_SESSION['user_id'], 'usd') : 1000; ?>;
        remainingBalance = currentBalance - totalWithFee;
        
        // Update display
        document.getElementById('totalValueMobile').textContent = formatTurkishNumber(usdAmount, 2) + ' USD';
        document.getElementById('requiredAmountMobile').textContent = formatTurkishNumber(totalWithFee, 2) + ' USD';
        document.getElementById('remainingBalanceMobile').textContent = formatTurkishNumber(remainingBalance, 2) + ' USD';
    }
    
    // SMART BUTTON CONTROL - Anlık Bakiye Kontrolü
    if (totalWithFee > currentBalance) {
        // Yetersiz Bakiye - Kırmızı Buton
        submitBtn.disabled = true;
        submitBtn.className = 'btn btn-danger w-100';
        submitBtn.innerHTML = '<i class="fas fa-exclamation-triangle me-2"></i>YETERSİZ BAKİYE';
    } else {
        // Yeterli Bakiye - Yeşil Buton
        submitBtn.disabled = false;
        submitBtn.className = 'btn btn-success w-100';
        submitBtn.innerHTML = '<i class="fas fa-shopping-cart me-2"></i>SATIN AL';
    }
    
    // Calculate lot equivalent for display
    const lotAmount = usdAmount / priceUSD;
    document.getElementById('lotAmountMobile').textContent = formatTurkishNumber(lotAmount, 4) + ' Lot';
}

// Chart variables
let desktopChart = null;
let mobileChart = null;
let currentSymbol = '';

// Chart initialization
function initializeCharts(symbol, price) {
    currentSymbol = symbol;
    const currentPrice = parseFloat(price);
    
    // Generate sample data based on current price
    const sampleData = generateSamplePriceData(currentPrice, 24); // 24 hours of hourly data
    
    // Destroy existing charts
    if (desktopChart) {
        desktopChart.destroy();
    }
    if (mobileChart) {
        mobileChart.destroy();
    }
    
    // Desktop chart
    const desktopCtx = document.getElementById('desktopPriceChart');
    if (desktopCtx) {
        desktopChart = createPriceChart(desktopCtx, sampleData, symbol);
    }
    
    // Mobile chart
    const mobileCtx = document.getElementById('mobilePriceChart');
    if (mobileCtx) {
        mobileChart = createPriceChart(mobileCtx, sampleData, symbol);
    }
}

// Generate sample price data
function generateSamplePriceData(currentPrice, hours) {
    const data = [];
    const labels = [];
    let basePrice = currentPrice * 0.95; // Start 5% lower
    
    for (let i = 0; i < hours; i++) {
        const change = (Math.random() - 0.5) * 0.02; // Random change ±1%
        basePrice = basePrice * (1 + change);
        
        const time = new Date();
        time.setHours(time.getHours() - (hours - i));
        
        data.push(basePrice);
        labels.push(time.toLocaleTimeString('tr-TR', { hour: '2-digit', minute: '2-digit' }));
    }
    
    // Ensure last price matches current price
    data[data.length - 1] = currentPrice;
    
    return { prices: data, labels: labels };
}

// Create chart with Chart.js
function createPriceChart(ctx, data, symbol) {
    return new Chart(ctx, {
        type: 'line',
        data: {
            labels: data.labels,
            datasets: [{
                label: `${symbol} Fiyat`,
                data: data.prices,
                borderColor: '#007bff',
                backgroundColor: 'rgba(0, 123, 255, 0.1)',
                borderWidth: 2,
                fill: true,
                tension: 0.1,
                pointRadius: 0,
                pointHoverRadius: 4
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: false
                }
            },
            scales: {
                x: {
                    display: true,
                    grid: {
                        display: false
                    },
                    ticks: {
                        maxTicksLimit: 6,
                        font: {
                            size: 10
                        }
                    }
                },
                y: {
                    display: true,
                    grid: {
                        color: 'rgba(0, 0, 0, 0.1)'
                    },
                    ticks: {
                        font: {
                            size: 10
                        },
                        callback: function(value) {
                            return '$' + value.toFixed(2);
                        }
                    }
                }
            },
            interaction: {
                intersect: false,
                mode: 'index'
            }
        }
    });
}

// Enhanced openTradeModal with chart initialization
function openTradeModal(button) {
    const symbol = button.dataset.symbol;
    const name = button.dataset.name;
    const price = parseFloat(button.dataset.price);
    const action = button.dataset.action;
    
    // Update modal content
    document.getElementById('modalSymbol').textContent = symbol;
    document.getElementById('modalName').textContent = name;
    document.getElementById('modalPrice').textContent = formatPrice(price);
    
    // Update modal logo with original logo from market data
    updateModalLogo(symbol);
    
    // Set hidden fields for forms
    document.getElementById('buySymbol').value = symbol;
    if (document.getElementById('buySymbolMobile')) {
        document.getElementById('buySymbolMobile').value = symbol;
    }
    
    // Show modal
    const modal = new bootstrap.Modal(document.getElementById('tradeModal'));
    modal.show();
    
    // Initialize charts after modal is shown
    setTimeout(() => {
        initializeCharts(symbol, price);
    }, 300);
}

// Update modal logo with original logo from markets data
function updateModalLogo(symbol) {
    // Find the market row with matching symbol
    const marketRow = document.querySelector(`.market-row[data-symbol="${symbol}"]`);
    const mobileCard = document.querySelector(`.mobile-market-card[data-symbol="${symbol}"]`);
    
    let logoElement = null;
    
    // Try to get logo from desktop table first
    if (marketRow) {
        logoElement = marketRow.querySelector('img');
    }
    // Fallback to mobile card
    else if (mobileCard) {
        logoElement = mobileCard.querySelector('img, .mobile-market-logo');
    }
    
    const modalLogoContainer = document.querySelector('#tradeModal .modal-header .bg-primary');
    
    if (logoElement && logoElement.tagName === 'IMG' && logoElement.src) {
        // Replace with original logo
        modalLogoContainer.innerHTML = `<img src="${logoElement.src}" alt="${symbol}" style="width: 40px; height: 40px; border-radius: 50%; object-fit: cover;">`;
    } else {
        // Fallback to icon if no logo available
        modalLogoContainer.innerHTML = '<i class="fas fa-chart-line text-white"></i>';
    }
}

// Chart period change handlers
document.addEventListener('click', function(e) {
    if (e.target.classList.contains('chart-period') || e.target.classList.contains('chart-period-mobile')) {
        // Remove active class from all period buttons
        document.querySelectorAll('.chart-period, .chart-period-mobile').forEach(btn => {
            btn.classList.remove('active');
        });
        
        // Add active class to clicked button
        e.target.classList.add('active');
        
        // Generate new data based on period
        const period = e.target.dataset.period;
        const currentPrice = parseFloat(document.getElementById('modalPrice').textContent);
        let hours;
        
        switch(period) {
            case '1H': hours = 24; break;
            case '1D': hours = 24; break;
            case '1W': hours = 24 * 7; break;
            case '1M': hours = 24 * 30; break;
            case '3M': hours = 24 * 90; break;
            default: hours = 24;
        }
        
        const sampleData = generateSamplePriceData(currentPrice, Math.min(hours, 100));
        
        // Update charts
        if (desktopChart) {
            desktopChart.data.labels = sampleData.labels;
            desktopChart.data.datasets[0].data = sampleData.prices;
            desktopChart.update();
        }
        
        if (mobileChart) {
            mobileChart.data.labels = sampleData.labels;
            mobileChart.data.datasets[0].data = sampleData.prices;
            mobileChart.update();
        }
    }
});

// Check for success/error messages on page load
document.addEventListener('DOMContentLoaded', function() {
    <?php if ($success_message): ?>
    showSuccessPopup('<?php echo addslashes($success_message); ?>');
    <?php endif; ?>
    
    <?php if ($error_message): ?>
    showErrorPopup('<?php echo addslashes($error_message); ?>');
    <?php endif; ?>
});
</script>

<!-- ENHANCED Trading Modal with Chart -->
<div class="modal fade" id="tradeModal" tabindex="-1" aria-labelledby="tradeModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-responsive">
        <div class="modal-content">
            <div class="modal-header">
                <div class="d-flex align-items-center">
                    <div class="bg-primary rounded-circle d-flex align-items-center justify-content-center me-3" 
                         style="width: 40px; height: 40px;">
                        <i class="fas fa-chart-line text-white"></i>
                    </div>
                    <div>
                        <h5 class="modal-title mb-0" id="modalSymbol">AAPL</h5>
                        <small class="text-muted" id="modalName">Apple Inc.</small>
                    </div>
                    <div class="ms-auto text-end">
                        <div class="h5 mb-0" id="modalPrice">$175.50</div>
                    </div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            
            <!-- Tab Navigation for Mobile -->
            <div class="modal-tab-nav d-md-none">
                <div class="nav nav-tabs border-0" id="nav-tab" role="tablist">
                    <button class="nav-link active flex-fill" id="nav-trade-tab" data-bs-toggle="tab" 
                            data-bs-target="#nav-trade" type="button" role="tab" aria-controls="nav-trade" aria-selected="true">
                        <i class="fas fa-shopping-cart me-1"></i>İşlem
                    </button>
                    <button class="nav-link flex-fill" id="nav-chart-tab" data-bs-toggle="tab" 
                            data-bs-target="#nav-chart" type="button" role="tab" aria-controls="nav-chart" aria-selected="false">
                        <i class="fas fa-chart-line me-1"></i>Grafik
                    </button>
                </div>
            </div>
            
            <div class="modal-body">
                <!-- Desktop: Side by side layout -->
                <div class="d-none d-md-block">
                    <div class="row">
                        <!-- Trading Form Column -->
                        <div class="col-md-4">
                            <h6 class="mb-3"><i class="fas fa-shopping-cart me-2"></i>İşlem Formu</h6>
                            <?php if (isLoggedIn()): ?>
                            <form id="buyForm" method="POST" action="markets.php?group=<?php echo $category; ?>">
                                <input type="hidden" name="trade_action" value="buy">
                                <input type="hidden" name="symbol" id="buySymbol" value="">
                                
                                <div class="mb-3">
                                    <label class="form-label">USD Miktar</label>
                                    <div class="input-group">
                                        <input type="number" class="form-control" id="usd_amount" name="usd_amount" step="0.01" min="0.01" 
                                               placeholder="10.00" oninput="calculateSimpleTrade()" required>
                                        <span class="input-group-text">USD</span>
                                    </div>
                                    <small class="text-muted">Satın almak istediğiniz USD tutarı</small>
                                </div>
                                
                                <!-- Trade Summary -->
                                <div class="card border-0 bg-light mb-3">
                                    <div class="card-body p-3">
                                        <div class="d-flex justify-content-between mb-1">
                                            <small class="text-muted">Toplam Değer:</small>
                                            <small class="fw-bold" id="totalValue">$0.00</small>
                                        </div>
                                        <div class="d-flex justify-content-between mb-1">
                                            <small class="text-muted">Lot Miktarı:</small>
                                            <small class="fw-bold" id="lotAmount">0.00 Lot</small>
                                        </div>
                                        <div class="d-flex justify-content-between mb-1">
                                            <small class="text-muted">Ödenecek Tutar:</small>
                                            <small class="fw-bold" id="requiredAmount">$0.00</small>
                                        </div>
                                        <div class="d-flex justify-content-between">
                                            <small class="text-muted">Kalan Bakiye:</small>
                                            <small class="fw-bold" id="remainingBalance">$0.00</small>
                                        </div>
                                    </div>
                                </div>
                                
                                <button type="submit" class="btn btn-success w-100">
                                    <i class="fas fa-shopping-cart me-2"></i>SATIN AL
                                </button>
                            </form>
                            <?php else: ?>
                            <div class="text-center py-4">
                                <i class="fas fa-user-lock fa-3x text-muted mb-3"></i>
                                <p class="text-muted mb-3">İşlem yapmak için giriş yapmanız gerekiyor</p>
                                <a href="login.php" class="btn btn-primary">
                                    <i class="fas fa-sign-in-alt me-2"></i>Giriş Yap
                                </a>
                            </div>
                            <?php endif; ?>
                        </div>
                        
                        <!-- Chart Column -->
                        <div class="col-md-8">
                            <h6 class="mb-3"><i class="fas fa-chart-line me-2"></i>Fiyat Grafiği</h6>
                            <div class="chart-container">
                                <canvas id="desktopPriceChart" width="400" height="300"></canvas>
                            </div>
                            <!-- Chart Controls -->
                            <div class="chart-controls mt-3">
                                <div class="btn-group w-100" role="group">
                                    <button type="button" class="btn btn-outline-secondary btn-sm chart-period active" data-period="1H">1S</button>
                                    <button type="button" class="btn btn-outline-secondary btn-sm chart-period" data-period="1D">1G</button>
                                    <button type="button" class="btn btn-outline-secondary btn-sm chart-period" data-period="1W">1H</button>
                                    <button type="button" class="btn btn-outline-secondary btn-sm chart-period" data-period="1M">1A</button>
                                    <button type="button" class="btn btn-outline-secondary btn-sm chart-period" data-period="3M">3A</button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Mobile: Tab content -->
                <div class="d-md-none">
                    <div class="tab-content" id="nav-tabContent">
                        <!-- Trading Tab -->
                        <div class="tab-pane fade show active" id="nav-trade" role="tabpanel" aria-labelledby="nav-trade-tab">
                            <?php if (isLoggedIn()): ?>
                            <form id="buyFormMobile" method="POST" action="markets.php?group=<?php echo $category; ?>">
                                <input type="hidden" name="trade_action" value="buy">
                                <input type="hidden" name="symbol" id="buySymbolMobile" value="">
                                
                                <div class="mb-3">
                                    <label class="form-label">USD Miktar</label>
                                    <div class="input-group">
                                        <input type="number" class="form-control" id="usd_amount_mobile" name="usd_amount" step="0.01" min="0.01" 
                                               placeholder="10.00" oninput="calculateSimpleTradeModal()" required>
                                        <span class="input-group-text">USD</span>
                                    </div>
                                    <small class="text-muted">Satın almak istediğiniz USD tutarı</small>
                                </div>
                                
                                <!-- Trade Summary -->
                                <div class="card border-0 bg-light mb-3">
                                    <div class="card-body p-3">
                                        <div class="d-flex justify-content-between mb-1">
                                            <small class="text-muted">Toplam Değer:</small>
                                            <small class="fw-bold" id="totalValueMobile">$0.00</small>
                                        </div>
                                        <div class="d-flex justify-content-between mb-1">
                                            <small class="text-muted">Lot Miktarı:</small>
                                            <small class="fw-bold" id="lotAmountMobile">0.00 Lot</small>
                                        </div>
                                        <div class="d-flex justify-content-between mb-1">
                                            <small class="text-muted">Ödenecek Tutar:</small>
                                            <small class="fw-bold" id="requiredAmountMobile">$0.00</small>
                                        </div>
                                        <div class="d-flex justify-content-between">
                                            <small class="text-muted">Kalan Bakiye:</small>
                                            <small class="fw-bold" id="remainingBalanceMobile">$0.00</small>
                                        </div>
                                    </div>
                                </div>
                                
                                <button type="submit" class="btn btn-success w-100" id="buyButtonMobile">
                                    <i class="fas fa-shopping-cart me-2"></i>SATIN AL
                                </button>
                            </form>
                            <?php else: ?>
                            <div class="text-center py-4">
                                <i class="fas fa-user-lock fa-3x text-muted mb-3"></i>
                                <p class="text-muted mb-3">İşlem yapmak için giriş yapmanız gerekiyor</p>
                                <a href="login.php" class="btn btn-primary">
                                    <i class="fas fa-sign-in-alt me-2"></i>Giriş Yap
                                </a>
                            </div>
                            <?php endif; ?>
                        </div>
                        
                        <!-- Chart Tab -->
                        <div class="tab-pane fade" id="nav-chart" role="tabpanel" aria-labelledby="nav-chart-tab">
                            <div class="chart-container">
                                <canvas id="mobilePriceChart" width="400" height="250"></canvas>
                            </div>
                            <!-- Chart Controls -->
                            <div class="chart-controls mt-3">
                                <div class="btn-group w-100" role="group">
                                    <button type="button" class="btn btn-outline-secondary btn-sm chart-period-mobile active" data-period="1H">1S</button>
                                    <button type="button" class="btn btn-outline-secondary btn-sm chart-period-mobile" data-period="1D">1G</button>
                                    <button type="button" class="btn btn-outline-secondary btn-sm chart-period-mobile" data-period="1W">1H</button>
                                    <button type="button" class="btn btn-outline-secondary btn-sm chart-period-mobile" data-period="1M">1A</button>
                                    <button type="button" class="btn btn-outline-secondary btn-sm chart-period-mobile" data-period="3M">3A</button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- LEVERAGE Trading Modal -->
<div class="modal fade" id="leverageModal" tabindex="-1" aria-labelledby="leverageModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-responsive-leverage">
        <div class="modal-content">
            <div class="modal-header bg-warning text-dark">
                <div class="d-flex align-items-center">
                    <div class="bg-dark rounded-circle d-flex align-items-center justify-content-center me-3" 
                         style="width: 40px; height: 40px;">
                        <i class="fas fa-bolt text-warning"></i>
                    </div>
                    <div>
                        <h5 class="modal-title mb-0" id="leverageModalSymbol">AAPL</h5>
                        <small class="text-muted" id="leverageModalName">Apple Inc.</small>
                    </div>
                    <div class="ms-auto text-end">
                        <div class="h5 mb-0" id="leverageModalPrice">$175.50</div>
                        <small class="badge bg-dark">Kaldıraçlı İşlem</small>
                    </div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <?php if (isLoggedIn()): ?>
                <!-- Leverage Trading Form -->
                <form id="leverageForm" method="POST" action="leverage_trade.php">
                    <input type="hidden" name="leverage_action" value="open">
                    <input type="hidden" name="symbol" id="leverageSymbol" value="">
                    <input type="hidden" name="entry_price" id="leverageEntryPrice" value="">
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">
                                    <i class="fas fa-shield-alt me-1"></i>Teminat Miktarı
                                </label>
                                <div class="input-group">
                                    <input type="number" class="form-control" id="collateral_amount" name="collateral_amount" 
                                           step="0.01" min="1" placeholder="100.00" oninput="calculateLeveragePosition()" required>
                                    <span class="input-group-text">USD</span>
                                </div>
                                <small class="text-muted">Pozisyon için yatıracağınız teminat</small>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">
                                    <i class="fas fa-chart-line me-1"></i>Kaldıraç Oranı
                                </label>
                                <select class="form-select" id="leverage_ratio" name="leverage_ratio" onchange="calculateLeveragePosition()" required>
                                    <option value="1">1x (Kaldıraçsız)</option>
                                    <option value="2">2x</option>
                                    <option value="5">5x</option>
                                    <option value="10">10x</option>
                                    <option value="20">20x</option>
                                    <option value="25">25x</option>
                                    <option value="50">50x</option>
                                    <option value="75">75x</option>
                                    <option value="100">100x</option>
                                </select>
                                <small class="text-muted">Pozisyon büyüklüğü çarpanı</small>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Auto-calculation Display -->
                    <div class="card border-0 bg-light mb-3">
                        <div class="card-header bg-transparent">
                            <h6 class="mb-0"><i class="fas fa-calculator me-1"></i>Pozisyon Hesaplaması</h6>
                        </div>
                        <div class="card-body p-3">
                            <div class="row text-center">
                                <div class="col-6 col-md-3 mb-2">
                                    <small class="text-muted d-block">Pozisyon Büyüklüğü</small>
                                    <strong id="positionSize" class="text-primary">$0.00</strong>
                                </div>
                                <div class="col-6 col-md-3 mb-2">
                                    <small class="text-muted d-block">İşlem Ücreti</small>
                                    <strong id="tradingFee" class="text-info">$0.00</strong>
                                </div>
                                <div class="col-6 col-md-3 mb-2">
                                    <small class="text-muted d-block">Gerekli Tutar</small>
                                    <strong id="requiredCollateral" class="text-dark">$0.00</strong>
                                </div>
                                <div class="col-6 col-md-3 mb-2">
                                    <small class="text-muted d-block">Kalan Bakiye</small>
                                    <strong id="leverageBalance" class="text-success">$0.00</strong>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Liquidation Prices -->
                    <div class="card border-0 bg-danger bg-opacity-10 mb-3">
                        <div class="card-header bg-transparent">
                            <h6 class="mb-0 text-danger"><i class="fas fa-exclamation-triangle me-1"></i>Likidasyon Fiyatları</h6>
                        </div>
                        <div class="card-body p-3">
                            <div class="row text-center">
                                <div class="col-6">
                                    <small class="text-muted d-block">LONG Pozisyon</small>
                                    <strong id="liquidationPriceLong" class="text-success">$0.00</strong>
                                    <div><small class="text-muted">Fiyat bu seviyenin altına düşerse pozisyon kapanır</small></div>
                                </div>
                                <div class="col-6">
                                    <small class="text-muted d-block">SHORT Pozisyon</small>
                                    <strong id="liquidationPriceShort" class="text-danger">$0.00</strong>
                                    <div><small class="text-muted">Fiyat bu seviyenin üstüne çıkarsa pozisyon kapanır</small></div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- PnL Display -->
                    <div class="card border-0 bg-success bg-opacity-10 mb-3">
                        <div class="card-body p-3 text-center">
                            <small class="text-muted d-block">Gerçekleşmemiş Kar/Zarar</small>
                            <h4 id="unrealizedPnl" class="mb-0 text-success">$0.00</h4>
                            <small class="text-muted">Pozisyon açıldığında fiyat değişimine göre güncellenecek</small>
                        </div>
                    </div>
                    
                    <!-- Trading Buttons -->
                    <div class="d-flex gap-2">
                        <button type="submit" name="trade_type" value="long" class="btn btn-success flex-fill">
                            <i class="fas fa-trending-up me-2"></i>AL (LONG)
                        </button>
                        <button type="submit" name="trade_type" value="short" class="btn btn-danger flex-fill">
                            <i class="fas fa-trending-down me-2"></i>SAT (SHORT)
                        </button>
                    </div>
                    
                    <!-- Risk Warning -->
                    <div class="alert alert-warning mt-3 mb-0" role="alert">
                        <div class="d-flex align-items-start">
                            <i class="fas fa-exclamation-triangle me-2 mt-1 flex-shrink-0"></i>
                            <div>
                                <strong>Risk Uyarısı:</strong> Kaldıraçlı işlemler yüksek risk içerir. 
                                Pozisyonunuz likidasyon fiyatına ulaşırsa teminatınızın tamamını kaybedebilirsiniz.
                            </div>
                        </div>
                    </div>
                </form>
                <?php else: ?>
                <div class="text-center py-4">
                    <i class="fas fa-user-lock fa-3x text-muted mb-3"></i>
                    <p class="text-muted mb-3">Kaldıraçlı işlem yapmak için giriş yapmanız gerekiyor</p>
                    <a href="login.php" class="btn btn-warning">
                        <i class="fas fa-sign-in-alt me-2"></i>Giriş Yap
                    </a>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>

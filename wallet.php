<?php
require_once 'includes/functions.php';

// Require login for wallet
requireLogin();

$page_title = t('wallet');
$error = '';
$success = '';

$user_id = $_SESSION['user_id'];

// Handle deposit/withdrawal requests
if ($_POST) {
    if (isset($_POST['deposit'])) {
        $amount = (float)($_POST['amount'] ?? 0);
        $method = $_POST['method'] ?? '';
        $reference = sanitizeInput($_POST['reference'] ?? '');
        $deposit_type = $_POST['deposit_type'] ?? 'normal';
        $tl_amount = (float)($_POST['tl_amount'] ?? 0);
        
        // Handle different deposit types
        if ($deposit_type == 'tl_to_usd') {
            // USD Mode: User pays in TL, gets USD
            if ($tl_amount < MIN_DEPOSIT_AMOUNT) {
                $error = getCurrentLang() == 'tr' ? 
                    'Minimum para yatırma tutarı ' . MIN_DEPOSIT_AMOUNT . ' TL' : 
                    'Minimum deposit amount is ' . MIN_DEPOSIT_AMOUNT . ' TL';
            } elseif ($amount <= 0) {
                $error = getCurrentLang() == 'tr' ? 'Geçersiz dolar tutarı' : 'Invalid USD amount';
            } elseif (!in_array($method, ['bank', 'digital', 'crypto'])) {
                $error = getCurrentLang() == 'tr' ? 'Geçersiz ödeme yöntemi' : 'Invalid payment method';
            } else {
                $database = new Database();
                $db = $database->getConnection();
                
                // Check if new columns exist, if not use basic insert
                try {
                    $query = "INSERT INTO deposits (user_id, amount, method, reference, deposit_type, tl_amount, usd_amount, exchange_rate) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
                    $stmt = $db->prepare($query);
                    
                    $exchange_rate = $usd_try_rate;
                    
                    if ($stmt->execute([$user_id, $amount, $method, $reference, 'tl_to_usd', $tl_amount, $amount, $exchange_rate])) {
                        $success = t('deposit_request_sent');
                        logActivity($user_id, 'deposit_request', "TL-to-USD Deposit: $tl_amount TL → $amount USD (Rate: $exchange_rate), Method: $method");
                    } else {
                        $error = getCurrentLang() == 'tr' ? 'Bir hata oluştu' : 'An error occurred';
                    }
                } catch (PDOException $e) {
                    // Fallback to basic insert if new columns don't exist
                    $query = "INSERT INTO deposits (user_id, amount, method, reference) VALUES (?, ?, ?, ?)";
                    $stmt = $db->prepare($query);
                    
                    $deposit_reference = $reference . " (TL: $tl_amount → USD: $amount, Rate: $usd_try_rate)";
                    
                    if ($stmt->execute([$user_id, $amount, $method, $deposit_reference])) {
                        $success = t('deposit_request_sent');
                        logActivity($user_id, 'deposit_request', "TL-to-USD Deposit: $tl_amount TL → $amount USD (Rate: $usd_try_rate), Method: $method");
                    } else {
                        $error = getCurrentLang() == 'tr' ? 'Bir hata oluştu' : 'An error occurred';
                    }
                }
            }
        } else {
            // Normal TL deposit
            if ($amount < MIN_DEPOSIT_AMOUNT) {
                $error = getCurrentLang() == 'tr' ? 
                    'Minimum para yatırma tutarı ' . MIN_DEPOSIT_AMOUNT . ' TL' : 
                    'Minimum deposit amount is ' . MIN_DEPOSIT_AMOUNT . ' TL';
            } elseif (!in_array($method, ['bank', 'digital', 'crypto'])) {
                $error = getCurrentLang() == 'tr' ? 'Geçersiz ödeme yöntemi' : 'Invalid payment method';
            } else {
                $database = new Database();
                $db = $database->getConnection();
                
                try {
                    $query = "INSERT INTO deposits (user_id, amount, method, reference, deposit_type) VALUES (?, ?, ?, ?, ?)";
                    $stmt = $db->prepare($query);
                    
                    if ($stmt->execute([$user_id, $amount, $method, $reference, 'normal'])) {
                        $success = t('deposit_request_sent');
                        logActivity($user_id, 'deposit_request', "Amount: $amount TL, Method: $method");
                    } else {
                        $error = getCurrentLang() == 'tr' ? 'Bir hata oluştu' : 'An error occurred';
                    }
                } catch (PDOException $e) {
                    // Fallback to basic insert if deposit_type column doesn't exist
                    $query = "INSERT INTO deposits (user_id, amount, method, reference) VALUES (?, ?, ?, ?)";
                    $stmt = $db->prepare($query);
                    
                    if ($stmt->execute([$user_id, $amount, $method, $reference])) {
                        $success = t('deposit_request_sent');
                        logActivity($user_id, 'deposit_request', "Amount: $amount TL, Method: $method");
                    } else {
                        $error = getCurrentLang() == 'tr' ? 'Bir hata oluştu' : 'An error occurred';
                    }
                }
            }
        }
    }
    
    if (isset($_POST['withdraw'])) {
        $amount = (float)($_POST['amount'] ?? 0);
        $method = $_POST['method'] ?? '';
        $iban_info = sanitizeInput($_POST['iban_info'] ?? '');
        $papara_info = sanitizeInput($_POST['papara_info'] ?? '');
        
        $balance_tl = getUserBalance($user_id, 'tl');
        
        if ($amount < MIN_WITHDRAWAL_AMOUNT) {
            $error = getCurrentLang() == 'tr' ? 
                'Minimum para çekme tutarı ' . MIN_WITHDRAWAL_AMOUNT . ' TL' : 
                'Minimum withdrawal amount is ' . MIN_WITHDRAWAL_AMOUNT . ' TL';
        } elseif ($amount > $balance_tl) {
            $error = t('insufficient_balance');
        } elseif (!in_array($method, ['iban', 'papara'])) {
            $error = getCurrentLang() == 'tr' ? 'Geçersiz ödeme yöntemi' : 'Invalid payment method';
        } elseif ($method == 'iban' && empty($iban_info)) {
            $error = getCurrentLang() == 'tr' ? 'IBAN bilgisi gerekli' : 'IBAN information required';
        } elseif ($method == 'papara' && empty($papara_info)) {
            $error = getCurrentLang() == 'tr' ? 'Papara bilgisi gerekli' : 'Papara information required';
        } else {
            $database = new Database();
            $db = $database->getConnection();
            
            $query = "INSERT INTO withdrawals (user_id, amount, method, iban_info, papara_info) VALUES (?, ?, ?, ?, ?)";
            $stmt = $db->prepare($query);
            
            if ($stmt->execute([$user_id, $amount, $method, $iban_info, $papara_info])) {
                $success = t('withdrawal_request_sent');
                logActivity($user_id, 'withdrawal_request', "Amount: $amount TL, Method: $method");
            } else {
                $error = getCurrentLang() == 'tr' ? 'Bir hata oluştu' : 'An error occurred';
            }
        }
    }
}

// Get trading currency settings and USD/TRY rate
$trading_currency = getTradingCurrency();
$currency_field = getCurrencyField($trading_currency);
$currency_symbol = getCurrencySymbol($trading_currency);
$usd_try_rate = getUSDTRYRate();

// Get user balances based on trading currency
$balance_tl = getUserBalance($user_id, 'tl');
$balance_usd = getUserBalance($user_id, 'usd');

// Set primary balance based on trading currency
if ($trading_currency == 1) { // TL Mode
    $primary_balance = $balance_tl;
    $primary_currency = 'TL';
    $secondary_balance = $balance_usd;
    $secondary_currency = 'USD';
} else { // USD Mode  
    $primary_balance = $balance_usd;
    $primary_currency = 'USD';
    $secondary_balance = $balance_tl;
    $secondary_currency = 'TL';
}

// Get recent deposits and withdrawals
$database = new Database();
$db = $database->getConnection();

$query = "SELECT * FROM deposits WHERE user_id = ? ORDER BY created_at DESC LIMIT 10";
$stmt = $db->prepare($query);
$stmt->execute([$user_id]);
$deposits = $stmt->fetchAll(PDO::FETCH_ASSOC);

$query = "SELECT * FROM withdrawals WHERE user_id = ? ORDER BY created_at DESC LIMIT 10";
$stmt = $db->prepare($query);
$stmt->execute([$user_id]);
$withdrawals = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get payment methods from database
$query = "SELECT * FROM payment_methods WHERE is_active = 1 ORDER BY sort_order";
$stmt = $db->prepare($query);
$stmt->execute();
$payment_methods = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Group payment methods by type
$banks = [];
$cryptos = [];
$digital = [];
foreach ($payment_methods as $method) {
    if ($method['type'] == 'bank') {
        $banks[] = $method;
    } elseif ($method['type'] == 'crypto') {
        $cryptos[] = $method;
    } elseif ($method['type'] == 'digital') {
        $digital[] = $method;
    }
}

include 'includes/header.php';
?>

<div class="container">
    <div class="row">
        <!-- Wallet Overview -->
        <div class="col-12 mb-4">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white">
                    <h4 class="mb-0"><?php echo t('wallet'); ?></h4>
                </div>
                <div class="card-body">
                    <div class="row justify-content-center">
                        <!-- Primary Currency (Based on Trading Parameter) -->
                        <div class="col-md-6 col-12 mb-3">
                            <div class="text-center p-4 bg-success bg-opacity-10 rounded border border-success">
                                <i class="fas fa-<?php echo $trading_currency == 1 ? 'turkish-lira-sign' : 'dollar-sign'; ?> fa-3x text-success mb-3"></i>
                                <div class="h3 mb-1 text-success"><?php echo formatNumber($primary_balance); ?></div>
                                <div class="h6 text-success">
                                    <?php echo $trading_currency == 1 ? 'Türk Lirası' : 'US Dollar'; ?>
                                </div>
                                <small class="text-success fw-bold">Ana Bakiye</small>
                            </div>
                        </div>
                        
                        <!-- Secondary Currency -->
                        <div class="col-md-6 col-12 mb-3">
                            <div class="text-center p-4 bg-light rounded border">
                                <i class="fas fa-<?php echo $trading_currency == 1 ? 'dollar-sign' : 'turkish-lira-sign'; ?> fa-2x text-muted mb-3"></i>
                                <div class="h4 mb-1"><?php echo formatNumber($secondary_balance); ?></div>
                                <div class="h6 text-muted">
                                    <?php echo $trading_currency == 1 ? 'US Dollar' : 'Türk Lirası'; ?>
                                </div>
                                <small class="text-muted">
                                    <?php if ($trading_currency == 1): ?>
                                        ≈ <?php echo formatNumber($secondary_balance * $usd_try_rate); ?> TL
                                    <?php else: ?>
                                        ≈ <?php echo formatNumber($secondary_balance / $usd_try_rate); ?> USD
                                    <?php endif; ?>
                                </small>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Exchange Rate Info -->
                    <div class="text-center mt-3">
                        <small class="text-muted">
                            <i class="fas fa-exchange-alt me-1"></i>
                            1 USD = <?php echo formatNumber($usd_try_rate, 4); ?> TL
                            <span class="ms-2">|</span>
                            <span class="ms-2">1 TL = <?php echo formatNumber(1 / $usd_try_rate, 4); ?> USD</span>
                        </small>
                    </div>
                </div>
                </div>
            </div>
        </div>
        
        <!-- Deposit/Withdraw Forms -->
        <div class="col-lg-6">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
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
                    
                    <!-- Deposit/Withdraw Tabs -->
                    <ul class="nav nav-pills nav-fill mb-3" id="walletTabs" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active" id="deposit-tab" data-bs-toggle="pill" data-bs-target="#deposit" type="button">
                                <i class="fas fa-plus me-1"></i><?php echo t('deposit'); ?>
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="withdraw-tab" data-bs-toggle="pill" data-bs-target="#withdraw" type="button">
                                <i class="fas fa-minus me-1"></i><?php echo t('withdraw'); ?>
                            </button>
                        </li>
                    </ul>
                    
                    <div class="tab-content" id="walletTabsContent">
                        <!-- Deposit Form -->
                        <div class="tab-pane fade show active" id="deposit" role="tabpanel">
                            <form method="POST" action="">
                                <?php if ($trading_currency == 2): // USD Mode - User pays in TL, gets USD ?>
                                <!-- TL Input for USD Account -->
                                <div class="mb-3">
                                    <label class="form-label">
                                        <i class="fas fa-turkish-lira-sign me-1 text-success"></i>
                                        Yatırılacak TL Tutarı
                                    </label>
                                    <div class="input-group">
                                        <input type="number" class="form-control" name="tl_amount" step="0.01" 
                                               min="<?php echo MIN_DEPOSIT_AMOUNT; ?>" id="tlDepositAmount" 
                                               oninput="calculateUSDConversion()" required>
                                        <span class="input-group-text bg-success text-white">TL</span>
                                    </div>
                                    <small class="text-muted">
                                        Minimum: <?php echo MIN_DEPOSIT_AMOUNT; ?> TL
                                    </small>
                                </div>

                                <!-- USD Equivalent Display -->
                                <div class="mb-3">
                                    <label class="form-label">
                                        <i class="fas fa-dollar-sign me-1 text-primary"></i>
                                        Hesabınıza Geçecek Dolar Miktarı
                                    </label>
                                    <div class="input-group">
                                        <input type="number" class="form-control bg-light" id="usdEquivalent" 
                                               step="0.01" readonly placeholder="0.00">
                                        <span class="input-group-text bg-primary text-white">USD</span>
                                    </div>
                                    <small class="text-info">
                                        <i class="fas fa-info-circle me-1"></i>
                                        1 USD = <?php echo formatNumber($usd_try_rate, 4); ?> TL kurunda hesaplanmaktadır
                                    </small>
                                </div>

                                <!-- Hidden field for backend processing -->
                                <input type="hidden" name="amount" id="hiddenUSDAmount" value="">
                                <input type="hidden" name="deposit_type" value="tl_to_usd">

                                <?php else: // TL Mode - Normal TL Deposit ?>
                                <!-- Normal TL Deposit -->
                                <div class="mb-3">
                                    <label class="form-label">
                                        <i class="fas fa-turkish-lira-sign me-1 text-success"></i>
                                        Yatırılacak TL Tutarı
                                    </label>
                                    <div class="input-group">
                                        <input type="number" class="form-control" name="amount" step="0.01" 
                                               min="<?php echo MIN_DEPOSIT_AMOUNT; ?>" required>
                                        <span class="input-group-text bg-success text-white">TL</span>
                                    </div>
                                    <small class="text-muted">
                                        Minimum: <?php echo MIN_DEPOSIT_AMOUNT; ?> TL
                                    </small>
                                </div>
                                <?php endif; ?>
                                
                                <div class="mb-3">
                                    <label class="form-label"><?php echo getCurrentLang() == 'tr' ? 'Ödeme Yöntemi' : 'Payment Method'; ?></label>
                                    <select class="form-select" name="method" id="depositMethod" onchange="showDepositDetails()" required>
                                        <option value=""><?php echo getCurrentLang() == 'tr' ? 'Seçiniz' : 'Select'; ?></option>
                                        <option value="bank">🏦 Banka Havalesi</option>
                                        <option value="digital">📱 Dijital Ödeme (Papara vb.)</option>
                                        <option value="crypto">₿ Kripto Para</option>
                                    </select>
                                </div>

                                <!-- Banka Seçimi -->
                                <div id="bankDepositDetails" style="display: none;">
                                    <div class="mb-3">
                                        <label class="form-label">
                                            <i class="fas fa-university me-2 text-primary"></i>
                                            Banka Seçiniz
                                        </label>
                                        <div class="row g-2">
                                            <?php foreach ($banks as $bank): ?>
                                            <div class="col-md-6 col-12">
                                                <div class="bank-card p-3 border-0 rounded-3 shadow-sm" onclick="selectBank('<?php echo $bank['code']; ?>', '<?php echo $bank['iban']; ?>', '<?php echo $bank['account_name']; ?>')">
                                                    <div class="d-flex align-items-center">
                                                        <div class="bank-icon me-3">
                                                            <div class="rounded-circle bg-primary bg-opacity-10 d-flex align-items-center justify-content-center" style="width: 50px; height: 50px;">
                                                                <span style="font-size: 1.5rem;"><?php echo $bank['icon']; ?></span>
                                                            </div>
                                                        </div>
                                                        <div class="flex-grow-1">
                                                            <div class="fw-bold text-dark mb-1"><?php echo $bank['name']; ?></div>
                                                            <small class="text-muted">Güvenli Havale</small>
                                                        </div>
                                                        <div class="check-icon" style="opacity: 0;">
                                                            <i class="fas fa-check-circle text-success fs-5"></i>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            <?php endforeach; ?>
                                        </div>
                                        <input type="hidden" name="selected_bank" id="selectedBank">
                                    </div>
                                    
                                    <div class="alert alert-info border-0 shadow-sm" id="bankInfo" style="display: none;">
                                        <div class="d-flex align-items-start">
                                            <i class="fas fa-info-circle text-info me-3 mt-1"></i>
                                            <div class="flex-grow-1">
                                                <h6 class="alert-heading mb-3">
                                                    <i class="fas fa-university me-2"></i>
                                                    Havale Bilgileri
                                                </h6>
                                                <div class="row">
                                                    <div class="col-12 mb-2">
                                                        <div class="d-flex justify-content-between align-items-center p-2 bg-white rounded border">
                                                            <span class="text-muted">IBAN:</span>
                                                            <span class="fw-bold" id="displayIban"></span>
                                                        </div>
                                                    </div>
                                                    <div class="col-12 mb-3">
                                                        <div class="d-flex justify-content-between align-items-center p-2 bg-white rounded border">
                                                            <span class="text-muted">Hesap Adı:</span>
                                                            <span class="fw-bold" id="displayAccountName"></span>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="bg-warning bg-opacity-10 border border-warning rounded p-2">
                                                    <small class="text-warning">
                                                        <i class="fas fa-exclamation-triangle me-2"></i>
                                                        <strong>Önemli:</strong> Havale açıklama kısmına kullanıcı adınızı yazınız.
                                                    </small>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Dijital Ödeme Seçimi -->
                                <div id="digitalDepositDetails" style="display: none;">
                                    <div class="mb-3">
                                        <label class="form-label">
                                            <i class="fas fa-mobile-alt me-2 text-primary"></i>
                                            Dijital Ödeme Yöntemi
                                        </label>
                                        <div class="row g-2">
                                            <?php foreach ($digital as $method): ?>
                                            <div class="col-md-6 col-12">
                                                <div class="digital-card p-3 border-0 rounded-3 shadow-sm" onclick="selectDigital('<?php echo $method['code']; ?>', '<?php echo $method['name']; ?>', '<?php echo $method['account_name']; ?>')">
                                                    <div class="d-flex align-items-center">
                                                        <div class="digital-icon me-3">
                                                            <div class="rounded-circle bg-gradient-primary d-flex align-items-center justify-content-center" style="width: 50px; height: 50px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
                                                                <span style="font-size: 1.5rem; color: white;"><?php echo $method['icon']; ?></span>
                                                            </div>
                                                        </div>
                                                        <div class="flex-grow-1">
                                                            <div class="fw-bold text-dark mb-1"><?php echo $method['name']; ?></div>
                                                            <small class="text-muted">
                                                                <i class="fas fa-bolt me-1"></i>
                                                                Anında Transfer
                                                            </small>
                                                        </div>
                                                        <div class="check-icon-digital" style="opacity: 0;">
                                                            <i class="fas fa-check-circle text-success fs-5"></i>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            <?php endforeach; ?>
                                        </div>
                                        <input type="hidden" name="selected_digital" id="selectedDigital">
                                    </div>
                                    
                                    <div class="alert alert-info border-0 shadow-sm" id="digitalInfo" style="display: none;">
                                        <div class="d-flex align-items-start">
                                            <i class="fas fa-mobile-alt text-info me-3 mt-1"></i>
                                            <div class="flex-grow-1">
                                                <h6 class="alert-heading mb-3">
                                                    <i class="fas fa-credit-card me-2"></i>
                                                    Dijital Ödeme Bilgileri
                                                </h6>
                                                <div class="row">
                                                    <div class="col-12 mb-2">
                                                        <div class="d-flex justify-content-between align-items-center p-2 bg-white rounded border">
                                                            <span class="text-muted">Yöntem:</span>
                                                            <span class="fw-bold" id="displayDigitalName"></span>
                                                        </div>
                                                    </div>
                                                    <div class="col-12 mb-2">
                                                        <div class="d-flex justify-content-between align-items-center p-2 bg-white rounded border">
                                                            <span class="text-muted">Hesap No:</span>
                                                            <span class="fw-bold" id="displayDigitalCode"></span>
                                                        </div>
                                                    </div>
                                                    <div class="col-12 mb-3">
                                                        <div class="d-flex justify-content-between align-items-center p-2 bg-white rounded border">
                                                            <span class="text-muted">Hesap Adı:</span>
                                                            <span class="fw-bold" id="displayDigitalAccount"></span>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="bg-success bg-opacity-10 border border-success rounded p-2">
                                                    <small class="text-success">
                                                        <i class="fas fa-lightning-bolt me-2"></i>
                                                        <strong>Avantaj:</strong> Anında işlem, 7/24 kullanım imkanı.
                                                    </small>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Kripto Para Seçimi -->
                                <div id="cryptoDepositDetails" style="display: none;">
                                    <div class="mb-3">
                                        <label class="form-label">
                                            <i class="fab fa-bitcoin me-2 text-warning"></i>
                                            Kripto Para Seçiniz
                                        </label>
                                        <div class="row g-2">
                                            <?php foreach ($cryptos as $crypto): ?>
                                            <div class="col-md-4 col-6">
                                                <div class="crypto-card p-3 border-0 rounded-3 shadow-sm text-center" onclick="selectCrypto('<?php echo $crypto['code']; ?>', '<?php echo $crypto['iban']; ?>', '<?php echo $crypto['account_name']; ?>')">
                                                    <div class="crypto-icon mb-2">
                                                        <div class="rounded-circle bg-gradient-crypto d-flex align-items-center justify-content-center mx-auto" style="width: 50px; height: 50px; background: linear-gradient(135deg, #f7931e 0%, #ff6b35 100%);">
                                                            <span style="font-size: 1.8rem; color: white;"><?php echo $crypto['icon']; ?></span>
                                                        </div>
                                                    </div>
                                                    <div class="fw-bold text-dark mb-1" style="font-size: 0.9rem;"><?php echo $crypto['name']; ?></div>
                                                    <small class="text-muted d-block"><?php echo $crypto['code']; ?></small>
                                                    <div class="check-icon-crypto" style="opacity: 0; position: absolute; top: 10px; right: 10px;">
                                                        <i class="fas fa-check-circle text-success fs-6"></i>
                                                    </div>
                                                </div>
                                            </div>
                                            <?php endforeach; ?>
                                        </div>
                                        <input type="hidden" name="selected_crypto" id="selectedCrypto">
                                    </div>
                                    
                                    <div class="alert alert-warning border-0 shadow-sm" id="cryptoInfo" style="display: none;">
                                        <div class="d-flex align-items-start">
                                            <i class="fab fa-bitcoin text-warning me-3 mt-1" style="font-size: 1.5rem;"></i>
                                            <div class="flex-grow-1">
                                                <h6 class="alert-heading mb-3">
                                                    <i class="fas fa-wallet me-2"></i>
                                                    Kripto Para Bilgileri
                                                </h6>
                                                <div class="row">
                                                    <div class="col-12 mb-2">
                                                        <div class="d-flex justify-content-between align-items-center p-2 bg-white rounded border">
                                                            <span class="text-muted">Kripto:</span>
                                                            <span class="fw-bold" id="displayCryptoName"></span>
                                                        </div>
                                                    </div>
                                                    <div class="col-12 mb-2">
                                                        <div class="d-flex justify-content-between align-items-center p-2 bg-white rounded border">
                                                            <span class="text-muted">Network:</span>
                                                            <span class="fw-bold" id="displayCryptoNetwork"></span>
                                                        </div>
                                                    </div>
                                                    <div class="col-12 mb-3">
                                                        <div class="p-2 bg-white rounded border">
                                                            <div class="text-muted mb-1">Wallet Adresi:</div>
                                                            <code class="d-block bg-light p-2 rounded small text-break" id="displayCryptoAddress"></code>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="bg-danger bg-opacity-10 border border-danger rounded p-2">
                                                    <small class="text-danger">
                                                        <i class="fas fa-shield-alt me-2"></i>
                                                        <strong>Güvenlik Uyarısı:</strong> Sadece bu ağa gönderim yapın. Yanlış ağ kullanımında paralarınız kaybolabilir!
                                                    </small>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label"><?php echo getCurrentLang() == 'tr' ? 'Referans/Açıklama' : 'Reference/Description'; ?></label>
                                    <input type="text" class="form-control" name="reference" 
                                           placeholder="<?php echo getCurrentLang() == 'tr' ? 'İşlem referansı veya açıklama' : 'Transaction reference or description'; ?>">
                                </div>
                                
                                <button type="submit" name="deposit" class="btn btn-success w-100">
                                    <i class="fas fa-plus me-2"></i><?php echo t('deposit'); ?>
                                </button>
                            </form>
                            
                            <!-- Deposit Instructions -->
                            <div class="mt-4 p-3 bg-info bg-opacity-10 border border-info rounded">
                                <h6 class="text-info"><?php echo getCurrentLang() == 'tr' ? 'Para Yatırma Talimatları' : 'Deposit Instructions'; ?></h6>
                                <small class="text-muted">
                                    <strong>IBAN:</strong> TR12 3456 7890 1234 5678 90<br>
                                    <strong>Hesap Adı:</strong> GlobalBorsa Ltd.<br>
                                    <strong>Papara No:</strong> 1234567890<br>
                                    <br>
                                    <?php echo getCurrentLang() == 'tr' ? 
                                        'Havale/EFT açıklama kısmına kullanıcı adınızı yazınız.' : 
                                        'Please include your username in the transfer description.'; ?>
                                </small>
                            </div>
                        </div>
                        
                        <!-- Simple Withdraw Form -->
                        <div class="tab-pane fade" id="withdraw" role="tabpanel">
                            <form method="POST" action="">
                                <!-- Ödeme Yöntemi -->
                                <div class="mb-3">
                                    <label class="form-label">Ödeme Yöntemi</label>
                                    <select class="form-select" name="method" id="withdrawMethod" required>
                                        <option value="">Seçiniz</option>
                                        <option value="iban">🏦 Banka Havalesi</option>
                                        <option value="papara">📱 Papara</option>
                                        <option value="crypto">₿ Kripto Para</option>
                                    </select>
                                </div>

                                <!-- Kullanıcı Bilgileri -->
                                <div class="row mb-3">
                                    <div class="col-md-6">
                                        <label class="form-label">Ad Soyad</label>
                                        <input type="text" class="form-control" readonly value="<?php 
                                        // Get user info from database
                                        $database = new Database();
                                        $db = $database->getConnection();
                                        $query = "SELECT username FROM users WHERE id = ?";
                                        $stmt = $db->prepare($query);
                                        $stmt->execute([$user_id]);
                                        $user_data = $stmt->fetch(PDO::FETCH_ASSOC);
                                        echo htmlspecialchars($user_data['username'] ?? 'Kullan��cı');
                                        ?>">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">TC Kimlik No</label>
                                        <input type="text" class="form-control" name="tc_number" 
                                               placeholder="12345678901" maxlength="11" required>
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label">Telefon Numarası</label>
                                    <input type="tel" class="form-control" name="phone" 
                                           placeholder="0555 123 45 67" required>
                                </div>

                                <!-- Tutar -->
                                <div class="mb-3">
                                    <label class="form-label">Çekilecek Tutar</label>
                                    <div class="input-group">
                                        <input type="number" class="form-control" name="amount" 
                                               step="<?php echo $trading_currency == 1 ? '10' : '1'; ?>" 
                                               min="<?php echo MIN_WITHDRAWAL_AMOUNT; ?>" 
                                               max="<?php echo $primary_balance; ?>" 
                                               placeholder="<?php echo MIN_WITHDRAWAL_AMOUNT; ?>" 
                                               id="withdrawAmount" oninput="calculateWithdrawConversion()" required>
                                        <span class="input-group-text">
                                            <?php echo $trading_currency == 1 ? 'TL' : 'USD'; ?>
                                        </span>
                                    </div>
                                    <div class="d-flex justify-content-between mt-1">
                                        <small class="text-muted">
                                            Kullanılabilir: <?php echo formatNumber($primary_balance); ?> <?php echo $primary_currency; ?>
                                        </small>
                                        <small class="text-info" id="withdrawConversion"></small>
                                    </div>
                                    <div class="d-flex justify-content-end mt-2">
                                        <?php if ($trading_currency == 1): ?>
                                        <button type="button" class="btn btn-outline-primary btn-sm me-1" onclick="setAmount(100)">100</button>
                                        <button type="button" class="btn btn-outline-primary btn-sm me-1" onclick="setAmount(500)">500</button>
                                        <button type="button" class="btn btn-outline-primary btn-sm" onclick="setAmount(1000)">1000</button>
                                        <?php else: ?>
                                        <button type="button" class="btn btn-outline-primary btn-sm me-1" onclick="setAmount(10)">10</button>
                                        <button type="button" class="btn btn-outline-primary btn-sm me-1" onclick="setAmount(50)">50</button>
                                        <button type="button" class="btn btn-outline-primary btn-sm" onclick="setAmount(100)">100</button>
                                        <?php endif; ?>
                                    </div>
                                </div>

                                <!-- Banka Bilgileri -->
                                <div id="bankDetails" style="display: none;">
                                    <div class="mb-3">
                                        <label class="form-label">Banka Seçiniz</label>
                                        <select class="form-select" name="bank_name">
                                            <option value="">Banka Seçiniz</option>
                                            <?php foreach ($banks as $bank): ?>
                                            <option value="<?php echo $bank['code']; ?>">
                                                <?php echo $bank['icon']; ?> <?php echo $bank['name']; ?>
                                            </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">IBAN</label>
                                        <input type="text" class="form-control" name="iban_info" 
                                               placeholder="TR00 0000 0000 0000 0000 0000 00" required>
                                    </div>
                                </div>

                                <!-- Papara Bilgileri -->
                                <div id="paparaDetails" style="display: none;">
                                    <?php foreach ($digital as $method): ?>
                                    <div class="mb-3">
                                        <label class="form-label"><?php echo $method['icon']; ?> <?php echo $method['name']; ?> Hesap No</label>
                                        <input type="text" class="form-control" name="papara_info" 
                                               placeholder="<?php echo $method['name']; ?> hesap numaranız" required>
                                    </div>
                                    <?php endforeach; ?>
                                </div>

                                <!-- Kripto Bilgileri -->
                                <div id="cryptoDetails" style="display: none;">
                                    <div class="mb-3">
                                        <label class="form-label">Kripto Para Seçiniz</label>
                                        <select class="form-select" name="crypto_type" required>
                                            <option value="">Kripto Para Seçiniz</option>
                                            <?php foreach ($cryptos as $crypto): ?>
                                            <option value="<?php echo $crypto['code']; ?>">
                                                <?php echo $crypto['icon']; ?> <?php echo $crypto['name']; ?> (<?php echo $crypto['code']; ?>)
                                            </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Wallet Adresi</label>
                                        <input type="text" class="form-control" name="crypto_address" 
                                               placeholder="Kripto para cüzdan adresinizi girin" required>
                                    </div>
                                    <div class="alert alert-warning">
                                        <small>
                                            <i class="fas fa-exclamation-triangle me-1"></i>
                                            Network ücretleri çekim tutarından düşülecektir.
                                        </small>
                                    </div>
                                </div>
                                
                                <div class="alert alert-info mb-3">
                                    <i class="fas fa-info-circle me-2"></i>
                                    <small>Para çekme işlemi admin onayı gerektirir. İşlem süresi 1-3 iş günüdür.</small>
                                </div>
                                
                                <button type="submit" name="withdraw" class="btn btn-danger w-100">
                                    <i class="fas fa-arrow-down me-2"></i>Para Çek
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Transaction History -->
        <div class="col-lg-6">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white">
                    <h5 class="mb-0"><?php echo t('transaction_history'); ?></h5>
                </div>
                <div class="card-body">
                    <!-- History Tabs -->
                    <ul class="nav nav-tabs nav-fill mb-3" id="historyTabs" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active" id="deposits-tab" data-bs-toggle="tab" data-bs-target="#deposits" type="button">
                                <?php echo getCurrentLang() == 'tr' ? 'Para Yatırma' : 'Deposits'; ?>
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="withdrawals-tab" data-bs-toggle="tab" data-bs-target="#withdrawals" type="button">
                                <?php echo getCurrentLang() == 'tr' ? 'Para Çekme' : 'Withdrawals'; ?>
                            </button>
                        </li>
                    </ul>
                    
                    <div class="tab-content" id="historyTabsContent">
                        <!-- Deposits History -->
                        <div class="tab-pane fade show active" id="deposits" role="tabpanel">
                            <?php if (empty($deposits)): ?>
                            <div class="text-center py-4">
                                <i class="fas fa-plus-circle fa-2x text-muted mb-3"></i>
                                <p class="text-muted">
                                    <?php echo getCurrentLang() == 'tr' ? 'Henüz para yatırma işlemi yok' : 'No deposit history yet'; ?>
                                </p>
                            </div>
                            <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-sm">
                                    <thead>
                                        <tr>
                                            <th><?php echo getCurrentLang() == 'tr' ? 'Tarih' : 'Date'; ?></th>
                                            <th><?php echo getCurrentLang() == 'tr' ? 'Tutar' : 'Amount'; ?></th>
                                            <th><?php echo getCurrentLang() == 'tr' ? 'Yöntem' : 'Method'; ?></th>
                                            <th><?php echo getCurrentLang() == 'tr' ? 'Durum' : 'Status'; ?></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($deposits as $deposit): ?>
                                        <tr>
                                            <td><?php echo date('d.m.Y H:i', strtotime($deposit['created_at'])); ?></td>
                                            <td>
                                                <?php 
                                // Show amount in appropriate currency based on trading parameter and deposit type
                                $is_usd_deposit = false;
                                $display_usd_amount = $deposit['amount'];
                                $display_tl_amount = 0;
                                
                                if (isset($deposit['deposit_type']) && $deposit['deposit_type'] == 'tl_to_usd') {
                                    $is_usd_deposit = true;
                                    $display_tl_amount = $deposit['tl_amount'] ?? 0;
                                } elseif (strpos($deposit['reference'], '→ USD:') !== false || strpos($deposit['reference'], 'USD:') !== false) {
                                    // Fallback: Reference field'dan bilgileri parse et
                                    $is_usd_deposit = true;
                                    if (preg_match('/TL:\s*([\d.,]+)/', $deposit['reference'], $matches)) {
                                        $display_tl_amount = (float)str_replace(',', '.', $matches[1]);
                                    }
                                    if (preg_match('/→\s*USD:\s*([\d.,]+)/', $deposit['reference'], $matches)) {
                                        $display_usd_amount = (float)str_replace(',', '.', $matches[1]);
                                    } elseif (preg_match('/USD:\s*([\d.,]+)/', $deposit['reference'], $matches)) {
                                        $display_usd_amount = (float)str_replace(',', '.', $matches[1]);
                                    }
                                }
                                
                                if ($is_usd_deposit && $trading_currency == 2) {
                                    // TL-to-USD deposit - show USD amount
                                    echo formatNumber($display_usd_amount) . ' USD';
                                    if ($display_tl_amount > 0) {
                                        echo '<br><small class="text-muted">(' . formatNumber($display_tl_amount) . ' TL)</small>';
                                    }
                                } elseif ($trading_currency == 2) {
                                    // USD Mode - convert TL to USD for display (legacy deposits)
                                    $usd_amount = $deposit['amount'] / $usd_try_rate;
                                    echo formatNumber($usd_amount) . ' USD';
                                    echo '<br><small class="text-muted">(' . formatNumber($deposit['amount']) . ' TL)</small>';
                                } else {
                                    // TL Mode - show TL amount
                                    echo formatNumber($deposit['amount']) . ' TL';
                                }
                                                ?>
                                            </td>
                                            <td><?php echo strtoupper($deposit['method']); ?></td>
                                            <td>
                                                <?php
                                                $status_class = $deposit['status'] == 'approved' ? 'success' : 
                                                              ($deposit['status'] == 'rejected' ? 'danger' : 'warning');
                                                ?>
                                                <span class="badge bg-<?php echo $status_class; ?>">
                                                    <?php echo t($deposit['status']); ?>
                                                </span>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                            <?php endif; ?>
                        </div>
                        
                        <!-- Withdrawals History -->
                        <div class="tab-pane fade" id="withdrawals" role="tabpanel">
                            <?php if (empty($withdrawals)): ?>
                            <div class="text-center py-4">
                                <i class="fas fa-minus-circle fa-2x text-muted mb-3"></i>
                                <p class="text-muted">
                                    <?php echo getCurrentLang() == 'tr' ? 'Henüz para çekme işlemi yok' : 'No withdrawal history yet'; ?>
                                </p>
                            </div>
                            <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-sm">
                                    <thead>
                                        <tr>
                                            <th><?php echo getCurrentLang() == 'tr' ? 'Tarih' : 'Date'; ?></th>
                                            <th><?php echo getCurrentLang() == 'tr' ? 'Tutar' : 'Amount'; ?></th>
                                            <th><?php echo getCurrentLang() == 'tr' ? 'Yöntem' : 'Method'; ?></th>
                                            <th><?php echo getCurrentLang() == 'tr' ? 'Durum' : 'Status'; ?></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($withdrawals as $withdrawal): ?>
                                        <tr>
                                            <td><?php echo date('d.m.Y H:i', strtotime($withdrawal['created_at'])); ?></td>
                                            <td>
                                                <?php 
                                                // Show amount in appropriate currency based on trading parameter
                                                if ($trading_currency == 2) {
                                                    // USD Mode - show as USD
                                                    echo formatNumber($withdrawal['amount']) . ' USD';
                                                } else {
                                                    // TL Mode - show as TL
                                                    echo formatNumber($withdrawal['amount']) . ' TL';
                                                }
                                                ?>
                                            </td>
                                            <td><?php echo strtoupper($withdrawal['method']); ?></td>
                                            <td>
                                                <?php
                                                $status_class = $withdrawal['status'] == 'approved' ? 'success' : 
                                                              ($withdrawal['status'] == 'rejected' ? 'danger' : 'warning');
                                                ?>
                                                <span class="badge bg-<?php echo $status_class; ?>">
                                                    <?php echo t($withdrawal['status']); ?>
                                                </span>
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
        </div>
    </div>
</div>

<!-- Modern Wallet Styles -->
<style>
/* Modern Bank Card Design */
.bank-card {
    border: 2px solid #e9ecef !important;
    cursor: pointer;
    transition: all 0.3s ease;
    background: white;
    position: relative;
    overflow: hidden;
}

.bank-card:hover {
    border-color: #007bff !important;
    box-shadow: 0 4px 20px rgba(0, 123, 255, 0.15) !important;
    transform: translateY(-2px);
}

.bank-card.active {
    border-color: #007bff !important;
    background: linear-gradient(135deg, #007bff 0%, #0056b3 100%);
    color: white !important;
    box-shadow: 0 6px 25px rgba(0, 123, 255, 0.3) !important;
}

.bank-card.active .text-dark {
    color: white !important;
}

.bank-card.active .text-muted {
    color: rgba(255, 255, 255, 0.8) !important;
}

.bank-card.active .check-icon {
    opacity: 1 !important;
}

/* Bank Icon Styling */
.bank-icon .rounded-circle {
    transition: all 0.3s ease;
}

.bank-card.active .bank-icon .rounded-circle {
    background: rgba(255, 255, 255, 0.2) !important;
}

/* Smooth animations */
.check-icon {
    transition: opacity 0.3s ease;
}

/* Modern Digital Payment Card Design */
.digital-card {
    border: 2px solid #e9ecef !important;
    cursor: pointer;
    transition: all 0.3s ease;
    background: white;
    position: relative;
    overflow: hidden;
}

.digital-card:hover {
    border-color: #667eea !important;
    box-shadow: 0 4px 20px rgba(102, 126, 234, 0.15) !important;
    transform: translateY(-2px);
}

.digital-card.active {
    border-color: #667eea !important;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white !important;
    box-shadow: 0 6px 25px rgba(102, 126, 234, 0.3) !important;
}

.digital-card.active .text-dark {
    color: white !important;
}

.digital-card.active .text-muted {
    color: rgba(255, 255, 255, 0.8) !important;
}

.digital-card.active .check-icon-digital {
    opacity: 1 !important;
}

.check-icon-digital {
    transition: opacity 0.3s ease;
}

.digital-card.active .digital-icon .rounded-circle {
    background: rgba(255, 255, 255, 0.2) !important;
}

/* Modern Crypto Card Design */
.crypto-card {
    border: 2px solid #e9ecef !important;
    cursor: pointer;
    transition: all 0.3s ease;
    background: white;
    position: relative;
    overflow: hidden;
}

.crypto-card:hover {
    border-color: #f7931e !important;
    box-shadow: 0 4px 20px rgba(247, 147, 30, 0.15) !important;
    transform: translateY(-2px);
}

.crypto-card.active {
    border-color: #f7931e !important;
    background: linear-gradient(135deg, #f7931e 0%, #ff6b35 100%);
    color: white !important;
    box-shadow: 0 6px 25px rgba(247, 147, 30, 0.3) !important;
}

.crypto-card.active .text-dark {
    color: white !important;
}

.crypto-card.active .text-muted {
    color: rgba(255, 255, 255, 0.8) !important;
}

.crypto-card.active .check-icon-crypto {
    opacity: 1 !important;
}

.check-icon-crypto {
    transition: opacity 0.3s ease;
}

.crypto-card.active .crypto-icon .rounded-circle {
    background: rgba(255, 255, 255, 0.2) !important;
}

/* Legacy styles for compatibility */
.withdraw-method-card, .bank-option, .crypto-option {
    border: 2px solid #e9ecef;
    border-radius: 8px;
    cursor: pointer;
    transition: all 0.3s ease;
    background: white;
}

.withdraw-method-card:hover, .bank-option:hover, .crypto-option:hover {
    border-color: #007bff;
    box-shadow: 0 2px 8px rgba(0, 123, 255, 0.15);
    transform: translateY(-2px);
}

.withdraw-method-card.active, .bank-option.active, .crypto-option.active {
    border-color: #007bff;
    background: linear-gradient(135deg, #007bff, #0056b3);
    color: white;
    box-shadow: 0 4px 12px rgba(0, 123, 255, 0.3);
}

.withdraw-method-card.active h6, .withdraw-method-card.active small,
.bank-option.active small, .crypto-option.active small {
    color: white !important;
}

.bank-logo {
    height: 40px;
    width: auto;
    max-width: 80px;
    object-fit: contain;
    margin-bottom: 0.5rem;
}

.bank-option, .crypto-option {
    text-align: center;
    padding: 1rem;
    margin-bottom: 0.5rem;
}

.crypto-logo {
    font-size: 2rem;
    font-weight: bold;
    color: #28a745;
    margin-bottom: 0.5rem;
}

.withdraw-details {
    animation: fadeIn 0.3s ease;
}

@keyframes fadeIn {
    from { opacity: 0; transform: translateY(10px); }
    to { opacity: 1; transform: translateY(0); }
}

.amount-adjuster {
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

/* Enhanced alert styling */
.alert.border-0.shadow-sm {
    border-left: 4px solid #0dcaf0 !important;
    background: linear-gradient(135deg, #e7f7ff 0%, #f0f9ff 100%);
}

/* Mobile Responsive */
@media (max-width: 768px) {
    /* Container spacing */
    .container {
        padding-left: 10px;
        padding-right: 10px;
    }
    
    /* Card spacing */
    .card {
        margin-bottom: 1rem;
    }
    
    /* Balance cards - stack vertically on mobile */
    .col-md-6.col-12 {
        margin-bottom: 1rem;
    }
    
    /* Wallet overview adjustments */
    .fa-3x {
        font-size: 2rem !important;
    }
    
    .h3 {
        font-size: 1.5rem !important;
    }
    
    /* Form improvements */
    .form-label {
        font-size: 0.9rem;
        font-weight: 600;
    }
    
    .form-control, .form-select {
        font-size: 16px; /* Prevents zoom on iOS */
        padding: 0.75rem;
    }
    
    .btn {
        padding: 0.75rem 1rem;
        font-size: 1rem;
    }
    
    /* Payment method cards */
    .bank-card, .digital-card, .crypto-card {
        margin-bottom: 0.75rem;
        padding: 1rem !important;
    }
    
    .bank-card .rounded-circle,
    .digital-card .rounded-circle,
    .crypto-card .rounded-circle {
        width: 40px !important;
        height: 40px !important;
    }
    
    .bank-card .rounded-circle span,
    .digital-card .rounded-circle span,
    .crypto-card .rounded-circle span {
        font-size: 1.2rem !important;
    }
    
    /* Text adjustments */
    .fw-bold {
        font-size: 0.9rem;
    }
    
    small {
        font-size: 0.8rem;
    }
    
    /* Alert improvements */
    .alert {
        padding: 0.75rem;
        font-size: 0.9rem;
    }
    
    .alert h6 {
        font-size: 1rem;
    }
    
    /* Table responsive improvements */
    .table-responsive {
        font-size: 0.85rem;
    }
    
    .table td {
        padding: 0.5rem 0.25rem;
        white-space: nowrap;
    }
    
    .badge {
        font-size: 0.7rem;
    }
    
    /* Crypto cards - 2 columns on mobile */
    .crypto-card {
        margin-bottom: 0.5rem;
    }
    
    /* Amount buttons */
    .btn-sm {
        padding: 0.375rem 0.5rem;
        font-size: 0.8rem;
    }
    
    /* Tab improvements */
    .nav-pills .nav-link {
        padding: 0.5rem 0.75rem;
        font-size: 0.9rem;
    }
    
    .nav-tabs .nav-link {
        padding: 0.5rem 0.75rem;
        font-size: 0.9rem;
    }
    
    /* Input group improvements */
    .input-group-text {
        padding: 0.75rem 0.5rem;
        font-size: 0.9rem;
    }
    
    /* Statistics cards */
    .bg-primary.bg-opacity-10,
    .bg-success.bg-opacity-10,
    .bg-info.bg-opacity-10,
    .bg-warning.bg-opacity-10 {
        padding: 0.75rem !important;
    }
    
    .fa-2x {
        font-size: 1.5rem !important;
    }
    
    .h4 {
        font-size: 1.25rem !important;
    }
    
    /* Touch improvements */
    .bank-card, .digital-card, .crypto-card {
        min-height: 60px;
        touch-action: manipulation;
    }
    
    /* Spacing adjustments */
    .mb-3 {
        margin-bottom: 1rem !important;
    }
    
    .p-3 {
        padding: 0.75rem !important;
    }
    
    .p-4 {
        padding: 1rem !important;
    }
}

/* Small mobile devices */
@media (max-width: 576px) {
    /* Further optimizations for very small screens */
    .container {
        padding-left: 5px;
        padding-right: 5px;
    }
    
    /* Force single column for crypto cards */
    .crypto-card {
        margin-bottom: 0.5rem;
    }
    
    .col-md-4.col-6 {
        flex: 0 0 50%;
        max-width: 50%;
    }
    
    /* Smaller text for very small screens */
    .h3 {
        font-size: 1.25rem !important;
    }
    
    .h4 {
        font-size: 1.1rem !important;
    }
    
    .h5 {
        font-size: 1rem !important;
    }
    
    .h6 {
        font-size: 0.9rem !important;
    }
    
    /* Tighter spacing */
    .card-body {
        padding: 1rem;
    }
    
    .card-header {
        padding: 0.75rem 1rem;
    }
    
    /* Stack form rows */
    .row.mb-3 .col-md-6 {
        margin-bottom: 0.5rem;
    }
    
    /* Responsive table adjustments */
    .table td {
        padding: 0.375rem 0.125rem;
        font-size: 0.8rem;
    }
    
    .table th {
        padding: 0.375rem 0.125rem;
        font-size: 0.8rem;
    }
    
    /* Button improvements */
    .btn-group-sm > .btn, .btn-sm {
        padding: 0.25rem 0.375rem;
        font-size: 0.75rem;
    }
    
    /* Modal and overlay improvements */
    .alert .d-flex {
        flex-direction: column;
        align-items: flex-start !important;
    }
    
    .alert .d-flex .me-3 {
        margin-right: 0 !important;
        margin-bottom: 0.5rem;
    }
}

/* Landscape mobile orientation */
@media (max-width: 768px) and (orientation: landscape) {
    .col-lg-6 {
        margin-bottom: 1rem;
    }
    
    /* Reduce vertical spacing in landscape */
    .mb-4 {
        margin-bottom: 1rem !important;
    }
    
    /* Optimize balance cards for landscape */
    .text-center.p-4 {
        padding: 1rem !important;
    }
}

/* Touch device optimizations */
@media (hover: none) and (pointer: coarse) {
    .bank-card:hover,
    .digital-card:hover,
    .crypto-card:hover {
        transform: none;
        border-color: inherit !important;
        box-shadow: inherit !important;
    }
    
    .bank-card:active,
    .digital-card:active,
    .crypto-card:active {
        transform: scale(0.98);
        transition: transform 0.1s ease;
    }
}
</style>

<script>
// Trading currency and exchange rate constants from PHP
const TRADING_CURRENCY = <?php echo $trading_currency; ?>;
const USD_TRY_RATE = <?php echo $usd_try_rate; ?>;

// Set amount quickly
function setAmount(amount) {
    const amountInput = document.querySelector('input[name="amount"]');
    if (amountInput) {
        amountInput.value = amount;
        // Trigger conversion calculation
        if (amountInput.id === 'withdrawAmount') {
            calculateWithdrawConversion();
        }
    }
}

// Calculate USD conversion for USD Mode deposits (TL to USD)
function calculateUSDConversion() {
    const tlAmountInput = document.getElementById('tlDepositAmount');
    const usdEquivalentInput = document.getElementById('usdEquivalent');
    const hiddenUSDAmountInput = document.getElementById('hiddenUSDAmount');
    
    if (!tlAmountInput || !usdEquivalentInput || !hiddenUSDAmountInput) return;
    
    const tlAmount = parseFloat(tlAmountInput.value) || 0;
    
    if (tlAmount <= 0) {
        usdEquivalentInput.value = '';
        hiddenUSDAmountInput.value = '';
        return;
    }
    
    // Convert TL to USD
    const usdAmount = tlAmount / USD_TRY_RATE;
    
    // Update display and hidden field
    usdEquivalentInput.value = usdAmount.toFixed(4);
    hiddenUSDAmountInput.value = usdAmount.toFixed(4);
}

// Calculate deposit conversion (for TL Mode)
function calculateDepositConversion() {
    const amountInput = document.getElementById('depositAmount');
    const conversionDisplay = document.getElementById('depositConversion');
    
    if (!amountInput || !conversionDisplay) return;
    
    const amount = parseFloat(amountInput.value) || 0;
    
    if (amount <= 0) {
        conversionDisplay.textContent = '';
        return;
    }
    
    let convertedAmount = 0;
    let conversionText = '';
    
    if (TRADING_CURRENCY === 1) { // TL Mode - show USD equivalent
        convertedAmount = amount / USD_TRY_RATE;
        conversionText = `≈ ${convertedAmount.toFixed(2)} USD`;
    } else { // USD Mode - show TL equivalent
        convertedAmount = amount * USD_TRY_RATE;
        conversionText = `≈ ${convertedAmount.toFixed(2)} TL`;
    }
    
    conversionDisplay.textContent = conversionText;
}

// Calculate withdrawal conversion
function calculateWithdrawConversion() {
    const amountInput = document.getElementById('withdrawAmount');
    const conversionDisplay = document.getElementById('withdrawConversion');
    
    if (!amountInput || !conversionDisplay) return;
    
    const amount = parseFloat(amountInput.value) || 0;
    
    if (amount <= 0) {
        conversionDisplay.textContent = '';
        return;
    }
    
    let convertedAmount = 0;
    let conversionText = '';
    
    if (TRADING_CURRENCY === 1) { // TL Mode - show USD equivalent
        convertedAmount = amount / USD_TRY_RATE;
        conversionText = `≈ ${convertedAmount.toFixed(2)} USD`;
    } else { // USD Mode - show TL equivalent
        convertedAmount = amount * USD_TRY_RATE;
        conversionText = `≈ ${convertedAmount.toFixed(2)} TL`;
    }
    
    conversionDisplay.textContent = conversionText;
}

// Turkish number formatting
function formatTurkishNumber(number, decimals = 2) {
    return new Intl.NumberFormat('tr-TR', {
        minimumFractionDigits: decimals,
        maximumFractionDigits: decimals
    }).format(number);
}

// TC Kimlik validation - sadece sayı
function validateTC() {
    const tcInput = document.querySelector('input[name="tc_number"]');
    if (tcInput) {
        tcInput.addEventListener('input', function() {
            this.value = this.value.replace(/\D/g, ''); // Sadece sayılar
            if (this.value.length > 11) {
                this.value = this.value.substring(0, 11);
            }
        });
    }
}

// Show/hide method details
document.getElementById('withdrawMethod').addEventListener('change', function() {
    const method = this.value;
    const bankDetails = document.getElementById('bankDetails');
    const paparaDetails = document.getElementById('paparaDetails'); 
    const cryptoDetails = document.getElementById('cryptoDetails');
    
    // Hide all details
    [bankDetails, paparaDetails, cryptoDetails].forEach(el => {
        if (el) el.style.display = 'none';
    });
    
    // Show relevant details
    if (method === 'iban' && bankDetails) {
        bankDetails.style.display = 'block';
    } else if (method === 'papara' && paparaDetails) {
        paparaDetails.style.display = 'block';
    } else if (method === 'crypto' && cryptoDetails) {
        cryptoDetails.style.display = 'block';
    }
});

// Show deposit details based on method selection
function showDepositDetails() {
    const method = document.getElementById('depositMethod').value;
    const bankDetails = document.getElementById('bankDepositDetails');
    const digitalDetails = document.getElementById('digitalDepositDetails');
    const cryptoDetails = document.getElementById('cryptoDepositDetails');
    
    // Hide all details
    [bankDetails, digitalDetails, cryptoDetails].forEach(el => {
        if (el) el.style.display = 'none';
    });
    
    // Show relevant details
    if (method === 'bank' && bankDetails) {
        bankDetails.style.display = 'block';
    } else if (method === 'digital' && digitalDetails) {
        digitalDetails.style.display = 'block';
    } else if (method === 'crypto' && cryptoDetails) {
        cryptoDetails.style.display = 'block';
    }
}

// Bank selection functions
function selectBank(code, iban, accountName) {
    // Remove active class from all bank cards
    document.querySelectorAll('.bank-card').forEach(el => {
        el.classList.remove('active');
    });
    
    // Also remove from legacy bank options if they exist
    document.querySelectorAll('.bank-option').forEach(el => {
        el.classList.remove('active');
    });
    
    // Add active class to selected card
    event.target.closest('.bank-card').classList.add('active');
    
    // Set hidden field
    document.getElementById('selectedBank').value = code;
    
    // Show bank info with smooth animation
    const bankInfo = document.getElementById('bankInfo');
    document.getElementById('displayIban').textContent = iban;
    document.getElementById('displayAccountName').textContent = accountName;
    
    if (bankInfo) {
        bankInfo.style.display = 'block';
        // Add fade-in animation
        bankInfo.style.opacity = '0';
        setTimeout(() => {
            bankInfo.style.opacity = '1';
        }, 10);
    }
}

// Digital payment selection
function selectDigital(code, name, accountName) {
    // Remove active class from all digital cards
    document.querySelectorAll('.digital-card').forEach(el => {
        el.classList.remove('active');
    });
    
    // Also remove from legacy digital options if they exist
    document.querySelectorAll('.digital-option').forEach(el => {
        el.classList.remove('active');
    });
    
    // Add active class to selected card
    event.target.closest('.digital-card').classList.add('active');
    
    // Set hidden field
    document.getElementById('selectedDigital').value = code;
    
    // Show digital info with smooth animation
    const digitalInfo = document.getElementById('digitalInfo');
    document.getElementById('displayDigitalName').textContent = name;
    document.getElementById('displayDigitalCode').textContent = code;
    document.getElementById('displayDigitalAccount').textContent = accountName;
    
    if (digitalInfo) {
        digitalInfo.style.display = 'block';
        // Add fade-in animation
        digitalInfo.style.opacity = '0';
        setTimeout(() => {
            digitalInfo.style.opacity = '1';
        }, 10);
    }
}

// Crypto selection
function selectCrypto(code, address, network) {
    // Remove active class from all crypto cards
    document.querySelectorAll('.crypto-card').forEach(el => {
        el.classList.remove('active');
    });
    
    // Also remove from legacy crypto options if they exist
    document.querySelectorAll('.crypto-option').forEach(el => {
        el.classList.remove('active');
    });
    
    // Add active class to selected card
    event.target.closest('.crypto-card').classList.add('active');
    
    // Set hidden field
    document.getElementById('selectedCrypto').value = code;
    
    // Show crypto info with smooth animation
    const cryptoInfo = document.getElementById('cryptoInfo');
    document.getElementById('displayCryptoName').textContent = code;
    document.getElementById('displayCryptoNetwork').textContent = network;
    document.getElementById('displayCryptoAddress').textContent = address;
    
    if (cryptoInfo) {
        cryptoInfo.style.display = 'block';
        // Add fade-in animation
        cryptoInfo.style.opacity = '0';
        setTimeout(() => {
            cryptoInfo.style.opacity = '1';
        }, 10);
    }
}

// Initialize when page loads
document.addEventListener('DOMContentLoaded', function() {
    validateTC();
});
</script>

<?php include 'includes/footer.php'; ?>

<?php
require_once '../config/database.php';
require_once '../config/api_keys.php';
require_once '../config/languages.php';

// Start session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Check if user is logged in
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

// Check if user is admin
function isAdmin() {
    return isset($_SESSION['is_admin']) && $_SESSION['is_admin'] == 1;
}

// Redirect if not logged in
function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: login.php');
        exit();
    }
}

// Redirect if not admin
function requireAdmin() {
    if (!isAdmin()) {
        header('Location: index.php');
        exit();
    }
}

// Format number with Turkish locale
function formatNumber($number, $decimals = 2) {
    return number_format($number, $decimals, ',', '.');
}

// Format price based on value
function formatPrice($price) {
    if ($price >= 1000) {
        return formatNumber($price, 2);
    } elseif ($price >= 1) {
        return formatNumber($price, 4);
    } else {
        return formatNumber($price, 8);
    }
}

// Get user balance
function getUserBalance($user_id, $currency = 'tl') {
    $database = new Database();
    $db = $database->getConnection();
    
    $query = "SELECT balance_" . $currency . " FROM users WHERE id = ?";
    $stmt = $db->prepare($query);
    $stmt->execute([$user_id]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    return $result ? $result['balance_' . $currency] : 0;
}

// Update user balance
function updateUserBalance($user_id, $currency, $amount, $operation = 'add') {
    $database = new Database();
    $db = $database->getConnection();
    
    $operator = $operation == 'add' ? '+' : '-';
    $query = "UPDATE users SET balance_" . $currency . " = balance_" . $currency . " " . $operator . " ? WHERE id = ?";
    $stmt = $db->prepare($query);
    return $stmt->execute([$amount, $user_id]);
}

// Sanitize input
function sanitizeInput($input) {
    return htmlspecialchars(strip_tags(trim($input)));
}

// Generate CSRF token
function generateCSRFToken() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

// Verify CSRF token
function verifyCSRFToken($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

// Log activity
function logActivity($user_id, $action, $details = '') {
    $database = new Database();
    $db = $database->getConnection();
    
    $query = "INSERT INTO activity_logs (user_id, action, details, ip_address, created_at) VALUES (?, ?, ?, ?, NOW())";
    $stmt = $db->prepare($query);
    $stmt->execute([$user_id, $action, $details, $_SERVER['REMOTE_ADDR'] ?? '']);
}

// Get system parameter value
function getSystemParameter($parameter_name, $default = '') {
    $database = new Database();
    $db = $database->getConnection();
    
    $query = "SELECT parameter_value FROM system_parameters WHERE parameter_name = ?";
    $stmt = $db->prepare($query);
    $stmt->execute([$parameter_name]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    return $result ? $result['parameter_value'] : $default;
}

// Set system parameter value
function setSystemParameter($parameter_name, $parameter_value) {
    $database = new Database();
    $db = $database->getConnection();
    
    $query = "INSERT INTO system_parameters (parameter_name, parameter_value, updated_at) 
              VALUES (?, ?, CURRENT_TIMESTAMP)
              ON DUPLICATE KEY UPDATE 
              parameter_value = VALUES(parameter_value), 
              updated_at = CURRENT_TIMESTAMP";
    $stmt = $db->prepare($query);
    return $stmt->execute([$parameter_name, $parameter_value]);
}

// Get current trading currency (1=TL, 2=USD)
function getTradingCurrency() {
    return (int)getSystemParameter('trading_currency', '1');
}

// Get currency symbol for display
function getCurrencySymbol($currency_mode = null) {
    if ($currency_mode === null) {
        $currency_mode = getTradingCurrency();
    }
    
    return $currency_mode == 1 ? 'TL' : 'USD';
}

// Get currency field name for database
function getCurrencyField($currency_mode = null) {
    if ($currency_mode === null) {
        $currency_mode = getTradingCurrency();
    }
    
    return $currency_mode == 1 ? 'tl' : 'usd';
}

// Get USD/TRY rate (simplified version)
function getUSDTRYRate() {
    return (float)getSystemParameter('usdtry_rate', '27.45');
}

// Financial market categories
function getFinancialCategories() {
    return [
        'us_stocks' => 'ABD Hisse Senetleri',
        'eu_stocks' => 'Avrupa Hisse Senetleri', 
        'world_stocks' => 'Dünya Hisse Senetleri',
        'commodities' => 'Emtialar',
        'forex_major' => 'Forex Majör Çiftler',
        'forex_minor' => 'Forex Minör Çiftler',
        'forex_exotic' => 'Forex Egzotik Çiftler',
        'indices' => 'Dünya Endeksleri'
    ];
}

// Get base price for symbol (simplified)
function getBasePriceForSymbol($symbol, $category) {
    $prices = [
        'us_stocks' => [
            'AAPL' => 175.00, 'MSFT' => 338.00, 'GOOGL' => 138.00, 'AMZN' => 145.00, 'TSLA' => 248.00
        ],
        'forex_major' => [
            'EURUSD=X' => 1.0925, 'GBPUSD=X' => 1.2785, 'USDJPY=X' => 148.25
        ],
        'forex_exotic' => [
            'USDTRY=X' => 27.45, 'EURTRY=X' => 29.95, 'GBPTRY=X' => 35.15
        ]
    ];
    
    return $prices[$category][$symbol] ?? 100.00;
}

// Get company name (simplified)
function getCompanyName($symbol, $category) {
    $names = [
        'us_stocks' => [
            'AAPL' => 'Apple Inc.', 'MSFT' => 'Microsoft Corporation', 'GOOGL' => 'Alphabet Inc.'
        ],
        'forex_major' => [
            'EURUSD=X' => 'EUR/USD', 'GBPUSD=X' => 'GBP/USD', 'USDJPY=X' => 'USD/JPY'
        ],
        'forex_exotic' => [
            'USDTRY=X' => 'USD/TRY', 'EURTRY=X' => 'EUR/TRY', 'GBPTRY=X' => 'GBP/TRY'
        ]
    ];
    
    return $names[$category][$symbol] ?? $symbol;
}

// Get category symbols (simplified)
function getCategorySymbols($category) {
    $symbols = [
        'us_stocks' => ['AAPL', 'MSFT', 'GOOGL', 'AMZN', 'TSLA'],
        'forex_major' => ['EURUSD=X', 'GBPUSD=X', 'USDJPY=X'],
        'forex_exotic' => ['USDTRY=X', 'EURTRY=X', 'GBPTRY=X']
    ];
    
    return $symbols[$category] ?? [];
}
?>

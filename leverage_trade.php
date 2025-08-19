<?php
require_once 'includes/functions.php';

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Require login for leverage trading
if (!isLoggedIn()) {
    $_SESSION['leverage_error'] = 'Giriş yapmanız gerekiyor.';
    header('Location: login.php');
    exit();
}

// Only handle POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $_SESSION['leverage_error'] = 'Geçersiz istek.';
    header('Location: markets.php');
    exit();
}

// Get user info first
$user_id = $_SESSION['user_id'];

// Get form data
$leverage_action = $_POST['leverage_action'] ?? '';
$symbol = $_POST['symbol'] ?? '';
$entry_price = (float)($_POST['entry_price'] ?? 0);
$collateral_amount = (float)($_POST['collateral_amount'] ?? 0);
$leverage_ratio = (int)($_POST['leverage_ratio'] ?? 1);
$trade_type = strtoupper($_POST['trade_type'] ?? '');

// Debug logging
error_log("LEVERAGE TRADE DEBUG: Starting leverage trade for user $user_id");
error_log("LEVERAGE TRADE DEBUG: symbol=$symbol, entry_price=$entry_price, collateral=$collateral_amount, leverage=$leverage_ratio, type=$trade_type");

// Validate input
if ($leverage_action !== 'open' || empty($symbol) || $entry_price <= 0 || $collateral_amount <= 0 || $leverage_ratio < 1 || !in_array($trade_type, ['LONG', 'SHORT'])) {
    error_log("LEVERAGE TRADE DEBUG: Validation failed");
    $_SESSION['leverage_error'] = 'Geçersiz işlem parametreleri.';
    header('Location: markets.php');
    exit();
}

try {
    $database = new Database();
    $db = $database->getConnection();
    
    if (!$db) {
        throw new Exception('Veritabanı bağlantısı başarısız.');
    }
    
    error_log("LEVERAGE TRADE DEBUG: Database connection OK");
    
    // 1. Check if leverage tables exist
    $tables_check = $db->query("SHOW TABLES LIKE 'leverage_positions'")->rowCount();
    if ($tables_check == 0) {
        throw new Exception('Kaldıraç tabloları henüz oluşturulmamış. Lütfen önce setup-leverage-database.php sayfasından tabloları oluşturun.');
    }
    error_log("LEVERAGE TRADE DEBUG: Tables exist OK");
    
    // 2. Check user balance (USD only for leverage)
    $user_balance = getUserBalance($user_id, 'usd');
    error_log("LEVERAGE TRADE DEBUG: Current USD balance = $user_balance");
    
    // 3. Calculate position details
    $position_size = $collateral_amount * $leverage_ratio;
    $trading_fee = $position_size * 0.001; // 0.1% fee
    $total_required = $collateral_amount + $trading_fee;
    error_log("LEVERAGE TRADE DEBUG: Calculations - position_size=$position_size, fee=$trading_fee, total_required=$total_required");
    
    // 4. Validate balance
    if ($user_balance < $total_required) {
        throw new Exception('Yetersiz bakiye. Gerekli: $' . number_format($total_required, 2) . ', Mevcut: $' . number_format($user_balance, 2));
    }
    
    // 5. Calculate liquidation price
    if ($trade_type === 'LONG') {
        $liquidation_price = $entry_price * (1 - (1 / $leverage_ratio));
    } else { // SHORT
        $liquidation_price = $entry_price * (1 + (1 / $leverage_ratio));
    }
    error_log("LEVERAGE TRADE DEBUG: Liquidation price calculated = $liquidation_price");
    
    // 6. Update user balance FIRST (to avoid foreign key issues)
    $balance_updated = updateUserBalance($user_id, 'usd', $total_required, 'subtract');
    if (!$balance_updated) {
        throw new Exception('Bakiye güncellenemedi.');
    }
    error_log("LEVERAGE TRADE DEBUG: Balance updated successfully");
    
    // 7. Insert leverage position (without foreign key constraints for now)
    $insert_position_sql = "
        INSERT INTO leverage_positions (
            user_id, symbol, collateral, leverage_ratio, position_size, 
            entry_price, liquidation_price, trade_type, trading_fee, margin_used
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ";
    
    $stmt = $db->prepare($insert_position_sql);
    $success = $stmt->execute([
        $user_id,
        $symbol,
        $collateral_amount,
        $leverage_ratio,
        $position_size,
        $entry_price,
        $liquidation_price,
        $trade_type,
        $trading_fee,
        $collateral_amount
    ]);
    
    if (!$success) {
        // Rollback balance update
        updateUserBalance($user_id, 'usd', $total_required, 'add');
        throw new Exception('Pozisyon kaydedilemedi: ' . implode(', ', $stmt->errorInfo()));
    }
    
    $position_id = $db->lastInsertId();
    error_log("LEVERAGE TRADE DEBUG: Position inserted with ID = $position_id");
    
    // 8. Insert leverage transaction
    $insert_transaction_sql = "
        INSERT INTO leverage_transactions (
            user_id, position_id, type, symbol, amount, price, fee, 
            leverage_ratio, trade_type
        ) VALUES (?, ?, 'OPEN', ?, ?, ?, ?, ?, ?)
    ";
    
    $stmt = $db->prepare($insert_transaction_sql);
    $stmt->execute([
        $user_id,
        $position_id,
        $symbol,
        $collateral_amount,
        $entry_price,
        $trading_fee,
        $leverage_ratio,
        $trade_type
    ]);
    error_log("LEVERAGE TRADE DEBUG: Transaction recorded");
    
    // 9. Log in regular transactions table (optional, best effort)
    try {
        $log_transaction_sql = "
            INSERT INTO transactions (
                user_id, type, symbol, amount, price, total, fee, created_at
            ) VALUES (?, ?, ?, ?, ?, ?, ?, NOW())
        ";
        
        $transaction_type = $trade_type === 'LONG' ? 'LEVERAGE_LONG' : 'LEVERAGE_SHORT';
        
        $stmt = $db->prepare($log_transaction_sql);
        $stmt->execute([
            $user_id,
            $transaction_type,
            $symbol,
            $collateral_amount,
            $entry_price,
            $total_required,
            $trading_fee
        ]);
        error_log("LEVERAGE TRADE DEBUG: General transaction logged");
    } catch (Exception $log_error) {
        error_log("LEVERAGE TRADE DEBUG: Failed to log general transaction: " . $log_error->getMessage());
        // Continue anyway, this is not critical
    }
    
    // Success message
    $trade_action_text = $trade_type === 'LONG' ? 'LONG POZİSYONU AÇILDI' : 'SHORT POZİSYONU AÇILDI';
    $detailed_message = "$collateral_amount USD teminat ile ${leverage_ratio}x $symbol $trade_action_text";
    
    $_SESSION['leverage_success'] = $detailed_message;
    error_log("LEVERAGE TRADE DEBUG: SUCCESS - redirecting to portfolio.php");
    header('Location: portfolio.php');
    exit();
    
} catch (Exception $e) {
    error_log("LEVERAGE TRADE DEBUG: ERROR - " . $e->getMessage());
    $_SESSION['leverage_error'] = 'Kaldıraç işlemi başarısız: ' . $e->getMessage();
    header('Location: markets.php');
    exit();
}
?>

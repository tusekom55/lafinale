<?php
require_once 'includes/functions.php';

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Require login
if (!isLoggedIn()) {
    $_SESSION['leverage_error'] = 'Giriş yapmanız gerekiyor.';
    header('Location: login.php');
    exit();
}

// Only handle POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $_SESSION['leverage_error'] = 'Geçersiz istek.';
    header('Location: portfolio.php');
    exit();
}

// Get user info
$user_id = $_SESSION['user_id'];

// Get form data
$position_id = (int)($_POST['position_id'] ?? 0);
$close_price = (float)($_POST['close_price'] ?? 0);

// Debug logging
error_log("CLOSE LEVERAGE DEBUG: Starting close for user $user_id, position $position_id, price $close_price");

// Validate input
if ($position_id <= 0 || $close_price <= 0) {
    error_log("CLOSE LEVERAGE DEBUG: Validation failed");
    $_SESSION['leverage_error'] = 'Geçersiz parametreler.';
    header('Location: portfolio.php');
    exit();
}

try {
    $database = new Database();
    $db = $database->getConnection();
    
    if (!$db) {
        throw new Exception('Veritabanı bağlantısı başarısız.');
    }
    
    error_log("CLOSE LEVERAGE DEBUG: Database connection OK");
    
    // 1. Get position details
    $query = "SELECT * FROM leverage_positions WHERE id = ? AND user_id = ? AND status = 'OPEN'";
    $stmt = $db->prepare($query);
    $stmt->execute([$position_id, $user_id]);
    $position = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$position) {
        throw new Exception('Pozisyon bulunamadı veya zaten kapalı.');
    }
    
    error_log("CLOSE LEVERAGE DEBUG: Position found - symbol={$position['symbol']}, type={$position['trade_type']}, entry={$position['entry_price']}");
    
    // 2. Calculate final PnL
    $price_change = $close_price - $position['entry_price'];
    if ($position['trade_type'] === 'SHORT') {
        $price_change = -$price_change; // Invert for short positions
    }
    $realized_pnl = ($price_change / $position['entry_price']) * $position['position_size'];
    
    // 3. Calculate closing fee
    $closing_fee = $position['position_size'] * 0.001; // 0.1% fee
    $net_pnl = $realized_pnl - $closing_fee;
    
    error_log("CLOSE LEVERAGE DEBUG: PnL calculated - realized_pnl=$realized_pnl, closing_fee=$closing_fee, net_pnl=$net_pnl");
    
    // 4. Update position status
    $update_position_sql = "UPDATE leverage_positions SET 
                            status = 'CLOSED', 
                            realized_pnl = ?, 
                            unrealized_pnl = 0,
                            closed_at = CURRENT_TIMESTAMP 
                            WHERE id = ?";
    $stmt = $db->prepare($update_position_sql);
    $position_updated = $stmt->execute([$realized_pnl, $position_id]);
    
    if (!$position_updated) {
        throw new Exception('Pozisyon güncellenemedi.');
    }
    
    error_log("CLOSE LEVERAGE DEBUG: Position updated successfully");
    
    // 5. Add closing transaction
    $insert_transaction_sql = "INSERT INTO leverage_transactions (
                                user_id, position_id, type, symbol, amount, price, fee, 
                                pnl, leverage_ratio, trade_type
                              ) VALUES (?, ?, 'CLOSE', ?, ?, ?, ?, ?, ?, ?)";
    $stmt = $db->prepare($insert_transaction_sql);
    $stmt->execute([
        $user_id, $position_id, $position['symbol'], $position['collateral'],
        $close_price, $closing_fee, $realized_pnl, $position['leverage_ratio'], $position['trade_type']
    ]);
    
    error_log("CLOSE LEVERAGE DEBUG: Closing transaction recorded");
    
    // 6. Return collateral + PnL to user balance
    $return_amount = $position['collateral'] + $net_pnl;
    if ($return_amount > 0) {
        $balance_updated = updateUserBalance($user_id, 'usd', $return_amount, 'add');
        if (!$balance_updated) {
            throw new Exception('Bakiye güncellenemedi.');
        }
        error_log("CLOSE LEVERAGE DEBUG: Balance updated - returned $return_amount USD");
    } else {
        error_log("CLOSE LEVERAGE DEBUG: No balance to return (return_amount=$return_amount)");
    }
    
    // 7. Log transaction in general transactions table (optional, best effort)
    try {
        $transaction_type = $position['trade_type'] === 'LONG' ? 'CLOSE_LONG' : 'CLOSE_SHORT';
        $log_transaction_sql = "INSERT INTO transactions (
                                user_id, type, symbol, amount, price, total, fee, created_at
                              ) VALUES (?, ?, ?, ?, ?, ?, ?, NOW())";
        $stmt = $db->prepare($log_transaction_sql);
        $stmt->execute([
            $user_id, $transaction_type, $position['symbol'], $position['collateral'],
            $close_price, $return_amount, $closing_fee
        ]);
        error_log("CLOSE LEVERAGE DEBUG: General transaction logged");
    } catch (Exception $log_error) {
        error_log("CLOSE LEVERAGE DEBUG: Failed to log general transaction: " . $log_error->getMessage());
        // Continue anyway, this is not critical
    }
    
    // Success message
    $trade_action_text = $position['trade_type'] === 'LONG' ? 'LONG POZİSYONU KAPATILDI' : 'SHORT POZİSYONU KAPATILDI';
    $pnl_text = $realized_pnl >= 0 ? '+$' . number_format($realized_pnl, 2) . ' KAR' : '-$' . number_format(abs($realized_pnl), 2) . ' ZARAR';
    $detailed_message = "{$position['symbol']} $trade_action_text - $pnl_text";
    
    $_SESSION['leverage_success'] = $detailed_message;
    error_log("CLOSE LEVERAGE DEBUG: SUCCESS - $detailed_message");
    header('Location: portfolio.php');
    exit();
    
} catch (Exception $e) {
    error_log("CLOSE LEVERAGE DEBUG: ERROR - " . $e->getMessage());
    $_SESSION['leverage_error'] = 'Pozisyon kapatma başarısız: ' . $e->getMessage();
    header('Location: portfolio.php');
    exit();
}
?>

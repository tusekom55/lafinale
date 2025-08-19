<?php
// STANDALONE API - Hiçbir include yok
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

// Session start
@session_start();

// Basit login check
if (!isset($_SESSION['user_id'])) {
    echo json_encode([
        'success' => false,
        'error' => 'User not logged in',
        'step' => 'login_check_failed'
    ]);
    exit;
}

// Input validation
$input = json_decode(file_get_contents('php://input'), true);

if (!$input || !isset($input['symbol'])) {
    echo json_encode([
        'success' => false,
        'error' => 'Symbol parameter required',
        'step' => 'input_validation_failed'
    ]);
    exit;
}

$symbol = $input['symbol'];
$user_id = $_SESSION['user_id'];

try {
    // Direct database connection - doğru credentials
    $db = new PDO(
        'mysql:host=localhost;dbname=u225998063_hurrra;charset=utf8', 
        'u225998063_seccc', 
        '123456Tubb',
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
    
    // Check portfolio
    $query = "SELECT * FROM user_portfolio WHERE user_id = ? AND symbol = ?";
    $stmt = $db->prepare($query);
    $stmt->execute([$user_id, $symbol]);
    $holding = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($holding && isset($holding['quantity']) && $holding['quantity'] > 0) {
        echo json_encode([
            'success' => true,
            'holding' => [
                'symbol' => $holding['symbol'],
                'quantity' => (float)$holding['quantity'],
                'avg_price' => (float)$holding['avg_price'],
                'total_invested' => (float)$holding['total_invested']
            ],
            'step' => 'portfolio_found'
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'error' => 'No holding found for this symbol',
            'step' => 'no_holding_found',
            'user_id' => $user_id,
            'symbol' => $symbol
        ]);
    }
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => 'Database error: ' . $e->getMessage(),
        'step' => 'database_error'
    ]);
}
?>

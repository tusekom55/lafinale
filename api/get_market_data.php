<?php
// Prevent any output before JSON
ob_start();

header('Content-Type: application/json');
header('Cache-Control: no-cache, must-revalidate');

try {
    // Clear any previous output
    ob_clean();
    
    require_once '../includes/functions.php';
    
    $category = $_GET['category'] ?? 'crypto_tl';
    $limit = (int)($_GET['limit'] ?? 50);
    
    // Validate category
    $valid_categories = ['crypto_tl', 'crypto_usd', 'forex'];
    if (!in_array($category, $valid_categories)) {
        $category = 'crypto_tl';
    }
    
    // Get market data
    $markets = getMarketData($category, $limit);
    
    $response = [
        'success' => true,
        'markets' => $markets,
        'count' => count($markets),
        'category' => $category,
        'timestamp' => time()
    ];
    
    echo json_encode($response);
    
} catch (Exception $e) {
    // Clear any previous output
    ob_clean();
    
    $error_response = [
        'success' => false,
        'error' => $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine()
    ];
    
    echo json_encode($error_response);
}

// End output buffering
ob_end_flush();
?>

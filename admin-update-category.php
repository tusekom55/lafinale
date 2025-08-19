<?php
require_once 'includes/functions.php';

header('Content-Type: application/json');

// Basit authentication check
if (!isset($_SESSION['admin_logged_in']) || !$_SESSION['admin_logged_in']) {
    echo json_encode(['success' => false, 'error' => 'Yetkisiz erişim']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Geçersiz istek metodu']);
    exit;
}

$category = $_POST['category'] ?? '';

if (empty($category)) {
    echo json_encode(['success' => false, 'error' => 'Kategori belirtilmedi']);
    exit;
}

// Validate category
$valid_categories = array_keys(getFinancialCategories());
if (!in_array($category, $valid_categories)) {
    echo json_encode(['success' => false, 'error' => 'Geçersiz kategori']);
    exit;
}

// Check API key
if (TWELVE_DATA_API_KEY === 'demo') {
    echo json_encode(['success' => false, 'error' => 'Demo mode - gerçek API key gerekli']);
    exit;
}

try {
    $symbols = getCategorySymbols($category);
    
    if (empty($symbols)) {
        echo json_encode(['success' => false, 'error' => 'Bu kategori için sembol bulunamadı']);
        exit;
    }
    
    // Batch processing - 10'ar sembol grupları halinde işle
    $batchSize = 10;
    $batches = array_chunk($symbols, $batchSize);
    $totalUpdated = 0;
    $totalRequests = 0;
    
    $database = new Database();
    $db = $database->getConnection();
    
    foreach ($batches as $batchIndex => $batch) {
        // Rate limiting - ilk batch değilse bekle
        if ($batchIndex > 0) {
            sleep(1); // 1 saniye bekle
        }
        
        $batchString = implode(',', $batch);
        $url = TWELVE_DATA_API_URL . "/quote?symbol={$batchString}&apikey=" . TWELVE_DATA_API_KEY;
        
        $context = stream_context_create([
            'http' => [
                'timeout' => 15,
                'user_agent' => 'GlobalBorsa/2.0'
            ]
        ]);
        
        $response = @file_get_contents($url, false, $context);
        $totalRequests++;
        
        if ($response === false) {
            continue; // Bu batch'i atla, devam et
        }
        
        $data = json_decode($response, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            continue; // JSON hatası, devam et
        }
        
        // API response kontrolü
        if (isset($data['code'])) {
            // Rate limit veya diğer hatalar
            if ($data['code'] === 429) {
                echo json_encode(['success' => false, 'error' => 'API rate limit aşıldı']);
                exit;
            }
            continue;
        }
        
        // Single symbol vs multiple symbols
        $instruments = count($batch) === 1 ? [$data] : $data;
        
        if (!is_array($instruments)) {
            continue;
        }
        
        foreach ($instruments as $instrument) {
            if (!isset($instrument['symbol'])) continue;
            
            $symbol = $instrument['symbol'];
            $name = $instrument['name'] ?? $symbol;
            $price = floatval($instrument['close'] ?? $instrument['price'] ?? 0);
            $change = floatval($instrument['change'] ?? 0);
            $change_percent = floatval($instrument['percent_change'] ?? 0);
            $volume = floatval($instrument['volume'] ?? 0);
            $high = floatval($instrument['high'] ?? $price);
            $low = floatval($instrument['low'] ?? $price);
            $market_cap = floatval($instrument['market_cap'] ?? $price * 1000000); // Fake market cap
            
            $query = "INSERT INTO markets (symbol, name, price, change_24h, volume_24h, high_24h, low_24h, market_cap, category, logo_url, created_at, updated_at) 
                      VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, '', NOW(), NOW())
                      ON DUPLICATE KEY UPDATE 
                      name = VALUES(name),
                      price = VALUES(price), 
                      change_24h = VALUES(change_24h), 
                      volume_24h = VALUES(volume_24h), 
                      high_24h = VALUES(high_24h), 
                      low_24h = VALUES(low_24h), 
                      market_cap = VALUES(market_cap),
                      updated_at = NOW()";
            
            $stmt = $db->prepare($query);
            $success = $stmt->execute([$symbol, $name, $price, $change_percent, $volume, $high, $low, $market_cap, $category]);
            
            if ($success) {
                $totalUpdated++;
            }
        }
    }
    
    echo json_encode([
        'success' => true,
        'updated' => $totalUpdated,
        'total' => count($symbols),
        'requests' => $totalRequests,
        'category' => $category
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false, 
        'error' => 'Veritabanı hatası: ' . $e->getMessage()
    ]);
}
?>

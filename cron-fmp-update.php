<?php
/**
 * CRON JOB for FMP Batch Updates
 * Ultra-optimized version with minimal API calls
 * 
 * Usage:
 * - Via cPanel: Add this file to Cron Jobs
 * - Via command line: 0 8 * * * /usr/bin/php /path/to/cron-fmp-update.php
 * - Via URL: 0 8 * * * curl -s https://yourdomain.com/cron-fmp-update.php
 */

// Only allow CLI or specific token access
$allowed_token = 'globalborsa_cron_2024_secure';
$token = $_GET['token'] ?? '';

if (php_sapi_name() !== 'cli' && $token !== $allowed_token) {
    http_response_code(403);
    exit('Access denied. Use: ?token=' . $allowed_token);
}

// Set execution limits
set_time_limit(120);
ini_set('memory_limit', '256M');
error_reporting(E_ALL);

// Load functions
require_once __DIR__ . '/includes/functions.php';

// Log function
function logCron($message) {
    $timestamp = date('Y-m-d H:i:s');
    $log = "[$timestamp] $message" . PHP_EOL;
    file_put_contents(__DIR__ . '/logs/cron-fmp.log', $log, FILE_APPEND | LOCK_EX);
    echo $log;
}

// Create logs directory if not exists
if (!is_dir(__DIR__ . '/logs')) {
    mkdir(__DIR__ . '/logs', 0755, true);
}

logCron("=== FMP Cron Update Started ===");

/**
 * ULTRA-OPTIMIZED BATCH UPDATE
 * Maximum 3-4 API calls total!
 */
function ultraOptimizedBatchUpdate() {
    $database = new Database();
    $db = $database->getConnection();
    
    $results = [
        'total_requests' => 0,
        'updated_symbols' => 0,
        'errors' => []
    ];
    
    try {
        // OPTIMIZATION 1: Limit symbols per category to reduce API calls
        $limits = [
            'us_stocks' => 20,     // Top 20 US stocks only
            'commodities' => 8,    // Top 8 commodities
            'indices' => 6,        // Top 6 indices
            'forex_major' => 6     // Top 6 forex pairs only
        ];
        
        // BATCH 1: US Stocks (1 API call)
        logCron("Processing US Stocks...");
        $query = "SELECT symbol, fmp_symbol FROM markets 
                  WHERE category = 'us_stocks' AND fmp_symbol IS NOT NULL 
                  ORDER BY market_cap DESC, volume_24h DESC 
                  LIMIT " . $limits['us_stocks'];
        $stmt = $db->prepare($query);
        $stmt->execute();
        $us_stocks = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (!empty($us_stocks)) {
            $fmp_symbols = array_column($us_stocks, 'fmp_symbol');
            $symbols_string = implode(',', $fmp_symbols);
            
            $result = makeFMPRequest('/quote/' . $symbols_string);
            $results['total_requests']++;
            
            if ($result['success'] && !empty($result['data'])) {
                foreach($result['data'] as $quote) {
                    $fmp_symbol = $quote['symbol'] ?? '';
                    foreach($us_stocks as $row) {
                        if ($row['fmp_symbol'] === $fmp_symbol) {
                            updateMarketRecord($db, $row['symbol'], $quote, 'us_stocks');
                            $results['updated_symbols']++;
                            break;
                        }
                    }
                }
                logCron("US Stocks updated: " . count($result['data']) . " symbols");
            } else {
                $results['errors'][] = 'US stocks failed';
                logCron("ERROR: US Stocks batch failed");
            }
        }
        
        // BATCH 2: Commodities (1 API call)
        logCron("Processing Commodities...");
        $query = "SELECT symbol, fmp_symbol FROM markets 
                  WHERE category = 'commodities' AND fmp_symbol IS NOT NULL 
                  LIMIT " . $limits['commodities'];
        $stmt = $db->prepare($query);
        $stmt->execute();
        $commodities = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (!empty($commodities)) {
            $fmp_symbols = array_column($commodities, 'fmp_symbol');
            $symbols_string = implode(',', $fmp_symbols);
            
            $result = makeFMPRequest('/quote/' . $symbols_string);
            $results['total_requests']++;
            
            if ($result['success'] && !empty($result['data'])) {
                foreach($result['data'] as $quote) {
                    $fmp_symbol = $quote['symbol'] ?? '';
                    foreach($commodities as $row) {
                        if ($row['fmp_symbol'] === $fmp_symbol) {
                            updateMarketRecord($db, $row['symbol'], $quote, 'commodities');
                            $results['updated_symbols']++;
                            break;
                        }
                    }
                }
                logCron("Commodities updated: " . count($result['data']) . " symbols");
            } else {
                $results['errors'][] = 'Commodities failed';
                logCron("ERROR: Commodities batch failed");
            }
        }
        
        // BATCH 3: Indices (1 API call)
        logCron("Processing Indices...");
        $query = "SELECT symbol, fmp_symbol FROM markets 
                  WHERE category = 'indices' AND fmp_symbol IS NOT NULL 
                  LIMIT " . $limits['indices'];
        $stmt = $db->prepare($query);
        $stmt->execute();
        $indices = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (!empty($indices)) {
            $fmp_symbols = array_column($indices, 'fmp_symbol');
            $symbols_string = implode(',', $fmp_symbols);
            
            $result = makeFMPRequest('/quote/' . $symbols_string);
            $results['total_requests']++;
            
            if ($result['success'] && !empty($result['data'])) {
                foreach($result['data'] as $quote) {
                    $fmp_symbol = $quote['symbol'] ?? '';
                    foreach($indices as $row) {
                        if ($row['fmp_symbol'] === $fmp_symbol) {
                            updateMarketRecord($db, $row['symbol'], $quote, 'indices');
                            $results['updated_symbols']++;
                            break;
                        }
                    }
                }
                logCron("Indices updated: " . count($result['data']) . " symbols");
            } else {
                $results['errors'][] = 'Indices failed';
                logCron("ERROR: Indices batch failed");
            }
        }
        
        // BATCH 4: Major Forex (1 API call for USD/TRY only - most important)
        logCron("Processing Key Forex...");
        $result = makeFMPRequest('/fx', ['from' => 'USD', 'to' => 'TRY']);
        $results['total_requests']++;
        
        if ($result['success'] && !empty($result['data'])) {
            $data = is_array($result['data']) ? $result['data'][0] : $result['data'];
            $quote = [
                'symbol' => 'USDTRY',
                'price' => $data['rate'] ?? $data['price'] ?? 0,
                'change' => 0,
                'changesPercentage' => 0,
                'volume' => 0,
                'dayHigh' => $data['rate'] ?? $data['price'] ?? 0,
                'dayLow' => $data['rate'] ?? $data['price'] ?? 0,
                'marketCap' => 0,
                'name' => 'USD/TRY'
            ];
            
            updateMarketRecord($db, 'USDTRY=X', $quote, 'forex_exotic');
            $results['updated_symbols']++;
            logCron("USD/TRY updated successfully");
        } else {
            $results['errors'][] = 'USD/TRY failed';
            logCron("ERROR: USD/TRY failed");
        }
        
    } catch (Exception $e) {
        $results['errors'][] = 'Critical error: ' . $e->getMessage();
        logCron("CRITICAL ERROR: " . $e->getMessage());
    }
    
    return $results;
}

// Run the optimized update
$start_time = microtime(true);
$results = ultraOptimizedBatchUpdate();
$execution_time = round(microtime(true) - $start_time, 2);

// Log results
logCron("=== UPDATE COMPLETED ===");
logCron("Execution time: {$execution_time} seconds");
logCron("Total API requests: {$results['total_requests']}");
logCron("Updated symbols: {$results['updated_symbols']}");
logCron("Errors: " . count($results['errors']));

if (!empty($results['errors'])) {
    logCron("Error details: " . implode(', ', $results['errors']));
}

logCron("Remaining daily quota: " . (100 - $results['total_requests']) . "/100");
logCron("=== END ===\n");

// Output results for web access
if (php_sapi_name() !== 'cli') {
    header('Content-Type: application/json');
    echo json_encode([
        'success' => count($results['errors']) === 0,
        'execution_time' => $execution_time,
        'stats' => $results,
        'message' => 'Cron update completed'
    ], JSON_PRETTY_PRINT);
}

exit(0);
?>

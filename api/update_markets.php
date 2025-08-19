<?php
// This file should be run via cron job every 5 minutes
require_once '../includes/functions.php';

// Set time limit for long running script
set_time_limit(300); // 5 minutes

try {
    echo "Starting market data update...\n";
    
    if (updateMarketData()) {
        echo "Market data updated successfully at " . date('Y-m-d H:i:s') . "\n";
        
        // Get count of updated markets
        $database = new Database();
        $db = $database->getConnection();
        
        $query = "SELECT COUNT(*) as count FROM markets WHERE updated_at >= DATE_SUB(NOW(), INTERVAL 10 MINUTE)";
        $stmt = $db->prepare($query);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        echo "Updated " . $result['count'] . " markets\n";
        
        // Log to file for debugging
        $log_message = date('Y-m-d H:i:s') . " - Market data updated successfully (" . $result['count'] . " markets)\n";
        file_put_contents('../logs/market_updates.log', $log_message, FILE_APPEND | LOCK_EX);
        
    } else {
        echo "Failed to update market data at " . date('Y-m-d H:i:s') . "\n";
        
        // Log error
        $log_message = date('Y-m-d H:i:s') . " - Failed to update market data\n";
        file_put_contents('../logs/market_updates.log', $log_message, FILE_APPEND | LOCK_EX);
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    
    // Log error
    $log_message = date('Y-m-d H:i:s') . " - Error: " . $e->getMessage() . "\n";
    file_put_contents('../logs/market_updates.log', $log_message, FILE_APPEND | LOCK_EX);
}
?>

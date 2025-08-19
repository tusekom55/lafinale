<?php
require_once 'includes/functions.php';

// Require admin access for this fix script
if (!isLoggedIn() || !isAdmin()) {
    echo "Bu sayfaya erişmek için admin girişi gereklidir.";
    exit();
}

$database = new Database();
$db = $database->getConnection();

echo "<html><head><title>Fix Transaction Types</title></head><body>";
echo "<h2>Transaction Types Normalization</h2>";

$fixed_count = 0;
$error_count = 0;

try {
    // Define normalization rules
    $normalization_rules = [
        // Convert various LONG patterns to LEVERAGE_LONG
        'leverage_long' => 'LEVERAGE_LONG',
        'Leverage_Long' => 'LEVERAGE_LONG',
        'long' => 'LEVERAGE_LONG',
        'LONG' => 'LEVERAGE_LONG',
        'open_long' => 'LEVERAGE_LONG',
        'OPEN_LONG' => 'LEVERAGE_LONG',
        
        // Convert various SHORT patterns to LEVERAGE_SHORT
        'leverage_short' => 'LEVERAGE_SHORT',
        'Leverage_Short' => 'LEVERAGE_SHORT',
        'short' => 'LEVERAGE_SHORT',
        'SHORT' => 'LEVERAGE_SHORT',
        'open_short' => 'LEVERAGE_SHORT',
        'OPEN_SHORT' => 'LEVERAGE_SHORT',
        
        // Convert various CLOSE_LONG patterns
        'close_long' => 'CLOSE_LONG',
        'Close_Long' => 'CLOSE_LONG',
        'long_close' => 'CLOSE_LONG',
        'LONG_CLOSE' => 'CLOSE_LONG',
        
        // Convert various CLOSE_SHORT patterns
        'close_short' => 'CLOSE_SHORT',
        'Close_Short' => 'CLOSE_SHORT',
        'short_close' => 'CLOSE_SHORT',
        'SHORT_CLOSE' => 'CLOSE_SHORT',
        
        // Convert buy/sell variations
        'Buy' => 'buy',
        'BUY' => 'buy',
        'Sell' => 'sell',
        'SELL' => 'sell'
    ];
    
    echo "<h3>Applying Normalization Rules:</h3>";
    echo "<ul>";
    
    foreach ($normalization_rules as $from => $to) {
        // Check if this type exists
        $check_query = "SELECT COUNT(*) FROM transactions WHERE type = ?";
        $stmt = $db->prepare($check_query);
        $stmt->execute([$from]);
        $count = $stmt->fetchColumn();
        
        if ($count > 0) {
            // Update the records
            $update_query = "UPDATE transactions SET type = ? WHERE type = ?";
            $stmt = $db->prepare($update_query);
            $success = $stmt->execute([$to, $from]);
            
            if ($success) {
                echo "<li><strong>$from</strong> → <strong>$to</strong> ($count records updated)</li>";
                $fixed_count += $count;
            } else {
                echo "<li style='color: red;'><strong>$from</strong> → <strong>$to</strong> (FAILED)</li>";
                $error_count++;
            }
        }
    }
    
    echo "</ul>";
    
    // Additional pattern-based fixes
    echo "<h3>Pattern-Based Fixes:</h3>";
    echo "<ul>";
    
    // Fix any remaining leverage patterns
    $patterns = [
        ['pattern' => '%leverage%', 'contains' => 'long', 'set_to' => 'LEVERAGE_LONG'],
        ['pattern' => '%leverage%', 'contains' => 'short', 'set_to' => 'LEVERAGE_SHORT'],
        ['pattern' => '%close%', 'contains' => 'long', 'set_to' => 'CLOSE_LONG'],
        ['pattern' => '%close%', 'contains' => 'short', 'set_to' => 'CLOSE_SHORT']
    ];
    
    foreach ($patterns as $pattern_rule) {
        $query = "UPDATE transactions 
                  SET type = ? 
                  WHERE type LIKE ? 
                  AND LOWER(type) LIKE ? 
                  AND type NOT IN ('buy', 'sell', 'LEVERAGE_LONG', 'LEVERAGE_SHORT', 'CLOSE_LONG', 'CLOSE_SHORT')";
        
        $stmt = $db->prepare($query);
        $stmt->execute([
            $pattern_rule['set_to'], 
            $pattern_rule['pattern'], 
            '%' . $pattern_rule['contains'] . '%'
        ]);
        
        $affected = $stmt->rowCount();
        if ($affected > 0) {
            echo "<li>Fixed {$affected} records matching pattern '{$pattern_rule['pattern']}' + '{$pattern_rule['contains']}' → <strong>{$pattern_rule['set_to']}</strong></li>";
            $fixed_count += $affected;
        }
    }
    
    echo "</ul>";
    
    // Show current status after fixes
    echo "<h3>Current Status After Fixes:</h3>";
    $query = "SELECT type, COUNT(*) as count FROM transactions GROUP BY type ORDER BY count DESC";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $types = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $supported_types = ['buy', 'sell', 'LEVERAGE_LONG', 'LEVERAGE_SHORT', 'CLOSE_LONG', 'CLOSE_SHORT'];
    
    echo "<table border='1' style='border-collapse: collapse; margin: 10px 0;'>";
    echo "<tr><th style='padding: 8px;'>Transaction Type</th><th style='padding: 8px;'>Count</th><th style='padding: 8px;'>Status</th></tr>";
    
    foreach ($types as $type_data) {
        $type = $type_data['type'];
        $count = $type_data['count'];
        
        if (in_array($type, $supported_types)) {
            $status = "<span style='color: green;'>✓ Supported</span>";
        } else {
            $status = "<span style='color: red;'>❌ Still Problematic</span>";
        }
        
        echo "<tr>";
        echo "<td style='padding: 8px;'><strong>$type</strong></td>";
        echo "<td style='padding: 8px;'>$count</td>";
        echo "<td style='padding: 8px;'>$status</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    echo "<div style='margin: 20px 0; padding: 15px; background-color: #d4edda; border: 1px solid #c3e6cb; border-radius: 5px;'>";
    echo "<h4 style='color: #155724; margin: 0 0 10px 0;'>Summary:</h4>";
    echo "<p style='margin: 5px 0; color: #155724;'>✓ Fixed <strong>$fixed_count</strong> transaction records</p>";
    if ($error_count > 0) {
        echo "<p style='margin: 5px 0; color: #721c24;'>❌ <strong>$error_count</strong> errors occurred</p>";
    }
    echo "<p style='margin: 5px 0; color: #155724;'>🎯 Question mark issue in portfolio should now be resolved!</p>";
    echo "</div>";

} catch (Exception $e) {
    echo "<div style='margin: 20px 0; padding: 15px; background-color: #f8d7da; border: 1px solid #f5c6cb; border-radius: 5px;'>";
    echo "<h4 style='color: #721c24; margin: 0 0 10px 0;'>Error:</h4>";
    echo "<p style='color: #721c24;'>" . $e->getMessage() . "</p>";
    echo "</div>";
}

echo "<br><a href='portfolio.php'>← Test Portfolio Page</a> | ";
echo "<a href='debug-transaction-types.php'>→ Check Transaction Types</a>";
echo "</body></html>";
?>

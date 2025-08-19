<?php
require_once 'includes/functions.php';

echo "<h2>Database Update for New Categories</h2>";

try {
    $database = new Database();
    $db = $database->getConnection();
    
    // Update markets table category enum
    echo "<p>1. Updating markets table category field...</p>";
    $query = "ALTER TABLE markets MODIFY COLUMN category ENUM(
        'us_stocks',
        'eu_stocks', 
        'world_stocks',
        'commodities',
        'forex_major',
        'forex_minor',
        'forex_exotic',
        'indices'
    ) DEFAULT 'us_stocks'";
    
    $db->exec($query);
    echo "<div style='background: #d4edda; padding: 10px; margin: 10px 0; border-radius: 5px;'>";
    echo "✅ Markets table updated successfully";
    echo "</div>";
    
    // Clean old data
    echo "<p>2. Cleaning old data...</p>";
    $query = "DELETE FROM markets WHERE category NOT IN (
        'us_stocks', 'eu_stocks', 'world_stocks', 'commodities',
        'forex_major', 'forex_minor', 'forex_exotic', 'indices'
    )";
    
    $result = $db->exec($query);
    echo "<div style='background: #d4edda; padding: 10px; margin: 10px 0; border-radius: 5px;'>";
    echo "✅ Cleaned $result old records";
    echo "</div>";
    
    // Add unique constraint (ignore if already exists)
    echo "<p>3. Adding unique constraint...</p>";
    try {
        $query = "ALTER TABLE markets ADD UNIQUE KEY unique_symbol (symbol)";
        $db->exec($query);
        echo "<div style='background: #d4edda; padding: 10px; margin: 10px 0; border-radius: 5px;'>";
        echo "✅ Unique constraint added";
        echo "</div>";
    } catch (Exception $e) {
        echo "<div style='background: #fff3cd; padding: 10px; margin: 10px 0; border-radius: 5px;'>";
        echo "⚠️ Unique constraint already exists or error: " . $e->getMessage();
        echo "</div>";
    }
    
    echo "<h3>Database Update Complete!</h3>";
    echo "<p><strong>Next Step:</strong> Now you can run <a href='yahoo-api-test.php'>yahoo-api-test.php</a> to populate the markets table with demo data.</p>";
    
    // Show current table structure
    echo "<h4>Current Markets Table Structure:</h4>";
    $query = "DESCRIBE markets";
    $stmt = $db->query($query);
    echo "<table border='1' style='border-collapse: collapse; margin: 10px 0;'>";
    echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th></tr>";
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo "<tr>";
        echo "<td>{$row['Field']}</td>";
        echo "<td>{$row['Type']}</td>";
        echo "<td>{$row['Null']}</td>";
        echo "<td>{$row['Key']}</td>";
        echo "<td>{$row['Default']}</td>";
        echo "</tr>";
    }
    echo "</table>";
    
} catch (Exception $e) {
    echo "<div style='background: #f8d7da; padding: 10px; margin: 10px 0; border-radius: 5px;'>";
    echo "❌ Error: " . $e->getMessage();
    echo "</div>";
}
?>

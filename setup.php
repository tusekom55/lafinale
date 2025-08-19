<?php
require_once 'config/database.php';

// This file should be run once to set up the database
echo "<h1>GlobalBorsa Database Setup</h1>";

try {
    // Create tables
    createTables();
    
    echo "<div style='color: green; margin: 20px 0;'>";
    echo "<h2>✅ Database setup completed successfully!</h2>";
    echo "<p>The following tables have been created:</p>";
    echo "<ul>";
    echo "<li>users (with admin user: admin/admin123)</li>";
    echo "<li>markets</li>";
    echo "<li>transactions</li>";
    echo "<li>deposits</li>";
    echo "<li>withdrawals</li>";
    echo "</ul>";
    echo "</div>";
    
    // Try to fetch some sample market data
    echo "<h3>Fetching sample market data...</h3>";
    
    require_once 'includes/functions.php';
    
    if (updateMarketData()) {
        echo "<div style='color: green;'>✅ Market data fetched successfully from CoinGecko API!</div>";
        
        // Show some sample data
        $markets = getMarketData('crypto_tl', 5);
        if (!empty($markets)) {
            echo "<h4>Sample Market Data:</h4>";
            echo "<table border='1' style='border-collapse: collapse; margin: 10px 0;'>";
            echo "<tr><th>Symbol</th><th>Name</th><th>Price (TL)</th><th>24h Change</th></tr>";
            foreach ($markets as $market) {
                echo "<tr>";
                echo "<td>" . htmlspecialchars($market['symbol']) . "</td>";
                echo "<td>" . htmlspecialchars($market['name']) . "</td>";
                echo "<td>" . number_format($market['price'], 2) . " TL</td>";
                echo "<td style='color: " . ($market['change_24h'] >= 0 ? 'green' : 'red') . ";'>";
                echo ($market['change_24h'] >= 0 ? '+' : '') . number_format($market['change_24h'], 2) . "%";
                echo "</td>";
                echo "</tr>";
            }
            echo "</table>";
        }
    } else {
        echo "<div style='color: orange;'>⚠️ Could not fetch market data. This is normal if you don't have internet connection.</div>";
    }
    
    echo "<div style='background: #f0f8ff; padding: 20px; margin: 20px 0; border-left: 4px solid #007bff;'>";
    echo "<h3>🚀 Next Steps:</h3>";
    echo "<ol>";
    echo "<li><strong>Delete this setup.php file</strong> for security</li>";
    echo "<li>Update database credentials in <code>config/database.php</code> for your hosting</li>";
    echo "<li>Update API keys in <code>config/api_keys.php</code> if needed</li>";
    echo "<li>Visit <a href='index.php'>index.php</a> to see the market page</li>";
    echo "<li>Login with: <strong>admin</strong> / <strong>admin123</strong></li>";
    echo "</ol>";
    echo "</div>";
    
    echo "<div style='background: #fff3cd; padding: 15px; margin: 20px 0; border-left: 4px solid #ffc107;'>";
    echo "<h4>⚙️ For Hostinger Deployment:</h4>";
    echo "<ul>";
    echo "<li>Upload all files to your <code>public_html</code> folder</li>";
    echo "<li>Create a MySQL database in Hostinger cPanel</li>";
    echo "<li>Update database credentials in <code>config/database.php</code></li>";
    echo "<li>Run this setup.php once, then delete it</li>";
    echo "<li>Set up a cron job to run <code>api/update_markets.php</code> every 5 minutes</li>";
    echo "</ul>";
    echo "</div>";
    
} catch (Exception $e) {
    echo "<div style='color: red; margin: 20px 0;'>";
    echo "<h2>❌ Error during setup:</h2>";
    echo "<p>" . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<p>Please check your database configuration in <code>config/database.php</code></p>";
    echo "</div>";
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>GlobalBorsa Setup</title>
    <style>
        body { 
            font-family: Arial, sans-serif; 
            max-width: 800px; 
            margin: 0 auto; 
            padding: 20px; 
            line-height: 1.6;
        }
        code { 
            background: #f4f4f4; 
            padding: 2px 6px; 
            border-radius: 3px; 
        }
        table { 
            width: 100%; 
            margin: 10px 0; 
        }
        th, td { 
            padding: 8px; 
            text-align: left; 
        }
        th { 
            background: #f8f9fa; 
        }
    </style>
</head>
<body>
    <div style="text-align: center; margin: 30px 0;">
        <a href="index.php" style="background: #007bff; color: white; padding: 12px 24px; text-decoration: none; border-radius: 5px; font-weight: bold;">
            🏠 Go to Homepage
        </a>
        <a href="login.php" style="background: #28a745; color: white; padding: 12px 24px; text-decoration: none; border-radius: 5px; font-weight: bold; margin-left: 10px;">
            🔐 Login Page
        </a>
    </div>
</body>
</html>

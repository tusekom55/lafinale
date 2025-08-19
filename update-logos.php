<?php
require_once 'includes/functions.php';

// Update logo URLs for all existing markets
function updateLogosInDatabase() {
    $database = new Database();
    $db = $database->getConnection();
    
    // Get all markets
    $query = "SELECT symbol, category FROM markets";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $markets = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $updated = 0;
    
    foreach ($markets as $market) {
        $symbol = $market['symbol'];
        $category = $market['category'];
        $logo_url = getLogoUrl($symbol, $category);
        
        if ($logo_url) {
            $updateQuery = "UPDATE markets SET logo_url = ? WHERE symbol = ?";
            $updateStmt = $db->prepare($updateQuery);
            $updateStmt->execute([$logo_url, $symbol]);
            $updated++;
        }
    }
    
    return $updated;
}

// Check if we're running from command line or web
if (php_sapi_name() === 'cli') {
    echo "Updating logo URLs...\n";
    $updated = updateLogosInDatabase();
    echo "Updated $updated market logos.\n";
} else {
    // Web interface
    echo "<h2>Logo Güncelleme Scripti</h2>";
    
    if (isset($_GET['update'])) {
        $updated = updateLogosInDatabase();
        echo "<div style='color: green; padding: 10px; border: 1px solid green; margin: 10px 0;'>";
        echo "✅ $updated piyasa logosu güncellendi!";
        echo "</div>";
        
        echo "<p><a href='index.php'>Ana sayfaya dön</a></p>";
    } else {
        echo "<p>Bu script, veritabanındaki tüm piyasa kayıtları için logo URL'lerini güncelleyecek.</p>";
        echo "<p><strong>Güncellenen kategoriler:</strong></p>";
        echo "<ul>";
        foreach (getFinancialCategories() as $key => $name) {
            echo "<li>$name ($key)</li>";
        }
        echo "</ul>";
        
        echo "<p><a href='?update=1' style='background: #007bff; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>Logo Güncellemesini Başlat</a></p>";
        echo "<p><a href='index.php'>Ana sayfaya dön</a></p>";
    }
}
?>

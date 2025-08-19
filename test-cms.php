<?php
// Simple test file to verify CMS functionality
require_once 'includes/functions.php';
require_once 'includes/content_functions.php';
require_once 'config/database.php';

echo "<h1>CMS System Test</h1>";

// Test database connection
try {
    $database = new Database();
    $db = $database->getConnection();
    echo "<p style='color: green;'>✓ Database connection successful</p>";
} catch (Exception $e) {
    echo "<p style='color: red;'>✗ Database connection failed: " . $e->getMessage() . "</p>";
    exit;
}

// Test if CMS tables exist
try {
    $stmt = $db->query("SHOW TABLES LIKE 'homepage_content'");
    if ($stmt->rowCount() > 0) {
        echo "<p style='color: green;'>✓ Homepage content table exists</p>";
    } else {
        echo "<p style='color: orange;'>⚠ Homepage content table does not exist. Please run create-homepage-content-table.sql</p>";
    }
} catch (Exception $e) {
    echo "<p style='color: red;'>✗ Error checking tables: " . $e->getMessage() . "</p>";
}

// Test CMS functions
try {
    if (function_exists('isCMSEnabled')) {
        echo "<p style='color: green;'>✓ CMS functions loaded</p>";
        
        // Test if CMS is enabled
        if (isCMSEnabled($db)) {
            echo "<p style='color: green;'>✓ CMS system is enabled and working</p>";
            
            // Test content loading
            $current_lang = getCurrentLang();
            $homepage_content = getAllHomepageContent($db, $current_lang);
            
            if (!empty($homepage_content)) {
                echo "<p style='color: green;'>✓ Content loaded successfully for language: $current_lang</p>";
                echo "<p>Available sections: " . implode(', ', array_keys($homepage_content)) . "</p>";
            } else {
                echo "<p style='color: orange;'>⚠ No content found. Please add content via admin panel at admin_content.php</p>";
            }
        } else {
            echo "<p style='color: orange;'>⚠ CMS system is not enabled or no content exists</p>";
        }
    } else {
        echo "<p style='color: red;'>✗ CMS functions not found</p>";
    }
} catch (Exception $e) {
    echo "<p style='color: red;'>✗ Error testing CMS: " . $e->getMessage() . "</p>";
}

echo "<hr>";
echo "<h2>Next Steps:</h2>";
echo "<ol>";
echo "<li>If homepage content table doesn't exist, run the SQL file: <code>create-homepage-content-table.sql</code></li>";
echo "<li>Access the admin panel: <a href='admin_content.php'>admin_content.php</a></li>";
echo "<li>Add content for your website sections</li>";
echo "<li>Visit the homepage: <a href='index.php'>index.php</a> to see dynamic content</li>";
echo "</ol>";

echo "<h2>CMS Features Implemented:</h2>";
echo "<ul>";
echo "<li>✓ Hero section dynamic content</li>";
echo "<li>✓ Features section dynamic content</li>";
echo "<li>✓ Markets ticker section dynamic content</li>";
echo "<li>✓ Education section dynamic content</li>";
echo "<li>✓ CTA section dynamic content</li>";
echo "<li>✓ Multi-language support (Turkish/English)</li>";
echo "<li>✓ Fallback content system</li>";
echo "<li>✓ Admin interface for content management</li>";
echo "</ul>";
?>

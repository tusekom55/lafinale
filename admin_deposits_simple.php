<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();

// Test database connection first
echo "Starting admin deposits page...<br>";

try {
    require_once 'config/database.php';
    echo "Database config loaded successfully<br>";
    
    $database = new Database();
    echo "Database instance created<br>";
    
    $db = $database->getConnection();
    echo "Database connection established<br>";
    
    // Test basic query
    $query = "SELECT COUNT(*) as count FROM deposits";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "Deposits count: " . $result['count'] . "<br>";
    
    echo "All tests passed! Database is working.";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "<br>";
    echo "File: " . $e->getFile() . " Line: " . $e->getLine();
}
?>

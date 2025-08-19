<?php
session_start();

// Simulate login for testing
if (!isset($_SESSION['user_id'])) {
    $_SESSION['user_id'] = 1;
    $_SESSION['username'] = 'test_user';
}

echo "<!DOCTYPE html>";
echo "<html>";
echo "<head>";
echo "<title>Form Test Sayfası</title>";
echo "<link href='https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css' rel='stylesheet'>";
echo "</head>";
echo "<body>";

echo "<div class='container mt-5'>";
echo "<h1>FORM TEST SAYFASI</h1>";

// Log area
echo "<div class='row'>";
echo "<div class='col-md-6'>";
echo "<h3>FORM</h3>";

// Check if POST data received
if ($_POST) {
    echo "<div class='alert alert-success'>";
    echo "<h4>✅ POST VERİSİ ALINDI!</h4>";
    echo "<pre>" . print_r($_POST, true) . "</pre>";
    echo "</div>";
    
    // Check specific fields
    $modal_action = $_POST['modal_action'] ?? 'NOT_SET';
    $symbol = $_POST['symbol'] ?? 'NOT_SET';
    $amount = $_POST['amount'] ?? 'NOT_SET';
    $leverage = $_POST['leverage'] ?? 'NOT_SET';
    
    echo "<div class='alert alert-info'>";
    echo "<h5>PARSED VERİLER:</h5>";
    echo "modal_action: " . $modal_action . "<br>";
    echo "symbol: " . $symbol . "<br>";
    echo "amount: " . $amount . "<br>";
    echo "leverage: " . $leverage . "<br>";
    echo "</div>";
    
} else {
    echo "<div class='alert alert-warning'>";
    echo "<h4>⚠️ POST VERİSİ YOK</h4>";
    echo "</div>";
}

// Test form
echo "<form method='POST' action='test-form.php'>";
echo "<div class='mb-3'>";
echo "<label class='form-label'>Symbol:</label>";
echo "<input type='text' class='form-control' name='symbol' value='AAPL' required>";
echo "</div>";

echo "<div class='mb-3'>";
echo "<label class='form-label'>Amount:</label>";
echo "<input type='number' class='form-control' name='amount' value='10' step='0.01' required>";
echo "</div>";

echo "<div class='mb-3'>";
echo "<label class='form-label'>Leverage:</label>";
echo "<input type='range' class='form-range' name='leverage' min='1' max='100' value='1'>";
echo "</div>";

echo "<input type='hidden' name='modal_action' value='buy'>";
echo "<input type='hidden' name='trade_type' value='simple'>";

echo "<button type='submit' class='btn btn-success'>TEST SUBMIT</button>";
echo "</form>";

echo "</div>";

// Log area
echo "<div class='col-md-6'>";
echo "<h3>DEBUG LOGLARI</h3>";

echo "<div class='alert alert-dark'>";
echo "<h5>SERVER BİLGİLERİ:</h5>";
echo "REQUEST_METHOD: " . $_SERVER['REQUEST_METHOD'] . "<br>";
echo "SCRIPT_NAME: " . $_SERVER['SCRIPT_NAME'] . "<br>";
echo "QUERY_STRING: " . ($_SERVER['QUERY_STRING'] ?? 'EMPTY') . "<br>";
echo "</div>";

echo "<div class='alert alert-secondary'>";
echo "<h5>$_GET VERİSİ:</h5>";
if (!empty($_GET)) {
    echo "<pre>" . print_r($_GET, true) . "</pre>";
} else {
    echo "BOŞ";
}
echo "</div>";

echo "<div class='alert alert-primary'>";
echo "<h5>SESSION VERİSİ:</h5>";
echo "<pre>" . print_r($_SESSION, true) . "</pre>";
echo "</div>";

// Additional tests
echo "<div class='alert alert-light'>";
echo "<h5>PHP AYARLARI:</h5>";
echo "post_max_size: " . ini_get('post_max_size') . "<br>";
echo "max_input_vars: " . ini_get('max_input_vars') . "<br>";
echo "upload_max_filesize: " . ini_get('upload_max_filesize') . "<br>";
echo "</div>";

echo "</div>";
echo "</div>";

// JavaScript test
echo "<script>";
echo "console.log('JavaScript çalışıyor');";
echo "document.querySelector('form').addEventListener('submit', function(e) {";
echo "  console.log('Form submit edildi');";
echo "  console.log('Form data:', new FormData(this));";
echo "});";
echo "</script>";

echo "</div>";
echo "</body>";
echo "</html>";
?>

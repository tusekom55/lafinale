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

try {
    $database = new Database();
    $db = $database->getConnection();
    
    // Tüm market verilerini sil
    $query = "DELETE FROM markets";
    $stmt = $db->prepare($query);
    $success = $stmt->execute();
    
    if ($success) {
        $deletedRows = $stmt->rowCount();
        echo json_encode([
            'success' => true,
            'deleted' => $deletedRows,
            'message' => 'Tüm market verileri temizlendi'
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'error' => 'Veri temizleme işlemi başarısız'
        ]);
    }
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false, 
        'error' => 'Veritabanı hatası: ' . $e->getMessage()
    ]);
}
?>

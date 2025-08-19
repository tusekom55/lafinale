<?php
require_once 'includes/functions.php';

// Simple command-line style debug
echo "<pre style='background: #f5f5f5; padding: 20px; font-family: monospace;'>";
echo "=== TRANSACTION TYPE DEBUG ANALIZ ===\n\n";

try {
    $database = new Database();
    $db = $database->getConnection();

    // 1. Tüm transaction type'ları listele
    echo "1. VERİTABANINDAKİ TÜM İŞLEM TÜRLERİ:\n";
    echo str_repeat("-", 50) . "\n";
    
    $query = "SELECT DISTINCT type, COUNT(*) as count FROM transactions GROUP BY type ORDER BY count DESC";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $types = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($types as $type) {
        $type_value = $type['type'];
        $count = $type['count'];
        
        if (is_null($type_value)) {
            echo "Type: NULL (Boş)               | Adet: $count | SORUN: NULL değer!\n";
        } elseif ($type_value === '') {
            echo "Type: '' (Boş string)          | Adet: $count | SORUN: Boş string!\n";
        } else {
            $hex = bin2hex($type_value);
            echo "Type: '$type_value'            | Adet: $count | HEX: $hex\n";
        }
    }
    
    echo "\n";
    
    // 2. Son işlemleri kontrol et
    echo "2. SON 10 İŞLEM:\n";
    echo str_repeat("-", 50) . "\n";
    
    $query = "SELECT id, user_id, type, symbol, amount, created_at FROM transactions ORDER BY created_at DESC LIMIT 10";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $recent = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($recent as $trans) {
        $type_value = $trans['type'];
        $id = $trans['id'];
        $symbol = $trans['symbol'];
        
        if (is_null($type_value)) {
            echo "ID: $id | Type: NULL           | Symbol: $symbol | SORUNLU!\n";
        } elseif ($type_value === '') {
            echo "ID: $id | Type: BOŞ            | Symbol: $symbol | SORUNLU!\n";
        } else {
            echo "ID: $id | Type: '$type_value'  | Symbol: $symbol | Normal\n";
        }
    }
    
    echo "\n";
    
    // 3. Kaldıraç ile ilgili işlemleri ara
    echo "3. KALDIRAÇ İLE İLGİLİ İŞLEMLER:\n";
    echo str_repeat("-", 50) . "\n";
    
    $leverage_types = ['LEVERAGE_LONG', 'LEVERAGE_SHORT', 'CLOSE_LONG', 'CLOSE_SHORT', 'leverage', 'long', 'short'];
    
    foreach ($leverage_types as $ltype) {
        $query = "SELECT COUNT(*) as count FROM transactions WHERE type LIKE ?";
        $stmt = $db->prepare($query);
        $stmt->execute(["%$ltype%"]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($result['count'] > 0) {
            echo "Type içinde '$ltype' geçen işlem sayısı: " . $result['count'] . "\n";
        }
    }
    
    // 4. NULL/Boş type'ları kontrol et
    echo "\n4. NULL/BOŞ TYPE KONTROL:\n";
    echo str_repeat("-", 50) . "\n";
    
    $query = "SELECT COUNT(*) as null_count FROM transactions WHERE type IS NULL";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $null_count = $stmt->fetch(PDO::FETCH_ASSOC)['null_count'];
    
    $query = "SELECT COUNT(*) as empty_count FROM transactions WHERE type = ''";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $empty_count = $stmt->fetch(PDO::FETCH_ASSOC)['empty_count'];
    
    echo "NULL type sayısı: $null_count\n";
    echo "Boş string type sayısı: $empty_count\n";
    
    if ($null_count > 0 || $empty_count > 0) {
        echo "SORUN BULUNDU: " . ($null_count + $empty_count) . " adet sorunlu işlem var!\n";
        
        // Sorunlu işlemleri göster
        echo "\nSorunlu işlemler:\n";
        $query = "SELECT id, user_id, symbol, amount, price, created_at FROM transactions WHERE type IS NULL OR type = '' ORDER BY created_at DESC LIMIT 10";
        $stmt = $db->prepare($query);
        $stmt->execute();
        $problematic = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($problematic as $trans) {
            echo "ID: {$trans['id']} | User: {$trans['user_id']} | Symbol: {$trans['symbol']} | Date: {$trans['created_at']}\n";
        }
    }
    
    // 5. Portfolio.php test
    echo "\n5. PORTFOLIO.PHP DISPLAY LOGIC TEST:\n";
    echo str_repeat("-", 50) . "\n";
    
    function testDisplayLogic($transaction_type) {
        if (empty($transaction_type) || is_null($transaction_type)) {
            return ['İŞLEM', 'bg-info', 'fas fa-exchange-alt'];
        }
        
        switch (strtoupper($transaction_type)) {
            case 'BUY':
                return ['ALIM', 'bg-success', 'fas fa-arrow-up'];
            case 'SELL':
                return ['SATIM', 'bg-danger', 'fas fa-arrow-down'];
            case 'LEVERAGE_LONG':
                return ['LONG AÇMA', 'bg-warning', 'fas fa-bolt'];
            case 'LEVERAGE_SHORT':
                return ['SHORT AÇMA', 'bg-warning', 'fas fa-bolt'];
            case 'CLOSE_LONG':
                return ['LONG KAPAMA', 'bg-secondary', 'fas fa-times'];
            case 'CLOSE_SHORT':
                return ['SHORT KAPAMA', 'bg-secondary', 'fas fa-times'];
            default:
                if (is_numeric($transaction_type)) {
                    if (in_array($transaction_type, ['5', '6', '7', '8', '9'])) {
                        return ['KALDIRAÇ', 'bg-warning', 'fas fa-bolt'];
                    }
                }
                $transaction_type_lower = strtolower($transaction_type);
                if (strpos($transaction_type_lower, 'leverage') !== false || 
                    strpos($transaction_type_lower, 'long') !== false || 
                    strpos($transaction_type_lower, 'short') !== false) {
                    return ['KALDIRAÇ', 'bg-warning', 'fas fa-bolt'];
                }
                return ['İŞLEM (' . strtoupper($transaction_type) . ')', 'bg-secondary', 'fas fa-exchange-alt'];
        }
    }
    
    $test_cases = [null, '', 'buy', 'sell', 'LEVERAGE_LONG', '5', 'leverage', 'garbled_text'];
    
    foreach ($test_cases as $test_type) {
        $result = testDisplayLogic($test_type);
        $display_name = $test_type === null ? 'NULL' : ($test_type === '' ? 'BOŞ' : $test_type);
        echo "Input: '$display_name' -> Output: '{$result[0]}' | Class: {$result[1]}\n";
    }
    
    echo "\n";
    echo "=== SONUÇ ===\n";
    if ($null_count > 0 || $empty_count > 0) {
        echo "SORUN BULUNDU: Transaction type'lar NULL/boş olan işlemler var.\n";
        echo "Bu işlemler portfolio.php'de soru işareti olarak görünüyor olabilir.\n";
        echo "Çözüm: Bu işlemlerin type'larını düzeltmek gerekiyor.\n";
    } else {
        echo "Transaction type'larda açık bir sorun görünmüyor.\n";
        echo "Sorun başka bir yerde olabilir (encoding, display logic, vb.)\n";
    }

} catch (Exception $e) {
    echo "HATA: " . $e->getMessage() . "\n";
}

echo "</pre>";
?>

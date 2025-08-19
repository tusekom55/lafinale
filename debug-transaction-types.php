<?php
require_once 'config/database.php';

// Create a debug page to check transaction types and find the issue
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>İşlem Türü Analiz</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; background: #f5f5f5; }
        .container { max-width: 1200px; margin: 0 auto; background: white; padding: 20px; border-radius: 8px; }
        .section { margin-bottom: 30px; border: 1px solid #ddd; padding: 15px; border-radius: 5px; }
        .section h3 { margin-top: 0; color: #333; border-bottom: 2px solid #007bff; padding-bottom: 10px; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #f8f9fa; }
        .type-cell { font-family: monospace; background: #f8f9fa; }
        .hex-value { color: #666; font-size: 0.9em; }
        .null-value { color: red; font-weight: bold; }
        .weird-char { background: yellow; color: red; font-weight: bold; }
        .warning { background: #fff3cd; border: 1px solid #ffeaa7; padding: 10px; border-radius: 4px; }
        .error { background: #f8d7da; border: 1px solid #f5c6cb; padding: 10px; border-radius: 4px; }
    </style>
</head>
<body>
    <div class="container">
        <h1>İşlem Türü Debug Analizi</h1>
        <p>Bu sayfa portföy geçmişindeki soru işaretlerinin nedenini bulmak için oluşturuldu.</p>

        <?php
        try {
            $database = new Database();
            $db = $database->getConnection();

            // Section 1: All transaction types
            echo '<div class="section">';
            echo '<h3>1. Veritabanındaki Tüm İşlem Türleri</h3>';
            
            $query = "SELECT DISTINCT type, COUNT(*) as count FROM transactions GROUP BY type ORDER BY count DESC";
            $stmt = $db->prepare($query);
            $stmt->execute();
            $types = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo '<table>';
            echo '<tr><th>İşlem Türü</th><th>HEX Kodu</th><th>Karakter Uzunluğu</th><th>Byte Uzunluğu</th><th>Adet</th><th>Durum</th></tr>';
            
            foreach ($types as $type) {
                $type_value = $type['type'];
                $hex = bin2hex($type_value ?? '');
                $char_length = mb_strlen($type_value ?? '', 'UTF-8');
                $byte_length = strlen($type_value ?? '');
                $count = $type['count'];
                
                // Check for issues
                $status = 'Normal';
                $css_class = '';
                
                if (is_null($type_value) || $type_value === '') {
                    $status = 'NULL/BOŞ';
                    $css_class = 'null-value';
                    $type_value = $type_value === null ? 'NULL' : 'BOŞ STRING';
                } elseif ($char_length !== $byte_length) {
                    $status = 'Encoding Sorunu';
                    $css_class = 'weird-char';
                } elseif (preg_match('/[^\x20-\x7E]/', $type_value)) {
                    $status = 'Garip Karakter';
                    $css_class = 'weird-char';
                }
                
                echo '<tr>';
                echo '<td class="type-cell ' . $css_class . '">' . htmlspecialchars($type_value, ENT_QUOTES, 'UTF-8') . '</td>';
                echo '<td class="hex-value">' . $hex . '</td>';
                echo '<td>' . $char_length . '</td>';
                echo '<td>' . $byte_length . '</td>';
                echo '<td>' . $count . '</td>';
                echo '<td class="' . $css_class . '">' . $status . '</td>';
                echo '</tr>';
            }
            echo '</table>';
            echo '</div>';

            // Section 2: Recent transactions with details
            echo '<div class="section">';
            echo '<h3>2. Son İşlemler (Potansiyel Sorunlu Olanlar)</h3>';
            
            $query = "SELECT id, user_id, type, symbol, amount, price, total, created_at 
                      FROM transactions 
                      WHERE type IS NULL OR type = '' OR type LIKE '%leverage%' OR type LIKE '%LEVERAGE%' 
                         OR type LIKE '%long%' OR type LIKE '%short%' OR type LIKE '%LONG%' OR type LIKE '%SHORT%'
                         OR LENGTH(type) != CHAR_LENGTH(type)
                      ORDER BY created_at DESC LIMIT 20";
            $stmt = $db->prepare($query);
            $stmt->execute();
            $problematic_transactions = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            if (empty($problematic_transactions)) {
                echo '<div class="warning">Açık sorunlu işlem bulunamadı. Tüm son işlemleri kontrol edelim...</div>';
                
                // Get recent transactions
                $query = "SELECT id, user_id, type, symbol, amount, price, total, created_at 
                          FROM transactions 
                          ORDER BY created_at DESC LIMIT 15";
                $stmt = $db->prepare($query);
                $stmt->execute();
                $problematic_transactions = $stmt->fetchAll(PDO::FETCH_ASSOC);
            }
            
            if (!empty($problematic_transactions)) {
                echo '<table>';
                echo '<tr><th>ID</th><th>Kullanıcı</th><th>Tür</th><th>HEX</th><th>Sembol</th><th>Miktar</th><th>Fiyat</th><th>Tarih</th></tr>';
                
                foreach ($problematic_transactions as $trans) {
                    $type_value = $trans['type'];
                    $hex = bin2hex($type_value ?? '');
                    
                    $css_class = '';
                    if (is_null($type_value) || $type_value === '') {
                        $css_class = 'null-value';
                        $type_value = $type_value === null ? 'NULL' : 'BOŞ';
                    } elseif (preg_match('/[^\x20-\x7E]/', $type_value)) {
                        $css_class = 'weird-char';
                    }
                    
                    echo '<tr>';
                    echo '<td>' . $trans['id'] . '</td>';
                    echo '<td>' . $trans['user_id'] . '</td>';
                    echo '<td class="type-cell ' . $css_class . '">' . htmlspecialchars($type_value, ENT_QUOTES, 'UTF-8') . '</td>';
                    echo '<td class="hex-value">' . $hex . '</td>';
                    echo '<td>' . htmlspecialchars($trans['symbol'], ENT_QUOTES, 'UTF-8') . '</td>';
                    echo '<td>' . number_format($trans['amount'], 6) . '</td>';
                    echo '<td>$' . number_format($trans['price'], 4) . '</td>';
                    echo '<td>' . $trans['created_at'] . '</td>';
                    echo '</tr>';
                }
                echo '</table>';
            }
            echo '</div>';

            // Section 3: Database schema info
            echo '<div class="section">';
            echo '<h3>3. Veritabanı Şema Bilgisi</h3>';
            
            // Table status
            $query = "SHOW TABLE STATUS LIKE 'transactions'";
            $stmt = $db->prepare($query);
            $stmt->execute();
            $table_info = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($table_info) {
                echo '<p><strong>Tablo Collation:</strong> ' . ($table_info['Collation'] ?? 'unknown') . '</p>';
                echo '<p><strong>Tablo Engine:</strong> ' . ($table_info['Engine'] ?? 'unknown') . '</p>';
            }
            
            // Column info
            $query = "SHOW FULL COLUMNS FROM transactions WHERE Field = 'type'";
            $stmt = $db->prepare($query);
            $stmt->execute();
            $column_info = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($column_info) {
                echo '<p><strong>Type Sütun Türü:</strong> ' . ($column_info['Type'] ?? 'unknown') . '</p>';
                echo '<p><strong>Type Sütun Collation:</strong> ' . ($column_info['Collation'] ?? 'unknown') . '</p>';
                echo '<p><strong>Type Sütun Null:</strong> ' . ($column_info['Null'] ?? 'unknown') . '</p>';
                echo '<p><strong>Type Sütun Default:</strong> ' . ($column_info['Default'] ?? 'none') . '</p>';
            }
            echo '</div>';

            // Section 4: Portfolio.php display logic test
            echo '<div class="section">';
            echo '<h3>4. Portfolio.php Display Logic Test</h3>';
            echo '<p>Portfolio.php\'daki işlem türü gösterim mantığını test edelim:</p>';
            
            // Test the logic from portfolio.php
            $test_types = ['buy', 'sell', 'LEVERAGE_LONG', 'LEVERAGE_SHORT', 'CLOSE_LONG', 'CLOSE_SHORT', null, '', '1', '2', '5', 'leverage', 'long', 'short'];
            
            echo '<table>';
            echo '<tr><th>Girdi</th><th>Çıktı</th><th>Badge Class</th><th>Icon</th></tr>';
            
            foreach ($test_types as $transaction_type) {
                // Copy the logic from portfolio.php
                if (empty($transaction_type) || is_null($transaction_type)) {
                    $display_text = 'İŞLEM';
                    $badge_class = 'bg-info';
                    $icon = 'fas fa-exchange-alt';
                } else {
                    $display_text = '';
                    $badge_class = 'bg-info';
                    $icon = '';
                    
                    switch (strtoupper($transaction_type)) {
                        case 'BUY':
                            $display_text = 'ALIM';
                            $badge_class = 'bg-success';
                            $icon = 'fas fa-arrow-up';
                            break;
                        case 'SELL':
                            $display_text = 'SATIM';
                            $badge_class = 'bg-danger';
                            $icon = 'fas fa-arrow-down';
                            break;
                        case 'LEVERAGE_LONG':
                            $display_text = 'LONG AÇMA';
                            $badge_class = 'bg-warning';
                            $icon = 'fas fa-bolt';
                            break;
                        case 'LEVERAGE_SHORT':
                            $display_text = 'SHORT AÇMA';
                            $badge_class = 'bg-warning';
                            $icon = 'fas fa-bolt';
                            break;
                        case 'CLOSE_LONG':
                            $display_text = 'LONG KAPAMA';
                            $badge_class = 'bg-secondary';
                            $icon = 'fas fa-times';
                            break;
                        case 'CLOSE_SHORT':
                            $display_text = 'SHORT KAPAMA';
                            $badge_class = 'bg-secondary';
                            $icon = 'fas fa-times';
                            break;
                        default:
                            $transaction_type_lower = strtolower($transaction_type);
                            
                            if (is_numeric($transaction_type)) {
                                switch ($transaction_type) {
                                    case '1':
                                    case '2':
                                        $display_text = 'ALIM';
                                        $badge_class = 'bg-success';
                                        $icon = 'fas fa-arrow-up';
                                        break;
                                    case '3':
                                    case '4':
                                        $display_text = 'SATIM';
                                        $badge_class = 'bg-danger';
                                        $icon = 'fas fa-arrow-down';
                                        break;
                                    case '5':
                                    case '6':
                                    case '7':
                                    case '8':
                                    case '9':
                                        $display_text = 'KALDIRAÇ';
                                        $badge_class = 'bg-warning';
                                        $icon = 'fas fa-bolt';
                                        break;
                                    default:
                                        $display_text = 'İŞLEM';
                                        $badge_class = 'bg-info';
                                        $icon = 'fas fa-exchange-alt';
                                        break;
                                }
                            } elseif (strpos($transaction_type_lower, 'leverage') !== false || 
                                strpos($transaction_type_lower, 'long') !== false || 
                                strpos($transaction_type_lower, 'short') !== false) {
                                $display_text = 'KALDIRAÇ';
                                $badge_class = 'bg-warning';
                                $icon = 'fas fa-bolt';
                            } elseif (strpos($transaction_type_lower, 'buy') !== false || 
                                strpos($transaction_type_lower, 'alim') !== false) {
                                $display_text = 'ALIM';
                                $badge_class = 'bg-success';
                                $icon = 'fas fa-arrow-up';
                            } elseif (strpos($transaction_type_lower, 'sell') !== false || 
                                strpos($transaction_type_lower, 'satim') !== false) {
                                $display_text = 'SATIM';
                                $badge_class = 'bg-danger';
                                $icon = 'fas fa-arrow-down';
                            } else {
                                $display_text = 'İŞLEM (' . strtoupper($transaction_type) . ')';
                                $badge_class = 'bg-secondary';
                                $icon = 'fas fa-exchange-alt';
                            }
                            break;
                    }
                }
                
                echo '<tr>';
                echo '<td class="type-cell">' . htmlspecialchars($transaction_type ?? 'NULL', ENT_QUOTES, 'UTF-8') . '</td>';
                echo '<td>' . htmlspecialchars($display_text, ENT_QUOTES, 'UTF-8') . '</td>';
                echo '<td>' . $badge_class . '</td>';
                echo '<td>' . $icon . '</td>';
                echo '</tr>';
            }
            echo '</table>';
            echo '</div>';

        } catch (Exception $e) {
            echo '<div class="error">Hata: ' . $e->getMessage() . '</div>';
        }
        ?>

        <div class="section">
            <h3>5. Sonuç ve Öneriler</h3>
            <div class="warning">
                <p><strong>Muhtemel Sorun Nedenleri:</strong></p>
                <ul>
                    <li>NULL veya boş transaction type değerleri</li>
                    <li>Karakter encoding sorunları (UTF-8 vs Latin1)</li>
                    <li>Beklenmeyen transaction type değerleri</li>
                    <li>Database collation uyumsuzluğu</li>
                </ul>
                <p><strong>Çözüm Önerileri:</strong></p>
                <ul>
                    <li>Transaction type NULL/boş olanları düzelt</li>
                    <li>Character encoding problemlerini gider</li>
                    <li>Portfolio.php'deki display logic'i güncelle</li>
                    <li>Database charset'ini UTF-8'e çevir</li>
                </ul>
            </div>
        </div>
    </div>
</body>
</html>

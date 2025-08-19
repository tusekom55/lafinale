<?php
require_once 'config/database.php';

// Check deposits table structure and payment methods
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Deposits Debug</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; background: #f5f5f5; }
        .container { max-width: 1200px; margin: 0 auto; background: white; padding: 20px; border-radius: 8px; }
        .section { margin-bottom: 30px; border: 1px solid #ddd; padding: 15px; border-radius: 5px; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #f8f9fa; }
        .error { background: #f8d7da; border: 1px solid #f5c6cb; padding: 10px; border-radius: 4px; }
    </style>
</head>
<body>
    <div class="container">
        <h1>Deposits Debug Analizi</h1>
        
        <?php
        try {
            $database = new Database();
            $db = $database->getConnection();

            // Section 1: Deposits table structure
            echo '<div class="section">';
            echo '<h3>1. Deposits Tablo Yapısı</h3>';
            
            $query = "DESCRIBE deposits";
            $stmt = $db->prepare($query);
            $stmt->execute();
            $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo '<table>';
            echo '<tr><th>Sütun</th><th>Tip</th><th>Null</th><th>Default</th></tr>';
            foreach ($columns as $col) {
                echo '<tr>';
                echo '<td>' . $col['Field'] . '</td>';
                echo '<td>' . $col['Type'] . '</td>';
                echo '<td>' . $col['Null'] . '</td>';
                echo '<td>' . ($col['Default'] ?? 'NULL') . '</td>';
                echo '</tr>';
            }
            echo '</table>';
            echo '</div>';

            // Section 2: Sample deposits
            echo '<div class="section">';
            echo '<h3>2. Son Para Yatırma Kayıtları</h3>';
            
            $query = "SELECT id, method, payment_method_id, amount, reference, created_at FROM deposits ORDER BY created_at DESC LIMIT 5";
            $stmt = $db->prepare($query);
            $stmt->execute();
            $deposits = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo '<table>';
            echo '<tr><th>ID</th><th>Method</th><th>Payment Method ID</th><th>Amount</th><th>Reference</th><th>Date</th></tr>';
            foreach ($deposits as $deposit) {
                echo '<tr>';
                echo '<td>' . $deposit['id'] . '</td>';
                echo '<td>' . htmlspecialchars($deposit['method'] ?? 'NULL') . '</td>';
                echo '<td>' . ($deposit['payment_method_id'] ?? 'NULL') . '</td>';
                echo '<td>' . number_format($deposit['amount'], 2) . '</td>';
                echo '<td>' . htmlspecialchars($deposit['reference'] ?? '') . '</td>';
                echo '<td>' . $deposit['created_at'] . '</td>';
                echo '</tr>';
            }
            echo '</table>';
            echo '</div>';

            // Section 3: Payment methods table check
            echo '<div class="section">';
            echo '<h3>3. Payment Methods Tablosu</h3>';
            
            $query = "SHOW TABLES LIKE 'payment_methods'";
            $stmt = $db->prepare($query);
            $stmt->execute();
            
            if ($stmt->rowCount() > 0) {
                echo '<p><strong>✅ Payment methods tablosu mevcut</strong></p>';
                
                $query = "SELECT id, name, type, code, iban FROM payment_methods WHERE is_active = 1";
                $stmt = $db->prepare($query);
                $stmt->execute();
                $methods = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                echo '<table>';
                echo '<tr><th>ID</th><th>Name</th><th>Type</th><th>Code</th><th>IBAN</th></tr>';
                foreach ($methods as $method) {
                    echo '<tr>';
                    echo '<td>' . $method['id'] . '</td>';
                    echo '<td>' . htmlspecialchars($method['name']) . '</td>';
                    echo '<td>' . $method['type'] . '</td>';
                    echo '<td>' . ($method['code'] ?? 'NULL') . '</td>';
                    echo '<td>' . ($method['iban'] ?? 'NULL') . '</td>';
                    echo '</tr>';
                }
                echo '</table>';
            } else {
                echo '<p><strong>❌ Payment methods tablosu bulunamadı</strong></p>';
            }
            echo '</div>';

            // Section 4: Test JOIN query
            echo '<div class="section">';
            echo '<h3>4. JOIN Sorgusu Test</h3>';
            
            $query = "SELECT d.id, d.method, d.payment_method_id, d.amount, d.created_at, 
                             pm.name as payment_method_name, pm.type as payment_method_type
                      FROM deposits d 
                      LEFT JOIN payment_methods pm ON d.payment_method_id = pm.id 
                      ORDER BY d.created_at DESC LIMIT 5";
            $stmt = $db->prepare($query);
            $stmt->execute();
            $joined_deposits = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo '<table>';
            echo '<tr><th>ID</th><th>Old Method</th><th>Payment Method ID</th><th>Payment Method Name</th><th>Type</th><th>Amount</th></tr>';
            foreach ($joined_deposits as $deposit) {
                echo '<tr>';
                echo '<td>' . $deposit['id'] . '</td>';
                echo '<td>' . htmlspecialchars($deposit['method'] ?? 'NULL') . '</td>';
                echo '<td>' . ($deposit['payment_method_id'] ?? 'NULL') . '</td>';
                echo '<td>' . htmlspecialchars($deposit['payment_method_name'] ?? 'NULL') . '</td>';
                echo '<td>' . ($deposit['payment_method_type'] ?? 'NULL') . '</td>';
                echo '<td>' . number_format($deposit['amount'], 2) . '</td>';
                echo '</tr>';
            }
            echo '</table>';
            echo '</div>';

        } catch (Exception $e) {
            echo '<div class="error">Hata: ' . $e->getMessage() . '</div>';
        }
        ?>
    </div>
</body>
</html>

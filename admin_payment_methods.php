<?php
session_start();
require_once 'config/database.php';

// Admin kontrolü
if (!isset($_SESSION['user_id']) || !isset($_SESSION['is_admin']) || $_SESSION['is_admin'] != 1) {
    header('Location: login.php');
    exit();
}

$database = new Database();
$db = $database->getConnection();

// İşlemler
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['action'])) {
        try {
            switch($_POST['action']) {
                case 'add_method':
                    $query = "INSERT INTO payment_methods (type, name, code, iban, account_name, icon, sort_order, is_active) 
                              VALUES (?, ?, ?, ?, ?, ?, ?, 1)";
                    $stmt = $db->prepare($query);
                    $stmt->execute([
                        $_POST['type'],
                        $_POST['name'],
                        $_POST['code'],
                        $_POST['iban'] ?? '',
                        $_POST['account_name'] ?? '',
                        $_POST['icon'] ?? '',
                        $_POST['sort_order'] ?? 0
                    ]);
                    $success_message = "Ödeme yöntemi başarıyla eklendi!";
                    break;
                    
                case 'update_method':
                    $query = "UPDATE payment_methods SET 
                              type = ?, name = ?, code = ?, iban = ?, account_name = ?, 
                              icon = ?, sort_order = ?, is_active = ?
                              WHERE id = ?";
                    $stmt = $db->prepare($query);
                    $stmt->execute([
                        $_POST['type'],
                        $_POST['name'],
                        $_POST['code'],
                        $_POST['iban'] ?? '',
                        $_POST['account_name'] ?? '',
                        $_POST['icon'] ?? '',
                        $_POST['sort_order'] ?? 0,
                        $_POST['is_active'] ?? 1,
                        $_POST['method_id']
                    ]);
                    $success_message = "Ödeme yöntemi başarıyla güncellendi!";
                    break;
                    
                case 'delete_method':
                    $query = "DELETE FROM payment_methods WHERE id = ?";
                    $stmt = $db->prepare($query);
                    $stmt->execute([$_POST['method_id']]);
                    $success_message = "Ödeme yöntemi başarıyla silindi!";
                    break;
                    
                case 'toggle_status':
                    $query = "UPDATE payment_methods SET is_active = ? WHERE id = ?";
                    $stmt = $db->prepare($query);
                    $new_status = $_POST['current_status'] == '1' ? '0' : '1';
                    $stmt->execute([$new_status, $_POST['method_id']]);
                    $success_message = "Durum başarıyla değiştirildi!";
                    break;
            }
        } catch(Exception $e) {
            $error_message = "Hata: " . $e->getMessage();
        }
    }
}

// Ödeme yöntemlerini çek
$query = "SELECT * FROM payment_methods ORDER BY type, sort_order";
$stmt = $db->prepare($query);
$stmt->execute();
$payment_methods = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Türe göre grupla
$banks = [];
$digital = [];
$cryptos = [];
foreach ($payment_methods as $method) {
    if ($method['type'] == 'bank') {
        $banks[] = $method;
    } elseif ($method['type'] == 'digital') {
        $digital[] = $method;
    } elseif ($method['type'] == 'crypto') {
        $cryptos[] = $method;
    }
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ödeme Yöntemleri - Admin Panel</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container">
            <a class="navbar-brand" href="admin.php">
                <i class="fas fa-shield-alt"></i> Admin Panel
            </a>
            <div class="navbar-nav ms-auto">
                <a href="admin.php" class="btn btn-outline-light btn-sm me-2">
                    <i class="fas fa-arrow-left"></i> Dashboard
                </a>
                <a href="logout.php" class="btn btn-outline-light btn-sm">
                    <i class="fas fa-sign-out-alt"></i> Çıkış
                </a>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1><i class="fas fa-credit-card"></i> Ödeme Yöntemleri</h1>
            <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#addMethodModal">
                <i class="fas fa-plus"></i> Yeni Ödeme Yöntemi
            </button>
        </div>

        <?php if (isset($success_message)): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <?php echo $success_message; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <?php if (isset($error_message)): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <?php echo $error_message; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <!-- Bankalar -->
        <div class="card mb-4">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0"><i class="fas fa-university me-2"></i>Banka Havaleleri</h5>
            </div>
            <div class="card-body">
                <?php if (empty($banks)): ?>
                    <p class="text-muted">Henüz banka eklenmemiş.</p>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Banka</th>
                                    <th>IBAN</th>
                                    <th>Hesap Adı</th>
                                    <th>Durum</th>
                                    <th>İşlemler</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($banks as $bank): ?>
                                <tr>
                                    <td>
                                        <?php echo $bank['icon']; ?> 
                                        <strong><?php echo htmlspecialchars($bank['name']); ?></strong>
                                    </td>
                                    <td><?php echo htmlspecialchars($bank['iban']); ?></td>
                                    <td><?php echo htmlspecialchars($bank['account_name']); ?></td>
                                    <td>
                                        <span class="badge <?php echo $bank['is_active'] ? 'bg-success' : 'bg-secondary'; ?>">
                                            <?php echo $bank['is_active'] ? 'Aktif' : 'Pasif'; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <button class="btn btn-sm btn-primary" onclick="editMethod(<?php echo htmlspecialchars(json_encode($bank)); ?>)">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <form method="POST" style="display: inline;">
                                            <input type="hidden" name="action" value="toggle_status">
                                            <input type="hidden" name="method_id" value="<?php echo $bank['id']; ?>">
                                            <input type="hidden" name="current_status" value="<?php echo $bank['is_active']; ?>">
                                            <button type="submit" class="btn btn-sm <?php echo $bank['is_active'] ? 'btn-warning' : 'btn-success'; ?>">
                                                <i class="fas <?php echo $bank['is_active'] ? 'fa-pause' : 'fa-play'; ?>"></i>
                                            </button>
                                        </form>
                                        <form method="POST" style="display: inline;">
                                            <input type="hidden" name="action" value="delete_method">
                                            <input type="hidden" name="method_id" value="<?php echo $bank['id']; ?>">
                                            <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Silmek istediğinizden emin misiniz?')">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Dijital Ödemeler -->
        <div class="card mb-4">
            <div class="card-header bg-success text-white">
                <h5 class="mb-0"><i class="fas fa-mobile-alt me-2"></i>Dijital Ödemeler (Papara vb.)</h5>
            </div>
            <div class="card-body">
                <?php if (empty($digital)): ?>
                    <p class="text-muted">Henüz dijital ödeme yöntemi eklenmemiş.</p>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Yöntem</th>
                                    <th>Hesap/Numara</th>
                                    <th>Hesap Adı</th>
                                    <th>Durum</th>
                                    <th>İşlemler</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($digital as $method): ?>
                                <tr>
                                    <td>
                                        <?php echo $method['icon']; ?> 
                                        <strong><?php echo htmlspecialchars($method['name']); ?></strong>
                                    </td>
                                    <td><?php echo htmlspecialchars($method['code']); ?></td>
                                    <td><?php echo htmlspecialchars($method['account_name']); ?></td>
                                    <td>
                                        <span class="badge <?php echo $method['is_active'] ? 'bg-success' : 'bg-secondary'; ?>">
                                            <?php echo $method['is_active'] ? 'Aktif' : 'Pasif'; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <button class="btn btn-sm btn-primary" onclick="editMethod(<?php echo htmlspecialchars(json_encode($method)); ?>)">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <form method="POST" style="display: inline;">
                                            <input type="hidden" name="action" value="toggle_status">
                                            <input type="hidden" name="method_id" value="<?php echo $method['id']; ?>">
                                            <input type="hidden" name="current_status" value="<?php echo $method['is_active']; ?>">
                                            <button type="submit" class="btn btn-sm <?php echo $method['is_active'] ? 'btn-warning' : 'btn-success'; ?>">
                                                <i class="fas <?php echo $method['is_active'] ? 'fa-pause' : 'fa-play'; ?>"></i>
                                            </button>
                                        </form>
                                        <form method="POST" style="display: inline;">
                                            <input type="hidden" name="action" value="delete_method">
                                            <input type="hidden" name="method_id" value="<?php echo $method['id']; ?>">
                                            <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Silmek istediğinizden emin misiniz?')">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Kripto Paralar -->
        <div class="card mb-4">
            <div class="card-header bg-warning text-dark">
                <h5 class="mb-0"><i class="fab fa-bitcoin me-2"></i>Kripto Para Cüzdanları</h5>
            </div>
            <div class="card-body">
                <?php if (empty($cryptos)): ?>
                    <p class="text-muted">Henüz kripto para cüzdanı eklenmemiş.</p>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Kripto Para</th>
                                    <th>Wallet Adresi</th>
                                    <th>Network</th>
                                    <th>Durum</th>
                                    <th>İşlemler</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($cryptos as $crypto): ?>
                                <tr>
                                    <td>
                                        <?php echo $crypto['icon']; ?> 
                                        <strong><?php echo htmlspecialchars($crypto['name']); ?></strong>
                                        <small class="text-muted">(<?php echo $crypto['code']; ?>)</small>
                                    </td>
                                    <td>
                                        <code class="small"><?php echo htmlspecialchars(substr($crypto['iban'], 0, 20)); ?>...</code>
                                    </td>
                                    <td><?php echo htmlspecialchars($crypto['account_name']); ?></td>
                                    <td>
                                        <span class="badge <?php echo $crypto['is_active'] ? 'bg-success' : 'bg-secondary'; ?>">
                                            <?php echo $crypto['is_active'] ? 'Aktif' : 'Pasif'; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <button class="btn btn-sm btn-primary" onclick="editMethod(<?php echo htmlspecialchars(json_encode($crypto)); ?>)">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <form method="POST" style="display: inline;">
                                            <input type="hidden" name="action" value="toggle_status">
                                            <input type="hidden" name="method_id" value="<?php echo $crypto['id']; ?>">
                                            <input type="hidden" name="current_status" value="<?php echo $crypto['is_active']; ?>">
                                            <button type="submit" class="btn btn-sm <?php echo $crypto['is_active'] ? 'btn-warning' : 'btn-success'; ?>">
                                                <i class="fas <?php echo $crypto['is_active'] ? 'fa-pause' : 'fa-play'; ?>"></i>
                                            </button>
                                        </form>
                                        <form method="POST" style="display: inline;">
                                            <input type="hidden" name="action" value="delete_method">
                                            <input type="hidden" name="method_id" value="<?php echo $crypto['id']; ?>">
                                            <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Silmek istediğinizden emin misiniz?')">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Ödeme Yöntemi Ekleme Modal -->
    <div class="modal fade" id="addMethodModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <form method="POST">
                    <div class="modal-header">
                        <h5 class="modal-title">Yeni Ödeme Yöntemi Ekle</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="action" value="add_method">
                        
                        <div class="mb-3">
                            <label class="form-label">Tür</label>
                            <select class="form-select" name="type" id="add_type" onchange="toggleFields('add')" required>
                                <option value="">Seçiniz</option>
                                <option value="bank">🏦 Banka</option>
                                <option value="digital">📱 Dijital Ödeme</option>
                                <option value="crypto">₿ Kripto Para</option>
                            </select>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Adı</label>
                                    <input type="text" class="form-control" name="name" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Icon</label>
                                    <input type="text" class="form-control" name="icon" placeholder="🏦 veya 📱">
                                </div>
                            </div>
                        </div>
                        
                        <div class="mb-3" id="add_code_field">
                            <label class="form-label" id="add_code_label">Kod/Numara</label>
                            <input type="text" class="form-control" name="code" id="add_code_input">
                        </div>
                        
                        <div class="mb-3" id="add_iban_field">
                            <label class="form-label" id="add_iban_label">IBAN</label>
                            <input type="text" class="form-control" name="iban" id="add_iban_input">
                        </div>
                        
                        <div class="mb-3" id="add_account_field">
                            <label class="form-label" id="add_account_label">Hesap Adı</label>
                            <input type="text" class="form-control" name="account_name" id="add_account_input">
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Sıralama</label>
                            <input type="number" class="form-control" name="sort_order" value="0">
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">İptal</button>
                        <button type="submit" class="btn btn-success">Ekle</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Ödeme Yöntemi Düzenleme Modal -->
    <div class="modal fade" id="editMethodModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <form method="POST">
                    <div class="modal-header">
                        <h5 class="modal-title">Ödeme Yöntemi Düzenle</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="action" value="update_method">
                        <input type="hidden" name="method_id" id="edit_method_id">
                        
                        <div class="mb-3">
                            <label class="form-label">Tür</label>
                            <select class="form-select" name="type" id="edit_type" onchange="toggleFields('edit')" required>
                                <option value="bank">🏦 Banka</option>
                                <option value="digital">📱 Dijital Ödeme</option>
                                <option value="crypto">₿ Kripto Para</option>
                            </select>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Adı</label>
                                    <input type="text" class="form-control" name="name" id="edit_name" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Icon</label>
                                    <input type="text" class="form-control" name="icon" id="edit_icon">
                                </div>
                            </div>
                        </div>
                        
                        <div class="mb-3" id="edit_code_field">
                            <label class="form-label" id="edit_code_label">Kod/Numara</label>
                            <input type="text" class="form-control" name="code" id="edit_code_input">
                        </div>
                        
                        <div class="mb-3" id="edit_iban_field">
                            <label class="form-label" id="edit_iban_label">IBAN</label>
                            <input type="text" class="form-control" name="iban" id="edit_iban_input">
                        </div>
                        
                        <div class="mb-3" id="edit_account_field">
                            <label class="form-label" id="edit_account_label">Hesap Adı</label>
                            <input type="text" class="form-control" name="account_name" id="edit_account_input">
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Sıralama</label>
                                    <input type="number" class="form-control" name="sort_order" id="edit_sort_order">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Durum</label>
                                    <select class="form-select" name="is_active" id="edit_is_active">
                                        <option value="1">Aktif</option>
                                        <option value="0">Pasif</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">İptal</button>
                        <button type="submit" class="btn btn-primary">Güncelle</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    function toggleFields(mode) {
        const type = document.getElementById(mode + '_type').value;
        const codeField = document.getElementById(mode + '_code_field');
        const ibanField = document.getElementById(mode + '_iban_field');
        const accountField = document.getElementById(mode + '_account_field');
        const codeLabel = document.getElementById(mode + '_code_label');
        const ibanLabel = document.getElementById(mode + '_iban_label');
        const accountLabel = document.getElementById(mode + '_account_label');
        const codeInput = document.getElementById(mode + '_code_input');
        const ibanInput = document.getElementById(mode + '_iban_input');
        const accountInput = document.getElementById(mode + '_account_input');
        
        // Reset all fields
        codeField.style.display = 'block';
        ibanField.style.display = 'block';
        accountField.style.display = 'block';
        codeInput.required = false;
        ibanInput.required = false;
        accountInput.required = false;
        
        if (type === 'bank') {
            codeLabel.textContent = 'Banka Kodu';
            ibanLabel.textContent = 'IBAN';
            accountLabel.textContent = 'Hesap Adı';
            codeField.style.display = 'none';
            ibanInput.required = true;
            accountInput.required = true;
        } else if (type === 'digital') {
            codeLabel.textContent = 'Hesap Numarası';
            ibanLabel.textContent = 'Ek Bilgi';
            accountLabel.textContent = 'Hesap Adı';
            ibanField.style.display = 'none';
            codeInput.required = true;
            accountInput.required = true;
        } else if (type === 'crypto') {
            codeLabel.textContent = 'Kripto Kodu (BTC, ETH vb.)';
            ibanLabel.textContent = 'Wallet Adresi';
            accountLabel.textContent = 'Network (ERC-20, TRC-20 vb.)';
            codeInput.required = true;
            ibanInput.required = true;
            accountInput.required = true;
        }
    }
    
    function editMethod(method) {
        document.getElementById('edit_method_id').value = method.id;
        document.getElementById('edit_type').value = method.type;
        document.getElementById('edit_name').value = method.name;
        document.getElementById('edit_icon').value = method.icon;
        document.getElementById('edit_code_input').value = method.code;
        document.getElementById('edit_iban_input').value = method.iban;
        document.getElementById('edit_account_input').value = method.account_name;
        document.getElementById('edit_sort_order').value = method.sort_order;
        document.getElementById('edit_is_active').value = method.is_active;
        
        toggleFields('edit');
        
        new bootstrap.Modal(document.getElementById('editMethodModal')).show();
    }
    </script>
</body>
</html>

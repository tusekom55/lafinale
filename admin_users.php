<?php
session_start();
require_once 'config/database.php';

// Basit admin kontrolü
if (!isset($_SESSION['user_id']) || !isset($_SESSION['is_admin']) || $_SESSION['is_admin'] != 1) {
    header('Location: login.php');
    exit();
}

$database = new Database();
$db = $database->getConnection();

$success = '';
$error = '';

// Form işlemleri
if ($_POST) {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'edit_balance') {
        $user_id = intval($_POST['user_id'] ?? 0);
        $new_balance = floatval($_POST['new_balance'] ?? 0);
        $currency = $_POST['currency'] ?? 'tl';
        $note = $_POST['note'] ?? '';
        
        if ($user_id > 0 && $new_balance >= 0) {
            // Mevcut bakiyeyi al
            $query = "SELECT balance_$currency as current_balance, username FROM users WHERE id = ?";
            $stmt = $db->prepare($query);
            $stmt->execute([$user_id]);
            $user_data = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($user_data) {
                $old_balance = $user_data['current_balance'];
                $username = $user_data['username'];
                
                // Bakiyeyi güncelle
                $query = "UPDATE users SET balance_$currency = ? WHERE id = ?";
                $stmt = $db->prepare($query);
                if ($stmt->execute([$new_balance, $user_id])) {
                    
                    // Bakiye değişiklik logu (opsiyonel - ileride kullanılabilir)
                    $change_amount = $new_balance - $old_balance;
                    $log_note = "Admin tarafından bakiye değiştirildi: " . number_format($old_balance, 2) . " -> " . number_format($new_balance, 2) . " " . strtoupper($currency);
                    if ($note) {
                        $log_note .= " (Not: $note)";
                    }
                    
                    $success = "Bakiye başarıyla güncellendi! $username kullanıcısının " . strtoupper($currency) . " bakiyesi: " . number_format($new_balance, 2);
                } else {
                    $error = "Bakiye güncellenirken hata oluştu!";
                }
            } else {
                $error = "Kullanıcı bulunamadı!";
            }
        } else {
            $error = "Geçersiz bakiye miktarı!";
        }
    }
}

// Arama/filtreleme parametreleri
$search = $_GET['search'] ?? '';
$filter = $_GET['filter'] ?? 'all'; // all, admin, user, active, inactive

// Kullanıcıları getir (işlem istatistikleri ile birlikte)
$whereClause = "WHERE u.id > 0";
$params = [];

if ($search) {
    $whereClause .= " AND (u.username LIKE ? OR u.email LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

if ($filter === 'admin') {
    $whereClause .= " AND u.is_admin = 1";
} elseif ($filter === 'user') {
    $whereClause .= " AND u.is_admin = 0";
}

$query = "SELECT u.id, u.username, u.email, u.balance_tl, u.balance_usd, u.is_admin, u.created_at,
                 COUNT(DISTINCT t.id) as transaction_count,
                 COUNT(DISTINCT d.id) as deposit_count,
                 COUNT(DISTINCT w.id) as withdrawal_count,
                 COUNT(DISTINCT up.id) as portfolio_positions,
                 MAX(GREATEST(
                     IFNULL(t.created_at, '1970-01-01'),
                     IFNULL(d.created_at, '1970-01-01'),
                     IFNULL(w.created_at, '1970-01-01')
                 )) as last_activity
          FROM users u
          LEFT JOIN transactions t ON u.id = t.user_id
          LEFT JOIN deposits d ON u.id = d.user_id
          LEFT JOIN withdrawals w ON u.id = w.user_id
          LEFT JOIN user_portfolio up ON u.id = up.user_id AND up.quantity > 0
          $whereClause
          GROUP BY u.id
          ORDER BY u.created_at DESC";

$stmt = $db->prepare($query);
$stmt->execute($params);
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Genel istatistikler
$total_users = count($users);
$admin_users = count(array_filter($users, function($u) { return $u['is_admin']; }));
$regular_users = $total_users - $admin_users;
$total_balance_tl = array_sum(array_column($users, 'balance_tl'));
$total_balance_usd = array_sum(array_column($users, 'balance_usd'));
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kullanıcı Yönetimi - Admin Panel</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="assets/css/admin-mobile.css" rel="stylesheet">
    <style>
        .stats-card { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; }
        .user-activity { font-size: 0.8em; color: #6c757d; }
        .badge-activity { font-size: 0.7em; }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container">
            <a class="navbar-brand" href="admin.php">
                <i class="fas fa-shield-alt"></i> Admin Panel
            </a>
            <div class="navbar-nav ms-auto">
                <a href="admin.php" class="btn btn-outline-light btn-sm me-2">
                    <i class="fas fa-home"></i> Ana Sayfa
                </a>
                <a href="logout.php" class="btn btn-outline-light btn-sm">
                    <i class="fas fa-sign-out-alt"></i> Çıkış
                </a>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1><i class="fas fa-users"></i> Kullanıcı Yönetimi</h1>
            <div>
                <a href="admin_users_detail.php" class="btn btn-info btn-sm">
                    <i class="fas fa-chart-bar"></i> Detaylı Analiz
                </a>
            </div>
        </div>
        
        <!-- Genel İstatistikler -->
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="card stats-card">
                    <div class="card-body text-center">
                        <i class="fas fa-users fa-2x mb-2"></i>
                        <h5>Toplam Kullanıcı</h5>
                        <h3><?php echo $total_users; ?></h3>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-danger text-white">
                    <div class="card-body text-center">
                        <i class="fas fa-user-shield fa-2x mb-2"></i>
                        <h5>Admin</h5>
                        <h3><?php echo $admin_users; ?></h3>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-success text-white">
                    <div class="card-body text-center">
                        <i class="fas fa-lira-sign fa-2x mb-2"></i>
                        <h5>Toplam TL</h5>
                        <h3><?php echo number_format($total_balance_tl, 0); ?></h3>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-info text-white">
                    <div class="card-body text-center">
                        <i class="fas fa-dollar-sign fa-2x mb-2"></i>
                        <h5>Toplam USD</h5>
                        <h3><?php echo number_format($total_balance_usd, 0); ?></h3>
                    </div>
                </div>
            </div>
        </div>
        
        <?php if ($success): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <?php echo $success; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <?php echo $error; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <!-- Arama ve Filtreleme -->
        <div class="card mb-4">
            <div class="card-header">
                <h6><i class="fas fa-search"></i> Arama ve Filtreleme</h6>
            </div>
            <div class="card-body">
                <form method="GET" class="row g-3">
                    <div class="col-md-6">
                        <input type="text" class="form-control" name="search" placeholder="Kullanıcı adı veya email ara..." value="<?php echo htmlspecialchars($search); ?>">
                    </div>
                    <div class="col-md-3">
                        <select name="filter" class="form-select">
                            <option value="all" <?php echo $filter === 'all' ? 'selected' : ''; ?>>Tüm Kullanıcılar</option>
                            <option value="admin" <?php echo $filter === 'admin' ? 'selected' : ''; ?>>Sadece Adminler</option>
                            <option value="user" <?php echo $filter === 'user' ? 'selected' : ''; ?>>Sadece Kullanıcılar</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="fas fa-search"></i> Ara
                        </button>
                    </div>
                    <div class="col-md-1">
                        <a href="admin_users.php" class="btn btn-secondary w-100">
                            <i class="fas fa-times"></i>
                        </a>
                    </div>
                </form>
            </div>
        </div>

        <!-- Kullanıcı Listesi -->
        <div class="card">
            <div class="card-header">
                <h5><i class="fas fa-list"></i> Kullanıcı Listesi (<?php echo count($users); ?> kullanıcı)</h5>
            </div>
            <div class="card-body">
                <!-- Desktop Table View -->
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Kullanıcı</th>
                                <th>Bakiyeler</th>
                                <th>İşlem Geçmişi</th>
                                <th>Son Aktivite</th>
                                <th>İşlemler</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($users as $user): ?>
                            <tr>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <div class="me-3">
                                            <i class="fas fa-user-circle fa-2x text-primary"></i>
                                        </div>
                                        <div>
                                            <strong><?php echo htmlspecialchars($user['username']); ?></strong>
                                            <?php if ($user['is_admin']): ?>
                                                <span class="badge bg-danger ms-1">Admin</span>
                                            <?php endif; ?>
                                            <br>
                                            <small class="text-muted"><?php echo htmlspecialchars($user['email']); ?></small>
                                            <br>
                                            <small class="text-muted">ID: <?php echo $user['id']; ?> • Kayıt: <?php echo date('d.m.Y', strtotime($user['created_at'])); ?></small>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <div class="mb-1">
                                        <span class="badge bg-success">TL: <?php echo number_format($user['balance_tl'], 2); ?></span>
                                    </div>
                                    <div>
                                        <span class="badge bg-info">USD: <?php echo number_format($user['balance_usd'], 2); ?></span>
                                    </div>
                                </td>
                                <td>
                                    <div class="user-activity">
                                        <span class="badge badge-activity bg-primary">İşlem: <?php echo $user['transaction_count']; ?></span>
                                        <span class="badge badge-activity bg-success">Yatırma: <?php echo $user['deposit_count']; ?></span>
                                        <br>
                                        <span class="badge badge-activity bg-warning">Çekme: <?php echo $user['withdrawal_count']; ?></span>
                                        <span class="badge badge-activity bg-info">Portföy: <?php echo $user['portfolio_positions']; ?></span>
                                    </div>
                                </td>
                                <td>
                                    <?php if ($user['last_activity'] && $user['last_activity'] !== '1970-01-01 00:00:00'): ?>
                                        <small class="text-muted">
                                            <?php 
                                            $days_ago = (strtotime('now') - strtotime($user['last_activity'])) / (60*60*24);
                                            if ($days_ago < 1) {
                                                echo 'Bugün';
                                            } elseif ($days_ago < 7) {
                                                echo round($days_ago) . ' gün önce';
                                            } else {
                                                echo date('d.m.Y', strtotime($user['last_activity']));
                                            }
                                            ?>
                                        </small>
                                    <?php else: ?>
                                        <small class="text-muted">Hiç aktivite yok</small>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="btn-group" role="group">
                                        <button class="btn btn-sm btn-primary" 
                                                onclick="showBalanceModal(<?php echo $user['id']; ?>, '<?php echo htmlspecialchars($user['username']); ?>', <?php echo $user['balance_tl']; ?>, <?php echo $user['balance_usd']; ?>)"
                                                title="Bakiye Düzenle">
                                            <i class="fas fa-wallet"></i>
                                        </button>
                                        <a href="admin_user_detail.php?id=<?php echo $user['id']; ?>" class="btn btn-sm btn-info" title="Detaylar">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Mobile Card View -->
                <div class="mobile-user-cards">
                    <?php foreach ($users as $user): ?>
                    <div class="mobile-user-card">
                        <div class="mobile-user-header">
                            <div class="user-avatar">
                                <i class="fas fa-user-circle fa-3x text-primary"></i>
                            </div>
                            <div class="user-info">
                                <h6>
                                    <?php echo htmlspecialchars($user['username']); ?>
                                    <?php if ($user['is_admin']): ?>
                                        <span class="badge bg-danger ms-1">Admin</span>
                                    <?php endif; ?>
                                </h6>
                                <small><?php echo htmlspecialchars($user['email']); ?></small><br>
                                <small class="text-muted">ID: <?php echo $user['id']; ?> • <?php echo date('d.m.Y', strtotime($user['created_at'])); ?></small>
                            </div>
                        </div>
                        
                        <div class="mobile-user-details">
                            <div class="mobile-detail-row">
                                <span class="label">TL Bakiye:</span>
                                <span class="value"><?php echo number_format($user['balance_tl'], 2); ?> TL</span>
                            </div>
                            <div class="mobile-detail-row">
                                <span class="label">USD Bakiye:</span>
                                <span class="value"><?php echo number_format($user['balance_usd'], 2); ?> USD</span>
                            </div>
                            <div class="mobile-detail-row">
                                <span class="label">İşlemler:</span>
                                <span class="value"><?php echo $user['transaction_count']; ?> işlem</span>
                            </div>
                            <div class="mobile-detail-row">
                                <span class="label">Yatırma/Çekme:</span>
                                <span class="value"><?php echo $user['deposit_count']; ?>/<?php echo $user['withdrawal_count']; ?></span>
                            </div>
                            <div class="mobile-detail-row">
                                <span class="label">Portföy:</span>
                                <span class="value"><?php echo $user['portfolio_positions']; ?> pozisyon</span>
                            </div>
                            <div class="mobile-detail-row">
                                <span class="label">Son Aktivite:</span>
                                <span class="value">
                                    <?php if ($user['last_activity'] && $user['last_activity'] !== '1970-01-01 00:00:00'): ?>
                                        <?php 
                                        $days_ago = (strtotime('now') - strtotime($user['last_activity'])) / (60*60*24);
                                        if ($days_ago < 1) {
                                            echo 'Bugün';
                                        } elseif ($days_ago < 7) {
                                            echo round($days_ago) . ' gün önce';
                                        } else {
                                            echo date('d.m.Y', strtotime($user['last_activity']));
                                        }
                                        ?>
                                    <?php else: ?>
                                        Hiç aktivite yok
                                    <?php endif; ?>
                                </span>
                            </div>
                        </div>
                        
                        <div class="mobile-user-actions">
                            <button class="btn btn-primary" 
                                    onclick="showBalanceModal(<?php echo $user['id']; ?>, '<?php echo htmlspecialchars($user['username']); ?>', <?php echo $user['balance_tl']; ?>, <?php echo $user['balance_usd']; ?>)">
                                <i class="fas fa-wallet"></i> Bakiye Düzenle
                            </button>
                            <a href="admin_user_detail.php?id=<?php echo $user['id']; ?>" class="btn btn-info">
                                <i class="fas fa-eye"></i> Detaylar
                            </a>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Bakiye Düzenleme Modal -->
    <div class="modal fade" id="balanceModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Bakiye Düzenle: <span id="modalUsername"></span></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="edit_balance">
                        <input type="hidden" name="user_id" id="modalUserId">
                        
                        <!-- Mevcut Bakiyeler -->
                        <div class="row mb-3">
                            <div class="col-6">
                                <label class="form-label">Mevcut TL Bakiye</label>
                                <input type="text" class="form-control bg-light" id="currentTlBalance" readonly>
                            </div>
                            <div class="col-6">
                                <label class="form-label">Mevcut USD Bakiye</label>
                                <input type="text" class="form-control bg-light" id="currentUsdBalance" readonly>
                            </div>
                        </div>
                        
                        <hr>
                        
                        <div class="mb-3">
                            <label class="form-label">Düzenlenecek Para Birimi</label>
                            <select name="currency" id="currencySelect" class="form-select" required>
                                <option value="tl">Türk Lirası (TL)</option>
                                <option value="usd">Amerikan Doları (USD)</option>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Yeni Bakiye Tutarı</label>
                            <input type="number" name="new_balance" id="newBalanceInput" class="form-control" step="0.01" min="0" required placeholder="Yeni bakiye tutarını giriniz">
                            <small class="text-muted">Kullanıcının yeni bakiye tutarını giriniz (mevcut bakiye bu tutarla değiştirilecek)</small>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Değişiklik Sebebi (Opsiyonel)</label>
                            <textarea name="note" class="form-control" rows="2" placeholder="Bakiye değişikliği sebebini buraya yazabilirsiniz..."></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">İptal</button>
                        <button type="submit" class="btn btn-primary">Bakiyeyi Güncelle</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function showBalanceModal(userId, username, balanceTl, balanceUsd) {
            document.getElementById('modalUserId').value = userId;
            document.getElementById('modalUsername').textContent = username;
            document.getElementById('currentTlBalance').value = parseFloat(balanceTl).toFixed(2) + ' TL';
            document.getElementById('currentUsdBalance').value = parseFloat(balanceUsd).toFixed(2) + ' USD';
            
            // Clear new balance input
            document.getElementById('newBalanceInput').value = '';
            
            const modal = new bootstrap.Modal(document.getElementById('balanceModal'));
            modal.show();
            
            // Focus on new balance input after modal is shown
            setTimeout(() => {
                document.getElementById('newBalanceInput').focus();
            }, 500);
        }
        
        // Update placeholder when currency changes
        document.getElementById('currencySelect').addEventListener('change', function() {
            const currency = this.value.toUpperCase();
            document.getElementById('newBalanceInput').placeholder = `Yeni ${currency} bakiyesini giriniz`;
        });
    </script>
</body>
</html>

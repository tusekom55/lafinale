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

$user_id = intval($_GET['id'] ?? 0);
if ($user_id <= 0) {
    header('Location: admin_users.php');
    exit();
}

$success = '';
$error = '';

// Form işlemleri
if ($_POST) {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'edit_balance') {
        $new_balance = floatval($_POST['new_balance'] ?? 0);
        $currency = $_POST['currency'] ?? 'tl';
        $note = $_POST['note'] ?? '';
        
        if ($new_balance >= 0) {
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
                    $success = "Bakiye başarıyla güncellendi! " . strtoupper($currency) . " bakiyesi: " . number_format($new_balance, 2);
                } else {
                    $error = "Bakiye güncellenirken hata oluştu!";
                }
            }
        } else {
            $error = "Geçersiz bakiye miktarı!";
        }
    }
}

// Kullanıcı bilgilerini getir
$query = "SELECT * FROM users WHERE id = ?";
$stmt = $db->prepare($query);
$stmt->execute([$user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    header('Location: admin_users.php');
    exit();
}

// İşlem geçmişini getir - Basit versiyon
$activities = [];

// Transactions getir
try {
    $query = "SELECT *, 'transaction' as activity_type, created_at as activity_date FROM transactions WHERE user_id = ? ORDER BY created_at DESC LIMIT 20";
    $stmt = $db->prepare($query);
    $stmt->execute([$user_id]);
    $transactions = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $activities = array_merge($activities, $transactions);
} catch(Exception $e) {
    // transactions tablosu yoksa sessizce devam et
}

// Deposits getir
try {
    $query = "SELECT *, 'deposit' as activity_type, created_at as activity_date FROM deposits WHERE user_id = ? ORDER BY created_at DESC LIMIT 20";
    $stmt = $db->prepare($query);
    $stmt->execute([$user_id]);
    $deposits = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $activities = array_merge($activities, $deposits);
} catch(Exception $e) {
    // deposits tablosu yoksa sessizce devam et
}

// Withdrawals getir
try {
    $query = "SELECT *, 'withdrawal' as activity_type, created_at as activity_date FROM withdrawals WHERE user_id = ? ORDER BY created_at DESC LIMIT 20";
    $stmt = $db->prepare($query);
    $stmt->execute([$user_id]);
    $withdrawals = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $activities = array_merge($activities, $withdrawals);
} catch(Exception $e) {
    // withdrawals tablosu yoksa sessizce devam et
}

// Tarihe göre sırala
usort($activities, function($a, $b) {
    return strtotime($b['activity_date']) - strtotime($a['activity_date']);
});

// Portföy bilgilerini getir
$query = "SELECT up.*, m.price as current_price, m.name as market_name,
                 (up.quantity * m.price) as current_value,
                 ((up.quantity * m.price) - up.total_invested) as profit_loss,
                 (((up.quantity * m.price) - up.total_invested) / up.total_invested * 100) as profit_loss_percent
          FROM user_portfolio up
          LEFT JOIN markets m ON up.symbol = m.symbol
          WHERE up.user_id = ? AND up.quantity > 0
          ORDER BY current_value DESC";

$stmt = $db->prepare($query);
$stmt->execute([$user_id]);
$portfolio = $stmt->fetchAll(PDO::FETCH_ASSOC);

// İstatistikler - Basit versiyon
$total_transactions = 0;
$total_deposits = 0;
$total_withdrawals = 0;

foreach ($activities as $activity) {
    if (isset($activity['activity_type'])) {
        switch($activity['activity_type']) {
            case 'transaction':
                $total_transactions++;
                break;
            case 'deposit':
                $total_deposits++;
                break;
            case 'withdrawal':
                $total_withdrawals++;
                break;
        }
    }
}

$portfolio_value = 0;
$portfolio_invested = 0;
foreach ($portfolio as $position) {
    if (isset($position['current_value']) && isset($position['total_invested'])) {
        $portfolio_value += $position['current_value'];
        $portfolio_invested += $position['total_invested'];
    }
}
$portfolio_profit = $portfolio_value - $portfolio_invested;
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($user['username']); ?> - Kullanıcı Detayları</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .profit { color: #28a745; }
        .loss { color: #dc3545; }
        .activity-icon { width: 30px; text-align: center; }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container">
            <a class="navbar-brand" href="admin.php">
                <i class="fas fa-shield-alt"></i> Admin Panel
            </a>
            <div class="navbar-nav ms-auto">
                <a href="admin_users.php" class="btn btn-outline-light btn-sm me-2">
                    <i class="fas fa-arrow-left"></i> Kullanıcı Listesi
                </a>
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
            <h1>
                <i class="fas fa-user-circle text-primary"></i> 
                <?php echo htmlspecialchars($user['username']); ?>
                <?php if ($user['is_admin']): ?>
                    <span class="badge bg-danger">Admin</span>
                <?php endif; ?>
            </h1>
            <div>
                <button class="btn btn-primary" onclick="showBalanceModal()">
                    <i class="fas fa-wallet"></i> Bakiye Düzenle
                </button>
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

        <div class="row">
            <!-- Kullanıcı Bilgileri -->
            <div class="col-md-6 mb-4">
                <div class="card">
                    <div class="card-header">
                        <h5><i class="fas fa-info-circle"></i> Kullanıcı Bilgileri</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-sm-6"><strong>ID:</strong></div>
                            <div class="col-sm-6"><?php echo $user['id']; ?></div>
                        </div>
                        <hr>
                        <div class="row">
                            <div class="col-sm-6"><strong>Email:</strong></div>
                            <div class="col-sm-6"><?php echo htmlspecialchars($user['email']); ?></div>
                        </div>
                        <hr>
                        <div class="row">
                            <div class="col-sm-6"><strong>Kayıt Tarihi:</strong></div>
                            <div class="col-sm-6"><?php echo date('d.m.Y H:i', strtotime($user['created_at'])); ?></div>
                        </div>
                        <hr>
                        <div class="row">
                            <div class="col-sm-6"><strong>Yetki:</strong></div>
                            <div class="col-sm-6">
                                <?php if ($user['is_admin']): ?>
                                    <span class="badge bg-danger">Yönetici</span>
                                <?php else: ?>
                                    <span class="badge bg-success">Kullanıcı</span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Bakiye Bilgileri -->
            <div class="col-md-6 mb-4">
                <div class="card">
                    <div class="card-header">
                        <h5><i class="fas fa-wallet"></i> Bakiye Durumu</h5>
                    </div>
                    <div class="card-body">
                        <div class="row text-center">
                            <div class="col-6">
                                <div class="border-end">
                                    <h4 class="text-success"><?php echo number_format($user['balance_tl'], 2); ?> TL</h4>
                                    <small class="text-muted">Türk Lirası</small>
                                </div>
                            </div>
                            <div class="col-6">
                                <h4 class="text-info"><?php echo number_format($user['balance_usd'], 2); ?> USD</h4>
                                <small class="text-muted">Amerikan Doları</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- İstatistikler -->
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="card bg-primary text-white">
                    <div class="card-body text-center">
                        <i class="fas fa-exchange-alt fa-2x mb-2"></i>
                        <h5>Al-Sat İşlemleri</h5>
                        <h3><?php echo $total_transactions; ?></h3>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-success text-white">
                    <div class="card-body text-center">
                        <i class="fas fa-arrow-down fa-2x mb-2"></i>
                        <h5>Para Yatırma</h5>
                        <h3><?php echo $total_deposits; ?></h3>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-warning text-white">
                    <div class="card-body text-center">
                        <i class="fas fa-arrow-up fa-2x mb-2"></i>
                        <h5>Para Çekme</h5>
                        <h3><?php echo $total_withdrawals; ?></h3>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-info text-white">
                    <div class="card-body text-center">
                        <i class="fas fa-chart-pie fa-2x mb-2"></i>
                        <h5>Portföy Değeri</h5>
                        <h3><?php echo number_format($portfolio_value, 0); ?>$</h3>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <!-- Portföy -->
            <div class="col-md-6 mb-4">
                <div class="card">
                    <div class="card-header">
                        <h5><i class="fas fa-chart-pie"></i> Portföy (<?php echo count($portfolio); ?> pozisyon)</h5>
                    </div>
                    <div class="card-body">
                        <?php if (count($portfolio) > 0): ?>
                            <div class="mb-3 text-center">
                                <h6>Toplam Kar/Zarar: 
                                    <span class="<?php echo $portfolio_profit >= 0 ? 'profit' : 'loss'; ?>">
                                        <?php echo $portfolio_profit >= 0 ? '+' : ''; ?><?php echo number_format($portfolio_profit, 2); ?>$
                                        (<?php echo number_format(($portfolio_profit / $portfolio_invested) * 100, 1); ?>%)
                                    </span>
                                </h6>
                            </div>
                            <div class="table-responsive">
                                <table class="table table-sm">
                                    <thead>
                                        <tr>
                                            <th>Sembol</th>
                                            <th>Miktar</th>
                                            <th>Değer</th>
                                            <th>K/Z %</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($portfolio as $position): ?>
                                        <tr>
                                            <td><strong><?php echo $position['symbol']; ?></strong></td>
                                            <td><?php echo number_format($position['quantity'], 4); ?></td>
                                            <td>$<?php echo number_format($position['current_value'], 0); ?></td>
                                            <td>
                                                <span class="<?php echo $position['profit_loss_percent'] >= 0 ? 'profit' : 'loss'; ?>">
                                                    <?php echo $position['profit_loss_percent'] >= 0 ? '+' : ''; ?><?php echo number_format($position['profit_loss_percent'], 1); ?>%
                                                </span>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php else: ?>
                            <div class="text-center text-muted">
                                <i class="fas fa-inbox fa-3x mb-3"></i>
                                <p>Henüz portföy pozisyonu bulunmuyor.</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- İşlem Geçmişi -->
            <div class="col-md-6 mb-4">
                <div class="card">
                    <div class="card-header">
                        <h5><i class="fas fa-history"></i> Son İşlemler</h5>
                    </div>
                    <div class="card-body">
                        <?php if (count($activities) > 0): ?>
                            <div class="table-responsive" style="max-height: 400px; overflow-y: auto;">
                                <table class="table table-sm">
                                    <tbody>
                                        <?php foreach (array_slice($activities, 0, 20) as $activity): ?>
                                        <tr>
                                            <td class="activity-icon">
                                                <?php
                                                $type = $activity['activity_type'] ?? 'unknown';
                                                switch($type) {
                                                    case 'transaction':
                                                        echo '<i class="fas fa-exchange-alt text-primary"></i>';
                                                        break;
                                                    case 'deposit':
                                                        $status = $activity['status'] ?? 'pending';
                                                        if ($status === 'approved') {
                                                            echo '<i class="fas fa-arrow-down text-success"></i>';
                                                        } elseif ($status === 'rejected') {
                                                            echo '<i class="fas fa-times text-danger"></i>';
                                                        } else {
                                                            echo '<i class="fas fa-clock text-warning"></i>';
                                                        }
                                                        break;
                                                    case 'withdrawal':
                                                        $status = $activity['status'] ?? 'pending';
                                                        if ($status === 'approved') {
                                                            echo '<i class="fas fa-arrow-up text-success"></i>';
                                                        } elseif ($status === 'rejected') {
                                                            echo '<i class="fas fa-times text-danger"></i>';
                                                        } else {
                                                            echo '<i class="fas fa-clock text-warning"></i>';
                                                        }
                                                        break;
                                                    default:
                                                        echo '<i class="fas fa-question text-muted"></i>';
                                                        break;
                                                }
                                                ?>
                                            </td>
                                            <td>
                                                <?php
                                                $type = $activity['activity_type'] ?? 'unknown';
                                                switch($type) {
                                                    case 'transaction':
                                                        $symbol = $activity['symbol'] ?? 'N/A';
                                                        $amount = $activity['amount'] ?? 0;
                                                        echo $symbol . ' - ' . number_format($amount, 4);
                                                        break;
                                                    case 'deposit':
                                                        $amount = $activity['amount'] ?? 0;
                                                        $status = $activity['status'] ?? 'pending';
                                                        echo 'Para Yatırma (' . ucfirst($status) . ') - ' . number_format($amount, 2) . ' TL';
                                                        break;
                                                    case 'withdrawal':
                                                        $amount = $activity['amount'] ?? 0;
                                                        $status = $activity['status'] ?? 'pending';
                                                        echo 'Para Çekme (' . ucfirst($status) . ') - ' . number_format($amount, 2) . ' TL';
                                                        break;
                                                    default:
                                                        echo 'Bilinmeyen işlem';
                                                        break;
                                                }
                                                ?>
                                            </td>
                                            <td class="text-end">
                                                <small class="text-muted">
                                                    <?php 
                                                    $date = $activity['activity_date'] ?? $activity['created_at'] ?? '';
                                                    echo $date ? date('d.m.Y H:i', strtotime($date)) : 'N/A';
                                                    ?>
                                                </small>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php else: ?>
                            <div class="text-center text-muted">
                                <i class="fas fa-history fa-3x mb-3"></i>
                                <p>Henüz işlem geçmişi bulunmuyor.</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Bakiye Düzenleme Modal -->
    <div class="modal fade" id="balanceModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Bakiye Düzenle: <?php echo htmlspecialchars($user['username']); ?></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="edit_balance">
                        
                        <!-- Mevcut Bakiyeler -->
                        <div class="row mb-3">
                            <div class="col-6">
                                <label class="form-label">Mevcut TL Bakiye</label>
                                <input type="text" class="form-control bg-light" value="<?php echo number_format($user['balance_tl'], 2); ?> TL" readonly>
                            </div>
                            <div class="col-6">
                                <label class="form-label">Mevcut USD Bakiye</label>
                                <input type="text" class="form-control bg-light" value="<?php echo number_format($user['balance_usd'], 2); ?> USD" readonly>
                            </div>
                        </div>
                        
                        <hr>
                        
                        <div class="mb-3">
                            <label class="form-label">Düzenlenecek Para Birimi</label>
                            <select name="currency" class="form-select" required>
                                <option value="tl">Türk Lirası (TL)</option>
                                <option value="usd">Amerikan Doları (USD)</option>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Yeni Bakiye Tutarı</label>
                            <input type="number" name="new_balance" class="form-control" step="0.01" min="0" required placeholder="Yeni bakiye tutarını giriniz">
                            <small class="text-muted">Kullanıcının yeni bakiye tutarını giriniz</small>
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
        function showBalanceModal() {
            const modal = new bootstrap.Modal(document.getElementById('balanceModal'));
            modal.show();
        }
    </script>
</body>
</html>

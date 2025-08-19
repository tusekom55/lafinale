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
    
    if ($action === 'approve') {
        $withdrawal_id = intval($_POST['withdrawal_id'] ?? 0);
        
        if ($withdrawal_id > 0) {
            $query = "UPDATE withdrawals SET status = 'approved', processed_at = NOW() WHERE id = ?";
            $stmt = $db->prepare($query);
            if ($stmt->execute([$withdrawal_id])) {
                $success = "Para çekme talebi onaylandı!";
            } else {
                $error = "İşlem sırasında hata oluştu!";
            }
        }
    }
    
    if ($action === 'reject') {
        $withdrawal_id = intval($_POST['withdrawal_id'] ?? 0);
        
        if ($withdrawal_id > 0) {
            // Withdrawal bilgilerini al
            $query = "SELECT * FROM withdrawals WHERE id = ? AND status = 'pending'";
            $stmt = $db->prepare($query);
            $stmt->execute([$withdrawal_id]);
            $withdrawal = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($withdrawal) {
                // Withdrawal reddet ve bakiyeyi iade et
                $db->beginTransaction();
                try {
                    // Withdrawal durumunu güncelle
                    $query = "UPDATE withdrawals SET status = 'rejected', processed_at = NOW() WHERE id = ?";
                    $stmt = $db->prepare($query);
                    $stmt->execute([$withdrawal_id]);
                    
                    // Kullanıcı bakiyesine iade et
                    $query = "UPDATE users SET balance_tl = balance_tl + ? WHERE id = ?";
                    $stmt = $db->prepare($query);
                    $stmt->execute([$withdrawal['amount'], $withdrawal['user_id']]);
                    
                    $db->commit();
                    $success = "Para çekme talebi reddedildi ve tutar kullanıcıya iade edildi!";
                } catch (Exception $e) {
                    $db->rollback();
                    $error = "İşlem sırasında hata oluştu!";
                }
            }
        }
    }
}

// Bekleyen çekme taleplerini getir
$query = "SELECT w.*, u.username, u.email 
          FROM withdrawals w 
          LEFT JOIN users u ON w.user_id = u.id 
          WHERE w.status = 'pending' 
          ORDER BY w.created_at DESC";
$stmt = $db->prepare($query);
$stmt->execute();
$pending_withdrawals = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Son işlemleri getir
$query = "SELECT w.*, u.username, u.email 
          FROM withdrawals w 
          LEFT JOIN users u ON w.user_id = u.id 
          WHERE w.status IN ('approved', 'rejected') 
          ORDER BY w.processed_at DESC 
          LIMIT 20";
$stmt = $db->prepare($query);
$stmt->execute();
$recent_withdrawals = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Para Çekme Onayları - Admin Panel</title>
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
                    <i class="fas fa-home"></i> Ana Sayfa
                </a>
                <a href="logout.php" class="btn btn-outline-light btn-sm">
                    <i class="fas fa-sign-out-alt"></i> Çıkış
                </a>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <h1><i class="fas fa-money-bill-wave"></i> Para Çekme Onayları</h1>
        
        <?php if ($success): ?>
            <div class="alert alert-success"><?php echo $success; ?></div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php endif; ?>

        <!-- Bekleyen Onaylar -->
        <div class="card mb-4">
            <div class="card-header bg-danger text-white">
                <h5><i class="fas fa-clock"></i> Bekleyen Çekme Talepleri (<?php echo count($pending_withdrawals); ?> talep)</h5>
            </div>
            <div class="card-body">
                <?php if (count($pending_withdrawals) > 0): ?>
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Kullanıcı</th>
                                    <th>Tutar</th>
                                    <th>Yöntem</th>
                                    <th>Hesap Bilgisi</th>
                                    <th>Tarih</th>
                                    <th>İşlemler</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($pending_withdrawals as $withdrawal): ?>
                                <tr>
                                    <td><?php echo $withdrawal['id']; ?></td>
                                    <td>
                                        <strong><?php echo htmlspecialchars($withdrawal['username']); ?></strong><br>
                                        <small class="text-muted"><?php echo htmlspecialchars($withdrawal['email']); ?></small>
                                    </td>
                                    <td>
                                        <strong class="text-danger"><?php echo number_format($withdrawal['amount'], 2); ?> TL</strong>
                                    </td>
                                    <td>
                                        <span class="badge bg-info"><?php echo strtoupper($withdrawal['method']); ?></span>
                                    </td>
                                    <td>
                                        <?php if ($withdrawal['method'] === 'iban' && $withdrawal['iban_info']): ?>
                                            <small><?php echo htmlspecialchars($withdrawal['iban_info']); ?></small>
                                        <?php elseif ($withdrawal['method'] === 'papara' && $withdrawal['papara_info']): ?>
                                            <small><?php echo htmlspecialchars($withdrawal['papara_info']); ?></small>
                                        <?php else: ?>
                                            -
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php echo date('d.m.Y H:i', strtotime($withdrawal['created_at'])); ?>
                                    </td>
                                    <td>
                                        <form method="POST" style="display: inline;">
                                            <input type="hidden" name="action" value="approve">
                                            <input type="hidden" name="withdrawal_id" value="<?php echo $withdrawal['id']; ?>">
                                            <button type="submit" class="btn btn-sm btn-success" 
                                                    onclick="return confirm('Bu para çekme talebini onaylamak istediğinizden emin misiniz? Kullanıcıya ödeme yapmanız gerekecek!')">
                                                <i class="fas fa-check"></i> Onayla
                                            </button>
                                        </form>
                                        
                                        <form method="POST" style="display: inline;">
                                            <input type="hidden" name="action" value="reject">
                                            <input type="hidden" name="withdrawal_id" value="<?php echo $withdrawal['id']; ?>">
                                            <button type="submit" class="btn btn-sm btn-danger" 
                                                    onclick="return confirm('Bu para çekme talebini reddetmek istediğinizden emin misiniz? Tutar kullanıcıya iade edilecek!')">
                                                <i class="fas fa-times"></i> Reddet
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle"></i> Bekleyen para çekme talebi bulunmuyor.
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Son İşlemler -->
        <div class="card">
            <div class="card-header">
                <h5><i class="fas fa-history"></i> Son İşlemler</h5>
            </div>
            <div class="card-body">
                <?php if (count($recent_withdrawals) > 0): ?>
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Kullanıcı</th>
                                    <th>Tutar</th>
                                    <th>Durum</th>
                                    <th>İşlem Tarihi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recent_withdrawals as $withdrawal): ?>
                                <tr>
                                    <td><?php echo $withdrawal['id']; ?></td>
                                    <td><?php echo htmlspecialchars($withdrawal['username']); ?></td>
                                    <td><?php echo number_format($withdrawal['amount'], 2); ?> TL</td>
                                    <td>
                                        <?php if ($withdrawal['status'] === 'approved'): ?>
                                            <span class="badge bg-success">Onaylandı</span>
                                        <?php else: ?>
                                            <span class="badge bg-danger">Reddedildi</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo date('d.m.Y H:i', strtotime($withdrawal['processed_at'])); ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="alert alert-info">
                        Henüz işlenmiş talep bulunmuyor.
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

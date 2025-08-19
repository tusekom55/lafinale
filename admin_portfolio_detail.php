<?php
session_start();
require_once 'config/database.php';
require_once 'includes/functions.php';

// Basit admin kontrolü
if (!isset($_SESSION['user_id']) || !isset($_SESSION['is_admin']) || $_SESSION['is_admin'] != 1) {
    header('Location: login.php');
    exit();
}

$database = new Database();
$db = $database->getConnection();

$success = '';
$error = '';
$user_id = intval($_GET['user_id'] ?? 0);

if ($user_id <= 0) {
    header('Location: admin_portfolio.php');
    exit();
}

// Fiyat manipülasyon işlemleri
if ($_POST && isset($_POST['action'])) {
    $action = $_POST['action'];
    
    try {
        if ($action === 'manipulate_price') {
            $symbol = $_POST['symbol'];
            $manipulation_type = $_POST['manipulation_type'];
            $custom_price = floatval($_POST['custom_price'] ?? 0);
            
            // Mevcut fiyatı al
            $query = "SELECT price FROM markets WHERE symbol = ?";
            $stmt = $db->prepare($query);
            $stmt->execute([$symbol]);
            $market = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($market) {
                $old_price = $market['price'];
                $new_price = $old_price;
                
                // Manipülasyon tipine göre yeni fiyat hesapla
                switch ($manipulation_type) {
                    case 'increase_5':
                        $new_price = $old_price * 1.05;
                        break;
                    case 'increase_10':
                        $new_price = $old_price * 1.10;
                        break;
                    case 'increase_20':
                        $new_price = $old_price * 1.20;
                        break;
                    case 'decrease_5':
                        $new_price = $old_price * 0.95;
                        break;
                    case 'decrease_10':
                        $new_price = $old_price * 0.90;
                        break;
                    case 'decrease_20':
                        $new_price = $old_price * 0.80;
                        break;
                    case 'custom':
                        if ($custom_price > 0) {
                            $new_price = $custom_price;
                        }
                        break;
                }
                
                if ($new_price != $old_price && $new_price > 0) {
                    $db->beginTransaction();
                    
                    // Markets tablosundaki fiyatı güncelle
                    $query = "UPDATE markets SET price = ?, change_24h = ? WHERE symbol = ?";
                    $change_percent = (($new_price - $old_price) / $old_price) * 100;
                    $stmt = $db->prepare($query);
                    $stmt->execute([$new_price, $change_percent, $symbol]);
                    
                    // Etkilenen kullanıcı sayısını hesapla
                    $query = "SELECT COUNT(DISTINCT user_id) as affected_users FROM user_portfolio WHERE symbol = ? AND quantity > 0";
                    $stmt = $db->prepare($query);
                    $stmt->execute([$symbol]);
                    $affected_users = $stmt->fetch(PDO::FETCH_ASSOC)['affected_users'];
                    
                    // Manipülasyon geçmişini kaydet
                    $query = "INSERT INTO price_manipulations (admin_id, symbol, old_price, new_price, change_percent, reason, affected_users) 
                             VALUES (?, ?, ?, ?, ?, ?, ?)";
                    $reason = "Fiyat manipülasyonu: " . $manipulation_type . " (" . number_format($change_percent, 2) . "%)";
                    $stmt = $db->prepare($query);
                    $stmt->execute([$_SESSION['user_id'], $symbol, $old_price, $new_price, $change_percent, $reason, $affected_users]);
                    
                    // Admin işlemini logla
                    $query = "INSERT INTO admin_actions (admin_id, action_type, target_user_id, symbol, details, ip_address) 
                             VALUES (?, 'price_change', ?, ?, ?, ?)";
                    $details = json_encode([
                        'old_price' => $old_price,
                        'new_price' => $new_price,
                        'change_percent' => $change_percent,
                        'manipulation_type' => $manipulation_type,
                        'affected_users' => $affected_users
                    ]);
                    $stmt = $db->prepare($query);
                    $stmt->execute([$_SESSION['user_id'], $user_id, $symbol, $details, $_SERVER['REMOTE_ADDR']]);
                    
                    $db->commit();
                    
                    $success = "✅ $symbol fiyatı başarıyla manipüle edildi! " . 
                              number_format($old_price, 4) . "$ → " . number_format($new_price, 4) . "$ " .
                              "(" . ($change_percent >= 0 ? '+' : '') . number_format($change_percent, 2) . "%) " .
                              "[$affected_users kullanıcı etkilendi]";
                } else {
                    $error = "❌ Geçersiz fiyat değeri!";
                }
            }
        }
        
        if ($action === 'reset_price') {
            $symbol = $_POST['symbol'];
            
            // Change yüzdesini sıfırla (basit sıfırlama)
            $query = "UPDATE markets SET change_24h = 0 WHERE symbol = ?";
            $stmt = $db->prepare($query);
            $stmt->execute([$symbol]);
            
            $success = "✅ $symbol değişim yüzdesi sıfırlandı!";
        }
        
    } catch(Exception $e) {
        $db->rollback();
        $error = "❌ Hata: " . $e->getMessage();
    }
}

// Kullanıcı bilgilerini getir (admin kullanıcıları da dahil et)
$query = "SELECT * FROM users WHERE id = ?";
$stmt = $db->prepare($query);
$stmt->execute([$user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    header('Location: admin_portfolio.php');
    exit();
}

// Kullanıcının portföyünü getir
$query = "SELECT 
            up.symbol,
            up.quantity,
            up.avg_price,
            up.total_invested,
            up.created_at as position_created,
            m.name as market_name,
            m.price as current_price,
            m.change_24h as change_percent,
            m.price as original_price,
            0 as is_manipulated,
            0 as manipulation_count,
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

// Portföy toplamları
$total_invested = array_sum(array_column($portfolio, 'total_invested'));
$total_current_value = array_sum(array_column($portfolio, 'current_value'));
$total_profit_loss = $total_current_value - $total_invested;
$total_profit_loss_percent = $total_invested > 0 ? ($total_profit_loss / $total_invested * 100) : 0;

// Son manipülasyon geçmişi
$query = "SELECT pm.*, u.username as admin_username 
          FROM price_manipulations pm
          LEFT JOIN users u ON pm.admin_id = u.id
          WHERE pm.symbol IN (SELECT symbol FROM user_portfolio WHERE user_id = ? AND quantity > 0)
          ORDER BY pm.created_at DESC
          LIMIT 10";
$stmt = $db->prepare($query);
$stmt->execute([$user_id]);
$recent_manipulations = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($user['username']); ?> - Portföy Detayı</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .profit { color: #28a745; font-weight: bold; }
        .loss { color: #dc3545; font-weight: bold; }
        .manipulation-buttons { display: flex; gap: 5px; flex-wrap: wrap; }
        .manipulation-buttons .btn { font-size: 0.8em; padding: 2px 8px; }
        .portfolio-header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; }
        .position-card { border-left: 4px solid #007bff; }
        .manipulated { border-left-color: #dc3545; background-color: #fff5f5; }
        .symbol-badge { font-size: 1.1em; font-weight: bold; }
        .price-display { font-family: 'Courier New', monospace; }
        .stats-grid { background: #f8f9fa; padding: 20px; border-radius: 10px; }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container">
            <a class="navbar-brand" href="admin.php">
                <i class="fas fa-shield-alt"></i> Admin Panel
            </a>
            <div class="navbar-nav ms-auto">
                <a href="admin_portfolio.php" class="btn btn-outline-light btn-sm me-2">
                    <i class="fas fa-arrow-left"></i> Portföy Listesi
                </a>
                <a href="admin.php" class="btn btn-outline-light btn-sm me-2">
                    <i class="fas fa-home"></i> Dashboard
                </a>
                <a href="logout.php" class="btn btn-outline-light btn-sm">
                    <i class="fas fa-sign-out-alt"></i> Çıkış
                </a>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <!-- Kullanıcı Bilgileri -->
        <div class="card portfolio-header mb-4">
            <div class="card-body">
                <div class="row align-items-center">
                    <div class="col-md-3">
                        <h3><i class="fas fa-user-circle"></i> <?php echo htmlspecialchars($user['username']); ?></h3>
                        <p class="mb-0"><?php echo htmlspecialchars($user['email']); ?></p>
                    </div>
                    <div class="col-md-9">
                        <div class="row text-center">
                            <div class="col-md-3">
                                <h5>Portföy Değeri</h5>
                                <h4><?php echo number_format($total_current_value, 2); ?> $</h4>
                            </div>
                            <div class="col-md-3">
                                <h5>Toplam Yatırım</h5>
                                <h4><?php echo number_format($total_invested, 2); ?> $</h4>
                            </div>
                            <div class="col-md-3">
                                <h5>Kar/Zarar</h5>
                                <h4 class="<?php echo $total_profit_loss >= 0 ? 'profit' : 'loss'; ?>">
                                    <?php echo $total_profit_loss >= 0 ? '+' : ''; ?><?php echo number_format($total_profit_loss, 2); ?> $
                                </h4>
                            </div>
                            <div class="col-md-3">
                                <h5>Oran</h5>
                                <h4 class="<?php echo $total_profit_loss_percent >= 0 ? 'profit' : 'loss'; ?>">
                                    <?php echo $total_profit_loss_percent >= 0 ? '+' : ''; ?><?php echo number_format($total_profit_loss_percent, 2); ?>%
                                </h4>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Başarı/Hata Mesajları -->
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
            <!-- Portföy Pozisyonları -->
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header">
                        <h5><i class="fas fa-chart-line"></i> Portföy Pozisyonları (<?php echo count($portfolio); ?> pozisyon)</h5>
                    </div>
                    <div class="card-body">
                        <?php if (count($portfolio) > 0): ?>
                            <?php foreach ($portfolio as $position): ?>
                                <div class="card position-card mb-3 <?php echo $position['is_manipulated'] ? 'manipulated' : ''; ?>">
                                    <div class="card-body">
                                        <div class="row align-items-center">
                                            <div class="col-md-3">
                                                <span class="symbol-badge text-primary"><?php echo $position['symbol']; ?></span>
                                                <?php if ($position['is_manipulated']): ?>
                                                    <span class="badge bg-danger ms-2">Manipüle Edildi</span>
                                                <?php endif; ?>
                                                <div class="small text-muted"><?php echo htmlspecialchars($position['market_name']); ?></div>
                                            </div>
                                            
                                            <div class="col-md-2 text-center">
                                                <div class="small text-muted">Miktar</div>
                                                <div class="fw-bold"><?php echo number_format($position['quantity'], 6); ?></div>
                                            </div>
                                            
                                            <div class="col-md-2 text-center">
                                                <div class="small text-muted">Alış Fiyatı</div>
                                                <div class="price-display"><?php echo number_format($position['avg_price'], 4); ?></div>
                                            </div>
                                            
                                            <div class="col-md-2 text-center">
                                                <div class="small text-muted">Güncel Fiyat</div>
                                                <div class="price-display fw-bold"><?php echo number_format($position['current_price'], 4); ?></div>
                                                <?php if ($position['is_manipulated']): ?>
                                                    <div class="small text-muted">Orijinal: $<?php echo number_format($position['original_price'], 4); ?></div>
                                                <?php endif; ?>
                                            </div>
                                            
                                            <div class="col-md-3 text-center">
                                                <div class="small text-muted">Kar/Zarar</div>
                                                <div class="fw-bold <?php echo $position['profit_loss'] >= 0 ? 'profit' : 'loss'; ?>">
                                                    <?php echo $position['profit_loss'] >= 0 ? '+' : ''; ?><?php echo number_format($position['profit_loss'], 2); ?>$
                                                    <div class="small">(<?php echo $position['profit_loss_percent'] >= 0 ? '+' : ''; ?><?php echo number_format($position['profit_loss_percent'], 2); ?>%)</div>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <hr class="my-2">
                                        
                                        <!-- Manipülasyon Butonları -->
                                        <div class="manipulation-buttons">
                                            <form method="POST" style="display: inline;">
                                                <input type="hidden" name="action" value="manipulate_price">
                                                <input type="hidden" name="symbol" value="<?php echo $position['symbol']; ?>">
                                                <input type="hidden" name="manipulation_type" value="increase_5">
                                                <button type="submit" class="btn btn-success btn-sm" onclick="return confirm('<?php echo $position['symbol']; ?> fiyatını %5 artırmak istediğinizden emin misiniz?')">
                                                    <i class="fas fa-arrow-up"></i> +5%
                                                </button>
                                            </form>
                                            
                                            <form method="POST" style="display: inline;">
                                                <input type="hidden" name="action" value="manipulate_price">
                                                <input type="hidden" name="symbol" value="<?php echo $position['symbol']; ?>">
                                                <input type="hidden" name="manipulation_type" value="increase_10">
                                                <button type="submit" class="btn btn-success btn-sm" onclick="return confirm('<?php echo $position['symbol']; ?> fiyatını %10 artırmak istediğinizden emin misiniz?')">
                                                    <i class="fas fa-arrow-up"></i> +10%
                                                </button>
                                            </form>
                                            
                                            <form method="POST" style="display: inline;">
                                                <input type="hidden" name="action" value="manipulate_price">
                                                <input type="hidden" name="symbol" value="<?php echo $position['symbol']; ?>">
                                                <input type="hidden" name="manipulation_type" value="increase_20">
                                                <button type="submit" class="btn btn-success btn-sm" onclick="return confirm('<?php echo $position['symbol']; ?> fiyatını %20 artırmak istediğinizden emin misiniz?')">
                                                    <i class="fas fa-rocket"></i> +20%
                                                </button>
                                            </form>
                                            
                                            <form method="POST" style="display: inline;">
                                                <input type="hidden" name="action" value="manipulate_price">
                                                <input type="hidden" name="symbol" value="<?php echo $position['symbol']; ?>">
                                                <input type="hidden" name="manipulation_type" value="decrease_5">
                                                <button type="submit" class="btn btn-danger btn-sm" onclick="return confirm('<?php echo $position['symbol']; ?> fiyatını %5 düşürmek istediğinizden emin misiniz?')">
                                                    <i class="fas fa-arrow-down"></i> -5%
                                                </button>
                                            </form>
                                            
                                            <form method="POST" style="display: inline;">
                                                <input type="hidden" name="action" value="manipulate_price">
                                                <input type="hidden" name="symbol" value="<?php echo $position['symbol']; ?>">
                                                <input type="hidden" name="manipulation_type" value="decrease_10">
                                                <button type="submit" class="btn btn-danger btn-sm" onclick="return confirm('<?php echo $position['symbol']; ?> fiyatını %10 düşürmek istediğinizden emin misiniz?')">
                                                    <i class="fas fa-arrow-down"></i> -10%
                                                </button>
                                            </form>
                                            
                                            <button type="button" class="btn btn-warning btn-sm" onclick="showCustomPriceModal('<?php echo $position['symbol']; ?>', <?php echo $position['current_price']; ?>)">
                                                <i class="fas fa-edit"></i> Özel Fiyat
                                            </button>
                                            
                                            <?php if ($position['is_manipulated']): ?>
                                                <form method="POST" style="display: inline;">
                                                    <input type="hidden" name="action" value="reset_price">
                                                    <input type="hidden" name="symbol" value="<?php echo $position['symbol']; ?>">
                                                    <button type="submit" class="btn btn-secondary btn-sm" onclick="return confirm('<?php echo $position['symbol']; ?> fiyatını orijinal değere sıfırlamak istediğinizden emin misiniz?')">
                                                        <i class="fas fa-undo"></i> Sıfırla
                                                    </button>
                                                </form>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="alert alert-info text-center">
                                <i class="fas fa-info-circle fa-2x mb-3"></i>
                                <h5>Portföy Boş</h5>
                                <p>Bu kullanıcının henüz açık pozisyonu bulunmuyor.</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <!-- Sağ Panel -->
            <div class="col-md-4">
                <!-- Kullanıcı İstatistikleri -->
                <div class="card mb-3">
                    <div class="card-header">
                        <h6><i class="fas fa-chart-bar"></i> Kullanıcı İstatistikleri</h6>
                    </div>
                    <div class="card-body stats-grid">
                        <div class="row text-center">
                            <div class="col-6 mb-3">
                                <div class="small text-muted">TL Bakiye</div>
                                <div class="fw-bold"><?php echo number_format($user['balance_tl'], 2); ?> ₺</div>
                            </div>
                            <div class="col-6 mb-3">
                                <div class="small text-muted">USD Bakiye</div>
                                <div class="fw-bold"><?php echo number_format($user['balance_usd'], 2); ?> $</div>
                            </div>
                            <div class="col-6">
                                <div class="small text-muted">Pozisyon Sayısı</div>
                                <div class="fw-bold"><?php echo count($portfolio); ?></div>
                            </div>
                            <div class="col-6">
                                <div class="small text-muted">Kayıt Tarihi</div>
                                <div class="fw-bold"><?php echo date('d.m.Y', strtotime($user['created_at'])); ?></div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Son Manipülasyonlar -->
                <div class="card">
                    <div class="card-header">
                        <h6><i class="fas fa-history"></i> Son Manipülasyonlar</h6>
                    </div>
                    <div class="card-body">
                        <?php if (count($recent_manipulations) > 0): ?>
                            <?php foreach ($recent_manipulations as $manipulation): ?>
                                <div class="border-bottom pb-2 mb-2">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <span class="fw-bold"><?php echo $manipulation['symbol']; ?></span>
                                        <span class="badge <?php echo $manipulation['change_percent'] >= 0 ? 'bg-success' : 'bg-danger'; ?>">
                                            <?php echo $manipulation['change_percent'] >= 0 ? '+' : ''; ?><?php echo number_format($manipulation['change_percent'], 2); ?>%
                                        </span>
                                    </div>
                                    <div class="small text-muted">
                                        $<?php echo number_format($manipulation['old_price'], 4); ?> → 
                                        $<?php echo number_format($manipulation['new_price'], 4); ?>
                                    </div>
                                    <div class="small text-muted">
                                        <?php echo date('d.m.Y H:i', strtotime($manipulation['created_at'])); ?> - 
                                        <?php echo $manipulation['admin_username']; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="text-muted text-center">
                                <i class="fas fa-info-circle"></i> Henüz manipülasyon yapılmamış
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Özel Fiyat Modal -->
    <div class="modal fade" id="customPriceModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="customPriceTitle">Özel Fiyat Belirle</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="manipulate_price">
                        <input type="hidden" name="symbol" id="customPriceSymbol">
                        <input type="hidden" name="manipulation_type" value="custom">
                        
                        <div class="mb-3">
                            <label class="form-label">Güncel Fiyat</label>
                            <input type="text" class="form-control" id="currentPrice" readonly>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Yeni Fiyat ($)</label>
                            <input type="number" step="0.0001" class="form-control" name="custom_price" id="customPrice" required>
                            <div class="form-text">Pozitif bir değer girin</div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">İptal</button>
                        <button type="submit" class="btn btn-primary">Fiyatı Güncelle</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function showCustomPriceModal(symbol, currentPrice) {
            document.getElementById('customPriceSymbol').value = symbol;
            document.getElementById('customPriceTitle').textContent = symbol + ' - Özel Fiyat Belirle';
            document.getElementById('currentPrice').value = '$' + currentPrice.toFixed(4);
            document.getElementById('customPrice').value = currentPrice.toFixed(4);
            
            const modal = new bootstrap.Modal(document.getElementById('customPriceModal'));
            modal.show();
        }
        
        // Auto refresh every 15 seconds
        setTimeout(function() {
            location.reload();
        }, 15000);
    </script>
</body>
</html>

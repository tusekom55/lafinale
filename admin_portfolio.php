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

// Sistem parametrelerini al
$trading_currency = getTradingCurrency();
$currency_symbol = getCurrencySymbol($trading_currency);
$usd_try_rate = getUSDTRYRate();

// Portföy hesaplama fonksiyonu
function calculateUserPortfolio($db, $user_id) {
    try {
        $query = "SELECT 
                    up.symbol,
                    up.quantity,
                    up.avg_price,
                    up.total_invested,
                    m.price as current_price,
                    (up.quantity * m.price) as current_value,
                    ((up.quantity * m.price) - up.total_invested) as profit_loss,
                    (((up.quantity * m.price) - up.total_invested) / up.total_invested * 100) as profit_loss_percent
                  FROM user_portfolio up
                  LEFT JOIN markets m ON up.symbol = m.symbol
                  WHERE up.user_id = ? AND up.quantity > 0
                  ORDER BY current_value DESC";
        
        $stmt = $db->prepare($query);
        $stmt->execute([$user_id]);
        $positions = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $total_invested = 0;
        $total_current_value = 0;
        $total_profit_loss = 0;
        
        foreach ($positions as $position) {
            $total_invested += $position['total_invested'];
            $total_current_value += $position['current_value'];
            $total_profit_loss += $position['profit_loss'];
        }
        
        $total_profit_loss_percent = $total_invested > 0 ? ($total_profit_loss / $total_invested * 100) : 0;
        
        return [
            'positions' => $positions,
            'total_invested' => $total_invested,
            'total_current_value' => $total_current_value,
            'total_profit_loss' => $total_profit_loss,
            'total_profit_loss_percent' => $total_profit_loss_percent,
            'position_count' => count($positions)
        ];
        
    } catch(Exception $e) {
        return [
            'positions' => [],
            'total_invested' => 0,
            'total_current_value' => 0,
            'total_profit_loss' => 0,
            'total_profit_loss_percent' => 0,
            'position_count' => 0
        ];
    }
}

// Arama/filtreleme parametreleri
$search = $_GET['search'] ?? '';
$filter = $_GET['filter'] ?? 'all'; // all, profitable, losing, empty

// Kullanıcıları getir (admin kullanıcıları da dahil et)
$whereClause = "WHERE u.id > 0";
$params = [];

if ($search) {
    $whereClause .= " AND (u.username LIKE ? OR u.email LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

$query = "SELECT u.id, u.username, u.email, u.balance_tl, u.balance_usd, u.created_at,
                 COUNT(up.id) as position_count
          FROM users u
          LEFT JOIN user_portfolio up ON u.id = up.user_id AND up.quantity > 0
          $whereClause
          GROUP BY u.id
          ORDER BY u.created_at DESC";

$stmt = $db->prepare($query);
$stmt->execute($params);
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Her kullanıcı için portföy hesapla
$user_portfolios = [];
foreach ($users as &$user) {
    $portfolio = calculateUserPortfolio($db, $user['id']);
    $user_portfolios[$user['id']] = $portfolio;
    $user['portfolio'] = $portfolio;
}

// Filtreleme uygula
if ($filter !== 'all') {
    $users = array_filter($users, function($user) use ($filter) {
        switch($filter) {
            case 'profitable':
                return $user['portfolio']['total_profit_loss'] > 0;
            case 'losing':
                return $user['portfolio']['total_profit_loss'] < 0;
            case 'empty':
                return $user['portfolio']['position_count'] == 0;
            default:
                return true;
        }
    });
}

// Genel istatistikler
$total_users = count($users);
$profitable_users = count(array_filter($users, function($u) { return $u['portfolio']['total_profit_loss'] > 0; }));
$losing_users = count(array_filter($users, function($u) { return $u['portfolio']['total_profit_loss'] < 0; }));
$total_portfolio_value = array_sum(array_column(array_column($users, 'portfolio'), 'total_current_value'));
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Portföy Yönetimi - Admin Panel</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .profit { color: #28a745; }
        .loss { color: #dc3545; }
        .portfolio-card { transition: all 0.3s; cursor: pointer; }
        .portfolio-card:hover { transform: translateY(-2px); box-shadow: 0 4px 15px rgba(0,0,0,0.1); }
        .profit-badge { background: linear-gradient(45deg, #28a745, #20c997); }
        .loss-badge { background: linear-gradient(45deg, #dc3545, #fd7e14); }
        .stats-card { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; }
        .search-section { background: #f8f9fa; padding: 20px; border-radius: 10px; margin-bottom: 20px; }
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
                    <i class="fas fa-home"></i> Dashboard
                </a>
                <a href="logout.php" class="btn btn-outline-light btn-sm">
                    <i class="fas fa-sign-out-alt"></i> Çıkış
                </a>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1><i class="fas fa-chart-pie"></i> Portföy Yönetimi</h1>
            <div>
                <a href="admin-portfolio-setup.php" class="btn btn-info btn-sm me-2">
                    <i class="fas fa-database"></i> Kurulum
                </a>
                <a href="admin_portfolio_bulk.php" class="btn btn-warning btn-sm">
                    <i class="fas fa-layer-group"></i> Toplu İşlemler
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
                <div class="card bg-success text-white">
                    <div class="card-body text-center">
                        <i class="fas fa-arrow-up fa-2x mb-2"></i>
                        <h5>Karda Olan</h5>
                        <h3><?php echo $profitable_users; ?></h3>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-danger text-white">
                    <div class="card-body text-center">
                        <i class="fas fa-arrow-down fa-2x mb-2"></i>
                        <h5>Zararda Olan</h5>
                        <h3><?php echo $losing_users; ?></h3>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-info text-white">
                    <div class="card-body text-center">
                        <i class="fas fa-money-bill-wave fa-2x mb-2"></i>
                        <h5>Toplam Değer</h5>
                        <h3><?php echo number_format($total_portfolio_value, 0); ?> $</h3>
                    </div>
                </div>
            </div>
        </div>

        <!-- Arama ve Filtreleme -->
        <div class="search-section">
            <form method="GET" class="row g-3">
                <div class="col-md-4">
                    <input type="text" class="form-control" name="search" placeholder="Kullanıcı adı veya email ara..." value="<?php echo htmlspecialchars($search); ?>">
                </div>
                <div class="col-md-3">
                    <select name="filter" class="form-select">
                        <option value="all" <?php echo $filter === 'all' ? 'selected' : ''; ?>>Tüm Kullanıcılar</option>
                        <option value="profitable" <?php echo $filter === 'profitable' ? 'selected' : ''; ?>>Karda Olanlar</option>
                        <option value="losing" <?php echo $filter === 'losing' ? 'selected' : ''; ?>>Zararda Olanlar</option>
                        <option value="empty" <?php echo $filter === 'empty' ? 'selected' : ''; ?>>Boş Portföyler</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="fas fa-search"></i> Ara
                    </button>
                </div>
                <div class="col-md-2">
                    <a href="admin_portfolio.php" class="btn btn-secondary w-100">
                        <i class="fas fa-times"></i> Temizle
                    </a>
                </div>
            </form>
        </div>

        <!-- Kullanıcı Portföyleri -->
        <div class="card">
            <div class="card-header">
                <h5><i class="fas fa-list"></i> Kullanıcı Portföyleri (<?php echo count($users); ?> kullanıcı)</h5>
            </div>
            <div class="card-body">
                <?php if (count($users) > 0): ?>
                    <div class="row">
                        <?php foreach ($users as $user): ?>
                            <div class="col-md-6 col-lg-4 mb-3">
                                <div class="card portfolio-card h-100" onclick="window.location.href='admin_portfolio_detail.php?user_id=<?php echo $user['id']; ?>'">
                                    <div class="card-header d-flex justify-content-between align-items-center">
                                        <strong><?php echo htmlspecialchars($user['username']); ?></strong>
                                        <span class="badge <?php echo $user['portfolio']['total_profit_loss'] >= 0 ? 'profit-badge' : 'loss-badge'; ?>">
                                            <?php if ($user['portfolio']['position_count'] > 0): ?>
                                                <?php echo $user['portfolio']['total_profit_loss'] >= 0 ? '+' : ''; ?>
                                                <?php echo number_format($user['portfolio']['total_profit_loss_percent'], 1); ?>%
                                            <?php else: ?>
                                                Boş
                                            <?php endif; ?>
                                        </span>
                                    </div>
                                    <div class="card-body">
                                        <div class="row text-center">
                                            <div class="col-6">
                                                <small class="text-muted">Portföy Değeri</small>
                                                <h6 class="mb-0"><?php echo number_format($user['portfolio']['total_current_value'], 0); ?> $</h6>
                                            </div>
                                            <div class="col-6">
                                                <small class="text-muted">Kar/Zarar</small>
                                                <h6 class="mb-0 <?php echo $user['portfolio']['total_profit_loss'] >= 0 ? 'profit' : 'loss'; ?>">
                                                    <?php echo $user['portfolio']['total_profit_loss'] >= 0 ? '+' : ''; ?>
                                                    <?php echo number_format($user['portfolio']['total_profit_loss'], 0); ?> $
                                                </h6>
                                            </div>
                                        </div>
                                        
                                        <hr class="my-2">
                                        
                                        <div class="row text-center">
                                            <div class="col-6">
                                                <small class="text-muted">Pozisyon Sayısı</small>
                                                <div><strong><?php echo $user['portfolio']['position_count']; ?></strong></div>
                                            </div>
                                            <div class="col-6">
                                                <small class="text-muted">Nakit Bakiye</small>
                                                <div><strong>
                                                    <?php 
                                                    if ($trading_currency == 1) { // TL Mode
                                                        $total_balance = $user['balance_tl'] + ($user['balance_usd'] * $usd_try_rate);
                                                        echo number_format($total_balance, 0) . ' TL';
                                                    } else { // USD Mode
                                                        $total_balance = $user['balance_usd'] + ($user['balance_tl'] / $usd_try_rate);
                                                        echo number_format($total_balance, 2) . ' USD';
                                                    }
                                                    ?>
                                                </strong></div>
                                            </div>
                                        </div>
                                        
                                        <?php if ($user['portfolio']['position_count'] > 0): ?>
                                            <div class="mt-2">
                                                <small class="text-muted d-block">En Büyük Pozisyonlar:</small>
                                                <?php $top_positions = array_slice($user['portfolio']['positions'], 0, 2); ?>
                                                <?php foreach ($top_positions as $position): ?>
                                                    <span class="badge bg-secondary me-1 mb-1">
                                                        <?php echo $position['symbol']; ?>: 
                                                        <?php echo number_format($position['current_value'], 0); ?>$
                                                    </span>
                                                <?php endforeach; ?>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                    <div class="card-footer">
                                        <small class="text-muted">
                                            <i class="fas fa-user"></i> <?php echo htmlspecialchars($user['email']); ?>
                                        </small>
                                        <div class="float-end">
                                            <i class="fas fa-eye text-primary"></i>
                                            <small class="text-muted">Detay</small>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="alert alert-info text-center">
                        <i class="fas fa-info-circle fa-2x mb-3"></i>
                        <h5>Kullanıcı bulunamadı</h5>
                        <p>Arama kriterlerinize uygun kullanıcı bulunmuyor.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Auto refresh every 30 seconds
        setTimeout(function() {
            location.reload();
        }, 30000);
    </script>
</body>
</html>

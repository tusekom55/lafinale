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

// Basit istatistikler
$query = "SELECT COUNT(*) as total_users FROM users";
$stmt = $db->prepare($query);
$stmt->execute();
$total_users = $stmt->fetch(PDO::FETCH_ASSOC)['total_users'];

$query = "SELECT COUNT(*) as total_transactions FROM transactions";
$stmt = $db->prepare($query);
$stmt->execute();
$total_transactions = $stmt->fetch(PDO::FETCH_ASSOC)['total_transactions'];

$query = "SELECT COUNT(*) as pending_deposits FROM deposits WHERE status = 'pending'";
$stmt = $db->prepare($query);
$stmt->execute();
$pending_deposits = $stmt->fetch(PDO::FETCH_ASSOC)['pending_deposits'];
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Panel - GlobalBorsa</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="assets/css/admin-mobile.css" rel="stylesheet">
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container">
            <a class="navbar-brand" href="admin.php">
                <i class="fas fa-shield-alt"></i> Admin Panel
            </a>
            <div class="navbar-nav ms-auto">
                <span class="navbar-text me-3">
                    Hoşgeldiniz, Admin
                </span>
                <a href="logout.php" class="btn btn-outline-light btn-sm">
                    <i class="fas fa-sign-out-alt"></i> Çıkış
                </a>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <h1><i class="fas fa-tachometer-alt"></i> Admin Dashboard</h1>
        
        <div class="row mt-4">
            <div class="col-md-4">
                <div class="card bg-primary text-white">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <i class="fas fa-users fa-3x me-3"></i>
                            <div>
                                <h5>Toplam Kullanıcı</h5>
                                <h2><?php echo $total_users; ?></h2>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-md-4">
                <div class="card bg-success text-white">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <i class="fas fa-exchange-alt fa-3x me-3"></i>
                            <div>
                                <h5>Toplam İşlem</h5>
                                <h2><?php echo $total_transactions; ?></h2>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-md-4">
                <div class="card bg-warning text-white">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <i class="fas fa-clock fa-3x me-3"></i>
                            <div>
                                <h5>Bekleyen Yatırım</h5>
                                <h2><?php echo $pending_deposits; ?></h2>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row mt-4">
            <div class="col-md-6 col-lg-4">
                <div class="card">
                    <div class="card-header">
                        <h5><i class="fas fa-users"></i> Kullanıcı Yönetimi</h5>
                    </div>
                    <div class="card-body">
                        <p>Kullanıcı listesi, bakiye güncelleme ve admin yetkileri</p>
                        <a href="admin_users.php" class="btn btn-primary">
                            <i class="fas fa-arrow-right"></i> Kullanıcıları Yönet
                        </a>
                    </div>
                </div>
            </div>
            
            <div class="col-md-6 col-lg-4">
                <div class="card">
                    <div class="card-header">
                        <h5><i class="fas fa-money-bill"></i> Para Yatırma</h5>
                    </div>
                    <div class="card-body">
                        <p>Bekleyen para yatırma taleplerini onaylayın</p>
                        <a href="admin_deposits.php" class="btn btn-success">
                            <i class="fas fa-arrow-right"></i> Yatırma Onayları
                        </a>
                    </div>
                </div>
            </div>

            <div class="col-md-6 col-lg-4">
                <div class="card">
                    <div class="card-header">
                        <h5><i class="fas fa-money-bill-wave"></i> Para Çekme</h5>
                    </div>
                    <div class="card-body">
                        <p>Bekleyen para çekme taleplerini onaylayın</p>
                        <a href="admin_withdrawals.php" class="btn btn-danger">
                            <i class="fas fa-arrow-right"></i> Çekme Onayları
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <div class="row mt-4">
            <div class="col-md-6 col-lg-4">
                <div class="card">
                    <div class="card-header">
                        <h5><i class="fas fa-exchange-alt"></i> İşlem Yönetimi</h5>
                    </div>
                    <div class="card-body">
                        <p>Tüm işlemleri görüntüle ve yönet</p>
                        <a href="admin_transactions.php" class="btn btn-info">
                            <i class="fas fa-arrow-right"></i> İşlemleri Görüntüle
                        </a>
                    </div>
                </div>
            </div>
            
            <div class="col-md-6 col-lg-4">
                <div class="card">
                    <div class="card-header">
                        <h5><i class="fas fa-chart-line"></i> Market Yönetimi</h5>
                    </div>
                    <div class="card-body">
                        <p>Semboller ve fiyatları yönet</p>
                        <a href="admin_markets.php" class="btn btn-warning">
                            <i class="fas fa-arrow-right"></i> Marketleri Yönet
                        </a>
                    </div>
                </div>
            </div>

            <div class="col-md-6 col-lg-4">
                <div class="card">
                    <div class="card-header">
                        <h5><i class="fas fa-chart-pie"></i> Portföy Yönetimi</h5>
                    </div>
                    <div class="card-body">
                        <p>Kullanıcı portföyleri ve fiyat manipülasyonu</p>
                        <a href="admin_portfolio.php" class="btn btn-primary">
                            <i class="fas fa-arrow-right"></i> Portföyleri Yönet
                        </a>
                    </div>
                </div>
            </div>
            
            <div class="col-md-6 col-lg-4">
                <div class="card">
                    <div class="card-header">
                        <h5><i class="fas fa-credit-card"></i> Ödeme Yöntemleri</h5>
                    </div>
                    <div class="card-body">
                        <p>Ödeme yöntemlerini düzenle ve yönet</p>
                        <a href="admin_payment_methods.php" class="btn btn-info">
                            <i class="fas fa-arrow-right"></i> Ödeme Yöntemleri
                        </a>
                    </div>
                </div>
            </div>
            
            <div class="col-md-6 col-lg-4">
                <div class="card">
                    <div class="card-header">
                        <h5><i class="fas fa-cogs"></i> Sistem Ayarları</h5>
                    </div>
                    <div class="card-body">
                        <p>Platform ayarları ve konfigürasyon</p>
                        <a href="admin_settings.php" class="btn btn-secondary">
                            <i class="fas fa-arrow-right"></i> Ayarları Düzenle
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

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

// Ayarları yükle
function getSettings($db) {
    $settings = [];
    $query = "SELECT * FROM settings";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($results as $setting) {
        $settings[$setting['setting_key']] = $setting['setting_value'];
    }
    
    // Varsayılan ayarlar
    $defaults = [
        'site_name' => 'GlobalBorsa',
        'site_description' => 'Kripto Para Alım Satım Platformu',
        'maintenance_mode' => '0',
        'registration_enabled' => '1',
        'trading_fee_percent' => '0.1',
        'min_deposit_amount' => '10',
        'min_withdrawal_amount' => '10',
        'max_withdrawal_amount' => '10000',
        'contact_email' => 'info@globalborsa.com',
        'company_address' => 'İstanbul, Türkiye',
        'api_update_interval' => '30',
        'email_notifications' => '1',
        'sms_notifications' => '0',
        'jivochat_code' => ''
    ];
    
    return array_merge($defaults, $settings);
}

// Logo upload işlemi
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'upload_logo') {
    try {
        $logo_type = $_POST['logo_type'] ?? 'main_logo';
        
        if (isset($_FILES['logo_file']) && $_FILES['logo_file']['error'] == 0) {
            $file = $_FILES['logo_file'];
            
            // Dosya doğrulama
            $allowed_types = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
            $max_size = 5 * 1024 * 1024; // 5MB
            
            if (!in_array($file['type'], $allowed_types)) {
                throw new Exception('Sadece JPG, PNG, GIF ve WebP formatları kabul edilir.');
            }
            
            if ($file['size'] > $max_size) {
                throw new Exception('Dosya boyutu 5MB\'dan küçük olmalıdır.');
            }
            
            // Dosya adı oluştur
            $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
            $filename = $logo_type . '_' . time() . '.' . $extension;
            $upload_path = 'uploads/logos/' . $filename;
            
            // Dosyayı yükle
            if (move_uploaded_file($file['tmp_name'], $upload_path)) {
                // Eski logoyu sil
                $old_logo_query = "SELECT setting_value FROM settings WHERE setting_key = ?";
                $old_stmt = $db->prepare($old_logo_query);
                $old_stmt->execute([$logo_type]);
                $old_logo = $old_stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($old_logo && file_exists($old_logo['setting_value'])) {
                    unlink($old_logo['setting_value']);
                }
                
                // Veritabanını güncelle
                $query = "INSERT INTO settings (setting_key, setting_value) VALUES (?, ?) 
                         ON DUPLICATE KEY UPDATE setting_value = ?";
                $stmt = $db->prepare($query);
                $stmt->execute([$logo_type, $upload_path, $upload_path]);
                
                $success_message = "Logo başarıyla yüklendi!";
            } else {
                throw new Exception('Dosya yüklenirken hata oluştu.');
            }
        } else {
            throw new Exception('Lütfen geçerli bir dosya seçin.');
        }
    } catch(Exception $e) {
        $error_message = "Logo yükleme hatası: " . $e->getMessage();
    }
}

// Logo silme işlemi
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'delete_logo') {
    try {
        $logo_type = $_POST['logo_type'] ?? '';
        
        // Mevcut logoyu al
        $query = "SELECT setting_value FROM settings WHERE setting_key = ?";
        $stmt = $db->prepare($query);
        $stmt->execute([$logo_type]);
        $logo = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($logo && file_exists($logo['setting_value'])) {
            unlink($logo['setting_value']);
        }
        
        // Veritabanından sil
        $query = "DELETE FROM settings WHERE setting_key = ?";
        $stmt = $db->prepare($query);
        $stmt->execute([$logo_type]);
        
        $success_message = "Logo başarıyla silindi!";
    } catch(Exception $e) {
        $error_message = "Logo silme hatası: " . $e->getMessage();
    }
}

// Ayar güncelleme
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'update_settings') {
    try {
        foreach ($_POST as $key => $value) {
            if ($key !== 'action') {
                // Ayar varsa güncelle, yoksa ekle
                $query = "INSERT INTO settings (setting_key, setting_value) VALUES (:key, :value) 
                         ON DUPLICATE KEY UPDATE setting_value = :value";
                $stmt = $db->prepare($query);
                $stmt->bindParam(':key', $key);
                $stmt->bindParam(':value', $value);
                $stmt->execute();
            }
        }
        $success_message = "Ayarlar başarıyla güncellendi!";
    } catch(Exception $e) {
        $error_message = "Hata: " . $e->getMessage();
    }
}

// Cache temizleme
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'clear_cache') {
    try {
        // Cache dosyalarını temizle (varsa)
        $cache_files = glob('cache/*.php');
        foreach($cache_files as $file) {
            if(is_file($file)) {
                unlink($file);
            }
        }
        $success_message = "Cache başarıyla temizlendi!";
    } catch(Exception $e) {
        $error_message = "Cache temizlenirken hata oluştu: " . $e->getMessage();
    }
}

// Veritabanı optimizasyonu
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'optimize_db') {
    try {
        $tables = ['users', 'transactions', 'deposits', 'withdrawals', 'markets', 'settings'];
        foreach($tables as $table) {
            $query = "OPTIMIZE TABLE " . $table;
            $stmt = $db->prepare($query);
            $stmt->execute();
        }
        $success_message = "Veritabanı başarıyla optimize edildi!";
    } catch(Exception $e) {
        $error_message = "Veritabanı optimize edilirken hata oluştu: " . $e->getMessage();
    }
}

$settings = getSettings($db);

// Sistem istatistikleri
$stats = [];
try {
    // Toplam kullanıcı sayısı
    $query = "SELECT COUNT(*) as count FROM users";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $stats['total_users'] = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    
    // Aktif kullanıcı sayısı (son 30 gün)
    $query = "SELECT COUNT(*) as count FROM users WHERE last_login >= DATE_SUB(NOW(), INTERVAL 30 DAY)";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $stats['active_users'] = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    
    // Toplam işlem sayısı
    $query = "SELECT COUNT(*) as count FROM transactions";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $stats['total_transactions'] = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    
    // Veritabanı boyutu
    $query = "SELECT ROUND(SUM(data_length + index_length) / 1024 / 1024, 2) AS db_size_mb FROM information_schema.tables WHERE table_schema = DATABASE()";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    $stats['db_size'] = $result['db_size_mb'] ?: 0;
    
} catch(Exception $e) {
    $stats = [
        'total_users' => 0,
        'active_users' => 0,
        'total_transactions' => 0,
        'db_size' => 0
    ];
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sistem Ayarları - Admin Panel</title>
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
        <h1><i class="fas fa-cogs"></i> Sistem Ayarları</h1>

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

        <!-- Sistem İstatistikleri -->
        <div class="row mt-4">
            <div class="col-md-3">
                <div class="card bg-primary text-white">
                    <div class="card-body text-center">
                        <i class="fas fa-users fa-2x mb-2"></i>
                        <h5>Toplam Kullanıcı</h5>
                        <h3><?php echo number_format($stats['total_users']); ?></h3>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-success text-white">
                    <div class="card-body text-center">
                        <i class="fas fa-user-check fa-2x mb-2"></i>
                        <h5>Aktif Kullanıcı</h5>
                        <h3><?php echo number_format($stats['active_users']); ?></h3>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-info text-white">
                    <div class="card-body text-center">
                        <i class="fas fa-exchange-alt fa-2x mb-2"></i>
                        <h5>Toplam İşlem</h5>
                        <h3><?php echo number_format($stats['total_transactions']); ?></h3>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-warning text-white">
                    <div class="card-body text-center">
                        <i class="fas fa-database fa-2x mb-2"></i>
                        <h5>DB Boyutu</h5>
                        <h3><?php echo $stats['db_size']; ?> MB</h3>
                    </div>
                </div>
            </div>
        </div>

        <!-- Logo Yönetimi -->
        <div class="row mt-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h5><i class="fas fa-image"></i> Site Logo Yönetimi</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <!-- Ana Logo -->
                            <div class="col-md-4">
                                <div class="card border">
                                    <div class="card-header bg-light">
                                        <h6 class="mb-0"><i class="fas fa-home"></i> Ana Logo</h6>
                                        <small class="text-muted">Sitenin ana logosu (navbar, ana sayfa)</small>
                                    </div>
                                    <div class="card-body text-center">
                                        <?php if (isset($settings['main_logo']) && file_exists($settings['main_logo'])): ?>
                                            <img src="<?php echo $settings['main_logo']; ?>" alt="Ana Logo" class="img-fluid mb-3" style="max-height: 100px; max-width: 100%;">
                                            <br>
                                            <form method="POST" style="display: inline;">
                                                <input type="hidden" name="action" value="delete_logo">
                                                <input type="hidden" name="logo_type" value="main_logo">
                                                <button type="submit" class="btn btn-danger btn-sm" onclick="return confirm('Ana logoyu silmek istediğinizden emin misiniz?')">
                                                    <i class="fas fa-trash"></i> Sil
                                                </button>
                                            </form>
                                        <?php else: ?>
                                            <i class="fas fa-image fa-3x text-muted mb-3"></i>
                                            <p class="text-muted">Logo yüklenmemiş</p>
                                        <?php endif; ?>
                                        
                                        <form method="POST" enctype="multipart/form-data" class="mt-3">
                                            <input type="hidden" name="action" value="upload_logo">
                                            <input type="hidden" name="logo_type" value="main_logo">
                                            <div class="mb-2">
                                                <input type="file" class="form-control form-control-sm" name="logo_file" accept="image/*" required>
                                            </div>
                                            <button type="submit" class="btn btn-primary btn-sm">
                                                <i class="fas fa-upload"></i> Yükle
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Favicon -->
                            <div class="col-md-4">
                                <div class="card border">
                                    <div class="card-header bg-light">
                                        <h6 class="mb-0"><i class="fas fa-star"></i> Favicon</h6>
                                        <small class="text-muted">Tarayıcı sekmesinde görünen icon</small>
                                    </div>
                                    <div class="card-body text-center">
                                        <?php if (isset($settings['favicon']) && file_exists($settings['favicon'])): ?>
                                            <img src="<?php echo $settings['favicon']; ?>" alt="Favicon" class="img-fluid mb-3" style="max-height: 64px; max-width: 64px;">
                                            <br>
                                            <form method="POST" style="display: inline;">
                                                <input type="hidden" name="action" value="delete_logo">
                                                <input type="hidden" name="logo_type" value="favicon">
                                                <button type="submit" class="btn btn-danger btn-sm" onclick="return confirm('Favicon\'u silmek istediğinizden emin misiniz?')">
                                                    <i class="fas fa-trash"></i> Sil
                                                </button>
                                            </form>
                                        <?php else: ?>
                                            <i class="fas fa-star fa-3x text-muted mb-3"></i>
                                            <p class="text-muted">Favicon yüklenmemiş</p>
                                        <?php endif; ?>
                                        
                                        <form method="POST" enctype="multipart/form-data" class="mt-3">
                                            <input type="hidden" name="action" value="upload_logo">
                                            <input type="hidden" name="logo_type" value="favicon">
                                            <div class="mb-2">
                                                <input type="file" class="form-control form-control-sm" name="logo_file" accept="image/*" required>
                                            </div>
                                            <button type="submit" class="btn btn-primary btn-sm">
                                                <i class="fas fa-upload"></i> Yükle
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Footer Logo -->
                            <div class="col-md-4">
                                <div class="card border">
                                    <div class="card-header bg-light">
                                        <h6 class="mb-0"><i class="fas fa-layer-group"></i> Footer Logo</h6>
                                        <small class="text-muted">Sayfa altında görünen logo</small>
                                    </div>
                                    <div class="card-body text-center">
                                        <?php if (isset($settings['footer_logo']) && file_exists($settings['footer_logo'])): ?>
                                            <img src="<?php echo $settings['footer_logo']; ?>" alt="Footer Logo" class="img-fluid mb-3" style="max-height: 100px; max-width: 100%;">
                                            <br>
                                            <form method="POST" style="display: inline;">
                                                <input type="hidden" name="action" value="delete_logo">
                                                <input type="hidden" name="logo_type" value="footer_logo">
                                                <button type="submit" class="btn btn-danger btn-sm" onclick="return confirm('Footer logoyu silmek istediğinizden emin misiniz?')">
                                                    <i class="fas fa-trash"></i> Sil
                                                </button>
                                            </form>
                                        <?php else: ?>
                                            <i class="fas fa-layer-group fa-3x text-muted mb-3"></i>
                                            <p class="text-muted">Footer logo yüklenmemiş</p>
                                        <?php endif; ?>
                                        
                                        <form method="POST" enctype="multipart/form-data" class="mt-3">
                                            <input type="hidden" name="action" value="upload_logo">
                                            <input type="hidden" name="logo_type" value="footer_logo">
                                            <div class="mb-2">
                                                <input type="file" class="form-control form-control-sm" name="logo_file" accept="image/*" required>
                                            </div>
                                            <button type="submit" class="btn btn-primary btn-sm">
                                                <i class="fas fa-upload"></i> Yükle
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="alert alert-info mt-3">
                            <h6><i class="fas fa-info-circle"></i> Logo Yükleme Kuralları:</h6>
                            <ul class="mb-0">
                                <li><strong>Dosya Formatları:</strong> JPG, PNG, GIF, WebP</li>
                                <li><strong>Maksimum Boyut:</strong> 5MB</li>
                                <li><strong>Ana Logo Önerilen Boyut:</strong> 200x60px (genişlik x yükseklik)</li>
                                <li><strong>Favicon Önerilen Boyut:</strong> 32x32px veya 64x64px</li>
                                <li><strong>Footer Logo:</strong> Ana logo ile aynı boyutlarda olabilir</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row mt-4">
            <!-- Genel Ayarlar -->
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header">
                        <h5><i class="fas fa-sliders-h"></i> Genel Ayarlar</h5>
                    </div>
                    <div class="card-body">
                        <form method="POST">
                            <input type="hidden" name="action" value="update_settings">
                            
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Site Adı</label>
                                        <input type="text" class="form-control" name="site_name" value="<?php echo htmlspecialchars($settings['site_name']); ?>">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Site Açıklaması</label>
                                        <input type="text" class="form-control" name="site_description" value="<?php echo htmlspecialchars($settings['site_description']); ?>">
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">İletişim E-posta</label>
                                        <input type="email" class="form-control" name="contact_email" value="<?php echo htmlspecialchars($settings['contact_email']); ?>">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Şirket Adresi</label>
                                        <input type="text" class="form-control" name="company_address" value="<?php echo htmlspecialchars($settings['company_address']); ?>">
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-3">
                                    <div class="mb-3">
                                        <label class="form-label">İşlem Komisyonu (%)</label>
                                        <input type="number" step="0.01" class="form-control" name="trading_fee_percent" value="<?php echo $settings['trading_fee_percent']; ?>">
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="mb-3">
                                        <label class="form-label">Min. Yatırım ($)</label>
                                        <input type="number" class="form-control" name="min_deposit_amount" value="<?php echo $settings['min_deposit_amount']; ?>">
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="mb-3">
                                        <label class="form-label">Min. Çekim ($)</label>
                                        <input type="number" class="form-control" name="min_withdrawal_amount" value="<?php echo $settings['min_withdrawal_amount']; ?>">
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="mb-3">
                                        <label class="form-label">Max. Çekim ($)</label>
                                        <input type="number" class="form-control" name="max_withdrawal_amount" value="<?php echo $settings['max_withdrawal_amount']; ?>">
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">API Güncelleme Aralığı (saniye)</label>
                                        <input type="number" class="form-control" name="api_update_interval" value="<?php echo $settings['api_update_interval']; ?>">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Sistem Durumu</label>
                                        <div class="form-check form-switch">
                                            <input class="form-check-input" type="checkbox" name="maintenance_mode" value="1" <?php echo $settings['maintenance_mode'] == '1' ? 'checked' : ''; ?>>
                                            <label class="form-check-label">Bakım Modu</label>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-4">
                                    <div class="form-check form-switch mb-3">
                                        <input class="form-check-input" type="checkbox" name="registration_enabled" value="1" <?php echo $settings['registration_enabled'] == '1' ? 'checked' : ''; ?>>
                                        <label class="form-check-label">Kayıt Açık</label>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-check form-switch mb-3">
                                        <input class="form-check-input" type="checkbox" name="email_notifications" value="1" <?php echo $settings['email_notifications'] == '1' ? 'checked' : ''; ?>>
                                        <label class="form-check-label">Email Bildirimleri</label>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-check form-switch mb-3">
                                        <input class="form-check-input" type="checkbox" name="sms_notifications" value="1" <?php echo $settings['sms_notifications'] == '1' ? 'checked' : ''; ?>>
                                        <label class="form-check-label">SMS Bildirimleri</label>
                                    </div>
                                </div>
                            </div>

                            <!-- JivoChat Entegrasyonu -->
                            <div class="row">
                                <div class="col-12">
                                    <div class="mb-3">
                                        <label class="form-label"><i class="fas fa-comments"></i> JivoChat Canlı Destek Kodu</label>
                                        <textarea class="form-control" name="jivochat_code" rows="4" placeholder="JivoChat entegrasyon kodunuzu buraya yapıştırın..."><?php echo htmlspecialchars($settings['jivochat_code']); ?></textarea>
                                        <div class="form-text">
                                            <small class="text-muted">
                                                <i class="fas fa-info-circle"></i> 
                                                JivoChat hesabınızdan aldığınız script kodunu buraya yapıştırın. 
                                                Kod tüm sayfalarda görünecek ve canlı destek özelliği aktif hale gelecektir.
                                            </small>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i> Ayarları Kaydet
                            </button>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Sistem İşlemleri -->
            <div class="col-md-4">
                <div class="card">
                    <div class="card-header">
                        <h5><i class="fas fa-tools"></i> Sistem İşlemleri</h5>
                    </div>
                    <div class="card-body">
                        <!-- Cache Temizleme -->
                        <div class="mb-3">
                            <h6><i class="fas fa-broom"></i> Cache Temizleme</h6>
                            <p class="text-muted small">Sistem cache dosyalarını temizler</p>
                            <form method="POST" style="display: inline;">
                                <input type="hidden" name="action" value="clear_cache">
                                <button type="submit" class="btn btn-warning btn-sm w-100" onclick="return confirm('Cache temizlensin mi?')">
                                    <i class="fas fa-trash-alt"></i> Cache Temizle
                                </button>
                            </form>
                        </div>

                        <hr>

                        <!-- Veritabanı Optimizasyonu -->
                        <div class="mb-3">
                            <h6><i class="fas fa-database"></i> Veritabanı Optimizasyonu</h6>
                            <p class="text-muted small">Veritabanı tablolarını optimize eder</p>
                            <form method="POST" style="display: inline;">
                                <input type="hidden" name="action" value="optimize_db">
                                <button type="submit" class="btn btn-info btn-sm w-100" onclick="return confirm('Veritabanı optimize edilsin mi?')">
                                    <i class="fas fa-wrench"></i> DB Optimize Et
                                </button>
                            </form>
                        </div>

                        <hr>

                        <!-- Hızlı Erişimler -->
                        <div class="mb-3">
                            <h6><i class="fas fa-external-link-alt"></i> Hızlı Erişimler</h6>
                            <div class="d-grid gap-2">
                                <a href="admin_users.php" class="btn btn-outline-primary btn-sm">
                                    <i class="fas fa-users"></i> Kullanıcılar
                                </a>
                                <a href="admin_deposits.php" class="btn btn-outline-success btn-sm">
                                    <i class="fas fa-money-bill"></i> Para Yatırma
                                </a>
                                <a href="admin_markets.php" class="btn btn-outline-warning btn-sm">
                                    <i class="fas fa-chart-line"></i> Marketler
                                </a>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Sistem Bilgileri -->
                <div class="card mt-3">
                    <div class="card-header">
                        <h6><i class="fas fa-info-circle"></i> Sistem Bilgileri</h6>
                    </div>
                    <div class="card-body">
                        <small>
                            <strong>PHP Sürümü:</strong> <?php echo phpversion(); ?><br>
                            <strong>Sunucu:</strong> <?php echo $_SERVER['SERVER_SOFTWARE']; ?><br>
                            <strong>Zaman:</strong> <?php echo date('d.m.Y H:i:s'); ?><br>
                            <strong>Zaman Dilimi:</strong> <?php echo date_default_timezone_get(); ?>
                        </small>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

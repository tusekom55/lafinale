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

// Homepage içeriklerini getir
function getHomepageContent($db, $language = 'tr') {
    $query = "SELECT section_name, content_key, content_value, content_type 
              FROM homepage_content 
              WHERE language = ? AND is_active = 1 
              ORDER BY section_name, display_order, content_key";
    $stmt = $db->prepare($query);
    $stmt->execute([$language]);
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $content = [];
    foreach ($results as $row) {
        $content[$row['section_name']][$row['content_key']] = [
            'value' => $row['content_value'],
            'type' => $row['content_type']
        ];
    }
    
    return $content;
}

// İçerik güncelleme
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'update_content') {
    try {
        $language = $_POST['language'] ?? 'tr';
        $updated_count = 0;
        
        foreach ($_POST as $key => $value) {
            if (strpos($key, '__') !== false && $key !== 'action' && $key !== 'language') {
                list($section, $content_key) = explode('__', $key, 2);
                
                $query = "UPDATE homepage_content 
                         SET content_value = ?, updated_at = NOW() 
                         WHERE section_name = ? AND content_key = ? AND language = ?";
                $stmt = $db->prepare($query);
                $stmt->execute([$value, $section, $content_key, $language]);
                $updated_count++;
            }
        }
        
        $success_message = "İçerik başarıyla güncellendi! ($updated_count öğe güncellendi)";
        
        // Cache temizle (varsa)
        if (function_exists('opcache_reset')) {
            opcache_reset();
        }
        
    } catch(Exception $e) {
        $error_message = "Hata: " . $e->getMessage();
    }
}

// Resim yükleme
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'upload_image') {
    try {
        $image_key = $_POST['image_key'] ?? '';
        
        if (isset($_FILES['image_file']) && $_FILES['image_file']['error'] == 0) {
            $file = $_FILES['image_file'];
            
            // Dosya doğrulama
            $allowed_types = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
            $max_size = 10 * 1024 * 1024; // 10MB
            
            if (!in_array($file['type'], $allowed_types)) {
                throw new Exception('Sadece JPG, PNG, GIF ve WebP formatları kabul edilir.');
            }
            
            if ($file['size'] > $max_size) {
                throw new Exception('Dosya boyutu 10MB\'dan küçük olmalıdır.');
            }
            
            // Uploads dizini oluştur
            if (!file_exists('uploads')) {
                mkdir('uploads', 0755, true);
            }
            if (!file_exists('uploads/homepage')) {
                mkdir('uploads/homepage', 0755, true);
            }
            
            // Dosya adı oluştur
            $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
            $filename = $image_key . '_' . time() . '.' . $extension;
            $upload_path = 'uploads/homepage/' . $filename;
            
            // Dosyayı yükle
            if (move_uploaded_file($file['tmp_name'], $upload_path)) {
                // Eski resmi sil
                $old_query = "SELECT image_path FROM homepage_images WHERE image_key = ?";
                $old_stmt = $db->prepare($old_query);
                $old_stmt->execute([$image_key]);
                $old_image = $old_stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($old_image && $old_image['image_path'] && file_exists($old_image['image_path'])) {
                    unlink($old_image['image_path']);
                }
                
                // Veritabanını güncelle
                $query = "INSERT INTO homepage_images (image_key, image_path, alt_text) 
                         VALUES (?, ?, ?) 
                         ON DUPLICATE KEY UPDATE 
                         image_path = VALUES(image_path), uploaded_at = NOW()";
                $stmt = $db->prepare($query);
                $alt_text = $_POST['alt_text'] ?? 'Homepage Image';
                $stmt->execute([$image_key, $upload_path, $alt_text]);
                
                $success_message = "Resim başarıyla yüklendi!";
            } else {
                throw new Exception('Dosya yüklenirken hata oluştu.');
            }
        } else {
            throw new Exception('Lütfen geçerli bir dosya seçin.');
        }
    } catch(Exception $e) {
        $error_message = "Resim yükleme hatası: " . $e->getMessage();
    }
}

$current_language = $_GET['lang'] ?? 'tr';
$content = getHomepageContent($db, $current_language);

// Resimleri getir
$query = "SELECT image_key, image_path, alt_text FROM homepage_images WHERE is_active = 1";
$stmt = $db->prepare($query);
$stmt->execute();
$images = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ana Sayfa İçerik Yönetimi - Admin Panel</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="assets/css/admin-mobile.css" rel="stylesheet">
    <style>
        .content-section {
            border: 1px solid #dee2e6;
            border-radius: 8px;
            margin-bottom: 2rem;
        }
        .content-section-header {
            background: #f8f9fa;
            padding: 1rem;
            border-bottom: 1px solid #dee2e6;
            font-weight: 600;
        }
        .content-section-body {
            padding: 1.5rem;
        }
        .language-tabs {
            margin-bottom: 2rem;
        }
        .preview-button {
            position: fixed;
            bottom: 20px;
            right: 20px;
            z-index: 1000;
        }
        .form-label {
            font-weight: 600;
            margin-bottom: 0.5rem;
        }
        .content-group {
            background: #f8f9fa;
            padding: 1rem;
            border-radius: 6px;
            margin-bottom: 1rem;
        }
        .content-group h6 {
            color: #495057;
            margin-bottom: 1rem;
            font-weight: 600;
        }
        .image-preview {
            max-width: 200px;
            max-height: 100px;
            border: 1px solid #dee2e6;
            border-radius: 4px;
            margin-top: 0.5rem;
        }
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
                    <i class="fas fa-arrow-left"></i> Dashboard
                </a>
                <a href="admin_settings.php" class="btn btn-outline-light btn-sm me-2">
                    <i class="fas fa-cogs"></i> Sistem Ayarları
                </a>
                <a href="logout.php" class="btn btn-outline-light btn-sm">
                    <i class="fas fa-sign-out-alt"></i> Çıkış
                </a>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1><i class="fas fa-edit"></i> Ana Sayfa İçerik Yönetimi</h1>
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

        <!-- Dil Seçimi -->
        <div class="language-tabs">
            <ul class="nav nav-pills">
                <li class="nav-item">
                    <a class="nav-link <?php echo $current_language == 'tr' ? 'active' : ''; ?>" 
                       href="?lang=tr">
                        <i class="fas fa-flag"></i> Türkçe
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo $current_language == 'en' ? 'active' : ''; ?>" 
                       href="?lang=en">
                        <i class="fas fa-flag"></i> English
                    </a>
                </li>
            </ul>
        </div>

        <form method="POST" id="contentForm">
            <input type="hidden" name="action" value="update_content">
            <input type="hidden" name="language" value="<?php echo $current_language; ?>">

            <!-- Hero Section -->
            <div class="content-section">
                <div class="content-section-header">
                    <i class="fas fa-home"></i> Hero Section (Ana Bölüm)
                </div>
                <div class="content-section-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Ana Başlık</label>
                                <textarea class="form-control" name="hero__title" rows="2"><?php echo htmlspecialchars($content['hero']['title']['value'] ?? ''); ?></textarea>
                                <small class="text-muted">HTML etiketleri kullanabilirsiniz (örn: &lt;br&gt; için satır atlama)</small>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Alt Başlık</label>
                                <textarea class="form-control" name="hero__subtitle" rows="2"><?php echo htmlspecialchars($content['hero']['subtitle']['value'] ?? ''); ?></textarea>
                            </div>
                        </div>
                    </div>
                    
                    <div class="content-group">
                        <h6><i class="fas fa-mouse-pointer"></i> Ana Buton</h6>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Buton Metni</label>
                                    <input type="text" class="form-control" name="hero__primary_button_text" 
                                           value="<?php echo htmlspecialchars($content['hero']['primary_button_text']['value'] ?? ''); ?>">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Buton Linki</label>
                                    <input type="text" class="form-control" name="hero__primary_button_link" 
                                           value="<?php echo htmlspecialchars($content['hero']['primary_button_link']['value'] ?? ''); ?>">
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="content-group">
                        <h6><i class="fas fa-mouse-pointer"></i> İkinci Buton</h6>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Buton Metni</label>
                                    <input type="text" class="form-control" name="hero__secondary_button_text" 
                                           value="<?php echo htmlspecialchars($content['hero']['secondary_button_text']['value'] ?? ''); ?>">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Buton Linki</label>
                                    <input type="text" class="form-control" name="hero__secondary_button_link" 
                                           value="<?php echo htmlspecialchars($content['hero']['secondary_button_link']['value'] ?? ''); ?>">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Features Section -->
            <div class="content-section">
                <div class="content-section-header">
                    <i class="fas fa-star"></i> Özellikler Bölümü
                </div>
                <div class="content-section-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Bölüm Başlığı</label>
                                <input type="text" class="form-control" name="features__section_title" 
                                       value="<?php echo htmlspecialchars($content['features']['section_title']['value'] ?? ''); ?>">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Bölüm Açıklaması</label>
                                <textarea class="form-control" name="features__section_description" rows="2"><?php echo htmlspecialchars($content['features']['section_description']['value'] ?? ''); ?></textarea>
                            </div>
                        </div>
                    </div>

                    <!-- Feature Cards -->
                    <?php for($i = 1; $i <= 3; $i++): ?>
                    <div class="content-group">
                        <h6><i class="fas fa-layer-group"></i> Özellik Kartı <?php echo $i; ?></h6>
                        <div class="row">
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label class="form-label">Başlık</label>
                                    <input type="text" class="form-control" name="features__feature<?php echo $i; ?>_title" 
                                           value="<?php echo htmlspecialchars($content['features']['feature'.$i.'_title']['value'] ?? ''); ?>">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Açıklama</label>
                                    <textarea class="form-control" name="features__feature<?php echo $i; ?>_text" rows="2"><?php echo htmlspecialchars($content['features']['feature'.$i.'_text']['value'] ?? ''); ?></textarea>
                                </div>
                            </div>
                            <div class="col-md-2">
                                <div class="mb-3">
                                    <label class="form-label">İkon (FontAwesome)</label>
                                    <input type="text" class="form-control" name="features__feature<?php echo $i; ?>_icon" 
                                           value="<?php echo htmlspecialchars($content['features']['feature'.$i.'_icon']['value'] ?? ''); ?>"
                                           placeholder="fas fa-star">
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endfor; ?>
                </div>
            </div>

            <!-- Markets Ticker Section -->
            <div class="content-section">
                <div class="content-section-header">
                    <i class="fas fa-chart-line"></i> Piyasa Verisi Bölümü
                </div>
                <div class="content-section-body">
                    <div class="mb-3">
                        <label class="form-label">Bölüm Başlığı</label>
                        <input type="text" class="form-control" name="markets_ticker__section_title" 
                               value="<?php echo htmlspecialchars($content['markets_ticker']['section_title']['value'] ?? ''); ?>">
                        <small class="text-muted">Piyasa verileri otomatik olarak API'den çekilir</small>
                    </div>
                </div>
            </div>

            <!-- Education Section -->
            <div class="content-section">
                <div class="content-section-header">
                    <i class="fas fa-graduation-cap"></i> Eğitim Bölümü
                </div>
                <div class="content-section-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Bölüm Başlığı</label>
                                <input type="text" class="form-control" name="education__section_title" 
                                       value="<?php echo htmlspecialchars($content['education']['section_title']['value'] ?? ''); ?>">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Bölüm Açıklaması</label>
                                <textarea class="form-control" name="education__section_description" rows="2"><?php echo htmlspecialchars($content['education']['section_description']['value'] ?? ''); ?></textarea>
                            </div>
                        </div>
                    </div>
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle"></i> 
                        <strong>Not:</strong> Eğitim kartlarının detayları kod içinde sabit olarak tanımlıdır. 
                        Gelecek güncellemede bu kartlar da düzenlenebilir hale getirilebilir.
                    </div>
                </div>
            </div>

            <!-- CTA Section -->
            <div class="content-section">
                <div class="content-section-header">
                    <i class="fas fa-bullhorn"></i> Çağrı Bölümü (CTA)
                </div>
                <div class="content-section-body">
                    <div class="row">
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label class="form-label">Badge Metni</label>
                                <input type="text" class="form-control" name="cta__badge_text" 
                                       value="<?php echo htmlspecialchars($content['cta']['badge_text']['value'] ?? ''); ?>">
                            </div>
                        </div>
                        <div class="col-md-8">
                            <div class="mb-3">
                                <label class="form-label">Ana Başlık</label>
                                <input type="text" class="form-control" name="cta__title" 
                                       value="<?php echo htmlspecialchars($content['cta']['title']['value'] ?? ''); ?>">
                            </div>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Açıklama</label>
                        <textarea class="form-control" name="cta__description" rows="3"><?php echo htmlspecialchars($content['cta']['description']['value'] ?? ''); ?></textarea>
                    </div>
                    
                    <div class="content-group">
                        <h6><i class="fas fa-mouse-pointer"></i> Ana Buton</h6>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Buton Metni</label>
                                    <input type="text" class="form-control" name="cta__primary_button_text" 
                                           value="<?php echo htmlspecialchars($content['cta']['primary_button_text']['value'] ?? ''); ?>">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Buton Linki</label>
                                    <input type="text" class="form-control" name="cta__primary_button_link" 
                                           value="<?php echo htmlspecialchars($content['cta']['primary_button_link']['value'] ?? ''); ?>">
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="content-group">
                        <h6><i class="fas fa-mouse-pointer"></i> İkinci Buton</h6>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Buton Metni</label>
                                    <input type="text" class="form-control" name="cta__secondary_button_text" 
                                           value="<?php echo htmlspecialchars($content['cta']['secondary_button_text']['value'] ?? ''); ?>">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Buton Linki</label>
                                    <input type="text" class="form-control" name="cta__secondary_button_link" 
                                           value="<?php echo htmlspecialchars($content['cta']['secondary_button_link']['value'] ?? ''); ?>">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Kaydet Butonu -->
            <div class="text-center mb-4">
                <button type="submit" class="btn btn-primary btn-lg me-3">
                    <i class="fas fa-save"></i> İçerikleri Kaydet
                </button>
                <a href="index.php" target="_blank" class="btn btn-outline-secondary btn-lg">
                    <i class="fas fa-eye"></i> Önizleme
                </a>
            </div>
        </form>

        <!-- Resim Yönetimi -->
        <div class="content-section">
            <div class="content-section-header">
                <i class="fas fa-images"></i> Resim Yönetimi
            </div>
            <div class="content-section-body">
                <div class="row">
                    <div class="col-md-6">
                        <h6>Hero Arka Plan Resmi</h6>
                        <?php if (isset($images['hero_background']) && $images['hero_background']): ?>
                            <img src="<?php echo $images['hero_background']; ?>" class="image-preview" alt="Hero Background">
                            <p class="text-muted small mt-1">Mevcut: <?php echo $images['hero_background']; ?></p>
                        <?php else: ?>
                            <p class="text-muted">Henüz yüklenmemiş</p>
                        <?php endif; ?>
                        
                        <form method="POST" enctype="multipart/form-data" class="mt-2">
                            <input type="hidden" name="action" value="upload_image">
                            <input type="hidden" name="image_key" value="hero_background">
                            <input type="hidden" name="alt_text" value="Hero Background">
                            <div class="input-group">
                                <input type="file" class="form-control form-control-sm" name="image_file" accept="image/*">
                                <button type="submit" class="btn btn-outline-primary btn-sm">Yükle</button>
                            </div>
                        </form>
                    </div>
                </div>
                
                <div class="alert alert-info mt-3">
                    <h6><i class="fas fa-info-circle"></i> Resim Yükleme Kuralları:</h6>
                    <ul class="mb-0">
                        <li><strong>Dosya Formatları:</strong> JPG, PNG, GIF, WebP</li>
                        <li><strong>Maksimum Boyut:</strong> 10MB</li>
                        <li><strong>Hero Arka Plan Önerilen Boyut:</strong> 1920x1080px (Full HD)</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>

    <!-- Önizleme Butonu (Fixed) -->
    <a href="index.php" target="_blank" class="btn btn-success preview-button">
        <i class="fas fa-eye"></i> Canlı Önizleme
    </a>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Form değişiklik uyarısı
        let formChanged = false;
        document.getElementById('contentForm').addEventListener('change', function() {
            formChanged = true;
        });

        window.addEventListener('beforeunload', function(e) {
            if (formChanged) {
                e.preventDefault();
                e.returnValue = 'Kaydedilmemiş değişiklikler var. Sayfadan ayrılmak istediğinizden emin misiniz?';
            }
        });

        document.getElementById('contentForm').addEventListener('submit', function() {
            formChanged = false;
        });

        // Karakter sayacı (opsiyonel)
        document.querySelectorAll('textarea, input[type="text"]').forEach(function(element) {
            element.addEventListener('input', function() {
                let length = this.value.length;
                if (length > 200) {
                    this.style.borderColor = '#ffc107';
                } else {
                    this.style.borderColor = '';
                }
            });
        });
    </script>
</body>
</html>

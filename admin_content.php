<?php
// Error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();

// Include required files
if (file_exists('config/database.php')) {
    require_once 'config/database.php';
} else {
    die('Database config file not found');
}

if (file_exists('includes/functions.php')) {
    require_once 'includes/functions.php';
} else {
    die('Functions file not found');
}

if (file_exists('includes/content_functions.php')) {
    require_once 'includes/content_functions.php';
} else {
    die('Content functions file not found');
}

// Simple admin check - if no session system, allow access
$is_admin = true;
if (isset($_SESSION['user_id'])) {
    $is_admin = isset($_SESSION['is_admin']) && $_SESSION['is_admin'] == 1;
}

if (!$is_admin && isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

try {
    $database = new Database();
    $db = $database->getConnection();
    if (!$db) {
        throw new Exception('Database connection failed');
    }
} catch (Exception $e) {
    die('Database error: ' . $e->getMessage());
}

// Get current language
$current_language = $_GET['lang'] ?? 'tr';

// Process form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action'])) {
    try {
        if ($_POST['action'] == 'update_content') {
            $language = $_POST['language'] ?? 'tr';
            $updated_count = 0;
            
            foreach ($_POST as $key => $value) {
                if (strpos($key, '__') !== false && $key !== 'action' && $key !== 'language') {
                    list($section, $content_key) = explode('__', $key, 2);
                    
                    // Insert or update content
                    $query = "INSERT INTO homepage_content (section_name, content_key, content_value, language, content_type, is_active) 
                             VALUES (?, ?, ?, ?, 'text', 1) 
                             ON DUPLICATE KEY UPDATE 
                             content_value = VALUES(content_value), updated_at = NOW()";
                    $stmt = $db->prepare($query);
                    $stmt->execute([$section, $content_key, $value, $language]);
                    $updated_count++;
                }
            }
            
            $success_message = "İçerik başarıyla güncellendi! ($updated_count öğe güncellendi)";
        }
    } catch(Exception $e) {
        $error_message = "Hata: " . $e->getMessage();
    }
}

// Get existing content
function getHomepageContent($db, $language = 'tr') {
    try {
        $query = "SELECT section_name, content_key, content_value FROM homepage_content WHERE language = ? AND is_active = 1";
        $stmt = $db->prepare($query);
        $stmt->execute([$language]);
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $content = [];
        foreach ($results as $row) {
            $content[$row['section_name']][$row['content_key']] = $row['content_value'];
        }
        
        return $content;
    } catch (Exception $e) {
        return [];
    }
}

$content = getHomepageContent($db, $current_language);
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>İçerik Yönetimi - Admin Panel</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
        .admin-header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); }
        .content-section { border: 1px solid #e0e0e0; border-radius: 8px; margin-bottom: 1.5rem; }
        .section-header { background: #f8f9fa; padding: 1rem; border-bottom: 1px solid #e0e0e0; font-weight: 600; }
        .section-body { padding: 1.5rem; }
        .quick-nav { position: fixed; top: 50%; right: 20px; transform: translateY(-50%); z-index: 1000; }
        .quick-nav .btn { margin-bottom: 5px; display: block; width: 50px; height: 50px; border-radius: 50%; }
        @media (max-width: 768px) { .quick-nav { display: none; } }
    </style>
</head>
<body>
    <!-- Header -->
    <div class="admin-header text-white py-3">
        <div class="container">
            <div class="row align-items-center">
                <div class="col">
                    <h1><i class="fas fa-edit"></i> İçerik Yönetimi</h1>
                    <small>Ana sayfa içeriklerini düzenleyin</small>
                </div>
                <div class="col-auto">
                    <a href="index.php" target="_blank" class="btn btn-light btn-sm me-2">
                        <i class="fas fa-eye"></i> Önizle
                    </a>
                    <?php if(file_exists('admin.php')): ?>
                    <a href="admin.php" class="btn btn-outline-light btn-sm">
                        <i class="fas fa-arrow-left"></i> Geri
                    </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <div class="container mt-4">
        <!-- Success/Error Messages -->
        <?php if (isset($success_message)): ?>
            <div class="alert alert-success alert-dismissible fade show">
                <i class="fas fa-check-circle"></i> <?php echo $success_message; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <?php if (isset($error_message)): ?>
            <div class="alert alert-danger alert-dismissible fade show">
                <i class="fas fa-exclamation-circle"></i> <?php echo $error_message; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <!-- Language Selection -->
        <div class="mb-4">
            <div class="btn-group" role="group">
                <a href="?lang=tr" class="btn <?php echo $current_language == 'tr' ? 'btn-primary' : 'btn-outline-primary'; ?>">
                    <i class="fas fa-flag"></i> Türkçe
                </a>
                <a href="?lang=en" class="btn <?php echo $current_language == 'en' ? 'btn-primary' : 'btn-outline-primary'; ?>">
                    <i class="fas fa-flag"></i> English
                </a>
            </div>
            <small class="text-muted ms-3">Düzenlemek istediğiniz dili seçin</small>
        </div>

        <form method="POST" id="contentForm">
            <input type="hidden" name="action" value="update_content">
            <input type="hidden" name="language" value="<?php echo $current_language; ?>">

            <!-- Hero Section -->
            <div class="content-section" id="hero-section">
                <div class="section-header">
                    <i class="fas fa-home text-primary"></i> Hero Bölümü
                </div>
                <div class="section-body">
                    <div class="row">
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Ana Başlık</label>
                            <textarea class="form-control" name="hero__title" rows="2" placeholder="Ana sayfa başlığı"><?php echo htmlspecialchars($content['hero']['title'] ?? ''); ?></textarea>
                            <small class="text-muted">HTML kullanabilirsiniz (örn: &lt;br&gt;)</small>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Alt Başlık</label>
                            <textarea class="form-control" name="hero__subtitle" rows="2" placeholder="Ana sayfa açıklaması"><?php echo htmlspecialchars($content['hero']['subtitle'] ?? ''); ?></textarea>
                        </div>
                    </div>
                    <div class="row mt-3">
                        <div class="col-md-3">
                            <label class="form-label fw-bold">Ana Buton Metni</label>
                            <input type="text" class="form-control" name="hero__primary_button_text" value="<?php echo htmlspecialchars($content['hero']['primary_button_text'] ?? ''); ?>" placeholder="Hemen Başla">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-bold">Ana Buton Linki</label>
                            <input type="text" class="form-control" name="hero__primary_button_link" value="<?php echo htmlspecialchars($content['hero']['primary_button_link'] ?? ''); ?>" placeholder="register.php">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-bold">İkinci Buton Metni</label>
                            <input type="text" class="form-control" name="hero__secondary_button_text" value="<?php echo htmlspecialchars($content['hero']['secondary_button_text'] ?? ''); ?>" placeholder="Piyasaları İncele">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-bold">İkinci Buton Linki</label>
                            <input type="text" class="form-control" name="hero__secondary_button_link" value="<?php echo htmlspecialchars($content['hero']['secondary_button_link'] ?? ''); ?>" placeholder="markets.php">
                        </div>
                    </div>
                </div>
            </div>

            <!-- Features Section -->
            <div class="content-section" id="features-section">
                <div class="section-header">
                    <i class="fas fa-star text-warning"></i> Özellikler Bölümü
                </div>
                <div class="section-body">
                    <div class="row">
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Bölüm Başlığı</label>
                            <input type="text" class="form-control" name="features__title" value="<?php echo htmlspecialchars($content['features']['title'] ?? ''); ?>" placeholder="Neden GlobalBorsa?">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Bölüm Açıklaması</label>
                            <textarea class="form-control" name="features__subtitle" rows="2" placeholder="Özellikler açıklaması"><?php echo htmlspecialchars($content['features']['subtitle'] ?? ''); ?></textarea>
                        </div>
                    </div>
                    
                    <!-- Feature Cards -->
                    <?php for($i = 1; $i <= 3; $i++): ?>
                    <div class="border rounded p-3 mt-3 bg-light">
                        <h6 class="text-primary mb-3"><i class="fas fa-layer-group"></i> Özellik <?php echo $i; ?></h6>
                        <div class="row">
                            <div class="col-md-4">
                                <label class="form-label">Başlık</label>
                                <input type="text" class="form-control" name="features__feature<?php echo $i; ?>_title" value="<?php echo htmlspecialchars($content['features']['feature'.$i.'_title'] ?? ''); ?>">
                            </div>
                            <div class="col-md-8">
                                <label class="form-label">Açıklama</label>
                                <textarea class="form-control" name="features__feature<?php echo $i; ?>_text" rows="2"><?php echo htmlspecialchars($content['features']['feature'.$i.'_text'] ?? ''); ?></textarea>
                            </div>
                        </div>
                    </div>
                    <?php endfor; ?>
                </div>
            </div>

            <!-- Markets Section -->
            <div class="content-section" id="markets-section">
                <div class="section-header">
                    <i class="fas fa-chart-line text-success"></i> Piyasalar Bölümü
                </div>
                <div class="section-body">
                    <label class="form-label fw-bold">Bölüm Başlığı</label>
                    <input type="text" class="form-control" name="markets__title" value="<?php echo htmlspecialchars($content['markets']['title'] ?? ''); ?>" placeholder="Canlı Piyasa Verileri">
                    <small class="text-muted">Piyasa verileri otomatik olarak API'den çekilir</small>
                </div>
            </div>

            <!-- Education Section -->
            <div class="content-section" id="education-section">
                <div class="section-header">
                    <i class="fas fa-graduation-cap text-info"></i> Eğitim Bölümü
                </div>
                <div class="section-body">
                    <div class="row">
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Bölüm Başlığı</label>
                            <input type="text" class="form-control" name="education__title" value="<?php echo htmlspecialchars($content['education']['title'] ?? ''); ?>" placeholder="Trading Akademisi">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Bölüm Açıklaması</label>
                            <textarea class="form-control" name="education__subtitle" rows="2" placeholder="Eğitim açıklaması"><?php echo htmlspecialchars($content['education']['subtitle'] ?? ''); ?></textarea>
                        </div>
                    </div>
                </div>
            </div>

            <!-- CTA Section -->
            <div class="content-section" id="cta-section">
                <div class="section-header">
                    <i class="fas fa-bullhorn text-danger"></i> CTA Bölümü
                </div>
                <div class="section-body">
                    <div class="row">
                        <div class="col-md-4">
                            <label class="form-label fw-bold">Badge Metni</label>
                            <input type="text" class="form-control" name="cta__badge" value="<?php echo htmlspecialchars($content['cta']['badge'] ?? ''); ?>" placeholder="🚀 Sınırlı Süreli Fırsat">
                        </div>
                        <div class="col-md-8">
                            <label class="form-label fw-bold">Ana Başlık</label>
                            <input type="text" class="form-control" name="cta__title" value="<?php echo htmlspecialchars($content['cta']['title'] ?? ''); ?>" placeholder="Yatırım Yolculuğunuza Hemen Başlayın!">
                        </div>
                    </div>
                    <div class="mt-3">
                        <label class="form-label fw-bold">Açıklama</label>
                        <textarea class="form-control" name="cta__text" rows="3" placeholder="CTA açıklaması"><?php echo htmlspecialchars($content['cta']['text'] ?? ''); ?></textarea>
                    </div>
                    <div class="row mt-3">
                        <div class="col-md-3">
                            <label class="form-label fw-bold">Ana Buton Metni</label>
                            <input type="text" class="form-control" name="cta__primary_button_text" value="<?php echo htmlspecialchars($content['cta']['primary_button_text'] ?? ''); ?>" placeholder="Ücretsiz Hesap Aç">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-bold">Ana Buton Linki</label>
                            <input type="text" class="form-control" name="cta__primary_button_link" value="<?php echo htmlspecialchars($content['cta']['primary_button_link'] ?? ''); ?>" placeholder="register.php">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-bold">İkinci Buton Metni</label>
                            <input type="text" class="form-control" name="cta__secondary_button_text" value="<?php echo htmlspecialchars($content['cta']['secondary_button_text'] ?? ''); ?>" placeholder="Piyasaları Keşfet">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-bold">İkinci Buton Linki</label>
                            <input type="text" class="form-control" name="cta__secondary_button_link" value="<?php echo htmlspecialchars($content['cta']['secondary_button_link'] ?? ''); ?>" placeholder="markets.php">
                        </div>
                    </div>
                </div>
            </div>

            <!-- Save Button -->
            <div class="text-center my-5">
                <button type="submit" class="btn btn-primary btn-lg px-5">
                    <i class="fas fa-save"></i> Değişiklikleri Kaydet
                </button>
            </div>
        </form>

        <!-- Info Box -->
        <div class="alert alert-info">
            <h6><i class="fas fa-info-circle"></i> Kullanım Bilgileri:</h6>
            <ul class="mb-0">
                <li>Değişiklikler anında kaydedilir ve ana sayfada görünür</li>
                <li>HTML etiketleri kullanabilirsiniz (&lt;br&gt;, &lt;strong&gt; gibi)</li>
                <li>Önizleme butonu ile değişiklikleri canlı olarak görüntüleyebilirsiniz</li>
                <li>İki dil desteği vardır: Türkçe ve İngilizce</li>
            </ul>
        </div>
    </div>

    <!-- Quick Navigation -->
    <div class="quick-nav">
        <a href="#hero-section" class="btn btn-primary btn-sm" title="Hero">
            <i class="fas fa-home"></i>
        </a>
        <a href="#features-section" class="btn btn-warning btn-sm" title="Özellikler">
            <i class="fas fa-star"></i>
        </a>
        <a href="#markets-section" class="btn btn-success btn-sm" title="Piyasalar">
            <i class="fas fa-chart-line"></i>
        </a>
        <a href="#education-section" class="btn btn-info btn-sm" title="Eğitim">
            <i class="fas fa-graduation-cap"></i>
        </a>
        <a href="#cta-section" class="btn btn-danger btn-sm" title="CTA">
            <i class="fas fa-bullhorn"></i>
        </a>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Smooth scrolling for navigation
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                e.preventDefault();
                document.querySelector(this.getAttribute('href')).scrollIntoView({
                    behavior: 'smooth'
                });
            });
        });

        // Form change detection
        let formChanged = false;
        document.getElementById('contentForm').addEventListener('change', function() {
            formChanged = true;
        });

        window.addEventListener('beforeunload', function(e) {
            if (formChanged) {
                e.preventDefault();
                e.returnValue = 'Kaydedilmemiş değişiklikler var!';
            }
        });

        document.getElementById('contentForm').addEventListener('submit', function() {
            formChanged = false;
        });

        // Auto-save notification
        document.getElementById('contentForm').addEventListener('submit', function() {
            const btn = this.querySelector('button[type="submit"]');
            const originalText = btn.innerHTML;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Kaydediliyor...';
            btn.disabled = true;
            
            setTimeout(() => {
                btn.innerHTML = originalText;
                btn.disabled = false;
            }, 2000);
        });
    </script>
</body>
</html>

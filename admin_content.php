<?php
// Error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Only start session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

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

// Default content for fallback
$default_content = [
    'tr' => [
        'hero' => [
            'title' => 'Türkiye\'nin En Güvenilir <br>Yatırım Platformu',
            'subtitle' => 'Düşük komisyonlar, güvenli altyapı ve profesyonel destek ile yatırımlarınızı büyütün.',
            'primary_button_text' => 'Hemen Başla',
            'primary_button_link' => 'register.php',
            'secondary_button_text' => 'Piyasaları İncele',
            'secondary_button_link' => 'markets.php'
        ],
        'features' => [
            'title' => 'Neden GlobalBorsa?',
            'subtitle' => 'Türkiye\'nin en güvenilir yatırım platformu olarak size sunduğumuz avantajlar',
            'feature1_title' => 'Güvenli Altyapı',
            'feature1_text' => 'Çoklu imza, soğuk cüzdan depolama ve 2FA ile paranız %100 güvende. Sigortalı varlık koruması.',
            'feature2_title' => 'Hızlı İşlemler',
            'feature2_text' => 'Milisaniye hızında emir eşleştirme motoru ile anlık alım-satım yapın. 0.1 saniyede işlem tamamlama.',
            'feature3_title' => 'Düşük Komisyonlar',
            'feature3_text' => 'Türkiye\'nin en düşük komisyon oranları ile daha fazla kar edin. Şeffaf ve adil fiyatlandırma.'
        ],
        'markets' => [
            'title' => 'Canlı Piyasa Verileri'
        ],
        'education' => [
            'title' => 'Trading Akademisi',
            'subtitle' => 'Profesyonel trader olmak için ihtiyacınız olan tüm bilgileri uzman analistlerimizden öğrenin'
        ],
        'cta' => [
            'badge' => '🚀 Sınırlı Süreli Fırsat',
            'title' => 'Yatırım Yolculuğunuza Hemen Başlayın!',
            'text' => 'Profesyonel araçlar, uzman analizler ve güvenli altyapı ile yatırımlarınızı bir sonraki seviyeye taşıyın. İlk yatırımınızda %100 bonus kazanma fırsatını kaçırmayın!',
            'primary_button_text' => 'Ücretsiz Hesap Aç',
            'primary_button_link' => 'register.php',
            'secondary_button_text' => 'Piyasaları Keşfet',
            'secondary_button_link' => 'markets.php'
        ]
    ],
    'en' => [
        'hero' => [
            'title' => 'Turkey\'s Most Trusted <br>Investment Platform',
            'subtitle' => 'Grow your investments with low commissions, secure infrastructure and professional support.',
            'primary_button_text' => 'Get Started',
            'primary_button_link' => 'register.php',
            'secondary_button_text' => 'Explore Markets',
            'secondary_button_link' => 'markets.php'
        ],
        'features' => [
            'title' => 'Why GlobalBorsa?',
            'subtitle' => 'Advantages we offer as Turkey\'s most trusted investment platform',
            'feature1_title' => 'Secure Infrastructure',
            'feature1_text' => 'Your money is 100% safe with multi-signature, cold wallet storage and 2FA. Insured asset protection.',
            'feature2_title' => 'Fast Transactions',
            'feature2_text' => 'Trade instantly with millisecond-speed order matching engine. Complete transactions in 0.1 seconds.',
            'feature3_title' => 'Low Commissions',
            'feature3_text' => 'Earn more with Turkey\'s lowest commission rates. Transparent and fair pricing.'
        ],
        'markets' => [
            'title' => 'Live Market Data'
        ],
        'education' => [
            'title' => 'Trading Academy',
            'subtitle' => 'Learn everything you need to become a professional trader from our expert analysts'
        ],
        'cta' => [
            'badge' => '🚀 Limited Time Offer',
            'title' => 'Start Your Investment Journey Now!',
            'text' => 'Take your investments to the next level with professional tools, expert analysis and secure infrastructure. Don\'t miss the opportunity to earn 100% bonus on your first investment!',
            'primary_button_text' => 'Open Free Account',
            'primary_button_link' => 'register.php',
            'secondary_button_text' => 'Explore Markets',
            'secondary_button_link' => 'markets.php'
        ]
    ]
];

// Function to get content with fallback
function getContentValueWithFallback($content, $section, $key, $language, $default_content) {
    if (isset($content[$section][$key]) && !empty($content[$section][$key])) {
        return $content[$section][$key];
    }
    return $default_content[$language][$section][$key] ?? '';
}
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
        .current-content { background: #f0f8f0; border-left: 4px solid #28a745; padding: 8px 12px; margin-bottom: 8px; font-size: 0.9em; }
    </style>
</head>
<body>
    <!-- Header -->
    <div class="admin-header text-white py-3">
        <div class="container">
            <div class="row align-items-center">
                <div class="col">
                    <h1><i class="fas fa-edit"></i> İçerik Yönetimi</h1>
                    <small>Ana sayfa içeriklerini düzenleyin - Mevcut içerikler otomatik yüklendi</small>
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
                            <textarea class="form-control" name="hero__title" rows="2" placeholder="Ana sayfa başlığı"><?php echo htmlspecialchars(getContentValueWithFallback($content, 'hero', 'title', $current_language, $default_content)); ?></textarea>
                            <small class="text-muted">HTML kullanabilirsiniz (örn: &lt;br&gt;)</small>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Alt Başlık</label>
                            <textarea class="form-control" name="hero__subtitle" rows="2" placeholder="Ana sayfa açıklaması"><?php echo htmlspecialchars(getContentValueWithFallback($content, 'hero', 'subtitle', $current_language, $default_content)); ?></textarea>
                        </div>
                    </div>
                    <div class="row mt-3">
                        <div class="col-md-3">
                            <label class="form-label fw-bold">Ana Buton Metni</label>
                            <input type="text" class="form-control" name="hero__primary_button_text" value="<?php echo htmlspecialchars(getContentValueWithFallback($content, 'hero', 'primary_button_text', $current_language, $default_content)); ?>" placeholder="Hemen Başla">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-bold">Ana Buton Linki</label>
                            <input type="text" class="form-control" name="hero__primary_button_link" value="<?php echo htmlspecialchars(getContentValueWithFallback($content, 'hero', 'primary_button_link', $current_language, $default_content)); ?>" placeholder="register.php">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-bold">İkinci Buton Metni</label>
                            <input type="text" class="form-control" name="hero__secondary_button_text" value="<?php echo htmlspecialchars(getContentValueWithFallback($content, 'hero', 'secondary_button_text', $current_language, $default_content)); ?>" placeholder="Piyasaları İncele">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-bold">İkinci Buton Linki</label>
                            <input type="text" class="form-control" name="hero__secondary_button_link" value="<?php echo htmlspecialchars(getContentValueWithFallback($content, 'hero', 'secondary_button_link', $current_language, $default_content)); ?>" placeholder="markets.php">
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
                            <input type="text" class="form-control" name="features__title" value="<?php echo htmlspecialchars(getContentValueWithFallback($content, 'features', 'title', $current_language, $default_content)); ?>" placeholder="Neden GlobalBorsa?">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Bölüm Açıklaması</label>
                            <textarea class="form-control" name="features__subtitle" rows="2" placeholder="Özellikler açıklaması"><?php echo htmlspecialchars(getContentValueWithFallback($content, 'features', 'subtitle', $current_language, $default_content)); ?></textarea>
                        </div>
                    </div>
                    
                    <!-- Feature Cards -->
                    <?php for($i = 1; $i <= 3; $i++): ?>
                    <div class="border rounded p-3 mt-3 bg-light">
                        <h6 class="text-primary mb-3"><i class="fas fa-layer-group"></i> Özellik <?php echo $i; ?></h6>
                        <div class="row">
                            <div class="col-md-4">
                                <label class="form-label">Başlık</label>
                                <input type="text" class="form-control" name="features__feature<?php echo $i; ?>_title" value="<?php echo htmlspecialchars(getContentValueWithFallback($content, 'features', 'feature'.$i.'_title', $current_language, $default_content)); ?>">
                            </div>
                            <div class="col-md-8">
                                <label class="form-label">Açıklama</label>
                                <textarea class="form-control" name="features__feature<?php echo $i; ?>_text" rows="2"><?php echo htmlspecialchars(getContentValueWithFallback($content, 'features', 'feature'.$i.'_text', $current_language, $default_content)); ?></textarea>
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
                    <input type="text" class="form-control" name="markets__title" value="<?php echo htmlspecialchars(getContentValueWithFallback($content, 'markets', 'title', $current_language, $default_content)); ?>" placeholder="Canlı Piyasa Verileri">
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
                            <input type="text" class="form-control" name="education__title" value="<?php echo htmlspecialchars(getContentValueWithFallback($content, 'education', 'title', $current_language, $default_content)); ?>" placeholder="Trading Akademisi">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Bölüm Açıklaması</label>
                            <textarea class="form-control" name="education__subtitle" rows="2" placeholder="Eğitim açıklaması"><?php echo htmlspecialchars(getContentValueWithFallback($content, 'education', 'subtitle', $current_language, $default_content)); ?></textarea>
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
                            <input type="text" class="form-control" name="cta__badge" value="<?php echo htmlspecialchars(getContentValueWithFallback($content, 'cta', 'badge', $current_language, $default_content)); ?>" placeholder="🚀 Sınırlı Süreli Fırsat">
                        </div>
                        <div class="col-md-8">
                            <label class="form-label fw-bold">Ana Başlık</label>
                            <input type="text" class="form-control" name="cta__title" value="<?php echo htmlspecialchars(getContentValueWithFallback($content, 'cta', 'title', $current_language, $default_content)); ?>" placeholder="Yatırım Yolculuğunuza Hemen Başlayın!">
                        </div>
                    </div>
                    <div class="mt-3">
                        <label class="form-label fw-bold">Açıklama</label>
                        <textarea class="form-control" name="cta__text" rows="3" placeholder="CTA açıklaması"><?php echo htmlspecialchars(getContentValueWithFallback($content, 'cta', 'text', $current_language, $default_content)); ?></textarea>
                    </div>
                    <div class="row mt-3">
                        <div class="col-md-3">
                            <label class="form-label fw-bold">Ana Buton Metni</label>
                            <input type="text" class="form-control" name="cta__primary_button_text" value="<?php echo htmlspecialchars(getContentValueWithFallback($content, 'cta', 'primary_button_text', $current_language, $default_content)); ?>" placeholder="Ücretsiz Hesap Aç">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-bold">Ana Buton Linki</label>
                            <input type="text" class="form-control" name="cta__primary_button_link" value="<?php echo htmlspecialchars(getContentValueWithFallback($content, 'cta', 'primary_button_link', $current_language, $default_content)); ?>" placeholder="register.php">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-bold">İkinci Buton Metni</label>
                            <input type="text" class="form-control" name="cta__secondary_button_text" value="<?php echo htmlspecialchars(getContentValueWithFallback($content, 'cta', 'secondary_button_text', $current_language, $default_content)); ?>" placeholder="Piyasaları Keşfet">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-bold">İkinci Buton Linki</label>
                            <input type="text" class="form-control" name="cta__secondary_button_link" value="<?php echo htmlspecialchars(getContentValueWithFallback($content, 'cta', 'secondary_button_link', $current_language, $default_content)); ?>" placeholder="markets.php">
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
                <li><strong>Mevcut veriler:</strong> Form alanları mevcut içeriklerle otomatik dolduruldu</li>
                <li><strong>Kaydetme:</strong> Değişiklikler anında kaydedilir ve ana sayfada görünür</li>
                <li><strong>HTML etiketleri:</strong> Başlık alanlarında kullanabilirsiniz (&lt;br&gt;, &lt;strong&gt; gibi)</li>
                <li><strong>Önizleme:</strong> Üst menüdeki "Önizle" butonu ile değişiklikleri canlı görüntüleyebilirsiniz</li>
                <li><strong>Dil desteği:</strong> Türkçe ve İngilizce ayrı ayrı yönetilebilir</li>
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

        // Show success message on page load if form was submitted
        <?php if(isset($success_message)): ?>
        setTimeout(() => {
            const alert = document.querySelector('.alert-success');
            if(alert) {
                alert.scrollIntoView({ behavior: 'smooth', block: 'center' });
            }
        }, 100);
        <?php endif; ?>
    </script>
</body>
</html>

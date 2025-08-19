<?php
require_once 'includes/functions.php';

// Basit admin authentication - production'da geliştirilmeli
$admin_password = 'admin123'; // Change this!
$is_admin = false;

if (isset($_POST['admin_login'])) {
    if ($_POST['password'] === $admin_password) {
        $_SESSION['admin_logged_in'] = true;
        $is_admin = true;
    } else {
        $login_error = 'Yanlış şifre!';
    }
}

if (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in']) {
    $is_admin = true;
}

if (isset($_POST['logout'])) {
    unset($_SESSION['admin_logged_in']);
    $is_admin = false;
}

$page_title = 'Data Manager - GlobalBorsa Admin';
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body { background: #f8f9fa; }
        .admin-card { border: none; box-shadow: 0 0 20px rgba(0,0,0,0.1); }
        .category-card { margin: 15px 0; transition: all 0.3s ease; }
        .category-card:hover { transform: translateY(-2px); box-shadow: 0 4px 15px rgba(0,0,0,0.1); }
        .status-badge { font-size: 0.8rem; }
        .progress-container { display: none; }
        .log-container { background: #f8f9fa; border-radius: 8px; padding: 15px; height: 300px; overflow-y: auto; font-family: monospace; font-size: 0.9rem; }
        .btn-update { min-width: 120px; }
        .api-status { padding: 10px; border-radius: 8px; margin: 15px 0; }
        .api-status.live { background: #d4edda; border: 1px solid #c3e6cb; color: #155724; }
        .api-status.demo { background: #fff3cd; border: 1px solid #ffeaa7; color: #856404; }
    </style>
</head>
<body>
    <div class="container mt-4">
        <?php if (!$is_admin): ?>
            <!-- Login Form -->
            <div class="row justify-content-center">
                <div class="col-md-4">
                    <div class="card admin-card">
                        <div class="card-header bg-primary text-white text-center">
                            <h4><i class="fas fa-lock"></i> Admin Login</h4>
                        </div>
                        <div class="card-body">
                            <?php if (isset($login_error)): ?>
                                <div class="alert alert-danger"><?php echo $login_error; ?></div>
                            <?php endif; ?>
                            <form method="post">
                                <div class="mb-3">
                                    <label class="form-label">Admin Şifresi:</label>
                                    <input type="password" name="password" class="form-control" required>
                                </div>
                                <button type="submit" name="admin_login" class="btn btn-primary w-100">
                                    <i class="fas fa-sign-in-alt"></i> Giriş Yap
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        <?php else: ?>
            <!-- Admin Panel -->
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1><i class="fas fa-database"></i> Data Manager</h1>
                <form method="post" style="margin: 0;">
                    <button type="submit" name="logout" class="btn btn-outline-secondary btn-sm">
                        <i class="fas fa-sign-out-alt"></i> Çıkış
                    </button>
                </form>
            </div>

            <!-- API Status -->
            <div class="card admin-card mb-4">
                <div class="card-body">
                    <h5><i class="fas fa-plug"></i> API Status</h5>
                    <div class="api-status <?php echo TWELVE_DATA_API_KEY === 'demo' ? 'demo' : 'live'; ?>">
                        <?php if (TWELVE_DATA_API_KEY === 'demo'): ?>
                            <i class="fas fa-exclamation-triangle"></i> <strong>Demo Mode:</strong> API key demo modunda
                        <?php else: ?>
                            <i class="fas fa-check-circle"></i> <strong>Live Mode:</strong> API key aktif (<?php echo substr(TWELVE_DATA_API_KEY, 0, 8); ?>...)
                        <?php endif; ?>
                    </div>
                    
                    <div class="row mt-3">
                        <div class="col-md-3">
                            <div class="text-center">
                                <h4 class="text-primary mb-1" id="requestCount">0</h4>
                                <small class="text-muted">Bugün Yapılan Request</small>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="text-center">
                                <h4 class="text-success mb-1">800</h4>
                                <small class="text-muted">Günlük Limit</small>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="text-center">
                                <h4 class="text-info mb-1" id="remainingRequests">800</h4>
                                <small class="text-muted">Kalan Request</small>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="text-center">
                                <h4 class="text-warning mb-1" id="lastUpdate">-</h4>
                                <small class="text-muted">Son Güncelleme</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Master Controls -->
            <div class="card admin-card mb-4">
                <div class="card-body">
                    <h5><i class="fas fa-cogs"></i> Master Controls</h5>
                    <div class="row">
                        <div class="col-md-6">
                            <button id="updateAllBtn" class="btn btn-primary btn-lg w-100" onclick="updateAllCategories()">
                                <i class="fas fa-sync"></i> Tüm Kategorileri Güncelle
                            </button>
                        </div>
                        <div class="col-md-6">
                            <button id="clearDataBtn" class="btn btn-danger btn-lg w-100" onclick="clearAllData()">
                                <i class="fas fa-trash"></i> Tüm Verileri Temizle
                            </button>
                        </div>
                    </div>
                    
                    <!-- Progress Bar -->
                    <div class="progress-container mt-3">
                        <div class="progress mb-2">
                            <div id="masterProgress" class="progress-bar" role="progressbar" style="width: 0%"></div>
                        </div>
                        <div id="progressText" class="text-center text-muted">Hazır...</div>
                    </div>
                </div>
            </div>

            <!-- Categories -->
            <div class="row">
                <?php 
                $categories = getFinancialCategories();
                foreach ($categories as $cat_key => $cat_name): 
                    // Get current data count
                    $database = new Database();
                    $db = $database->getConnection();
                    $query = "SELECT COUNT(*) as count, MAX(updated_at) as last_update FROM markets WHERE category = ?";
                    $stmt = $db->prepare($query);
                    $stmt->execute([$cat_key]);
                    $result = $stmt->fetch(PDO::FETCH_ASSOC);
                    $count = $result['count'] ?? 0;
                    $last_update = $result['last_update'] ?? null;
                    
                    $symbols = getCategorySymbols($cat_key);
                    $symbol_count = count($symbols);
                ?>
                <div class="col-md-6 col-lg-4">
                    <div class="card category-card">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h6 class="mb-0"><?php echo $cat_name; ?></h6>
                            <span class="status-badge badge <?php echo $count > 0 ? 'bg-success' : 'bg-warning'; ?>">
                                <?php echo $count; ?>/<?php echo $symbol_count; ?>
                            </span>
                        </div>
                        <div class="card-body">
                            <p class="text-muted small mb-2">
                                <i class="fas fa-chart-line"></i> <?php echo $symbol_count; ?> sembol
                            </p>
                            <?php if ($last_update): ?>
                                <p class="text-muted small mb-2">
                                    <i class="fas fa-clock"></i> <?php echo date('d.m.Y H:i', strtotime($last_update)); ?>
                                </p>
                            <?php endif; ?>
                            
                            <button class="btn btn-outline-primary btn-update w-100" 
                                    onclick="updateCategory('<?php echo $cat_key; ?>', '<?php echo $cat_name; ?>')">
                                <i class="fas fa-sync"></i> Güncelle
                            </button>
                            
                            <div class="mt-2" id="progress-<?php echo $cat_key; ?>" style="display: none;">
                                <div class="progress mb-1">
                                    <div class="progress-bar" role="progressbar" style="width: 0%"></div>
                                </div>
                                <small class="text-muted">İşleniyor...</small>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>

            <!-- Logs -->
            <div class="card admin-card mt-4">
                <div class="card-header">
                    <h5><i class="fas fa-terminal"></i> İşlem Logları</h5>
                </div>
                <div class="card-body">
                    <div id="logContainer" class="log-container">
                        <div class="text-muted">İşlem logları burada görünecek...</div>
                    </div>
                    <button class="btn btn-sm btn-outline-secondary mt-2" onclick="clearLogs()">
                        <i class="fas fa-eraser"></i> Logları Temizle
                    </button>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        let requestCount = 0;
        let isUpdating = false;

        function log(message, type = 'info') {
            const logContainer = document.getElementById('logContainer');
            const timestamp = new Date().toLocaleTimeString();
            const colorClass = type === 'error' ? 'text-danger' : type === 'success' ? 'text-success' : 'text-info';
            
            logContainer.innerHTML += `<div class="${colorClass}">[${timestamp}] ${message}</div>`;
            logContainer.scrollTop = logContainer.scrollHeight;
        }

        function updateRequestCount() {
            requestCount++;
            document.getElementById('requestCount').textContent = requestCount;
            document.getElementById('remainingRequests').textContent = Math.max(0, 800 - requestCount);
        }

        function updateLastUpdate() {
            const now = new Date().toLocaleTimeString();
            document.getElementById('lastUpdate').textContent = now;
        }

        async function updateCategory(category, categoryName) {
            if (isUpdating) {
                log('Başka bir güncelleme devam ediyor...', 'error');
                return;
            }

            isUpdating = true;
            const btn = event.target.closest('button');
            const originalText = btn.innerHTML;
            const progressDiv = document.getElementById(`progress-${category}`);
            const progressBar = progressDiv.querySelector('.progress-bar');

            btn.disabled = true;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Güncelleniyor...';
            progressDiv.style.display = 'block';

            log(`${categoryName} kategorisi güncelleniyor...`);

            try {
                const response = await fetch('admin-update-category.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `category=${category}`
                });

                const data = await response.json();

                if (data.success) {
                    log(`✅ ${categoryName}: ${data.updated} sembol güncellendi`, 'success');
                    requestCount += data.requests || 0;
                    updateRequestCount();
                    updateLastUpdate();
                    
                    // Update status badge
                    const badge = btn.closest('.card').querySelector('.status-badge');
                    badge.textContent = data.updated + '/' + data.total;
                    badge.className = 'status-badge badge bg-success';
                    
                    progressBar.style.width = '100%';
                    setTimeout(() => {
                        progressDiv.style.display = 'none';
                        progressBar.style.width = '0%';
                    }, 1000);
                } else {
                    log(`❌ ${categoryName}: ${data.error}`, 'error');
                }
            } catch (error) {
                log(`❌ ${categoryName}: Network hatası`, 'error');
            }

            btn.disabled = false;
            btn.innerHTML = originalText;
            isUpdating = false;
        }

        async function updateAllCategories() {
            if (isUpdating) {
                log('Başka bir güncelleme devam ediyor...', 'error');
                return;
            }

            const btn = document.getElementById('updateAllBtn');
            const progressContainer = document.querySelector('.progress-container');
            const progressBar = document.getElementById('masterProgress');
            const progressText = document.getElementById('progressText');

            btn.disabled = true;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Tüm Kategoriler Güncelleniyor...';
            progressContainer.style.display = 'block';

            const categories = <?php echo json_encode(array_keys(getFinancialCategories())); ?>;
            const total = categories.length;
            let completed = 0;

            log('🚀 Tüm kategoriler güncelleniyor...');

            for (let i = 0; i < categories.length; i++) {
                const category = categories[i];
                progressText.textContent = `${category} güncelleniyor... (${i + 1}/${total})`;
                
                try {
                    const response = await fetch('admin-update-category.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded',
                        },
                        body: `category=${category}`
                    });

                    const data = await response.json();
                    
                    if (data.success) {
                        log(`✅ ${category}: ${data.updated} sembol güncellendi`, 'success');
                        requestCount += data.requests || 0;
                    } else {
                        log(`❌ ${category}: ${data.error}`, 'error');
                    }
                } catch (error) {
                    log(`❌ ${category}: Network hatası`, 'error');
                }

                completed++;
                const progress = (completed / total) * 100;
                progressBar.style.width = progress + '%';
                
                // Rate limiting: 1 saniye bekle
                if (i < categories.length - 1) {
                    await new Promise(resolve => setTimeout(resolve, 1000));
                }
            }

            updateRequestCount();
            updateLastUpdate();
            
            progressText.textContent = 'Tamamlandı!';
            log('🎉 Tüm kategoriler güncellendi!', 'success');

            setTimeout(() => {
                progressContainer.style.display = 'none';
                progressBar.style.width = '0%';
                progressText.textContent = 'Hazır...';
            }, 2000);

            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-sync"></i> Tüm Kategorileri Güncelle';
        }

        async function clearAllData() {
            if (!confirm('Tüm market verilerini silmek istediğinizden emin misiniz?')) {
                return;
            }

            const btn = document.getElementById('clearDataBtn');
            btn.disabled = true;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Siliniyor...';

            try {
                const response = await fetch('admin-clear-data.php', {
                    method: 'POST'
                });

                const data = await response.json();
                
                if (data.success) {
                    log('🗑️ Tüm veriler temizlendi', 'success');
                    location.reload(); // Sayfayı yenile
                } else {
                    log('❌ Veri temizleme hatası: ' + data.error, 'error');
                }
            } catch (error) {
                log('❌ Network hatası', 'error');
            }

            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-trash"></i> Tüm Verileri Temizle';
        }

        function clearLogs() {
            document.getElementById('logContainer').innerHTML = '<div class="text-muted">Loglar temizlendi...</div>';
        }

        // Initialize
        log('📊 Data Manager başlatıldı');
        updateLastUpdate();
    </script>
</body>
</html>

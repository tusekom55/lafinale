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

// Aktions handling
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['action'])) {
        try {
            switch($_POST['action']) {
                case 'add_symbol':
                    // Her 2 sütunu da ekle - admin paneli ve markets.php uyumluluğu için
                    $query = "INSERT INTO markets (symbol, name, current_price, change_percent, price, change_24h, is_active, category, updated_at) 
                              VALUES (:symbol, :name, :current_price, :change_percent, :current_price, :change_percent, 1, 'us_stocks', CURRENT_TIMESTAMP)";
                    $stmt = $db->prepare($query);
                    $stmt->bindParam(':symbol', $_POST['symbol']);
                    $stmt->bindParam(':name', $_POST['name']);
                    $stmt->bindParam(':current_price', $_POST['current_price']);
                    $stmt->bindParam(':change_percent', $_POST['change_percent']);
                    $stmt->execute();
                    $success_message = "Sembol başarıyla eklendi! (Hem admin hem markets.php için)";
                    break;
                    
                case 'update_price':
                    // Her 2 sütunu da güncelle - admin paneli ve markets.php uyumluluğu için
                    $query = "UPDATE markets SET 
                              current_price = :current_price, 
                              change_percent = :change_percent,
                              price = :current_price,
                              change_24h = :change_percent,
                              updated_at = CURRENT_TIMESTAMP 
                              WHERE id = :id";
                    $stmt = $db->prepare($query);
                    $stmt->bindParam(':current_price', $_POST['current_price']);
                    $stmt->bindParam(':change_percent', $_POST['change_percent']);
                    $stmt->bindParam(':id', $_POST['market_id']);
                    $stmt->execute();
                    $success_message = "Fiyat başarıyla güncellendi! (Hem admin hem markets.php için)";
                    break;
                    
                case 'toggle_status':
                    $query = "UPDATE markets SET is_active = :is_active WHERE id = :id";
                    $stmt = $db->prepare($query);
                    $new_status = $_POST['current_status'] == '1' ? '0' : '1';
                    $stmt->bindParam(':is_active', $new_status);
                    $stmt->bindParam(':id', $_POST['market_id']);
                    $stmt->execute();
                    $success_message = "Durum başarıyla değiştirildi!";
                    break;
                    
                case 'delete_symbol':
                    $query = "DELETE FROM markets WHERE id = :id";
                    $stmt = $db->prepare($query);
                    $stmt->bindParam(':id', $_POST['market_id']);
                    $stmt->execute();
                    $success_message = "Sembol başarıyla silindi!";
                    break;
            }
        } catch(Exception $e) {
            $error_message = "Hata: " . $e->getMessage();
        }
    }
}

// Sayfalama için
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$per_page = 20;
$offset = ($page - 1) * $per_page;

// Toplam sembol sayısı
$count_query = "SELECT COUNT(*) as total FROM markets";
$count_stmt = $db->prepare($count_query);
$count_stmt->execute();
$total_markets = $count_stmt->fetch(PDO::FETCH_ASSOC)['total'];
$total_pages = ceil($total_markets / $per_page);

// Sembol listesi - Fiyata göre azalan sıralama (price alanına göre)
$query = "SELECT * FROM markets ORDER BY price DESC LIMIT :limit OFFSET :offset";
$stmt = $db->prepare($query);
$stmt->bindValue(':limit', $per_page, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$markets = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Market Yönetimi - Admin Panel</title>
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
                    <i class="fas fa-arrow-left"></i> Dashboard
                </a>
                <a href="logout.php" class="btn btn-outline-light btn-sm">
                    <i class="fas fa-sign-out-alt"></i> Çıkış
                </a>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1><i class="fas fa-chart-line"></i> Market Yönetimi</h1>
            <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#addSymbolModal">
                <i class="fas fa-plus"></i> Yeni Sembol Ekle
            </button>
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

        <div class="card">
            <div class="card-header">
                <h5><i class="fas fa-list"></i> Sembol Listesi</h5>
                <small class="text-muted">Toplam: <?php echo $total_markets; ?> sembol</small>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Sembol</th>
                                <th>Ad</th>
                                <th>Güncel Fiyat</th>
                                <th>Değişim %</th>
                                <th>Durum</th>
                                <th>Son Güncelleme</th>
                                <th>İşlemler</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($markets as $market): ?>
                            <tr>
                                <td><strong><?php echo htmlspecialchars($market['symbol']); ?></strong></td>
                                <td><?php echo htmlspecialchars($market['name']); ?></td>
                                <td>
                                    $<?php echo number_format($market['price'], 4); ?>
                                </td>
                                <td>
                                    <span class="badge <?php echo $market['change_24h'] >= 0 ? 'bg-success' : 'bg-danger'; ?>">
                                        <?php echo $market['change_24h'] >= 0 ? '+' : ''; ?><?php echo number_format($market['change_24h'], 2); ?>%
                                    </span>
                                </td>
                                <td>
                                    <span class="badge <?php echo $market['is_active'] ? 'bg-success' : 'bg-secondary'; ?>">
                                        <?php echo $market['is_active'] ? 'Aktif' : 'Pasif'; ?>
                                    </span>
                                </td>
                                <td>
                                    <?php echo $market['last_updated'] ? date('d.m.Y H:i', strtotime($market['last_updated'])) : '-'; ?>
                                </td>
                                <td>
                                    <button class="btn btn-sm btn-primary" onclick="editPrice(<?php echo $market['id']; ?>, '<?php echo $market['symbol']; ?>', <?php echo $market['price']; ?>, <?php echo $market['change_24h']; ?>)">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <form method="POST" style="display: inline;" onsubmit="return confirm('Durumu değiştirmek istediğinizden emin misiniz?');">
                                        <input type="hidden" name="action" value="toggle_status">
                                        <input type="hidden" name="market_id" value="<?php echo $market['id']; ?>">
                                        <input type="hidden" name="current_status" value="<?php echo $market['is_active']; ?>">
                                        <button type="submit" class="btn btn-sm <?php echo $market['is_active'] ? 'btn-warning' : 'btn-success'; ?>">
                                            <i class="fas <?php echo $market['is_active'] ? 'fa-pause' : 'fa-play'; ?>"></i>
                                        </button>
                                    </form>
                                    <form method="POST" style="display: inline;" onsubmit="return confirm('Bu sembolü silmek istediğinizden emin misiniz?');">
                                        <input type="hidden" name="action" value="delete_symbol">
                                        <input type="hidden" name="market_id" value="<?php echo $market['id']; ?>">
                                        <button type="submit" class="btn btn-sm btn-danger">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </form>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Sayfalama -->
                <?php if ($total_pages > 1): ?>
                <nav aria-label="Sayfa navigasyonu">
                    <ul class="pagination justify-content-center">
                        <?php if ($page > 1): ?>
                            <li class="page-item">
                                <a class="page-link" href="?page=<?php echo $page-1; ?>">Önceki</a>
                            </li>
                        <?php endif; ?>
                        
                        <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                            <li class="page-item <?php echo $i == $page ? 'active' : ''; ?>">
                                <a class="page-link" href="?page=<?php echo $i; ?>"><?php echo $i; ?></a>
                            </li>
                        <?php endfor; ?>
                        
                        <?php if ($page < $total_pages): ?>
                            <li class="page-item">
                                <a class="page-link" href="?page=<?php echo $page+1; ?>">Sonraki</a>
                            </li>
                        <?php endif; ?>
                    </ul>
                </nav>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Yeni Sembol Ekleme Modal -->
    <div class="modal fade" id="addSymbolModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST">
                    <div class="modal-header">
                        <h5 class="modal-title">Yeni Sembol Ekle</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="action" value="add_symbol">
                        
                        <div class="mb-3">
                            <label class="form-label">Sembol Kodu</label>
                            <input type="text" class="form-control" name="symbol" required placeholder="Örn: BTCUSD">
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Sembol Adı</label>
                            <input type="text" class="form-control" name="name" required placeholder="Örn: Bitcoin/USD">
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Başlangıç Fiyatı</label>
                                    <input type="number" step="0.0001" class="form-control" name="current_price" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Değişim %</label>
                                    <input type="number" step="0.01" class="form-control" name="change_percent" value="0">
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">İptal</button>
                        <button type="submit" class="btn btn-success">Ekle</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Fiyat Güncelleme Modal -->
    <div class="modal fade" id="editPriceModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST">
                    <div class="modal-header">
                        <h5 class="modal-title" id="editPriceTitle">Fiyat Güncelle</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="action" value="update_price">
                        <input type="hidden" name="market_id" id="edit_market_id">
                        
                        <!-- Mevcut Fiyat Bilgisi -->
                        <div class="mb-3">
                            <label class="form-label">Mevcut Fiyat (Bilgi)</label>
                            <div class="input-group">
                                <span class="input-group-text">$</span>
                                <input type="text" class="form-control bg-light" id="current_price_display" readonly>
                            </div>
                            <small class="text-muted">Bu bilgi amaçlıdır, değiştirilemez.</small>
                        </div>
                        
                        <!-- Yeni Fiyat Girişi -->
                        <div class="mb-3">
                            <label class="form-label">Yeni Fiyat Giriniz</label>
                            <div class="input-group">
                                <span class="input-group-text">$</span>
                                <input type="number" step="0.0001" class="form-control" name="current_price" id="new_price_input" required placeholder="Yeni fiyatı giriniz">
                            </div>
                        </div>
                        
                        <!-- Değişim Yüzdesi - Otomatik Hesaplanan -->
                        <div class="mb-3">
                            <label class="form-label">Değişim % (Otomatik Hesaplanır)</label>
                            <input type="number" step="0.01" class="form-control" name="change_percent" id="edit_change_percent" value="0" placeholder="Otomatik hesaplanacak">
                            <small class="text-muted">Fiyat değiştikçe otomatik hesaplanır. İsterseniz manuel değiştirebilirsiniz.</small>
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
    let originalPrice = 0;
    
    function editPrice(id, symbol, currentPrice, changePercent) {
        // Modal formunu ayarla
        document.getElementById('edit_market_id').value = id;
        document.getElementById('editPriceTitle').textContent = symbol + ' Fiyat Güncelle';
        
        // Orijinal fiyatı sakla
        originalPrice = parseFloat(currentPrice);
        
        // Mevcut fiyatı bilgi alanına göster (readonly)
        document.getElementById('current_price_display').value = originalPrice.toFixed(4);
        
        // Yeni fiyat alanını temizle
        document.getElementById('new_price_input').value = '';
        
        // Değişim yüzdesini sıfırla
        document.getElementById('edit_change_percent').value = '0.00';
        
        // Modalı aç
        new bootstrap.Modal(document.getElementById('editPriceModal')).show();
        
        // Yeni fiyat alanına odaklan ve change event ekle
        setTimeout(() => {
            const newPriceInput = document.getElementById('new_price_input');
            newPriceInput.focus();
            
            // Otomatik hesaplama için event listener ekle
            newPriceInput.oninput = function() {
                calculateChangePercent();
            };
        }, 500);
    }
    
    function calculateChangePercent() {
        const newPriceInput = document.getElementById('new_price_input');
        const changePercentInput = document.getElementById('edit_change_percent');
        
        const newPrice = parseFloat(newPriceInput.value);
        
        if (newPrice && originalPrice && originalPrice > 0) {
            // Değişim yüzdesini hesapla: ((yeni_fiyat - eski_fiyat) / eski_fiyat) * 100
            const changePercent = ((newPrice - originalPrice) / originalPrice) * 100;
            changePercentInput.value = changePercent.toFixed(2);
        } else {
            changePercentInput.value = '0.00';
        }
    }
    </script>
</body>
</html>

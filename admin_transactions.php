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

// İşlemleri getir
$page = intval($_GET['page'] ?? 1);
$per_page = 20;
$offset = ($page - 1) * $per_page;

$query = "SELECT t.*, u.username 
          FROM transactions t 
          LEFT JOIN users u ON t.user_id = u.id 
          ORDER BY t.created_at DESC 
          LIMIT $per_page OFFSET $offset";
$stmt = $db->prepare($query);
$stmt->execute();
$transactions = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Toplam sayıyı al
$query = "SELECT COUNT(*) as total FROM transactions";
$stmt = $db->prepare($query);
$stmt->execute();
$total_transactions = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
$total_pages = ceil($total_transactions / $per_page);
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>İşlem Yönetimi - Admin Panel</title>
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
        <h1><i class="fas fa-exchange-alt"></i> İşlem Yönetimi</h1>
        
        <div class="card">
            <div class="card-header">
                <h5>Tüm İşlemler (<?php echo number_format($total_transactions); ?> işlem)</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Kullanıcı</th>
                                <th>Tip</th>
                                <th>Sembol</th>
                                <th>Miktar</th>
                                <th>Fiyat</th>
                                <th>Toplam</th>
                                <th>Komisyon</th>
                                <th>Durum</th>
                                <th>Tarih</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($transactions as $transaction): ?>
                            <tr>
                                <td><?php echo $transaction['id']; ?></td>
                                <td><?php echo htmlspecialchars($transaction['username']); ?></td>
                                <td>
                                    <span class="badge <?php echo $transaction['type'] === 'buy' ? 'bg-success' : 'bg-danger'; ?>">
                                        <?php echo strtoupper($transaction['type']); ?>
                                    </span>
                                </td>
                                <td><?php echo htmlspecialchars($transaction['symbol']); ?></td>
                                <td><?php echo number_format($transaction['amount'], 6); ?></td>
                                <td>$<?php echo number_format($transaction['price'], 4); ?></td>
                                <td><?php echo number_format($transaction['total'], 2); ?></td>
                                <td><?php echo number_format($transaction['fee'], 2); ?></td>
                                <td>
                                    <span class="badge <?php echo $transaction['status'] === 'completed' ? 'bg-success' : 'bg-warning'; ?>">
                                        <?php echo ucfirst($transaction['status']); ?>
                                    </span>
                                </td>
                                <td><?php echo date('d.m.Y H:i', strtotime($transaction['created_at'])); ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                
                <!-- Pagination -->
                <?php if ($total_pages > 1): ?>
                    <nav>
                        <ul class="pagination justify-content-center">
                            <?php if ($page > 1): ?>
                                <li class="page-item">
                                    <a class="page-link" href="?page=<?php echo ($page - 1); ?>">Önceki</a>
                                </li>
                            <?php endif; ?>
                            
                            <?php for ($i = max(1, $page - 2); $i <= min($total_pages, $page + 2); $i++): ?>
                                <li class="page-item <?php echo $i == $page ? 'active' : ''; ?>">
                                    <a class="page-link" href="?page=<?php echo $i; ?>"><?php echo $i; ?></a>
                                </li>
                            <?php endfor; ?>
                            
                            <?php if ($page < $total_pages): ?>
                                <li class="page-item">
                                    <a class="page-link" href="?page=<?php echo ($page + 1); ?>">Sonraki</a>
                                </li>
                            <?php endif; ?>
                        </ul>
                    </nav>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

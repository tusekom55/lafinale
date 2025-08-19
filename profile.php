<?php
require_once 'includes/functions.php';

// Require login for profile
requireLogin();

$page_title = t('profile');
$error = '';
$success = '';

$user_id = $_SESSION['user_id'];

// Get user information
$database = new Database();
$db = $database->getConnection();

$query = "SELECT * FROM users WHERE id = ?";
$stmt = $db->prepare($query);
$stmt->execute([$user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

// Handle profile update
if ($_POST && isset($_POST['update_profile'])) {
    $first_name = sanitizeInput($_POST['first_name'] ?? '');
    $last_name = sanitizeInput($_POST['last_name'] ?? '');
    $email = sanitizeInput($_POST['email'] ?? '');
    
    if (empty($first_name)) {
        $error = getCurrentLang() == 'tr' ? 'Ad gerekli' : 'First name is required';
    } elseif (empty($last_name)) {
        $error = getCurrentLang() == 'tr' ? 'Soyad gerekli' : 'Last name is required';
    } elseif (empty($email)) {
        $error = getCurrentLang() == 'tr' ? 'E-posta adresi gerekli' : 'Email address is required';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = getCurrentLang() == 'tr' ? 'Geçerli bir e-posta adresi girin' : 'Please enter a valid email address';
    } else {
        // Check if email is already used by another user
        $query = "SELECT id FROM users WHERE email = ? AND id != ?";
        $stmt = $db->prepare($query);
        $stmt->execute([$email, $user_id]);
        
        if ($stmt->fetch()) {
            $error = getCurrentLang() == 'tr' ? 'Bu e-posta adresi zaten kullanılıyor' : 'This email address is already in use';
        } else {
            $query = "UPDATE users SET first_name = ?, last_name = ?, email = ? WHERE id = ?";
            $stmt = $db->prepare($query);
            
            if ($stmt->execute([$first_name, $last_name, $email, $user_id])) {
                $success = getCurrentLang() == 'tr' ? 'Profil başarıyla güncellendi' : 'Profile updated successfully';
                $user['first_name'] = $first_name;
                $user['last_name'] = $last_name;
                $user['email'] = $email;
                logActivity($user_id, 'profile_update', 'Profile updated');
            } else {
                $error = getCurrentLang() == 'tr' ? 'Bir hata oluştu' : 'An error occurred';
            }
        }
    }
}

// Handle password change
if ($_POST && isset($_POST['change_password'])) {
    $current_password = $_POST['current_password'] ?? '';
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    
    if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
        $error = getCurrentLang() == 'tr' ? 'Tüm şifre alanlarını doldurun' : 'Please fill all password fields';
    } elseif (!password_verify($current_password, $user['password'])) {
        $error = getCurrentLang() == 'tr' ? 'Mevcut şifre yanlış' : 'Current password is incorrect';
    } elseif (strlen($new_password) < 6) {
        $error = getCurrentLang() == 'tr' ? 'Yeni şifre en az 6 karakter olmalı' : 'New password must be at least 6 characters';
    } elseif ($new_password !== $confirm_password) {
        $error = getCurrentLang() == 'tr' ? 'Yeni şifreler eşleşmiyor' : 'New passwords do not match';
    } else {
        $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
        $query = "UPDATE users SET password = ? WHERE id = ?";
        $stmt = $db->prepare($query);
        
        if ($stmt->execute([$hashed_password, $user_id])) {
            $success = getCurrentLang() == 'tr' ? 'Şifre başarıyla değiştirildi' : 'Password changed successfully';
            logActivity($user_id, 'password_change', 'Password updated');
        } else {
            $error = getCurrentLang() == 'tr' ? 'Bir hata oluştu' : 'An error occurred';
        }
    }
}

// Get user statistics
$query = "SELECT COUNT(*) as trade_count, COALESCE(SUM(total), 0) as total_volume FROM transactions WHERE user_id = ?";
$stmt = $db->prepare($query);
$stmt->execute([$user_id]);
$stats = $stmt->fetch(PDO::FETCH_ASSOC);

$query = "SELECT COUNT(*) as deposit_count, COALESCE(SUM(amount), 0) as total_deposits FROM deposits WHERE user_id = ? AND status = 'approved'";
$stmt = $db->prepare($query);
$stmt->execute([$user_id]);
$deposit_stats = $stmt->fetch(PDO::FETCH_ASSOC);

include 'includes/header.php';
?>

<div class="container">
    <div class="row">
        <!-- Profile Information -->
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white">
                    <h4 class="mb-0"><?php echo t('profile'); ?></h4>
                </div>
                <div class="card-body">
                    <?php if ($error): ?>
                    <div class="alert alert-danger" role="alert">
                        <i class="fas fa-exclamation-triangle me-2"></i><?php echo $error; ?>
                    </div>
                    <?php endif; ?>
                    
                    <?php if ($success): ?>
                    <div class="alert alert-success" role="alert">
                        <i class="fas fa-check-circle me-2"></i><?php echo $success; ?>
                    </div>
                    <?php endif; ?>
                    
                    <!-- Profile Update Form -->
                    <form method="POST" action="">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label"><?php echo getCurrentLang() == 'tr' ? 'Ad' : 'First Name'; ?></label>
                                    <input type="text" class="form-control" name="first_name" 
                                           value="<?php echo htmlspecialchars($user['first_name'] ?? ''); ?>" 
                                           minlength="2" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label"><?php echo getCurrentLang() == 'tr' ? 'Soyad' : 'Last Name'; ?></label>
                                    <input type="text" class="form-control" name="last_name" 
                                           value="<?php echo htmlspecialchars($user['last_name'] ?? ''); ?>" 
                                           minlength="2" required>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label"><?php echo t('username'); ?></label>
                                    <input type="text" class="form-control" value="<?php echo htmlspecialchars($user['username']); ?>" readonly>
                                    <small class="text-muted">
                                        <?php echo getCurrentLang() == 'tr' ? 'Kullanıcı adı değiştirilemez' : 'Username cannot be changed'; ?>
                                    </small>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label"><?php echo t('email'); ?></label>
                                    <input type="email" class="form-control" name="email" 
                                           value="<?php echo htmlspecialchars($user['email']); ?>" required>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label"><?php echo getCurrentLang() == 'tr' ? 'Üyelik Tarihi' : 'Member Since'; ?></label>
                                    <input type="text" class="form-control" 
                                           value="<?php echo date('d.m.Y', strtotime($user['created_at'])); ?>" readonly>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label"><?php echo getCurrentLang() == 'tr' ? 'Hesap Durumu' : 'Account Status'; ?></label>
                                    <input type="text" class="form-control" 
                                           value="<?php echo getCurrentLang() == 'tr' ? 'Aktif' : 'Active'; ?>" readonly>
                                </div>
                            </div>
                        </div>
                        
                        <button type="submit" name="update_profile" class="btn btn-primary">
                            <i class="fas fa-save me-2"></i><?php echo getCurrentLang() == 'tr' ? 'Profili Güncelle' : 'Update Profile'; ?>
                        </button>
                    </form>
                </div>
            </div>
            
            <!-- Change Password -->
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white">
                    <h5 class="mb-0"><?php echo getCurrentLang() == 'tr' ? 'Şifre Değiştir' : 'Change Password'; ?></h5>
                </div>
                <div class="card-body">
                    <form method="POST" action="">
                        <div class="mb-3">
                            <label class="form-label"><?php echo getCurrentLang() == 'tr' ? 'Mevcut Şifre' : 'Current Password'; ?></label>
                            <input type="password" class="form-control" name="current_password" required>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label"><?php echo getCurrentLang() == 'tr' ? 'Yeni Şifre' : 'New Password'; ?></label>
                                    <input type="password" class="form-control" name="new_password" minlength="6" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label"><?php echo getCurrentLang() == 'tr' ? 'Yeni Şifre Tekrar' : 'Confirm New Password'; ?></label>
                                    <input type="password" class="form-control" name="confirm_password" minlength="6" required>
                                </div>
                            </div>
                        </div>
                        
                        <button type="submit" name="change_password" class="btn btn-warning">
                            <i class="fas fa-key me-2"></i><?php echo getCurrentLang() == 'tr' ? 'Şifreyi Değiştir' : 'Change Password'; ?>
                        </button>
                    </form>
                </div>
            </div>
        </div>
        
        <!-- Account Statistics -->
        <div class="col-lg-4">
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white">
                    <h5 class="mb-0"><?php echo getCurrentLang() == 'tr' ? 'Hesap İstatistikleri' : 'Account Statistics'; ?></h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-6 mb-3">
                            <div class="text-center p-3 bg-primary bg-opacity-10 rounded">
                                <i class="fas fa-exchange-alt fa-2x text-primary mb-2"></i>
                                <div class="h4 mb-1"><?php echo $stats['trade_count']; ?></div>
                                <small class="text-muted">
                                    <?php echo getCurrentLang() == 'tr' ? 'Toplam İşlem' : 'Total Trades'; ?>
                                </small>
                            </div>
                        </div>
                        <div class="col-6 mb-3">
                            <div class="text-center p-3 bg-success bg-opacity-10 rounded">
                                <i class="fas fa-chart-line fa-2x text-success mb-2"></i>
                                <div class="h4 mb-1"><?php echo formatNumber($stats['total_volume']); ?></div>
                                <small class="text-muted">
                                    <?php echo getCurrentLang() == 'tr' ? 'İşlem Hacmi (TL)' : 'Trade Volume (TL)'; ?>
                                </small>
                            </div>
                        </div>
                        <div class="col-6 mb-3">
                            <div class="text-center p-3 bg-info bg-opacity-10 rounded">
                                <i class="fas fa-plus-circle fa-2x text-info mb-2"></i>
                                <div class="h4 mb-1"><?php echo $deposit_stats['deposit_count']; ?></div>
                                <small class="text-muted">
                                    <?php echo getCurrentLang() == 'tr' ? 'Para Yatırma' : 'Deposits'; ?>
                                </small>
                            </div>
                        </div>
                        <div class="col-6 mb-3">
                            <div class="text-center p-3 bg-warning bg-opacity-10 rounded">
                                <i class="fas fa-coins fa-2x text-warning mb-2"></i>
                                <div class="h4 mb-1"><?php echo formatNumber($deposit_stats['total_deposits']); ?></div>
                                <small class="text-muted">
                                    <?php echo getCurrentLang() == 'tr' ? 'Toplam Yatırım (TL)' : 'Total Deposits (TL)'; ?>
                                </small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Quick Actions -->
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white">
                    <h5 class="mb-0">💰 Para İşlemleri</h5>
                </div>
                <div class="card-body">
                    <div class="d-grid gap-2">
                        <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#depositModal">
                            <i class="fas fa-plus-circle me-2"></i>Para Yatır
                        </button>
                        <button class="btn btn-danger" data-bs-toggle="modal" data-bs-target="#withdrawModal">
                            <i class="fas fa-minus-circle me-2"></i>Para Çek
                        </button>
                        <a href="portfolio.php" class="btn btn-primary">
                            <i class="fas fa-chart-pie me-2"></i>Portföyüm
                        </a>
                        <a href="trading.php" class="btn btn-info">
                            <i class="fas fa-history me-2"></i>İşlem Geçmişi
                        </a>
                    </div>
                </div>
            </div>

            <!-- Account Security -->
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white">
                    <h5 class="mb-0"><?php echo getCurrentLang() == 'tr' ? 'Hesap Güvenliği' : 'Account Security'; ?></h5>
                </div>
                <div class="card-body">
                    <div class="d-flex align-items-center mb-3">
                        <i class="fas fa-shield-alt fa-2x text-success me-3"></i>
                        <div>
                            <h6 class="mb-1"><?php echo getCurrentLang() == 'tr' ? 'Şifre Koruması' : 'Password Protection'; ?></h6>
                            <small class="text-muted">
                                <?php echo getCurrentLang() == 'tr' ? 'Hesabınız şifre ile korunuyor' : 'Your account is protected with password'; ?>
                            </small>
                        </div>
                    </div>
                    
                    <div class="d-flex align-items-center mb-3">
                        <i class="fas fa-envelope fa-2x text-info me-3"></i>
                        <div>
                            <h6 class="mb-1"><?php echo getCurrentLang() == 'tr' ? 'E-posta Doğrulaması' : 'Email Verification'; ?></h6>
                            <small class="text-muted">
                                <?php echo getCurrentLang() == 'tr' ? 'E-posta adresiniz kayıtlı' : 'Your email address is registered'; ?>
                            </small>
                        </div>
                    </div>
                    
                    <div class="d-flex align-items-center">
                        <i class="fas fa-clock fa-2x text-warning me-3"></i>
                        <div>
                            <h6 class="mb-1"><?php echo getCurrentLang() == 'tr' ? 'Son Giriş' : 'Last Login'; ?></h6>
                            <small class="text-muted">
                                <?php echo getCurrentLang() == 'tr' ? 'Şimdi aktif' : 'Currently active'; ?>
                            </small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Password confirmation validation
document.querySelector('input[name="confirm_password"]').addEventListener('input', function() {
    const newPassword = document.querySelector('input[name="new_password"]').value;
    const confirmPassword = this.value;
    
    if (confirmPassword && newPassword !== confirmPassword) {
        this.setCustomValidity(getCurrentLang() === 'tr' ? 'Şifreler eşleşmiyor' : 'Passwords do not match');
    } else {
        this.setCustomValidity('');
    }
});
</script>

<?php include 'includes/footer.php'; ?>

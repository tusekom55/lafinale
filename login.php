<?php
require_once 'includes/functions.php';

// Redirect if already logged in
if (isLoggedIn()) {
    header('Location: index.php');
    exit();
}

$page_title = t('login');
$error = '';
$success = '';

if ($_POST) {
    $username = sanitizeInput($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    
    if (empty($username) || empty($password)) {
        $error = getCurrentLang() == 'tr' ? 'Tüm alanları doldurun' : 'Please fill all fields';
    } else {
        $database = new Database();
        $db = $database->getConnection();
        
        $query = "SELECT id, username, password, is_admin FROM users WHERE username = ? OR email = ?";
        $stmt = $db->prepare($query);
        $stmt->execute([$username, $username]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['is_admin'] = $user['is_admin'];
            
            logActivity($user['id'], 'login', 'User logged in');
            
            header('Location: index.php');
            exit();
        } else {
            $error = t('login_error');
        }
    }
}

include 'includes/header.php';
?>

<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-6 col-lg-4">
            <div class="card border-0 shadow-sm">
                <div class="card-body p-5">
                    <div class="text-center mb-4">
                        <i class="fas fa-chart-line fa-3x text-primary mb-3"></i>
                        <h3 class="h4 mb-0"><?php echo t('login'); ?></h3>
                        <p class="text-muted"><?php echo SITE_NAME; ?></p>
                    </div>
                    
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
                    
                    <form method="POST" action="">
                        <div class="mb-3">
                            <label for="username" class="form-label"><?php echo t('username'); ?> / <?php echo t('email'); ?></label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-user"></i></span>
                                <input type="text" class="form-control" id="username" name="username" 
                                       value="<?php echo htmlspecialchars($username ?? ''); ?>" required>
                            </div>
                        </div>
                        
                        <div class="mb-4">
                            <label for="password" class="form-label"><?php echo t('password'); ?></label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-lock"></i></span>
                                <input type="password" class="form-control" id="password" name="password" required>
                                <button class="btn btn-outline-secondary" type="button" onclick="togglePassword()">
                                    <i class="fas fa-eye" id="toggleIcon"></i>
                                </button>
                            </div>
                        </div>
                        
                        <button type="submit" class="btn btn-primary w-100 mb-3">
                            <i class="fas fa-sign-in-alt me-2"></i><?php echo t('login'); ?>
                        </button>
                    </form>
                    
                    <div class="text-center">
                        <p class="text-muted mb-0">
                            <?php echo getCurrentLang() == 'tr' ? 'Hesabınız yok mu?' : "Don't have an account?"; ?>
                            <a href="register.php" class="text-decoration-none"><?php echo t('register'); ?></a>
                        </p>
                    </div>
                    
                    <!-- Demo Account Info -->
                    <div class="mt-4 p-3 bg-light rounded">
                        <h6 class="mb-2"><?php echo getCurrentLang() == 'tr' ? 'Demo Hesap' : 'Demo Account'; ?></h6>
                        <small class="text-muted">
                            <strong><?php echo getCurrentLang() == 'tr' ? 'Kullanıcı Adı' : 'Username'; ?>:</strong> admin<br>
                            <strong><?php echo getCurrentLang() == 'tr' ? 'Şifre' : 'Password'; ?>:</strong> admin123
                        </small>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function togglePassword() {
    const passwordField = document.getElementById('password');
    const toggleIcon = document.getElementById('toggleIcon');
    
    if (passwordField.type === 'password') {
        passwordField.type = 'text';
        toggleIcon.className = 'fas fa-eye-slash';
    } else {
        passwordField.type = 'password';
        toggleIcon.className = 'fas fa-eye';
    }
}

// Auto-focus on username field
document.addEventListener('DOMContentLoaded', function() {
    document.getElementById('username').focus();
});
</script>

<?php include 'includes/footer.php'; ?>

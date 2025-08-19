<?php
require_once 'includes/functions.php';

// Redirect if already logged in
if (isLoggedIn()) {
    header('Location: index.php');
    exit();
}

$page_title = t('register');
$error = '';
$success = '';

if ($_POST) {
    $first_name = sanitizeInput($_POST['first_name'] ?? '');
    $last_name = sanitizeInput($_POST['last_name'] ?? '');
    $username = sanitizeInput($_POST['username'] ?? '');
    $email = sanitizeInput($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    
    // Validation
    if (empty($first_name) || empty($last_name) || empty($username) || empty($email) || empty($password) || empty($confirm_password)) {
        $error = getCurrentLang() == 'tr' ? 'Tüm alanları doldurun' : 'Please fill all fields';
    } elseif (strlen($first_name) < 2) {
        $error = getCurrentLang() == 'tr' ? 'Ad en az 2 karakter olmalı' : 'First name must be at least 2 characters';
    } elseif (strlen($last_name) < 2) {
        $error = getCurrentLang() == 'tr' ? 'Soyad en az 2 karakter olmalı' : 'Last name must be at least 2 characters';
    } elseif (strlen($username) < 3) {
        $error = getCurrentLang() == 'tr' ? 'Kullanıcı adı en az 3 karakter olmalı' : 'Username must be at least 3 characters';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = getCurrentLang() == 'tr' ? 'Geçerli bir e-posta adresi girin' : 'Please enter a valid email address';
    } elseif (strlen($password) < 6) {
        $error = getCurrentLang() == 'tr' ? 'Şifre en az 6 karakter olmalı' : 'Password must be at least 6 characters';
    } elseif ($password !== $confirm_password) {
        $error = getCurrentLang() == 'tr' ? 'Şifreler eşleşmiyor' : 'Passwords do not match';
    } else {
        $database = new Database();
        $db = $database->getConnection();
        
        // Check if username or email already exists
        $query = "SELECT id FROM users WHERE username = ? OR email = ?";
        $stmt = $db->prepare($query);
        $stmt->execute([$username, $email]);
        
        if ($stmt->fetch()) {
            $error = getCurrentLang() == 'tr' ? 'Bu kullanıcı adı veya e-posta zaten kullanılıyor' : 'Username or email already exists';
        } else {
            // Create new user
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $query = "INSERT INTO users (first_name, last_name, username, email, password) VALUES (?, ?, ?, ?, ?)";
            $stmt = $db->prepare($query);
            
            if ($stmt->execute([$first_name, $last_name, $username, $email, $hashed_password])) {
                $success = t('register_success');
                
                // Log the registration
                $user_id = $db->lastInsertId();
                logActivity($user_id, 'register', 'User registered');
                
                // Clear form data
                $first_name = $last_name = $username = $email = '';
            } else {
                $error = getCurrentLang() == 'tr' ? 'Kayıt sırasında bir hata oluştu' : 'An error occurred during registration';
            }
        }
    }
}

include 'includes/header.php';
?>

<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-6 col-lg-5">
            <div class="card border-0 shadow-sm">
                <div class="card-body p-5">
                    <div class="text-center mb-4">
                        <i class="fas fa-user-plus fa-3x text-primary mb-3"></i>
                        <h3 class="h4 mb-0"><?php echo t('register'); ?></h3>
                        <p class="text-muted"><?php echo getCurrentLang() == 'tr' ? 'Yeni hesap oluştur' : 'Create new account'; ?></p>
                    </div>
                    
                    <?php if ($error): ?>
                    <div class="alert alert-danger" role="alert">
                        <i class="fas fa-exclamation-triangle me-2"></i><?php echo $error; ?>
                    </div>
                    <?php endif; ?>
                    
                    <?php if ($success): ?>
                    <div class="alert alert-success" role="alert">
                        <i class="fas fa-check-circle me-2"></i><?php echo $success; ?>
                        <div class="mt-2">
                            <a href="login.php" class="btn btn-success btn-sm">
                                <?php echo getCurrentLang() == 'tr' ? 'Şimdi Giriş Yap' : 'Login Now'; ?>
                            </a>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <form method="POST" action="">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="first_name" class="form-label"><?php echo getCurrentLang() == 'tr' ? 'Ad' : 'First Name'; ?></label>
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="fas fa-user"></i></span>
                                        <input type="text" class="form-control" id="first_name" name="first_name" 
                                               value="<?php echo htmlspecialchars($first_name ?? ''); ?>" 
                                               minlength="2" maxlength="50" required>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="last_name" class="form-label"><?php echo getCurrentLang() == 'tr' ? 'Soyad' : 'Last Name'; ?></label>
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="fas fa-user"></i></span>
                                        <input type="text" class="form-control" id="last_name" name="last_name" 
                                               value="<?php echo htmlspecialchars($last_name ?? ''); ?>" 
                                               minlength="2" maxlength="50" required>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="username" class="form-label"><?php echo t('username'); ?></label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-at"></i></span>
                                <input type="text" class="form-control" id="username" name="username" 
                                       value="<?php echo htmlspecialchars($username ?? ''); ?>" 
                                       minlength="3" maxlength="50" required>
                            </div>
                            <small class="text-muted">
                                <?php echo getCurrentLang() == 'tr' ? 'En az 3 karakter, sadece harf, rakam ve alt çizgi' : 'At least 3 characters, letters, numbers and underscore only'; ?>
                            </small>
                        </div>
                        
                        <div class="mb-3">
                            <label for="email" class="form-label"><?php echo t('email'); ?></label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-envelope"></i></span>
                                <input type="email" class="form-control" id="email" name="email" 
                                       value="<?php echo htmlspecialchars($email ?? ''); ?>" required>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="password" class="form-label"><?php echo t('password'); ?></label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-lock"></i></span>
                                <input type="password" class="form-control" id="password" name="password" 
                                       minlength="6" required>
                                <button class="btn btn-outline-secondary" type="button" onclick="togglePassword('password')">
                                    <i class="fas fa-eye" id="toggleIcon1"></i>
                                </button>
                            </div>
                            <small class="text-muted">
                                <?php echo getCurrentLang() == 'tr' ? 'En az 6 karakter' : 'At least 6 characters'; ?>
                            </small>
                        </div>
                        
                        <div class="mb-4">
                            <label for="confirm_password" class="form-label"><?php echo t('confirm_password'); ?></label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-lock"></i></span>
                                <input type="password" class="form-control" id="confirm_password" name="confirm_password" 
                                       minlength="6" required>
                                <button class="btn btn-outline-secondary" type="button" onclick="togglePassword('confirm_password')">
                                    <i class="fas fa-eye" id="toggleIcon2"></i>
                                </button>
                            </div>
                        </div>
                        
                        <button type="submit" class="btn btn-primary w-100 mb-3">
                            <i class="fas fa-user-plus me-2"></i><?php echo t('register'); ?>
                        </button>
                    </form>
                    
                    <div class="text-center">
                        <p class="text-muted mb-0">
                            <?php echo getCurrentLang() == 'tr' ? 'Zaten hesabınız var mı?' : 'Already have an account?'; ?>
                            <a href="login.php" class="text-decoration-none"><?php echo t('login'); ?></a>
                        </p>
                    </div>
                    
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function togglePassword(fieldId) {
    const passwordField = document.getElementById(fieldId);
    const toggleIcon = document.getElementById(fieldId === 'password' ? 'toggleIcon1' : 'toggleIcon2');
    
    if (passwordField.type === 'password') {
        passwordField.type = 'text';
        toggleIcon.className = 'fas fa-eye-slash';
    } else {
        passwordField.type = 'password';
        toggleIcon.className = 'fas fa-eye';
    }
}

// Password confirmation validation
document.getElementById('confirm_password').addEventListener('input', function() {
    const password = document.getElementById('password').value;
    const confirmPassword = this.value;
    
    if (confirmPassword && password !== confirmPassword) {
        this.setCustomValidity(getCurrentLang() === 'tr' ? 'Şifreler eşleşmiyor' : 'Passwords do not match');
    } else {
        this.setCustomValidity('');
    }
});

// Username validation
document.getElementById('username').addEventListener('input', function() {
    const username = this.value;
    const regex = /^[a-zA-Z0-9_]+$/;
    
    if (username && !regex.test(username)) {
        this.setCustomValidity(getCurrentLang() === 'tr' ? 
            'Sadece harf, rakam ve alt çizgi kullanın' : 
            'Only letters, numbers and underscore allowed');
    } else {
        this.setCustomValidity('');
    }
});

// Auto-focus on first name field
document.addEventListener('DOMContentLoaded', function() {
    document.getElementById('first_name').focus();
});
</script>

<?php include 'includes/footer.php'; ?>

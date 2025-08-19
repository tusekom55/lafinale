<?php
// Language configuration
session_start();

// Set default language
if (!isset($_SESSION['lang'])) {
    $_SESSION['lang'] = 'tr';
}

// Language switching
if (isset($_GET['lang']) && in_array($_GET['lang'], ['tr', 'en'])) {
    $_SESSION['lang'] = $_GET['lang'];
}

// Language arrays
$lang = array();

// Turkish translations
$lang['tr'] = array(
    // Navigation
    'markets' => 'Piyasalar',
    'trading' => 'İşlem',
    'wallet' => 'Cüzdan',
    'profile' => 'Profil',
    'login' => 'Giriş',
    'register' => 'Kayıt',
    'logout' => 'Çıkış',
    'admin' => 'Admin',
    
    // Market page
    'crypto_markets' => 'Kripto Para Piyasaları',
    'market_name' => 'Piyasa Adı',
    'last_price' => 'Son Fiyat',
    'change' => 'Değişim',
    'low_24h' => '24S En Düşük',
    'high_24h' => '24S En Yüksek',
    'volume_24h' => '24S Hacim',
    'search_markets' => 'Piyasa Ara...',
    
    // Trading
    'buy' => 'Al',
    'sell' => 'Sat',
    'amount' => 'Miktar',
    'price' => 'Fiyat',
    'total' => 'Toplam',
    'order_book' => 'Emir Defteri',
    'trade_history' => 'İşlem Geçmişi',
    'my_orders' => 'Emirlerim',
    
    // Wallet
    'balance' => 'Bakiye',
    'deposit' => 'Para Yatır',
    'withdraw' => 'Para Çek',
    'transaction_history' => 'İşlem Geçmişi',
    'available_balance' => 'Kullanılabilir Bakiye',
    
    // Forms
    'username' => 'Kullanıcı Adı',
    'email' => 'E-posta',
    'password' => 'Şifre',
    'confirm_password' => 'Şifre Tekrar',
    'submit' => 'Gönder',
    'cancel' => 'İptal',
    
    // Messages
    'login_success' => 'Başarıyla giriş yaptınız',
    'login_error' => 'Kullanıcı adı veya şifre hatalı',
    'register_success' => 'Kayıt başarılı, giriş yapabilirsiniz',
    'insufficient_balance' => 'Yetersiz bakiye',
    'trade_success' => 'İşlem başarılı',
    'deposit_request_sent' => 'Para yatırma talebi gönderildi',
    'withdrawal_request_sent' => 'Para çekme talebi gönderildi',
    
    // Status
    'pending' => 'Beklemede',
    'approved' => 'Onaylandı',
    'rejected' => 'Reddedildi',
    'completed' => 'Tamamlandı'
);

// English translations
$lang['en'] = array(
    // Navigation
    'markets' => 'Markets',
    'trading' => 'Trading',
    'wallet' => 'Wallet',
    'profile' => 'Profile',
    'login' => 'Login',
    'register' => 'Register',
    'logout' => 'Logout',
    'admin' => 'Admin',
    
    // Market page
    'crypto_markets' => 'Cryptocurrency Markets',
    'market_name' => 'Market Name',
    'last_price' => 'Last Price',
    'change' => 'Change',
    'low_24h' => '24h Low',
    'high_24h' => '24h High',
    'volume_24h' => '24h Volume',
    'search_markets' => 'Search Markets...',
    
    // Trading
    'buy' => 'Buy',
    'sell' => 'Sell',
    'amount' => 'Amount',
    'price' => 'Price',
    'total' => 'Total',
    'order_book' => 'Order Book',
    'trade_history' => 'Trade History',
    'my_orders' => 'My Orders',
    
    // Wallet
    'balance' => 'Balance',
    'deposit' => 'Deposit',
    'withdraw' => 'Withdraw',
    'transaction_history' => 'Transaction History',
    'available_balance' => 'Available Balance',
    
    // Forms
    'username' => 'Username',
    'email' => 'Email',
    'password' => 'Password',
    'confirm_password' => 'Confirm Password',
    'submit' => 'Submit',
    'cancel' => 'Cancel',
    
    // Messages
    'login_success' => 'Login successful',
    'login_error' => 'Invalid username or password',
    'register_success' => 'Registration successful, you can login now',
    'insufficient_balance' => 'Insufficient balance',
    'trade_success' => 'Trade successful',
    'deposit_request_sent' => 'Deposit request sent',
    'withdrawal_request_sent' => 'Withdrawal request sent',
    
    // Status
    'pending' => 'Pending',
    'approved' => 'Approved',
    'rejected' => 'Rejected',
    'completed' => 'Completed'
);

// Get current language text
function t($key) {
    global $lang;
    $current_lang = $_SESSION['lang'] ?? 'tr';
    return $lang[$current_lang][$key] ?? $key;
}

// Get current language
function getCurrentLang() {
    return $_SESSION['lang'] ?? 'tr';
}
?>

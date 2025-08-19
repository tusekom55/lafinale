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
    'completed' => 'Tamamlandı',
    
    // Wallet specific
    'payment_method' => 'Ödeme Yöntemi',
    'select' => 'Seçiniz',
    'bank_transfer' => 'Banka Havalesi',
    'digital_payment' => 'Dijital Ödeme',
    'crypto_currency' => 'Kripto Para',
    'reference_description' => 'Referans/Açıklama',
    'transfer_reference_placeholder' => 'İşlem referansı veya açıklama',
    'deposit_instructions' => 'Para Yatırma Talimatları',
    'include_username_description' => 'Havale/EFT açıklama kısmına kullanıcı adınızı yazınız.',
    'deposits' => 'Para Yatırma',
    'withdrawals' => 'Para Çekme',
    'no_deposit_history' => 'Henüz para yatırma işlemi yok',
    'no_withdrawal_history' => 'Henüz para çekme işlemi yok',
    'date' => 'Tarih',
    'method' => 'Yöntem',
    'status' => 'Durum',
    'minimum_deposit' => 'Minimum para yatırma tutarı',
    'minimum_withdrawal' => 'Minimum para çekme tutarı',
    'invalid_payment_method' => 'Geçersiz ödeme yöntemi',
    'invalid_amount' => 'Geçersiz tutar',
    'an_error_occurred' => 'Bir hata oluştu',
    'main_balance' => 'Ana Bakiye',
    'available_balance_text' => 'Kullanılabilir',
    'exchange_rate' => 'Döviz Kuru',
    'turkish_lira' => 'Türk Lirası',
    'us_dollar' => 'Amerikan Doları',
    'withdraw_instructions' => 'Para çekme işlemi admin onayı gerektirir. İşlem süresi 1-3 iş günüdür.',
    
    // Profile specific
    'profile_settings' => 'Profil Ayarları',
    'personal_information' => 'Kişisel Bilgiler',
    'account_settings' => 'Hesap Ayarları',
    'security_settings' => 'Güvenlik Ayarları',
    'change_password' => 'Şifre Değiştir',
    'current_password' => 'Mevcut Şifre',
    'new_password' => 'Yeni Şifre',
    'confirm_new_password' => 'Yeni Şifre Tekrar',
    'update_profile' => 'Profili Güncelle',
    'full_name' => 'Ad Soyad',
    'phone' => 'Telefon',
    'address' => 'Adres',
    'two_factor_auth' => 'İki Faktörlü Doğrulama',
    'enable_2fa' => '2FA Etkinleştir',
    'disable_2fa' => '2FA Devre Dışı Bırak',
    'profile_updated' => 'Profil başarıyla güncellendi',
    'password_changed' => 'Şifre başarıyla değiştirildi',
    
    // Markets specific
    'market_overview' => 'Piyasa Genel Bakış',
    'price' => 'Fiyat',
    'change_24h' => '24s Değişim',
    'volume' => 'Hacim',
    'market_cap' => 'Piyasa Değeri',
    'buy_now' => 'Hemen Al',
    'sell_now' => 'Hemen Sat',
    'view_chart' => 'Grafik Görüntüle',
    'top_gainers' => 'En Çok Kazananlar',
    'top_losers' => 'En Çok Kaybedenler',
    'trending' => 'Trend',
    
    // Trading specific
    'order_type' => 'Emir Tipi',
    'market_order' => 'Piyasa Emri',
    'limit_order' => 'Limit Emri',
    'place_order' => 'Emir Ver',
    'cancel_order' => 'Emri İptal Et',
    'order_placed' => 'Emir başarıyla verildi',
    'order_cancelled' => 'Emir başarıyla iptal edildi',
    'open_orders' => 'Açık Emirler',
    'order_history' => 'Emir Geçmişi',
    
    // General UI
    'save' => 'Kaydet',
    'update' => 'Güncelle',
    'delete' => 'Sil',
    'edit' => 'Düzenle',
    'view' => 'Görüntüle',
    'close' => 'Kapat',
    'back' => 'Geri',
    'next' => 'İleri',
    'previous' => 'Önceki',
    'search' => 'Ara',
    'filter' => 'Filtrele',
    'sort' => 'Sırala',
    'loading' => 'Yükleniyor...',
    'no_data' => 'Veri bulunamadı',
    'error' => 'Hata',
    'success' => 'Başarılı',
    'warning' => 'Uyarı',
    'info' => 'Bilgi'
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
    'completed' => 'Completed',
    
    // Wallet specific
    'payment_method' => 'Payment Method',
    'select' => 'Select',
    'bank_transfer' => 'Bank Transfer',
    'digital_payment' => 'Digital Payment',
    'crypto_currency' => 'Cryptocurrency',
    'reference_description' => 'Reference/Description',
    'transfer_reference_placeholder' => 'Transaction reference or description',
    'deposit_instructions' => 'Deposit Instructions',
    'include_username_description' => 'Please include your username in the transfer description.',
    'deposits' => 'Deposits',
    'withdrawals' => 'Withdrawals',
    'no_deposit_history' => 'No deposit history yet',
    'no_withdrawal_history' => 'No withdrawal history yet',
    'date' => 'Date',
    'method' => 'Method',
    'status' => 'Status',
    'minimum_deposit' => 'Minimum deposit amount is',
    'minimum_withdrawal' => 'Minimum withdrawal amount is',
    'invalid_payment_method' => 'Invalid payment method',
    'invalid_amount' => 'Invalid amount',
    'an_error_occurred' => 'An error occurred',
    'main_balance' => 'Main Balance',
    'available_balance_text' => 'Available',
    'exchange_rate' => 'Exchange Rate',
    'turkish_lira' => 'Turkish Lira',
    'us_dollar' => 'US Dollar',
    'withdraw_instructions' => 'Withdrawal request requires admin approval. Processing time is 1-3 business days.',
    
    // Profile specific
    'profile_settings' => 'Profile Settings',
    'personal_information' => 'Personal Information',
    'account_settings' => 'Account Settings',
    'security_settings' => 'Security Settings',
    'change_password' => 'Change Password',
    'current_password' => 'Current Password',
    'new_password' => 'New Password',
    'confirm_new_password' => 'Confirm New Password',
    'update_profile' => 'Update Profile',
    'full_name' => 'Full Name',
    'phone' => 'Phone',
    'address' => 'Address',
    'two_factor_auth' => 'Two Factor Authentication',
    'enable_2fa' => 'Enable 2FA',
    'disable_2fa' => 'Disable 2FA',
    'profile_updated' => 'Profile updated successfully',
    'password_changed' => 'Password changed successfully',
    
    // Markets specific
    'market_overview' => 'Market Overview',
    'price' => 'Price',
    'change_24h' => '24h Change',
    'volume' => 'Volume',
    'market_cap' => 'Market Cap',
    'buy_now' => 'Buy Now',
    'sell_now' => 'Sell Now',
    'view_chart' => 'View Chart',
    'top_gainers' => 'Top Gainers',
    'top_losers' => 'Top Losers',
    'trending' => 'Trending',
    
    // Trading specific
    'order_type' => 'Order Type',
    'market_order' => 'Market Order',
    'limit_order' => 'Limit Order',
    'place_order' => 'Place Order',
    'cancel_order' => 'Cancel Order',
    'order_placed' => 'Order placed successfully',
    'order_cancelled' => 'Order cancelled successfully',
    'open_orders' => 'Open Orders',
    'order_history' => 'Order History',
    
    // General UI
    'save' => 'Save',
    'update' => 'Update',
    'delete' => 'Delete',
    'edit' => 'Edit',
    'view' => 'View',
    'close' => 'Close',
    'back' => 'Back',
    'next' => 'Next',
    'previous' => 'Previous',
    'search' => 'Search',
    'filter' => 'Filter',
    'sort' => 'Sort',
    'loading' => 'Loading...',
    'no_data' => 'No data available',
    'error' => 'Error',
    'success' => 'Success',
    'warning' => 'Warning',
    'info' => 'Information'
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

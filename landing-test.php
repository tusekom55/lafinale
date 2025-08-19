<?php
require_once 'includes/functions.php';

$page_title = 'GlobalBorsa - Türkiye\'nin En Güvenilir Kripto Borsası';

// Get some sample market data for display
$markets = getMarketData('crypto_tl', 6);
?>

<!DOCTYPE html>
<html lang="<?php echo getCurrentLang(); ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?></title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <!-- AOS Animation Library -->
    <link href="https://cdn.jsdelivr.net/npm/aos@2.3.4/dist/aos.css" rel="stylesheet">
    
    <!-- Custom Landing CSS -->
    <link href="assets/css/landing.css" rel="stylesheet">
    
    <style>
        /* Critical CSS - XM.com Style */
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            margin: 0;
            padding: 0;
            line-height: 1.6;
            overflow-x: hidden;
        }
        
        .hero-section {
            min-height: 100vh;
            position: relative;
            background: linear-gradient(135deg, #1a365d 0%, #2b5ce6 50%, #3182ce 100%);
            color: white;
            display: flex;
            flex-direction: column;
            overflow: hidden;
        }
        
        .hero-background {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: 1;
        }
        
        .hero-content {
            flex: 1;
            display: flex;
            align-items: center;
            padding: 2rem 0;
            position: relative;
            z-index: 50;
        }
        
        .navbar-dark {
            position: relative;
            z-index: 100;
            background: rgba(26, 54, 93, 0.8);
            backdrop-filter: blur(10px);
            padding: 1.5rem 0;
        }
        
        .hero-title {
            font-size: 3.5rem;
            font-weight: 700;
            line-height: 1.2;
            margin-bottom: 2rem;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.3);
        }
        
        .hero-subtitle {
            font-size: 1.3rem;
            opacity: 0.9;
            margin-bottom: 3rem;
            max-width: 600px;
            margin-left: auto;
            margin-right: auto;
        }
        
        .hero-stats {
            font-size: 1.1rem;
            margin-bottom: 2rem;
            opacity: 0.9;
        }
        
        .trust-text {
            color: #ffd700;
            font-weight: 600;
        }
        
        .btn-primary {
            background: #2b5ce6;
            border: 2px solid #2b5ce6;
            font-weight: 600;
            padding: 0.75rem 1.5rem;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-block;
            border-radius: 0.375rem;
        }
        
        .btn-lg {
            font-size: 1.1rem;
            padding: 1rem 2rem;
            border-radius: 10px;
        }
        
        .btn-primary:hover {
            background: #3182ce;
            border-color: #3182ce;
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(43, 92, 230, 0.3);
        }
        
        .crypto-ticker {
            margin-top: 4rem;
            position: relative;
            z-index: 50;
        }
        
        .ticker-title {
            text-align: center;
            font-size: 1.1rem;
            margin-bottom: 2rem;
            opacity: 0.9;
        }
        
        .crypto-cards {
            display: flex;
            justify-content: center;
            gap: 1rem;
            flex-wrap: wrap;
            max-width: 1000px;
            margin: 0 auto;
        }
        
        .crypto-card {
            background: rgba(255, 255, 255, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.2);
            border-radius: 12px;
            padding: 1rem;
            min-width: 180px;
            text-align: center;
            transition: all 0.3s ease;
            backdrop-filter: blur(10px);
        }
        
        .crypto-card:hover {
            transform: translateY(-5px);
            background: rgba(255, 255, 255, 0.15);
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.2);
        }
        
        .crypto-icon img {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            margin-bottom: 0.5rem;
        }
        
        .crypto-symbol {
            font-weight: 700;
            font-size: 1.1rem;
        }
        
        .crypto-name {
            font-size: 0.9rem;
            opacity: 0.8;
            margin-bottom: 0.5rem;
        }
        
        .positive {
            color: #48bb78;
        }
        
        .negative {
            color: #f56565;
        }
        
        .features-section {
            background: #f7fafc;
            padding: 6rem 0;
        }
        
        .features-section h2 {
            font-size: 2.5rem;
            font-weight: 700;
            color: #2d3748;
            margin-bottom: 1rem;
        }
        
        .text-primary {
            color: #2b5ce6;
        }
        
        .feature-card {
            background: white;
            border-radius: 20px;
            padding: 2rem;
            height: 100%;
            transition: all 0.3s ease;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            display: flex;
            align-items: center;
            gap: 2rem;
            margin-bottom: 2rem;
        }
        
        .feature-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 25px 50px rgba(0, 0, 0, 0.15);
        }
        
        .card-content {
            flex: 1;
        }
        
        .card-content h3 {
            font-size: 1.5rem;
            font-weight: 700;
            color: #2d3748;
            margin-bottom: 1rem;
        }
        
        .feature-btn {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            background: #2b5ce6;
            color: white;
            text-decoration: none;
            padding: 0.75rem 1.5rem;
            border-radius: 8px;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        
        .feature-btn:hover {
            background: #3182ce;
            color: white;
            text-decoration: none;
        }
        
        .stats-section {
            background: #1a202c;
            color: white;
            padding: 6rem 0;
        }
        
        .stat-card {
            text-align: center;
            padding: 2rem 1rem;
            background: rgba(255, 255, 255, 0.05);
            border-radius: 15px;
            margin-bottom: 2rem;
        }
        
        .stat-number {
            font-size: 3rem;
            font-weight: 700;
            color: #ffd700;
            margin-bottom: 0.5rem;
        }
        
        .cta-section {
            background: #f7fafc;
            padding: 6rem 0;
            text-align: center;
        }
        
        .landing-footer {
            background: #2d3748;
            color: white;
            padding: 3rem 0 2rem;
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 15px;
        }
        
        @media (max-width: 768px) {
            .hero-title {
                font-size: 2.5rem;
            }
            
            .crypto-cards {
                gap: 0.5rem;
            }
            
            .feature-card {
                flex-direction: column;
                text-align: center;
                gap: 1rem;
            }
        }
    </style>
</head>
<body>
    <!-- Hero Section -->
    <section class="hero-section">
        <div class="hero-background">
            <div class="mountain-animation"></div>
            <div class="particles"></div>
        </div>
        
        <nav class="navbar navbar-expand-lg navbar-dark">
            <div class="container">
                <a class="navbar-brand" href="#">
                    <i class="fas fa-chart-line me-2"></i>GlobalBorsa
                </a>
                
                <div class="ms-auto d-flex align-items-center">
                    <div class="language-switcher me-3">
                        <a href="?lang=tr" class="<?php echo getCurrentLang() == 'tr' ? 'active' : ''; ?>">TR</a>
                        <a href="?lang=en" class="<?php echo getCurrentLang() == 'en' ? 'active' : ''; ?>">EN</a>
                    </div>
                    
                    <a href="login.php" class="btn btn-outline-light me-2">
                        <?php echo getCurrentLang() == 'tr' ? 'Giriş' : 'Login'; ?>
                    </a>
                    <a href="register.php" class="btn btn-primary">
                        <?php echo getCurrentLang() == 'tr' ? 'Hemen Başla' : 'Get Started'; ?>
                    </a>
                </div>
            </div>
        </nav>
        
        <div class="container hero-content">
            <div class="row justify-content-center text-center">
                <div class="col-lg-10">
                    <div class="hero-stats" data-aos="fade-down">
                        <?php echo getCurrentLang() == 'tr' ? 'Binlerce yatırımcı' : 'Thousands of investors'; ?>
                        <span class="trust-text"><?php echo getCurrentLang() == 'tr' ? 'bize güveniyor' : 'trust us'; ?></span>
                    </div>
                    
                    <h1 class="hero-title" data-aos="fade-up" data-aos-delay="200">
                        <?php echo getCurrentLang() == 'tr' ? 
                            'Türkiye\'nin en güvenilir kripto<br>borsası olmamız tesadüf değil' : 
                            'Being Turkey\'s most trusted crypto<br>exchange is no coincidence'; ?>
                    </h1>
                    
                    <p class="hero-subtitle" data-aos="fade-up" data-aos-delay="400">
                        <?php echo getCurrentLang() == 'tr' ? 
                            'Yatırımcılara rahatça kâr edebilecekleri seçkin bir yatırım ortamı sağlıyoruz.' : 
                            'We provide an exclusive investment environment where investors can easily profit.'; ?>
                    </p>
                    
                    <div class="hero-buttons" data-aos="fade-up" data-aos-delay="600">
                        <a href="register.php" class="btn btn-primary btn-lg me-3">
                            <?php echo getCurrentLang() == 'tr' ? '1.000 TL\'ye varan %100 bonus alın' : 'Get up to 1,000 TL 100% bonus'; ?>
                        </a>
                        <div class="bonus-note">
                            *<?php echo getCurrentLang() == 'tr' ? 'Sınırlı süreli teklif' : 'Limited time offer'; ?>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Crypto Ticker -->
            <div class="crypto-ticker" data-aos="slide-left" data-aos-delay="800">
                <div class="ticker-title">
                    <?php echo getCurrentLang() == 'tr' ? 'En popüler kripto paralarına kolay erişim' : 'Easy access to the most popular cryptocurrencies'; ?>
                </div>
                <div class="crypto-cards">
                    <?php foreach (array_slice($markets, 0, 5) as $index => $market): ?>
                    <div class="crypto-card" data-aos="fade-left" data-aos-delay="<?php echo 1000 + ($index * 100); ?>">
                        <div class="crypto-icon">
                            <?php if ($market['logo_url']): ?>
                            <img src="<?php echo $market['logo_url']; ?>" alt="<?php echo $market['name']; ?>">
                            <?php else: ?>
                            <i class="fab fa-bitcoin"></i>
                            <?php endif; ?>
                        </div>
                        <div class="crypto-info">
                            <div class="crypto-symbol"><?php echo $market['symbol']; ?></div>
                            <div class="crypto-name"><?php echo $market['name']; ?></div>
                        </div>
                        <div class="crypto-price">
                            <div class="price"><?php echo formatPrice($market['price']); ?> TL</div>
                            <div class="change <?php echo $market['change_24h'] >= 0 ? 'positive' : 'negative'; ?>">
                                <?php echo ($market['change_24h'] >= 0 ? '+' : '') . number_format($market['change_24h'], 2); ?>%
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
        
        <!-- Awards Section -->
        <div class="awards-section" data-aos="fade-up" data-aos-delay="1200">
            <div class="container text-center">
                <i class="fas fa-award award-icon"></i>
                <div class="award-text">
                    <?php echo getCurrentLang() == 'tr' ? 'Her yıl en iyi borsa ödülünün sahibi' : 'Winner of the best exchange award every year'; ?>
                </div>
            </div>
        </div>
    </section>
    
    <!-- Features Section -->
    <section class="features-section">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-lg-10 text-center">
                    <h2 data-aos="fade-up">
                        <?php echo getCurrentLang() == 'tr' ? 'Yatırımcılarımızın' : 'Take a look at our traders\''; ?>
                        <span class="text-primary">
                            <?php echo getCurrentLang() == 'tr' ? 'favorilerine' : 'favorites'; ?>
                        </span>
                        <?php echo getCurrentLang() == 'tr' ? 'göz atın' : ''; ?>
                    </h2>
                    <p class="features-subtitle" data-aos="fade-up" data-aos-delay="200">
                        <?php echo getCurrentLang() == 'tr' ? 
                            'Yatırımda herkesin ilk tercihi olmamızı sağlayan bazı vazgeçilmez ürünlerimiz hakkında bilgi edinin.' : 
                            'Learn about some of our indispensable products that make us everyone\'s first choice in investment.'; ?>
                    </p>
                </div>
            </div>
            
            <div class="row g-4 mt-4">
                <!-- GlobalBorsa App -->
                <div class="col-lg-6" data-aos="slide-right" data-aos-delay="300">
                    <div class="feature-card app-card">
                        <div class="card-content">
                            <h3><?php echo getCurrentLang() == 'tr' ? 'GlobalBorsa uygulaması' : 'GlobalBorsa app'; ?></h3>
                            <p>
                                <?php echo getCurrentLang() == 'tr' ? 
                                    'Yüksek puanlı, ödüllü GlobalBorsa uygulamasıyla hizmetlerimize eksiksiz erişin.' : 
                                    'Get complete access to our services with the highly-rated, award-winning GlobalBorsa app.'; ?>
                            </p>
                            <div class="app-ratings">
                                <div class="rating">
                                    <i class="fab fa-apple"></i>
                                    <div class="stars">★★★★★</div>
                                    <span>4.8</span>
                                </div>
                                <div class="rating">
                                    <i class="fab fa-google-play"></i>
                                    <div class="stars">★★★★★</div>
                                    <span>4.9</span>
                                </div>
                            </div>
                            <a href="#" class="feature-btn">
                                <?php echo getCurrentLang() == 'tr' ? 'Uygulamayı Edinin' : 'Get the App'; ?>
                                <i class="fas fa-arrow-right"></i>
                            </a>
                        </div>
                        <div class="card-visual">
                            <div class="phone-mockup">
                                <i class="fas fa-mobile-alt"></i>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- 100% Bonus -->
                <div class="col-lg-6" data-aos="slide-left" data-aos-delay="400">
                    <div class="feature-card bonus-card">
                        <div class="card-content">
                            <h3><?php echo getCurrentLang() == 'tr' ? '%100 bonus' : '100% bonus'; ?></h3>
                            <p>
                                <?php echo getCurrentLang() == 'tr' ? 
                                    'Daha fazla yatırım, daha az risk ve daha çok getiri için fonlarımızı kullanın.' : 
                                    'Use our funds for more investment, less risk and more returns.'; ?>
                            </p>
                            <div class="bonus-amount">
                                <?php echo getCurrentLang() == 'tr' ? '1.000 TL\'ye varan %100 bonus alın' : 'Get up to 1,000 TL 100% bonus'; ?>
                            </div>
                            <a href="register.php" class="feature-btn">
                                <?php echo getCurrentLang() == 'tr' ? 'Bonusunuzu alın' : 'Get your bonus'; ?>
                                <i class="fas fa-arrow-right"></i>
                            </a>
                        </div>
                        <div class="card-visual">
                            <div class="gift-icon">
                                <i class="fas fa-gift"></i>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Crypto Competitions -->
                <div class="col-lg-6" data-aos="slide-right" data-aos-delay="500">
                    <div class="feature-card competition-card">
                        <div class="card-content">
                            <h3><?php echo getCurrentLang() == 'tr' ? 'GlobalBorsa yarışmaları' : 'GlobalBorsa competitions'; ?></h3>
                            <p>
                                <?php echo getCurrentLang() == 'tr' ? 
                                    'Yatırımlarınızla zirveye ilerleyin ve toplam 50.000 TL çekilebilir nakit ödülden payınızı alın.' : 
                                    'Advance to the top with your investments and get your share of 50,000 TL total withdrawable cash prizes.'; ?>
                            </p>
                            <a href="#" class="feature-btn">
                                <?php echo getCurrentLang() == 'tr' ? 'Hemen katılın' : 'Join now'; ?>
                                <i class="fas fa-arrow-right"></i>
                            </a>
                        </div>
                        <div class="card-visual">
                            <div class="trophy-icon">
                                <i class="fas fa-trophy"></i>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Copy Trading -->
                <div class="col-lg-6" data-aos="slide-left" data-aos-delay="600">
                    <div class="feature-card copy-trading-card">
                        <div class="card-content">
                            <h3><?php echo getCurrentLang() == 'tr' ? 'GlobalBorsa copy trade' : 'GlobalBorsa copy trade'; ?></h3>
                            <p>
                                <?php echo getCurrentLang() == 'tr' ? 
                                    'Kazançlı yatırım stratejilerini kopyalayan 1.000\'den fazla yatırımcıya katılın ya da işlemlerinizi paylaşıp komisyon kazanın.' : 
                                    'Join over 1,000 investors copying profitable investment strategies or share your trades and earn commissions.'; ?>
                            </p>
                            <a href="#" class="feature-btn">
                                <?php echo getCurrentLang() == 'tr' ? 'Copy trade\'e başlayın' : 'Start copy trading'; ?>
                                <i class="fas fa-arrow-right"></i>
                            </a>
                        </div>
                        <div class="card-visual">
                            <div class="trader-icon">
                                <i class="fas fa-users"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
    
    <!-- Stats Section -->
    <section class="stats-section">
        <div class="container">
            <div class="row justify-content-center text-center">
                <div class="col-lg-10">
                    <h2 data-aos="fade-up">
                        <?php echo getCurrentLang() == 'tr' ? 'Sunduklarımızı rakamlar da doğruluyor' : 'The numbers also confirm what we offer'; ?>
                    </h2>
                    <p data-aos="fade-up" data-aos-delay="200">
                        <?php echo getCurrentLang() == 'tr' ? 
                            'En iyi yatırım potansiyeline ulaşmanız için bizim kadar imkan sunan başka bir borsa yok.' : 
                            'There is no other exchange that offers as much opportunity as we do for you to reach the best investment potential.'; ?>
                    </p>
                </div>
            </div>
            
            <div class="row g-4 mt-4">
                <div class="col-lg-3 col-md-6" data-aos="fade-up" data-aos-delay="300">
                    <div class="stat-card">
                        <div class="stat-number" data-counter="500000">0</div>
                        <div class="stat-label">
                            <?php echo getCurrentLang() == 'tr' ? 'TL işlem hacmi' : 'TL transaction volume'; ?>
                        </div>
                    </div>
                </div>
                
                <div class="col-lg-3 col-md-6" data-aos="fade-up" data-aos-delay="400">
                    <div class="stat-card">
                        <div class="stat-number">0</div>
                        <div class="stat-label">
                            <?php echo getCurrentLang() == 'tr' ? 'işlem reddi veya yeniden fiyatlama' : 'trade rejection or repricing'; ?>
                        </div>
                    </div>
                </div>
                
                <div class="col-lg-3 col-md-6" data-aos="fade-up" data-aos-delay="500">
                    <div class="stat-card">
                        <div class="stat-number" data-counter="99.5">0</div>
                        <div class="stat-suffix">%</div>
                        <div class="stat-label">
                            <?php echo getCurrentLang() == 'tr' ? 'oranında para çekme işlemi otomatik onaylanır' : 'withdrawal transactions are automatically approved'; ?>
                        </div>
                    </div>
                </div>
                
                <div class="col-lg-3 col-md-6" data-aos="fade-up" data-aos-delay="600">
                    <div class="stat-card">
                        <div class="stat-number" data-counter="1000">0</div>
                        <div class="stat-suffix">+</div>
                        <div class="stat-label">
                            <?php echo getCurrentLang() == 'tr' ? 'aktif kullanıcı' : 'active users'; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
    
    <!-- CTA Section -->
    <section class="cta-section">
        <div class="container">
            <div class="row justify-content-center text-center">
                <div class="col-lg-8">
                    <h2 data-aos="fade-up">
                        <?php echo getCurrentLang() == 'tr' ? 'Hemen başlayın' : 'Get started now'; ?>
                    </h2>
                    <p data-aos="fade-up" data-aos-delay="200">
                        <?php echo getCurrentLang() == 'tr' ? 
                            'Binlerce yatırımcının güvendiği platformda siz de yerimi alın.' : 
                            'Take your place on the platform trusted by thousands of investors.'; ?>
                    </p>
                    <div class="cta-buttons" data-aos="fade-up" data-aos-delay="400">
                        <a href="register.php" class="btn btn-primary btn-lg me-3">
                            <?php echo getCurrentLang() == 'tr' ? 'Ücretsiz Hesap Aç' : 'Open Free Account'; ?>
                        </a>
                        <a href="index.php" class="btn btn-outline-primary btn-lg">
                            <?php echo getCurrentLang() == 'tr' ? 'Piyasaları İncele' : 'Explore Markets'; ?>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </section>
    
    <!-- Footer -->
    <footer class="landing-footer">
        <div class="container">
            <div class="row">
                <div class="col-md-6">
                    <div class="footer-brand">
                        <i class="fas fa-chart-line me-2"></i>GlobalBorsa
                    </div>
                    <p><?php echo getCurrentLang() == 'tr' ? 'Türkiye\'nin en güvenilir kripto borsası' : 'Turkey\'s most trusted crypto exchange'; ?></p>
                </div>
                <div class="col-md-6 text-md-end">
                    <div class="footer-links">
                        <a href="login.php"><?php echo getCurrentLang() == 'tr' ? 'Giriş' : 'Login'; ?></a>
                        <a href="register.php"><?php echo getCurrentLang() == 'tr' ? 'Kayıt' : 'Register'; ?></a>
                        <a href="index.php"><?php echo getCurrentLang() == 'tr' ? 'Piyasalar' : 'Markets'; ?></a>
                    </div>
                </div>
            </div>
            <hr>
            <div class="row">
                <div class="col-12 text-center">
                    <p class="mb-0">
                        &copy; 2024 GlobalBorsa. 
                        <?php echo getCurrentLang() == 'tr' ? 'Tüm hakları saklıdır.' : 'All rights reserved.'; ?>
                    </p>
                </div>
            </div>
        </div>
    </footer>
    
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <!-- AOS Animation -->
    <script src="https://cdn.jsdelivr.net/npm/aos@2.3.4/dist/aos.js"></script>
    <!-- Custom Landing JS -->
    <script src="assets/js/landing.js"></script>
</body>
</html>

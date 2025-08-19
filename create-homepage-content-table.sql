-- Homepage Content Management System Database Schema
-- Run this SQL to create the necessary tables

-- Create homepage_content table for storing dynamic content
CREATE TABLE IF NOT EXISTS `homepage_content` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `section_name` varchar(100) NOT NULL,
  `content_key` varchar(100) NOT NULL,
  `content_value` text NOT NULL,
  `content_type` enum('text','html','image','json') NOT NULL DEFAULT 'text',
  `language` varchar(5) NOT NULL DEFAULT 'tr',
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `display_order` int(11) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_content` (`section_name`, `content_key`, `language`),
  INDEX `idx_section_lang` (`section_name`, `language`),
  INDEX `idx_active` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Insert default homepage content
INSERT INTO `homepage_content` (`section_name`, `content_key`, `content_value`, `content_type`, `language`) VALUES
-- Hero Section - Turkish
('hero', 'title', 'Türkiye\'nin En Güvenilir <br>Yatırım Platformu', 'html', 'tr'),
('hero', 'subtitle', 'Düşük komisyonlar, güvenli altyapı ve profesyonel destek ile yatırımlarınızı büyütün.', 'text', 'tr'),
('hero', 'primary_button_text', 'Hemen Başla', 'text', 'tr'),
('hero', 'primary_button_link', 'register.php', 'text', 'tr'),
('hero', 'secondary_button_text', 'Piyasaları İncele', 'text', 'tr'),
('hero', 'secondary_button_link', 'markets.php', 'text', 'tr'),

-- Hero Section - English
('hero', 'title', 'Turkey\'s Most Trusted <br>Investment Platform', 'html', 'en'),
('hero', 'subtitle', 'Grow your investments with low commissions, secure infrastructure and professional support.', 'text', 'en'),
('hero', 'primary_button_text', 'Get Started', 'text', 'en'),
('hero', 'primary_button_link', 'register.php', 'text', 'en'),
('hero', 'secondary_button_text', 'Explore Markets', 'text', 'en'),
('hero', 'secondary_button_link', 'markets.php', 'text', 'en'),

-- Features Section - Turkish
('features', 'section_title', 'Neden GlobalBorsa?', 'text', 'tr'),
('features', 'section_description', 'Türkiye\'nin en güvenilir yatırım platformu olarak size sunduğumuz avantajlar', 'text', 'tr'),
('features', 'feature1_title', 'Güvenli Altyapı', 'text', 'tr'),
('features', 'feature1_text', 'Çoklu imza, soğuk cüzdan depolama ve 2FA ile paranız %100 güvende. Sigortalı varlık koruması.', 'text', 'tr'),
('features', 'feature1_icon', 'fas fa-shield-alt', 'text', 'tr'),
('features', 'feature2_title', 'Hızlı İşlemler', 'text', 'tr'),
('features', 'feature2_text', 'Milisaniye hızında emir eşleştirme motoru ile anlık alım-satım yapın. 0.1 saniyede işlem tamamlama.', 'text', 'tr'),
('features', 'feature2_icon', 'fas fa-bolt', 'text', 'tr'),
('features', 'feature3_title', 'Düşük Komisyonlar', 'text', 'tr'),
('features', 'feature3_text', 'Türkiye\'nin en düşük komisyon oranları ile daha fazla kar edin. Şeffaf ve adil fiyatlandırma.', 'text', 'tr'),
('features', 'feature3_icon', 'fas fa-percentage', 'text', 'tr'),

-- Features Section - English
('features', 'section_title', 'Why GlobalBorsa?', 'text', 'en'),
('features', 'section_description', 'Advantages we offer as Turkey\'s most trusted investment platform', 'text', 'en'),
('features', 'feature1_title', 'Secure Infrastructure', 'text', 'en'),
('features', 'feature1_text', 'Your money is 100% safe with multi-signature, cold wallet storage and 2FA. Insured asset protection.', 'text', 'en'),
('features', 'feature1_icon', 'fas fa-shield-alt', 'text', 'en'),
('features', 'feature2_title', 'Fast Transactions', 'text', 'en'),
('features', 'feature2_text', 'Trade instantly with millisecond-speed order matching engine. Complete transactions in 0.1 seconds.', 'text', 'en'),
('features', 'feature2_icon', 'fas fa-bolt', 'text', 'en'),
('features', 'feature3_title', 'Low Commissions', 'text', 'en'),
('features', 'feature3_text', 'Earn more with Turkey\'s lowest commission rates. Transparent and fair pricing.', 'text', 'en'),
('features', 'feature3_icon', 'fas fa-percentage', 'text', 'en'),

-- Markets Ticker Section - Turkish
('markets_ticker', 'section_title', 'Canlı Piyasa Verileri', 'text', 'tr'),

-- Markets Ticker Section - English
('markets_ticker', 'section_title', 'Live Market Data', 'text', 'en'),

-- Education Section - Turkish
('education', 'section_title', 'Trading Akademisi', 'text', 'tr'),
('education', 'section_description', 'Profesyonel trader olmak için ihtiyacınız olan tüm bilgileri uzman analistlerimizden öğrenin', 'text', 'tr'),

-- Education Section - English
('education', 'section_title', 'Trading Academy', 'text', 'en'),
('education', 'section_description', 'Learn everything you need to become a professional trader from our expert analysts', 'text', 'en'),

-- CTA Section - Turkish
('cta', 'badge_text', '🚀 Sınırlı Süreli Fırsat', 'text', 'tr'),
('cta', 'title', 'Yatırım Yolculuğunuza Hemen Başlayın!', 'text', 'tr'),
('cta', 'description', 'Profesyonel araçlar, uzman analizler ve güvenli altyapı ile yatırımlarınızı bir sonraki seviyeye taşıyın. İlk yatırımınızda %100 bonus kazanma fırsatını kaçırmayın!', 'text', 'tr'),
('cta', 'primary_button_text', 'Ücretsiz Hesap Aç', 'text', 'tr'),
('cta', 'primary_button_link', 'register.php', 'text', 'tr'),
('cta', 'secondary_button_text', 'Piyasaları Keşfet', 'text', 'tr'),
('cta', 'secondary_button_link', 'markets.php', 'text', 'tr'),

-- CTA Section - English
('cta', 'badge_text', '🚀 Limited Time Offer', 'text', 'en'),
('cta', 'title', 'Start Your Investment Journey Now!', 'text', 'en'),
('cta', 'description', 'Take your investments to the next level with professional tools, expert analysis and secure infrastructure. Don\'t miss the opportunity to earn 100% bonus on your first investment!', 'text', 'en'),
('cta', 'primary_button_text', 'Open Free Account', 'text', 'en'),
('cta', 'primary_button_link', 'register.php', 'text', 'en'),
('cta', 'secondary_button_text', 'Explore Markets', 'text', 'en'),
('cta', 'secondary_button_link', 'markets.php', 'text', 'en');

-- Create homepage_images table for managing homepage images
CREATE TABLE IF NOT EXISTS `homepage_images` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `image_key` varchar(100) NOT NULL,
  `image_path` varchar(255) NOT NULL,
  `alt_text` varchar(255) NOT NULL,
  `title` varchar(255) DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `uploaded_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_image_key` (`image_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Insert default images
INSERT INTO `homepage_images` (`image_key`, `image_path`, `alt_text`, `title`) VALUES
('hero_background', '6256878.jpg', 'Trading Chart Background', 'Financial Trading Chart Background'),
('site_logo', '', 'Site Logo', 'GlobalBorsa Logo'),
('favicon', '', 'Site Favicon', 'GlobalBorsa Favicon');
